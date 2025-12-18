<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\PaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tenantId;

    public function __construct(public PaymentReminder $reminder)
    {
        $this->tenantId = $reminder->tenant_id;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if (in_array($this->reminder->method, ['email', 'both'])) {
            $channels[] = 'mail';
        }

        // Canal SMS via le provider du tenant
        if (in_array($this->reminder->method, ['sms', 'both'])) {
            $channels[] = SmsChannel::class;
        }

        // Toujours stocker en base pour l'historique
        $channels[] = 'database';

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $invoice = $this->reminder->invoice;
        $subject = $this->getEmailSubject();

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . $this->getCustomerName() . ',')
            ->line($this->reminder->message);

        // Add invoice details
        $mail->line('')
            ->line('**Détails de la facture :**')
            ->line('Numéro : ' . $invoice->invoice_number)
            ->line('Montant : ' . number_format($invoice->total, 2, ',', ' ') . ' €')
            ->line('Date d\'échéance : ' . $invoice->due_date->format('d/m/Y'));

        // Add action button - customer portal link
        $mail->action('Payer ma facture', url('/customer/invoices/' . $invoice->id));

        // Add footer based on reminder type
        if ($this->reminder->type === 'after_due') {
            $mail->line('')
                ->line('En cas de difficultés de paiement, n\'hésitez pas à nous contacter pour trouver une solution.')
                ->salutation('Cordialement,');
        } else {
            $mail->salutation('Cordialement,');
        }

        return $mail;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $invoice = $this->reminder->invoice;
        $amount = number_format($invoice->total, 2, ',', ' ');
        $dueDate = $invoice->due_date->format('d/m/Y');

        return match ($this->reminder->type) {
            'before_due' => "BoxiBox: Facture {$invoice->invoice_number} de {$amount}€ à échéance le {$dueDate}. Pensez à régler avant la date limite.",
            'on_due' => "BoxiBox: Facture {$invoice->invoice_number} ({$amount}€) arrive à échéance AUJOURD'HUI. Réglez-la dès maintenant.",
            'after_due' => "BoxiBox: RAPPEL - Facture {$invoice->invoice_number} de {$amount}€ en retard depuis le {$dueDate}. Merci de régulariser rapidement.",
            default => "BoxiBox: Rappel paiement facture {$invoice->invoice_number} - {$amount}€. Échéance: {$dueDate}.",
        };
    }

    /**
     * Get SMS type for tracking.
     */
    public function getSmsType(): string
    {
        return 'payment_reminder';
    }

    /**
     * Get SMS metadata for tracking.
     */
    public function getSmsMetadata(): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'invoice_id' => $this->reminder->invoice_id,
            'reminder_type' => $this->reminder->type,
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'invoice_id' => $this->reminder->invoice_id,
            'invoice_number' => $this->reminder->invoice->invoice_number,
            'type' => $this->reminder->type,
            'amount' => $this->reminder->invoice->total,
            'due_date' => $this->reminder->invoice->due_date->toISOString(),
            'message' => $this->reminder->message,
        ];
    }

    /**
     * Get email subject based on reminder type
     */
    protected function getEmailSubject(): string
    {
        $invoiceNumber = $this->reminder->invoice->invoice_number;

        return match ($this->reminder->type) {
            'before_due' => "Rappel : Facture {$invoiceNumber} à échéance dans 3 jours",
            'on_due' => "Échéance aujourd'hui : Facture {$invoiceNumber}",
            'after_due' => "Facture {$invoiceNumber} en retard - Relance",
            default => "Rappel de paiement - Facture {$invoiceNumber}",
        };
    }

    /**
     * Get customer name for greeting
     */
    protected function getCustomerName(): string
    {
        $customer = $this->reminder->customer;

        if ($customer->type === 'company') {
            return $customer->company_name;
        }

        return $customer->first_name . ' ' . $customer->last_name;
    }
}
