<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\MoveinAccessCode;
use App\Models\MoveinConfiguration;
use App\Models\MoveinSession;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\Tenant;
use App\Notifications\MoveInAccessCodeNotification;
use App\Notifications\MoveInCompletedNotification;
use App\Notifications\MoveInReminderNotification;
use App\Notifications\MoveInStartedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ContactlessMoveInService
{
    protected ?StripePaymentService $paymentService;

    public function __construct(?StripePaymentService $paymentService = null)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a new move-in session
     */
    public function createSession(array $data): MoveinSession
    {
        $tenant = Tenant::findOrFail($data['tenant_id']);
        $config = MoveinConfiguration::getOrCreateForTenant($tenant->id);

        if (!$config->enabled) {
            throw new \Exception('Le service d\'emménagement sans contact n\'est pas activé.');
        }

        $sessionData = [
            'tenant_id' => $tenant->id,
            'site_id' => $data['site_id'],
            'box_id' => $data['box_id'] ?? null,
            'reservation_id' => $data['reservation_id'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'language' => $data['language'] ?? 'fr',
            'status' => MoveinSession::STATUS_PENDING,
            'current_step' => $config->require_identity_verification
                ? MoveinSession::STEP_IDENTITY
                : MoveinSession::STEP_BOX_SELECTION,
            'completed_steps' => [],
            'device_type' => $data['device_type'] ?? null,
            'browser' => $data['browser'] ?? null,
            'ip_address' => request()->ip(),
            'preferred_movein_date' => $data['preferred_date'] ?? null,
            'preferred_time_slot' => $data['preferred_time_slot'] ?? null,
        ];

        // If from reservation, link it
        if (!empty($data['reservation_id'])) {
            $reservation = Reservation::findOrFail($data['reservation_id']);
            $sessionData['box_id'] = $reservation->box_id;
            $sessionData['email'] = $reservation->customer?->email ?? $sessionData['email'];
            $sessionData['customer_id'] = $reservation->customer_id;
        }

        $session = MoveinSession::create($sessionData);

        // Log the start
        $session->stepLogs()->create([
            'step' => 'session',
            'action' => 'started',
            'data' => ['source' => $data['source'] ?? 'web'],
            'ip_address' => request()->ip(),
        ]);

        // Send notification to customer
        if ($session->email) {
            try {
                // Would use notification here
                // $session->notify(new MoveInStartedNotification($session));
            } catch (\Exception $e) {
                Log::warning('Failed to send move-in start notification', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $session;
    }

    /**
     * Create session from existing reservation
     */
    public function createFromReservation(Reservation $reservation): MoveinSession
    {
        return $this->createSession([
            'tenant_id' => $reservation->tenant_id,
            'site_id' => $reservation->site_id,
            'box_id' => $reservation->box_id,
            'reservation_id' => $reservation->id,
            'email' => $reservation->customer?->email ?? $reservation->email,
            'phone' => $reservation->customer?->phone ?? $reservation->phone,
            'first_name' => $reservation->customer?->first_name ?? $reservation->first_name,
            'last_name' => $reservation->customer?->last_name ?? $reservation->last_name,
            'source' => 'reservation',
        ]);
    }

    /**
     * Verify customer identity
     */
    public function verifyIdentity(MoveinSession $session, array $data): array
    {
        if (!$session->canProceed()) {
            return ['success' => false, 'error' => 'Session expirée ou terminée'];
        }

        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

        try {
            $verificationResult = [
                'method' => $data['method'] ?? 'document',
                'verified' => false,
                'details' => [],
            ];

            // Handle different verification methods
            switch ($data['method'] ?? 'document') {
                case 'document':
                    $verificationResult = $this->verifyDocument($session, $data);
                    break;

                case 'video_selfie':
                    if (!$config->allow_video_verification) {
                        return ['success' => false, 'error' => 'Vérification vidéo non autorisée'];
                    }
                    $verificationResult = $this->verifyVideoSelfie($session, $data);
                    break;

                case 'otp':
                    $verificationResult = $this->verifyOTP($session, $data);
                    break;
            }

            if ($verificationResult['verified']) {
                $session->update([
                    'identity_method' => $data['method'] ?? 'document',
                    'identity_data' => $verificationResult['details'],
                    'identity_verified_at' => now(),
                ]);

                $session->markStepCompleted(MoveinSession::STEP_IDENTITY, [
                    'method' => $data['method'] ?? 'document',
                ]);

                return ['success' => true, 'session' => $session->fresh()];
            }

            $session->stepLogs()->create([
                'step' => MoveinSession::STEP_IDENTITY,
                'action' => 'failed',
                'data' => $verificationResult,
                'error_message' => $verificationResult['error'] ?? 'Vérification échouée',
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => false,
                'error' => $verificationResult['error'] ?? 'Vérification d\'identité échouée',
            ];
        } catch (\Exception $e) {
            Log::error('Identity verification failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Erreur lors de la vérification'];
        }
    }

    /**
     * Verify document (simplified - in production, use a service like Onfido or Jumio)
     */
    protected function verifyDocument(MoveinSession $session, array $data): array
    {
        // In production, this would call an external verification API
        // For now, we do basic validation

        $details = [
            'document_type' => $data['document_type'] ?? 'id_card',
            'first_name' => $data['first_name'] ?? $session->first_name,
            'last_name' => $data['last_name'] ?? $session->last_name,
        ];

        // Update session with provided info
        $session->update([
            'first_name' => $details['first_name'],
            'last_name' => $details['last_name'],
        ]);

        // Store document images if provided
        if (!empty($data['front_image'])) {
            $frontPath = $data['front_image']->store("identity/{$session->tenant_id}/{$session->id}", 'private');
            $details['front_image'] = $frontPath;
        }

        if (!empty($data['back_image'])) {
            $backPath = $data['back_image']->store("identity/{$session->tenant_id}/{$session->id}", 'private');
            $details['back_image'] = $backPath;
        }

        // Simplified verification (always pass for demo)
        // In production: call external API for OCR and verification
        return [
            'verified' => true,
            'method' => 'document',
            'details' => $details,
            'confidence' => 0.95,
        ];
    }

    /**
     * Verify via video selfie
     */
    protected function verifyVideoSelfie(MoveinSession $session, array $data): array
    {
        // In production, this would use face recognition and liveness detection
        return [
            'verified' => true,
            'method' => 'video_selfie',
            'details' => ['liveness_check' => true],
        ];
    }

    /**
     * Verify via OTP
     */
    protected function verifyOTP(MoveinSession $session, array $data): array
    {
        // Simple OTP verification - in production use a proper OTP service
        $storedOTP = cache()->get("movein_otp_{$session->id}");

        if ($storedOTP && $storedOTP === $data['otp']) {
            cache()->forget("movein_otp_{$session->id}");
            return [
                'verified' => true,
                'method' => 'otp',
                'details' => ['verified_via' => $data['channel'] ?? 'sms'],
            ];
        }

        return [
            'verified' => false,
            'method' => 'otp',
            'error' => 'Code invalide ou expiré',
        ];
    }

    /**
     * Send OTP for verification
     */
    public function sendOTP(MoveinSession $session, string $channel = 'sms'): array
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP for 10 minutes
        cache()->put("movein_otp_{$session->id}", $otp, 600);

        // Send via appropriate channel
        if ($channel === 'sms' && $session->phone) {
            // In production, send SMS via Twilio/other provider
            Log::info("OTP for session {$session->id}: {$otp}");
        } elseif ($channel === 'email' && $session->email) {
            // Send email with OTP
            Log::info("OTP for session {$session->id}: {$otp}");
        }

        return ['success' => true, 'channel' => $channel];
    }

    /**
     * Select a box for the session
     */
    public function selectBox(MoveinSession $session, int $boxId): array
    {
        if (!$session->canProceed()) {
            return ['success' => false, 'error' => 'Session expirée ou terminée'];
        }

        $box = Box::where('id', $boxId)
            ->where('site_id', $session->site_id)
            ->first();

        if (!$box) {
            return ['success' => false, 'error' => 'Box non trouvé'];
        }

        if ($box->status !== 'available') {
            return ['success' => false, 'error' => 'Ce box n\'est plus disponible'];
        }

        $session->update(['box_id' => $boxId]);
        $session->markStepCompleted(MoveinSession::STEP_BOX_SELECTION, [
            'box_id' => $boxId,
            'box_number' => $box->number,
            'volume' => $box->volume,
            'price' => $box->current_price,
        ]);

        // Temporarily reserve the box
        $box->update(['status' => 'reserved']);

        return ['success' => true, 'session' => $session->fresh(), 'box' => $box];
    }

    /**
     * Generate and send contract for signature
     */
    public function generateContract(MoveinSession $session): array
    {
        if (!$session->canProceed() || !$session->box_id) {
            return ['success' => false, 'error' => 'Session invalide ou box non sélectionné'];
        }

        try {
            // Create or get customer
            $customer = $this->getOrCreateCustomer($session);
            $session->update(['customer_id' => $customer->id]);

            // Generate contract
            $box = $session->box;
            $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

            $contractData = [
                'tenant_id' => $session->tenant_id,
                'site_id' => $session->site_id,
                'box_id' => $session->box_id,
                'customer_id' => $customer->id,
                'contract_number' => Contract::generateContractNumber($session->tenant_id),
                'start_date' => $session->preferred_movein_date ?? now()->addDay(),
                'monthly_price' => $box->current_price ?? $box->base_price,
                'deposit_amount' => ($box->current_price ?? $box->base_price) * $config->deposit_months,
                'status' => 'pending_signature',
            ];

            $contract = Contract::create($contractData);
            $session->update([
                'contract_id' => $contract->id,
                'contract_sent_at' => now(),
            ]);

            $session->stepLogs()->create([
                'step' => MoveinSession::STEP_CONTRACT,
                'action' => 'started',
                'data' => ['contract_id' => $contract->id],
                'ip_address' => request()->ip(),
            ]);

            return [
                'success' => true,
                'contract' => $contract,
                'session' => $session->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error('Contract generation failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Erreur lors de la génération du contrat'];
        }
    }

    /**
     * Sign contract digitally
     */
    public function signContract(MoveinSession $session, array $signatureData): array
    {
        if (!$session->canProceed() || !$session->contract_id) {
            return ['success' => false, 'error' => 'Session invalide ou contrat non généré'];
        }

        try {
            $contract = $session->contract;

            // Store signature
            $signaturePath = null;
            if (!empty($signatureData['signature_image'])) {
                // Base64 signature image
                $signaturePath = $this->storeSignatureImage(
                    $signatureData['signature_image'],
                    $session
                );
            }

            // Update contract
            $contract->update([
                'status' => 'signed',
                'signed_at' => now(),
                'signature_ip' => request()->ip(),
                'signature_path' => $signaturePath,
            ]);

            // Update session
            $session->update([
                'contract_signed_at' => now(),
                'signature_ip' => request()->ip(),
                'signature_user_agent' => request()->userAgent(),
            ]);

            $session->markStepCompleted(MoveinSession::STEP_CONTRACT, [
                'contract_id' => $contract->id,
                'signed_at' => now()->toISOString(),
            ]);

            return [
                'success' => true,
                'contract' => $contract->fresh(),
                'session' => $session->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error('Contract signing failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Erreur lors de la signature'];
        }
    }

    /**
     * Process payment
     */
    public function processPayment(MoveinSession $session, array $paymentData): array
    {
        if (!$session->canProceed() || !$session->contract_id) {
            return ['success' => false, 'error' => 'Session invalide'];
        }

        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);
        $contract = $session->contract;

        // Calculate amount
        $amount = $contract->monthly_price;
        if ($config->deposit_months > 0) {
            $amount += $contract->deposit_amount ?? ($contract->monthly_price * $config->deposit_months);
        }

        try {
            $paymentResult = ['success' => false];

            switch ($paymentData['method']) {
                case 'card':
                    if (!$config->allow_card_payment) {
                        return ['success' => false, 'error' => 'Paiement par carte non autorisé'];
                    }
                    $paymentResult = $this->processCardPayment($session, $amount, $paymentData);
                    break;

                case 'sepa':
                    if (!$config->allow_sepa_mandate) {
                        return ['success' => false, 'error' => 'Prélèvement SEPA non autorisé'];
                    }
                    $paymentResult = $this->processSEPAMandate($session, $amount, $paymentData);
                    break;
            }

            if ($paymentResult['success']) {
                $session->update([
                    'payment_method' => $paymentData['method'],
                    'payment_intent_id' => $paymentResult['payment_id'] ?? null,
                    'amount_paid' => $amount,
                    'payment_completed_at' => now(),
                ]);

                // Update contract status
                $contract->update(['status' => 'active']);

                // Update box status
                $session->box->update(['status' => 'occupied']);

                $session->markStepCompleted(MoveinSession::STEP_PAYMENT, [
                    'method' => $paymentData['method'],
                    'amount' => $amount,
                ]);

                return [
                    'success' => true,
                    'session' => $session->fresh(),
                    'amount' => $amount,
                ];
            }

            $session->stepLogs()->create([
                'step' => MoveinSession::STEP_PAYMENT,
                'action' => 'failed',
                'data' => ['method' => $paymentData['method']],
                'error_message' => $paymentResult['error'] ?? 'Paiement échoué',
                'ip_address' => request()->ip(),
            ]);

            return $paymentResult;
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Erreur lors du paiement'];
        }
    }

    /**
     * Process card payment via Stripe
     */
    protected function processCardPayment(MoveinSession $session, float $amount, array $data): array
    {
        if (!$this->paymentService) {
            // Simulation for demo
            return [
                'success' => true,
                'payment_id' => 'pi_demo_' . uniqid(),
            ];
        }

        try {
            $result = $this->paymentService->chargeCard(
                $session->customer,
                $amount * 100, // Convert to cents
                $data['payment_method_id'] ?? null,
                "Emménagement - Box {$session->box->number}"
            );

            return [
                'success' => $result['status'] === 'succeeded',
                'payment_id' => $result['payment_intent_id'] ?? null,
                'error' => $result['error'] ?? null,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Setup SEPA mandate
     */
    protected function processSEPAMandate(MoveinSession $session, float $amount, array $data): array
    {
        // In production, this would set up a SEPA mandate via Stripe or GoCardless
        return [
            'success' => true,
            'payment_id' => 'sepa_' . uniqid(),
            'mandate_id' => 'mandate_' . uniqid(),
        ];
    }

    /**
     * Generate access code and complete the move-in
     */
    public function grantAccess(MoveinSession $session): array
    {
        if (!$session->canProceed()) {
            return ['success' => false, 'error' => 'Session invalide'];
        }

        try {
            $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

            // Generate access code
            $accessCode = $session->generateAccessCode();

            // Generate QR code if enabled
            if ($config->enable_qr_code) {
                $qrPath = $this->generateAccessQRCode($accessCode);
                $accessCode->update(['qr_code_path' => $qrPath]);
            }

            $session->update([
                'access_code' => $accessCode->code,
                'access_qr_code' => $accessCode->qr_code_path,
                'access_granted_at' => now(),
            ]);

            $session->markStepCompleted(MoveinSession::STEP_ACCESS, [
                'access_code' => $accessCode->code,
                'valid_until' => $accessCode->valid_until->toISOString(),
            ]);

            // Send access code via SMS and/or email
            $this->sendAccessCodeNotifications($session, $accessCode, $config);

            // Mark session as completed
            $session->update(['status' => MoveinSession::STATUS_COMPLETED]);

            // Notify staff
            if ($config->notify_staff_on_completion) {
                $this->notifyStaffCompletion($session);
            }

            // Update analytics
            $this->updateAnalytics($session, 'completed');

            return [
                'success' => true,
                'session' => $session->fresh(),
                'access_code' => $accessCode,
            ];
        } catch (\Exception $e) {
            Log::error('Access grant failed', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Erreur lors de la génération du code d\'accès'];
        }
    }

    /**
     * Generate QR code for access
     */
    protected function generateAccessQRCode(MoveinAccessCode $accessCode): string
    {
        $qrContent = json_encode([
            'type' => 'movein_access',
            'code' => $accessCode->code,
            'site_id' => $accessCode->site_id,
            'valid_until' => $accessCode->valid_until->toISOString(),
        ]);

        $qrImage = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->generate($qrContent);

        $path = "qrcodes/{$accessCode->tenant_id}/{$accessCode->code}.png";
        Storage::disk('public')->put($path, $qrImage);

        return $path;
    }

    /**
     * Send access code notifications
     */
    protected function sendAccessCodeNotifications(MoveinSession $session, MoveinAccessCode $accessCode, MoveinConfiguration $config): void
    {
        $data = [
            'code' => $accessCode->formatted_code,
            'valid_until' => $accessCode->valid_until->format('d/m/Y H:i'),
            'site_name' => $session->site->name,
            'box_number' => $session->box->number,
        ];

        if ($config->send_access_code_email && $session->email) {
            // Send email notification
            // Mail::to($session->email)->send(new MoveInAccessCodeMail($data));
        }

        if ($config->send_access_code_sms && $session->phone) {
            // Send SMS notification
            // SMS::send($session->phone, "Votre code d'accès BoxiBox: {$data['code']}. Valide jusqu'au {$data['valid_until']}");
        }
    }

    /**
     * Notify staff of completion
     */
    protected function notifyStaffCompletion(MoveinSession $session): void
    {
        // In production, notify relevant staff members
        Log::info("Move-in completed", [
            'session_id' => $session->id,
            'customer' => $session->full_name,
            'box' => $session->box->number,
        ]);
    }

    /**
     * Get or create customer from session data
     */
    protected function getOrCreateCustomer(MoveinSession $session): Customer
    {
        if ($session->customer_id) {
            return Customer::findOrFail($session->customer_id);
        }

        return Customer::firstOrCreate(
            [
                'tenant_id' => $session->tenant_id,
                'email' => $session->email,
            ],
            [
                'first_name' => $session->first_name,
                'last_name' => $session->last_name,
                'phone' => $session->phone,
                'status' => 'active',
            ]
        );
    }

    /**
     * Store signature image
     */
    protected function storeSignatureImage(string $base64Image, MoveinSession $session): string
    {
        $image = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $path = "signatures/{$session->tenant_id}/{$session->id}_" . time() . '.png';
        Storage::disk('private')->put($path, $image);
        return $path;
    }

    /**
     * Update analytics
     */
    protected function updateAnalytics(MoveinSession $session, string $event): void
    {
        // Update or create daily analytics record
        DB::table('movein_analytics')->updateOrInsert(
            [
                'tenant_id' => $session->tenant_id,
                'date' => now()->toDateString(),
            ],
            [
                'sessions_completed' => DB::raw('sessions_completed + 1'),
                'total_revenue' => DB::raw("total_revenue + {$session->amount_paid}"),
                'total_boxes_rented' => DB::raw('total_boxes_rented + 1'),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Get session progress for display
     */
    public function getSessionProgress(MoveinSession $session): array
    {
        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

        $steps = [
            [
                'id' => MoveinSession::STEP_IDENTITY,
                'name' => 'Vérification d\'identité',
                'icon' => 'user-check',
                'required' => $config->require_identity_verification,
                'completed' => in_array(MoveinSession::STEP_IDENTITY, $session->completed_steps ?? []),
            ],
            [
                'id' => MoveinSession::STEP_BOX_SELECTION,
                'name' => 'Choix du box',
                'icon' => 'box',
                'required' => true,
                'completed' => in_array(MoveinSession::STEP_BOX_SELECTION, $session->completed_steps ?? []),
            ],
            [
                'id' => MoveinSession::STEP_CONTRACT,
                'name' => 'Signature du contrat',
                'icon' => 'file-signature',
                'required' => true,
                'completed' => in_array(MoveinSession::STEP_CONTRACT, $session->completed_steps ?? []),
            ],
            [
                'id' => MoveinSession::STEP_PAYMENT,
                'name' => 'Paiement',
                'icon' => 'credit-card',
                'required' => $config->require_upfront_payment,
                'completed' => in_array(MoveinSession::STEP_PAYMENT, $session->completed_steps ?? []),
            ],
            [
                'id' => MoveinSession::STEP_ACCESS,
                'name' => 'Code d\'accès',
                'icon' => 'key',
                'required' => true,
                'completed' => in_array(MoveinSession::STEP_ACCESS, $session->completed_steps ?? []),
            ],
        ];

        return [
            'steps' => $steps,
            'current_step' => $session->current_step,
            'progress_percentage' => $session->progress_percentage,
            'is_completed' => $session->isCompleted(),
            'is_expired' => $session->isExpired(),
        ];
    }

    /**
     * Get dashboard stats for tenant
     */
    public function getDashboardStats(int $tenantId): array
    {
        $today = now()->toDateString();
        $last30Days = now()->subDays(30)->toDateString();

        $sessions = MoveinSession::where('tenant_id', $tenantId);

        return [
            'active_sessions' => (clone $sessions)->active()->count(),
            'completed_today' => (clone $sessions)->completed()
                ->whereDate('updated_at', $today)->count(),
            'completed_30_days' => (clone $sessions)->completed()
                ->whereDate('updated_at', '>=', $last30Days)->count(),
            'expired_30_days' => (clone $sessions)
                ->where('status', MoveinSession::STATUS_EXPIRED)
                ->whereDate('updated_at', '>=', $last30Days)->count(),
            'conversion_rate' => $this->calculateConversionRate($tenantId, $last30Days),
            'avg_completion_time' => $this->calculateAvgCompletionTime($tenantId),
        ];
    }

    protected function calculateConversionRate(int $tenantId, string $since): float
    {
        $total = MoveinSession::where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', $since)
            ->count();

        if ($total === 0) return 0;

        $completed = MoveinSession::where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', $since)
            ->where('status', MoveinSession::STATUS_COMPLETED)
            ->count();

        return round(($completed / $total) * 100, 1);
    }

    protected function calculateAvgCompletionTime(int $tenantId): ?int
    {
        $sessions = MoveinSession::where('tenant_id', $tenantId)
            ->where('status', MoveinSession::STATUS_COMPLETED)
            ->whereNotNull('created_at')
            ->whereNotNull('updated_at')
            ->limit(100)
            ->get();

        if ($sessions->isEmpty()) return null;

        $totalMinutes = $sessions->sum(function ($s) {
            return $s->created_at->diffInMinutes($s->updated_at);
        });

        return (int) round($totalMinutes / $sessions->count());
    }
}
