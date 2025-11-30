<?php

namespace App\Notifications;

use App\Models\PaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public PaymentReminder $reminder)
    {
        //
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

        // Add SMS channel if needed in the future
        // if (in_array($this->reminder->method, ['sms', 'both'])) {
        //     $channels[] = 'sms';
        // }

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

        // Add action button
        $mail->action('Voir ma facture', url('/tenant/invoices/' . $invoice->id));

        // Add footer based on reminder type
        if ($this->reminder->type === 'after_due') {
            $mail->line('')
                ->line('En cas de difficultés de paiement, n\'hésitez pas à nous contacter pour trouver une solution.')
                ->level('warning');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reminder_id' => $this->reminder->id,
            'invoice_id' => $this->reminder->invoice_id,
            'type' => $this->reminder->type,
            'amount' => $this->reminder->invoice->total,
        ];
    }

    /**
     * Get email subject based on reminder type
     */
    protected function getEmailSubject(): string
    {
        $invoiceNumber = $this->reminder->invoice->invoice_number;

        return match($this->reminder->type) {
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
