<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecoverAbandonedBookings extends Command
{
    protected $signature = 'bookings:recover-abandoned {--dry-run : Simulate without sending}';

    protected $description = 'Send recovery emails for abandoned bookings to increase conversion';

    /**
     * Recovery sequence configuration
     * - After 30 minutes: First reminder
     * - After 24 hours: Second reminder with urgency
     * - After 72 hours: Final reminder with discount offer
     */
    protected array $recoverySequence = [
        ['delay_minutes' => 30, 'type' => 'first_reminder', 'subject' => 'Votre box vous attend !'],
        ['delay_minutes' => 1440, 'type' => 'second_reminder', 'subject' => 'Votre réservation est toujours disponible'],
        ['delay_minutes' => 4320, 'type' => 'final_reminder', 'subject' => 'Dernière chance - 10% de réduction'],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
        }

        $this->info('🔄 Processing abandoned bookings recovery...');

        $recovered = 0;
        $totalProcessed = 0;

        // Get abandoned bookings (status: pending, incomplete, or cart_abandoned)
        $abandonedBookings = Booking::whereIn('status', ['pending', 'incomplete', 'cart_abandoned'])
            ->where('created_at', '>=', now()->subDays(5)) // Only last 5 days
            ->whereNotNull('email')
            ->with(['site', 'box'])
            ->get();

        foreach ($abandonedBookings as $booking) {
            $minutesSinceCreation = now()->diffInMinutes($booking->created_at);
            $recoveryStep = $this->getRecoveryStep($booking, $minutesSinceCreation);

            if (!$recoveryStep) {
                continue;
            }

            $totalProcessed++;

            if ($dryRun) {
                $this->line("  [DRY RUN] Would send '{$recoveryStep['type']}' to {$booking->email}");
                $this->line("    Booking: {$booking->reference} - {$booking->box?->number}");
                $recovered++;
                continue;
            }

            try {
                $this->sendRecoveryEmail($booking, $recoveryStep);
                $this->markRecoverySent($booking, $recoveryStep['type']);

                $this->line("  ✓ Sent '{$recoveryStep['type']}' to {$booking->email}");
                $recovered++;

                Log::info('Abandoned booking recovery email sent', [
                    'booking_id' => $booking->id,
                    'email' => $booking->email,
                    'recovery_type' => $recoveryStep['type'],
                ]);

            } catch (\Exception $e) {
                $this->error("  ✗ Failed to send recovery for {$booking->reference}: {$e->getMessage()}");
                Log::error('Failed to send abandoned booking recovery', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->info("  Total abandoned bookings: {$abandonedBookings->count()}");
        $this->info("  Processed for recovery: {$totalProcessed}");
        $this->info("  Recovery emails " . ($dryRun ? 'would be' : '') . " sent: {$recovered}");

        return Command::SUCCESS;
    }

    /**
     * Determine which recovery step to apply based on timing and previous sends
     */
    private function getRecoveryStep(Booking $booking, int $minutesSinceCreation): ?array
    {
        $metadata = $booking->metadata ?? [];
        $recoverySent = $metadata['recovery_emails_sent'] ?? [];

        foreach ($this->recoverySequence as $step) {
            // Skip if this step was already sent
            if (in_array($step['type'], $recoverySent)) {
                continue;
            }

            // Check if enough time has passed for this step
            if ($minutesSinceCreation >= $step['delay_minutes']) {
                // For subsequent steps, check the previous one was sent
                $stepIndex = array_search($step, $this->recoverySequence);
                if ($stepIndex > 0) {
                    $previousStep = $this->recoverySequence[$stepIndex - 1];
                    if (!in_array($previousStep['type'], $recoverySent)) {
                        continue;
                    }
                }
                return $step;
            }
        }

        return null;
    }

    /**
     * Send recovery email to the customer
     */
    private function sendRecoveryEmail(Booking $booking, array $recoveryStep): void
    {
        $customerName = $booking->first_name ?? 'Client';
        $boxInfo = $booking->box ? "{$booking->box->number} ({$booking->box->size}m²)" : 'votre box';
        $siteName = $booking->site?->name ?? 'notre site';
        $price = number_format($booking->total_price ?? 0, 2, ',', ' ') . ' €';

        // Build recovery URL with token
        $recoveryToken = encrypt($booking->id . '|' . now()->timestamp);
        $recoveryUrl = url("/booking/recover/{$recoveryToken}");

        $content = $this->buildEmailContent($recoveryStep['type'], [
            'customer_name' => $customerName,
            'box_info' => $boxInfo,
            'site_name' => $siteName,
            'price' => $price,
            'recovery_url' => $recoveryUrl,
            'reference' => $booking->reference ?? 'N/A',
        ]);

        // Send email using Laravel's mail system
        Mail::html($content, function ($message) use ($booking, $recoveryStep) {
            $message->to($booking->email)
                ->subject($recoveryStep['subject'])
                ->from(config('mail.from.address'), config('mail.from.name'));
        });
    }

    /**
     * Build email content based on recovery type
     */
    private function buildEmailContent(string $type, array $data): string
    {
        $baseStyle = '
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #8FBD56 0%, #5cd3b9 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .header h1 { color: white; margin: 0; font-size: 24px; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .btn { display: inline-block; background: #8FBD56; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
                .btn:hover { background: #7aa94a; }
                .discount { background: #ff6b6b; color: white; padding: 10px 20px; border-radius: 5px; font-size: 18px; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        ';

        switch ($type) {
            case 'first_reminder':
                return "
                    <html><head>{$baseStyle}</head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>📦 Votre box vous attend !</h1>
                        </div>
                        <div class='content'>
                            <p>Bonjour {$data['customer_name']},</p>
                            <p>Nous avons remarqué que vous n'avez pas terminé votre réservation pour <strong>{$data['box_info']}</strong> sur <strong>{$data['site_name']}</strong>.</p>
                            <p>Votre sélection est encore disponible ! Ne laissez pas passer cette opportunité.</p>
                            <p style='text-align: center;'>
                                <a href='{$data['recovery_url']}' class='btn'>Finaliser ma réservation</a>
                            </p>
                            <p><strong>Récapitulatif :</strong></p>
                            <ul>
                                <li>Box : {$data['box_info']}</li>
                                <li>Site : {$data['site_name']}</li>
                                <li>Prix : {$data['price']}/mois</li>
                            </ul>
                        </div>
                        <div class='footer'>
                            <p>BoxiBox - Votre solution de self-stockage</p>
                        </div>
                    </div>
                    </body></html>
                ";

            case 'second_reminder':
                return "
                    <html><head>{$baseStyle}</head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>⏰ Votre réservation expire bientôt !</h1>
                        </div>
                        <div class='content'>
                            <p>Bonjour {$data['customer_name']},</p>
                            <p>La demande est forte pour les boxes sur <strong>{$data['site_name']}</strong>.</p>
                            <p>Votre box <strong>{$data['box_info']}</strong> est toujours disponible, mais nous ne pouvons pas la garantir plus longtemps.</p>
                            <p style='text-align: center;'>
                                <a href='{$data['recovery_url']}' class='btn'>Réserver maintenant</a>
                            </p>
                            <p>Des questions ? Notre équipe est disponible pour vous aider.</p>
                        </div>
                        <div class='footer'>
                            <p>BoxiBox - Votre solution de self-stockage</p>
                        </div>
                    </div>
                    </body></html>
                ";

            case 'final_reminder':
                return "
                    <html><head>{$baseStyle}</head><body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🎁 Offre exclusive pour vous !</h1>
                        </div>
                        <div class='content'>
                            <p>Bonjour {$data['customer_name']},</p>
                            <p>C'est votre dernière chance ! Pour vous remercier de votre intérêt, nous vous offrons :</p>
                            <p style='text-align: center;'>
                                <span class='discount'>-10% sur votre premier mois</span>
                            </p>
                            <p>Cette offre expire dans <strong>24 heures</strong>.</p>
                            <p style='text-align: center;'>
                                <a href='{$data['recovery_url']}?discount=RECOVERY10' class='btn'>Profiter de l'offre</a>
                            </p>
                            <p><small>Code promo : RECOVERY10 (appliqué automatiquement)</small></p>
                        </div>
                        <div class='footer'>
                            <p>BoxiBox - Votre solution de self-stockage</p>
                        </div>
                    </div>
                    </body></html>
                ";

            default:
                return '';
        }
    }

    /**
     * Mark recovery email as sent in booking metadata
     */
    private function markRecoverySent(Booking $booking, string $type): void
    {
        $metadata = $booking->metadata ?? [];
        $metadata['recovery_emails_sent'] = $metadata['recovery_emails_sent'] ?? [];
        $metadata['recovery_emails_sent'][] = $type;
        $metadata['last_recovery_sent_at'] = now()->toIso8601String();

        $booking->update(['metadata' => $metadata]);
    }
}
