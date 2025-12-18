<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\MoveinConfiguration;
use App\Models\MoveinSession;
use App\Models\Site;
use App\Services\ContactlessMoveInService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicMoveInController extends Controller
{
    protected ContactlessMoveInService $moveInService;

    public function __construct(ContactlessMoveInService $moveInService)
    {
        $this->moveInService = $moveInService;
    }

    /**
     * Start or resume a move-in session
     */
    public function start(Request $request, string $token)
    {
        $session = MoveinSession::where('session_token', $token)->first();

        if (!$session) {
            return Inertia::render('Public/MoveIn/NotFound');
        }

        if ($session->isExpired()) {
            return Inertia::render('Public/MoveIn/Expired', [
                'session' => $session,
            ]);
        }

        if ($session->isCompleted()) {
            return redirect()->route('movein.public.confirmation', ['token' => $token]);
        }

        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);
        $progress = $this->moveInService->getSessionProgress($session);

        // Redirect to current step
        return Inertia::render('Public/MoveIn/Flow', [
            'session' => $this->getPublicSessionData($session),
            'config' => $this->getPublicConfig($config),
            'progress' => $progress,
            'token' => $token,
            'site' => [
                'name' => $session->site->name,
                'address' => $session->site->address,
            ],
            'box' => $session->box ? [
                'id' => $session->box->id,
                'number' => $session->box->number,
                'volume' => $session->box->volume,
                'price' => $session->box->current_price ?? $session->box->base_price,
            ] : null,
        ]);
    }

    /**
     * Get available boxes for selection
     */
    public function getAvailableBoxes(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $boxes = Box::where('site_id', $session->site_id)
            ->where('status', 'available')
            ->select('id', 'number', 'volume', 'current_price', 'base_price', 'length', 'width', 'height')
            ->orderBy('volume')
            ->get()
            ->map(function ($box) {
                return [
                    'id' => $box->id,
                    'number' => $box->number,
                    'volume' => $box->volume,
                    'price' => $box->current_price ?? $box->base_price,
                    'dimensions' => $box->length && $box->width && $box->height
                        ? "{$box->length}m x {$box->width}m x {$box->height}m"
                        : null,
                ];
            });

        return response()->json(['boxes' => $boxes]);
    }

    /**
     * Submit identity verification
     */
    public function submitIdentity(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $validated = $request->validate([
            'method' => 'required|in:document,video_selfie,otp',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'document_type' => 'nullable|string',
            'front_image' => 'nullable|image|max:5120',
            'back_image' => 'nullable|image|max:5120',
            'selfie_image' => 'nullable|image|max:5120',
            'otp' => 'nullable|string|size:6',
        ]);

        $result = $this->moveInService->verifyIdentity($session, $validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'session' => $this->getPublicSessionData($result['session']),
                'next_step' => $result['session']->current_step,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Request OTP
     */
    public function requestOTP(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $channel = $request->input('channel', 'sms');
        $result = $this->moveInService->sendOTP($session, $channel);

        return response()->json($result);
    }

    /**
     * Select a box
     */
    public function selectBox(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $validated = $request->validate([
            'box_id' => 'required|integer|exists:boxes,id',
        ]);

        $result = $this->moveInService->selectBox($session, $validated['box_id']);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'session' => $this->getPublicSessionData($result['session']),
                'box' => [
                    'id' => $result['box']->id,
                    'number' => $result['box']->number,
                    'volume' => $result['box']->volume,
                    'price' => $result['box']->current_price ?? $result['box']->base_price,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Get contract for review
     */
    public function getContract(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        // Generate contract if not exists
        if (!$session->contract_id) {
            $result = $this->moveInService->generateContract($session);
            if (!$result['success']) {
                return response()->json(['error' => $result['error']], 400);
            }
            $session = $result['session'];
        }

        $contract = $session->contract;
        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

        return response()->json([
            'contract' => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'start_date' => $contract->start_date->format('d/m/Y'),
                'monthly_price' => $contract->monthly_price,
                'deposit_amount' => $contract->deposit_amount,
                'total_due' => $contract->monthly_price + ($contract->deposit_amount ?? 0),
            ],
            'require_initials' => $config->require_initials,
            'terms_url' => route('terms'),
        ]);
    }

    /**
     * Sign contract
     */
    public function signContract(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $validated = $request->validate([
            'signature_image' => 'required|string', // Base64 image
            'initials' => 'nullable|string',
            'accept_terms' => 'required|accepted',
        ]);

        $result = $this->moveInService->signContract($session, $validated);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'session' => $this->getPublicSessionData($result['session']),
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $contract = $session->contract;
        $amount = $contract->monthly_price + ($contract->deposit_amount ?? 0);

        // In production, create Stripe PaymentIntent
        return response()->json([
            'client_secret' => 'demo_client_secret_' . uniqid(),
            'amount' => $amount,
            'currency' => 'eur',
        ]);
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request, string $token)
    {
        $session = $this->getValidSession($token);
        if (!$session) {
            return response()->json(['error' => 'Session invalide'], 400);
        }

        $validated = $request->validate([
            'method' => 'required|in:card,sepa',
            'payment_method_id' => 'nullable|string',
            'iban' => 'nullable|string',
        ]);

        $result = $this->moveInService->processPayment($session, $validated);

        if ($result['success']) {
            // Grant access after successful payment
            $accessResult = $this->moveInService->grantAccess($result['session']);

            return response()->json([
                'success' => true,
                'session' => $this->getPublicSessionData($accessResult['session']),
                'access_code' => $accessResult['access_code'] ? [
                    'code' => $accessResult['access_code']->formatted_code,
                    'valid_until' => $accessResult['access_code']->valid_until->format('d/m/Y H:i'),
                    'qr_code' => $accessResult['access_code']->qr_code_path
                        ? asset('storage/' . $accessResult['access_code']->qr_code_path)
                        : null,
                ] : null,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'],
        ], 400);
    }

    /**
     * Show confirmation page
     */
    public function confirmation(Request $request, string $token)
    {
        $session = MoveinSession::where('session_token', $token)
            ->with(['site', 'box', 'customer', 'contract', 'activeAccessCode'])
            ->first();

        if (!$session) {
            return Inertia::render('Public/MoveIn/NotFound');
        }

        if (!$session->isCompleted()) {
            return redirect()->route('movein.public.start', ['token' => $token]);
        }

        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

        return Inertia::render('Public/MoveIn/Confirmation', [
            'session' => $this->getPublicSessionData($session),
            'site' => [
                'name' => $session->site->name,
                'address' => $session->site->address,
                'opening_hours' => $session->site->opening_hours,
            ],
            'box' => [
                'number' => $session->box->number,
                'volume' => $session->box->volume,
            ],
            'contract' => [
                'number' => $session->contract->contract_number,
                'start_date' => $session->contract->start_date->format('d/m/Y'),
                'monthly_price' => $session->contract->monthly_price,
            ],
            'access_code' => $session->activeAccessCode ? [
                'code' => $session->activeAccessCode->formatted_code,
                'valid_until' => $session->activeAccessCode->valid_until->format('d/m/Y H:i'),
                'remaining_uses' => $session->activeAccessCode->remaining_uses,
                'qr_code' => $session->activeAccessCode->qr_code_path
                    ? asset('storage/' . $session->activeAccessCode->qr_code_path)
                    : null,
            ] : null,
            'completion_message' => $config->getCompletionMessageForLanguage($session->language),
        ]);
    }

    /**
     * Helper: Get valid session
     */
    protected function getValidSession(string $token): ?MoveinSession
    {
        $session = MoveinSession::where('session_token', $token)->first();

        if (!$session || !$session->canProceed()) {
            return null;
        }

        return $session;
    }

    /**
     * Helper: Get public-safe session data
     */
    protected function getPublicSessionData(MoveinSession $session): array
    {
        return [
            'id' => $session->id,
            'token' => $session->session_token,
            'status' => $session->status,
            'current_step' => $session->current_step,
            'completed_steps' => $session->completed_steps ?? [],
            'progress_percentage' => $session->progress_percentage,
            'first_name' => $session->first_name,
            'last_name' => $session->last_name,
            'email' => $session->email,
            'preferred_movein_date' => $session->preferred_movein_date?->format('d/m/Y'),
            'expires_at' => $session->expires_at?->format('d/m/Y H:i'),
            'identity_verified' => $session->identity_verified_at !== null,
            'contract_signed' => $session->contract_signed_at !== null,
            'payment_completed' => $session->payment_completed_at !== null,
            'access_granted' => $session->access_granted_at !== null,
        ];
    }

    /**
     * Helper: Get public-safe config
     */
    protected function getPublicConfig(MoveinConfiguration $config): array
    {
        return [
            'require_identity_verification' => $config->require_identity_verification,
            'require_selfie' => $config->require_selfie,
            'allow_video_verification' => $config->allow_video_verification,
            'enable_digital_signature' => $config->enable_digital_signature,
            'require_initials' => $config->require_initials,
            'require_upfront_payment' => $config->require_upfront_payment,
            'allow_sepa_mandate' => $config->allow_sepa_mandate,
            'allow_card_payment' => $config->allow_card_payment,
            'deposit_months' => $config->deposit_months,
            'enable_qr_code' => $config->enable_qr_code,
            'welcome_message' => $config->getWelcomeMessageForLanguage('fr'),
        ];
    }
}
