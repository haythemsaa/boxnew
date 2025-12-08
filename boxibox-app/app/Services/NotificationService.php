<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use App\Mail\InvoiceNotification;
use App\Mail\PaymentReminder;
use App\Mail\ContractExpirationWarning;
use App\Mail\PaymentConfirmation;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Check if email notifications are enabled.
     */
    protected function isEmailEnabled(): bool
    {
        return config('mail.default') !== 'log' || app()->environment('local', 'testing');
    }

    /**
     * Send invoice notification email.
     */
    public function sendInvoiceNotification(Invoice $invoice, string $type = 'sent'): bool
    {
        try {
            $invoice->load(['customer', 'contract']);

            if (!$invoice->customer || !$invoice->customer->email) {
                Log::warning("Cannot send invoice notification: no customer email for invoice #{$invoice->id}");
                return false;
            }

            Mail::to($invoice->customer->email)
                ->queue(new InvoiceNotification($invoice, $type));

            Log::info("Invoice notification ({$type}) sent to {$invoice->customer->email} for invoice #{$invoice->invoice_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send invoice notification: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'type' => $type,
            ]);
            return false;
        }
    }

    /**
     * Send payment reminder email.
     */
    public function sendPaymentReminder(Invoice $invoice): bool
    {
        try {
            $invoice->load(['customer', 'contract']);

            if (!in_array($invoice->status, ['pending', 'sent', 'overdue', 'partial'])) {
                Log::info("Skipping payment reminder for invoice #{$invoice->id}: status is {$invoice->status}");
                return false;
            }

            if (!$invoice->customer || !$invoice->customer->email) {
                Log::warning("Cannot send payment reminder: no customer email for invoice #{$invoice->id}");
                return false;
            }

            Mail::to($invoice->customer->email)
                ->queue(new PaymentReminder($invoice));

            // Update reminder count on invoice
            $invoice->increment('reminder_count');
            $invoice->update(['last_reminder_at' => now()]);

            Log::info("Payment reminder sent to {$invoice->customer->email} for invoice #{$invoice->invoice_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment reminder: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
            ]);
            return false;
        }
    }

    /**
     * Send contract expiration warning.
     */
    public function sendContractExpirationWarning(Contract $contract, int $daysUntilExpiry = 30): bool
    {
        try {
            $contract->load(['customer', 'box']);

            if ($contract->status !== 'active' || !$contract->end_date) {
                Log::info("Skipping expiration warning for contract #{$contract->id}: not active or no end date");
                return false;
            }

            if (!$contract->customer || !$contract->customer->email) {
                Log::warning("Cannot send expiration warning: no customer email for contract #{$contract->id}");
                return false;
            }

            Mail::to($contract->customer->email)
                ->queue(new ContractExpirationWarning($contract, $daysUntilExpiry));

            Log::info("Contract expiration warning sent to {$contract->customer->email} for contract #{$contract->contract_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send contract expiration warning: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
            ]);
            return false;
        }
    }

    /**
     * Send contract renewal confirmation.
     */
    public function sendRenewalConfirmation(Contract $contract): bool
    {
        try {
            $contract->load(['customer', 'box']);

            if (!$contract->customer || !$contract->customer->email) {
                Log::warning("Cannot send renewal confirmation: no customer email for contract #{$contract->id}");
                return false;
            }

            // Using invoice notification with 'renewal' type for now
            Mail::to($contract->customer->email)
                ->queue(new \App\Mail\ContractExpirationWarning($contract, 0));

            Log::info("Renewal confirmation sent to {$contract->customer->email} for contract #{$contract->contract_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send renewal confirmation: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
            ]);
            return false;
        }
    }

    /**
     * Send contract termination notification.
     */
    public function sendTerminationNotification(Contract $contract): bool
    {
        try {
            $contract->load(['customer', 'box']);

            if (!$contract->customer || !$contract->customer->email) {
                Log::warning("Cannot send termination notification: no customer email for contract #{$contract->id}");
                return false;
            }

            // Send a plain text email for termination for now
            Mail::raw(
                "Bonjour,\n\nNous confirmons la resiliation de votre contrat #{$contract->contract_number}.\n\nCordialement,\nL'equipe BoxiBox",
                function ($message) use ($contract) {
                    $message->to($contract->customer->email)
                        ->subject("Confirmation de resiliation - Contrat #{$contract->contract_number}");
                }
            );

            Log::info("Termination notification sent to {$contract->customer->email} for contract #{$contract->contract_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send termination notification: ' . $e->getMessage(), [
                'contract_id' => $contract->id,
            ]);
            return false;
        }
    }

    /**
     * Send daily digest of overdue invoices.
     */
    public function sendOverdueInvoicesDigest(int $tenantId): int
    {
        $overdue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->with(['customer', 'contract'])
            ->get();

        if ($overdue->isEmpty()) {
            return 0;
        }

        // Group by customer
        $byCustomer = $overdue->groupBy('customer_id');

        $sent = 0;
        foreach ($byCustomer as $customerId => $invoices) {
            $customer = $invoices->first()->customer;

            if (!$customer || !$customer->email) {
                continue;
            }

            try {
                // Send individual reminders for each invoice
                foreach ($invoices as $invoice) {
                    Mail::to($customer->email)
                        ->queue(new PaymentReminder($invoice));
                }
                $sent++;
            } catch (\Exception $e) {
                Log::error("Failed to send overdue digest to customer {$customerId}: " . $e->getMessage());
            }
        }

        Log::info("Overdue digest sent to {$sent} customers for tenant {$tenantId}");
        return $sent;
    }

    /**
     * Send payment confirmation email.
     */
    public function sendPaymentConfirmation(Invoice $invoice, float $amount): bool
    {
        try {
            $invoice->load(['customer', 'contract']);

            if (!$invoice->customer || !$invoice->customer->email) {
                Log::warning("Cannot send payment confirmation: no customer email for invoice #{$invoice->id}");
                return false;
            }

            Mail::to($invoice->customer->email)
                ->queue(new PaymentConfirmation($invoice, $amount));

            Log::info("Payment confirmation sent to {$invoice->customer->email} for invoice #{$invoice->invoice_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send payment confirmation: ' . $e->getMessage(), [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);
            return false;
        }
    }

    /**
     * Send customer welcome email.
     */
    public function sendWelcomeEmail(Customer $customer): bool
    {
        try {
            if (!$customer->email) {
                Log::warning("Cannot send welcome email: no email for customer #{$customer->id}");
                return false;
            }

            Mail::to($customer->email)
                ->queue(new WelcomeEmail($customer));

            Log::info("Welcome email sent to {$customer->email}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage(), [
                'customer_id' => $customer->id,
            ]);
            return false;
        }
    }

    /**
     * Send monthly summary report.
     */
    public function sendMonthlySummary(int $tenantId, string $recipientEmail): bool
    {
        try {
            // Get summary data
            $startOfMonth = now()->startOfMonth();
            $contracts = Contract::where('tenant_id', $tenantId)->where('status', 'active')->count();
            $totalInvoiced = Invoice::where('tenant_id', $tenantId)
                ->where('invoice_date', '>=', $startOfMonth)
                ->sum('total');
            $totalPaid = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->where('paid_at', '>=', $startOfMonth)
                ->sum('total');
            $overdueAmount = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->sum('total');

            $content = "Rapport mensuel - " . now()->format('F Y') . "\n\n";
            $content .= "Contrats actifs: {$contracts}\n";
            $content .= "Total facture: " . number_format($totalInvoiced, 2, ',', ' ') . " EUR\n";
            $content .= "Total encaisse: " . number_format($totalPaid, 2, ',', ' ') . " EUR\n";
            $content .= "Impayes: " . number_format($overdueAmount, 2, ',', ' ') . " EUR\n";

            Mail::raw($content, function ($message) use ($recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('Rapport mensuel - BoxiBox');
            });

            Log::info("Monthly summary sent to {$recipientEmail}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send monthly summary: ' . $e->getMessage(), [
                'tenant_id' => $tenantId,
            ]);
            return false;
        }
    }

    /**
     * Send bulk SMS (placeholder for SMS integration).
     */
    public function sendSms(string $phoneNumber, string $message): bool
    {
        try {
            // TODO: Integrate with SMS provider (Twilio, Vonage, etc.)
            Log::info("SMS would be sent to {$phoneNumber}: {$message}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS to {$phoneNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify tenant users about a new booking from the website.
     */
    public function notifyNewBooking(Booking $booking): int
    {
        try {
            $booking->load(['box', 'site', 'tenant']);

            $customerName = trim($booking->customer_first_name . ' ' . $booking->customer_last_name);
            $boxCode = $booking->box?->code ?? 'N/A';
            $boxSize = $booking->box?->size ? " ({$booking->box->size} m²)" : '';
            $siteName = $booking->site?->name ?? '';
            $monthlyPrice = number_format($booking->monthly_price ?? 0, 2, ',', ' ');
            $startDate = $booking->start_date?->format('d/m/Y') ?? '-';
            $source = $booking->source ?? 'website';

            $sourceLabel = match($source) {
                'website' => 'Site web',
                'widget' => 'Widget intégré',
                'api' => 'API',
                default => ucfirst($source),
            };

            // Create in-app notifications for all users of this tenant
            $users = User::where('tenant_id', $booking->tenant_id)
                ->where('is_active', true)
                ->get();

            $notificationCount = 0;

            foreach ($users as $user) {
                // Create in-app notification
                Notification::create([
                    'tenant_id' => $booking->tenant_id,
                    'user_id' => $user->id,
                    'type' => 'new_booking',
                    'title' => 'Nouvelle réservation en ligne',
                    'message' => "Nouvelle réservation de {$customerName} pour le box {$boxCode}{$boxSize}" .
                        ($siteName ? " à {$siteName}" : '') .
                        ". Loyer: {$monthlyPrice} €/mois. Début: {$startDate}. Source: {$sourceLabel}.",
                    'channels' => ['in_app', 'email'],
                    'priority' => 'high',
                    'related_type' => Booking::class,
                    'related_id' => $booking->id,
                    'data' => [
                        'booking_id' => $booking->id,
                        'booking_number' => $booking->booking_number,
                        'customer_name' => $customerName,
                        'customer_email' => $booking->customer_email,
                        'customer_phone' => $booking->customer_phone,
                        'box_code' => $boxCode,
                        'box_size' => $booking->box?->size,
                        'site_name' => $siteName,
                        'monthly_price' => $booking->monthly_price,
                        'start_date' => $startDate,
                        'source' => $source,
                        'status' => $booking->status,
                    ],
                ]);

                $notificationCount++;

                // Also send email notification to users with email enabled
                if ($user->email) {
                    try {
                        $emailContent = $this->buildNewBookingEmailContent($booking, $customerName, $boxCode, $boxSize, $siteName, $monthlyPrice, $startDate, $sourceLabel);

                        Mail::raw($emailContent, function ($message) use ($user, $booking) {
                            $message->to($user->email)
                                ->subject("🎉 Nouvelle réservation #{$booking->booking_number} - {$booking->customer_first_name} {$booking->customer_last_name}");
                        });
                    } catch (\Exception $e) {
                        Log::warning("Failed to send new booking email to {$user->email}: " . $e->getMessage());
                    }
                }
            }

            Log::info("New booking notification sent to {$notificationCount} users for booking #{$booking->booking_number}");
            return $notificationCount;
        } catch (\Exception $e) {
            Log::error('Failed to notify new booking: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
            ]);
            return 0;
        }
    }

    /**
     * Build email content for new booking notification.
     */
    protected function buildNewBookingEmailContent(
        Booking $booking,
        string $customerName,
        string $boxCode,
        string $boxSize,
        string $siteName,
        string $monthlyPrice,
        string $startDate,
        string $sourceLabel
    ): string {
        $content = "Bonjour,\n\n";
        $content .= "Une nouvelle réservation vient d'être effectuée sur votre site !\n\n";
        $content .= "═══════════════════════════════════════\n";
        $content .= "📋 DÉTAILS DE LA RÉSERVATION\n";
        $content .= "═══════════════════════════════════════\n\n";
        $content .= "Numéro: {$booking->booking_number}\n";
        $content .= "Statut: " . ucfirst($booking->status) . "\n";
        $content .= "Source: {$sourceLabel}\n\n";

        $content .= "👤 CLIENT\n";
        $content .= "───────────────────────────────────────\n";
        $content .= "Nom: {$customerName}\n";
        $content .= "Email: {$booking->customer_email}\n";
        if ($booking->customer_phone) {
            $content .= "Téléphone: {$booking->customer_phone}\n";
        }
        if ($booking->customer_company) {
            $content .= "Entreprise: {$booking->customer_company}\n";
        }
        $content .= "\n";

        $content .= "📦 BOX RÉSERVÉ\n";
        $content .= "───────────────────────────────────────\n";
        $content .= "Box: {$boxCode}{$boxSize}\n";
        if ($siteName) {
            $content .= "Site: {$siteName}\n";
        }
        $content .= "Loyer mensuel: {$monthlyPrice} €\n";
        if ($booking->deposit_amount > 0) {
            $content .= "Dépôt de garantie: " . number_format($booking->deposit_amount, 2, ',', ' ') . " €\n";
        }
        $content .= "\n";

        $content .= "📅 DATES\n";
        $content .= "───────────────────────────────────────\n";
        $content .= "Date de début: {$startDate}\n";
        if ($booking->planned_duration_months) {
            $content .= "Durée prévue: {$booking->planned_duration_months} mois\n";
        }
        if ($booking->end_date) {
            $content .= "Date de fin: " . $booking->end_date->format('d/m/Y') . "\n";
        }
        $content .= "\n";

        // Special requests
        $specialNeeds = [];
        if ($booking->needs_24h_access) $specialNeeds[] = "Accès 24h/24";
        if ($booking->needs_climate_control) $specialNeeds[] = "Contrôle climatique";
        if ($booking->needs_electricity) $specialNeeds[] = "Électricité";
        if ($booking->needs_insurance) $specialNeeds[] = "Assurance";
        if ($booking->needs_moving_help) $specialNeeds[] = "Aide au déménagement";

        if (!empty($specialNeeds)) {
            $content .= "⚡ BESOINS SPÉCIAUX\n";
            $content .= "───────────────────────────────────────\n";
            $content .= implode(", ", $specialNeeds) . "\n\n";
        }

        if ($booking->special_requests) {
            $content .= "📝 DEMANDES PARTICULIÈRES\n";
            $content .= "───────────────────────────────────────\n";
            $content .= $booking->special_requests . "\n\n";
        }

        $content .= "═══════════════════════════════════════\n\n";
        $content .= "Connectez-vous à votre espace pour gérer cette réservation.\n\n";
        $content .= "Cordialement,\n";
        $content .= "L'équipe BoxiBox\n";

        return $content;
    }

    /**
     * Send booking confirmation email to the customer.
     */
    public function sendBookingConfirmationToCustomer(Booking $booking): bool
    {
        try {
            if (!$booking->customer_email) {
                Log::warning("Cannot send booking confirmation: no customer email for booking #{$booking->id}");
                return false;
            }

            $booking->load(['box', 'site']);

            $boxCode = $booking->box?->code ?? 'N/A';
            $boxSize = $booking->box?->size ? " ({$booking->box->size} m²)" : '';
            $siteName = $booking->site?->name ?? '';
            $siteAddress = $booking->site?->address ?? '';
            $monthlyPrice = number_format($booking->monthly_price ?? 0, 2, ',', ' ');
            $startDate = $booking->start_date?->format('d/m/Y') ?? '-';

            $content = "Bonjour {$booking->customer_first_name},\n\n";
            $content .= "Nous avons bien reçu votre demande de réservation et nous vous en remercions !\n\n";
            $content .= "═══════════════════════════════════════\n";
            $content .= "📋 RÉCAPITULATIF DE VOTRE RÉSERVATION\n";
            $content .= "═══════════════════════════════════════\n\n";
            $content .= "Numéro de réservation: {$booking->booking_number}\n";
            $content .= "Statut: En attente de confirmation\n\n";

            $content .= "📦 VOTRE BOX\n";
            $content .= "───────────────────────────────────────\n";
            $content .= "Box: {$boxCode}{$boxSize}\n";
            if ($siteName) {
                $content .= "Site: {$siteName}\n";
            }
            if ($siteAddress) {
                $content .= "Adresse: {$siteAddress}\n";
            }
            $content .= "\n";

            $content .= "💰 TARIFICATION\n";
            $content .= "───────────────────────────────────────\n";
            $content .= "Loyer mensuel: {$monthlyPrice} €\n";
            if ($booking->deposit_amount > 0) {
                $content .= "Dépôt de garantie: " . number_format($booking->deposit_amount, 2, ',', ' ') . " €\n";
            }
            if ($booking->discount_amount > 0) {
                $content .= "Remise appliquée: -" . number_format($booking->discount_amount, 2, ',', ' ') . " €\n";
            }
            $content .= "\n";

            $content .= "📅 DATE DE DÉBUT\n";
            $content .= "───────────────────────────────────────\n";
            $content .= "{$startDate}\n\n";

            $content .= "═══════════════════════════════════════\n\n";
            $content .= "Notre équipe va traiter votre demande dans les plus brefs délais.\n";
            $content .= "Vous recevrez un email de confirmation dès que votre réservation sera validée.\n\n";
            $content .= "Si vous avez des questions, n'hésitez pas à nous contacter.\n\n";
            $content .= "À très bientôt !\n\n";
            $content .= "Cordialement,\n";
            $content .= "L'équipe BoxiBox\n";

            Mail::raw($content, function ($message) use ($booking) {
                $message->to($booking->customer_email)
                    ->subject("Confirmation de réservation #{$booking->booking_number}");
            });

            Log::info("Booking confirmation sent to {$booking->customer_email} for booking #{$booking->booking_number}");
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send booking confirmation: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
            ]);
            return false;
        }
    }
}
