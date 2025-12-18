<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tenantId;

    /**
     * The invoice instance.
     */
    public function __construct(
        public Invoice $invoice,
        public bool $includeSms = false
    ) {
        $this->tenantId = $invoice->tenant_id;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        // Ajouter SMS si demandé et si le client a un numéro de téléphone
        if ($this->includeSms && ($notifiable->mobile || $notifiable->phone)) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = abs(now()->diffInDays($this->invoice->due_date, false));
        $customerName = $this->invoice->customer->type === 'company'
            ? $this->invoice->customer->company_name
            : $this->invoice->customer->first_name;

        $amount = number_format($this->invoice->total, 2, ',', ' ');

        return (new MailMessage)
            ->subject("Facture {$this->invoice->invoice_number} en retard - Relance")
            ->greeting("Bonjour {$customerName},")
            ->line("Nous vous rappelons que la facture **{$this->invoice->invoice_number}** est en retard de **{$daysOverdue} jour(s)**.")
            ->line('')
            ->line('**Détails de la facture :**')
            ->line("- Numéro : {$this->invoice->invoice_number}")
            ->line("- Montant dû : {$amount} €")
            ->line("- Date d'échéance : {$this->invoice->due_date->format('d/m/Y')}")
            ->line("- Jours de retard : {$daysOverdue}")
            ->line('')
            ->action('Payer ma facture', url("/customer/invoices/{$this->invoice->id}"))
            ->line('')
            ->line('Merci de procéder au règlement dans les meilleurs délais afin d\'éviter toute interruption de service.')
            ->line('En cas de difficultés de paiement, n\'hésitez pas à nous contacter pour trouver une solution.')
            ->salutation('Cordialement,');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $daysOverdue = abs(now()->diffInDays($this->invoice->due_date, false));
        $amount = number_format($this->invoice->total, 2, ',', ' ');

        return "BoxiBox: RAPPEL - Facture {$this->invoice->invoice_number} ({$amount}€) en retard de {$daysOverdue}j. Merci de régulariser rapidement pour éviter une interruption de service.";
    }

    /**
     * Get SMS type for tracking.
     */
    public function getSmsType(): string
    {
        return 'invoice_overdue';
    }

    /**
     * Get SMS metadata for tracking.
     */
    public function getSmsMetadata(): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'days_overdue' => abs(now()->diffInDays($this->invoice->due_date, false)),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_overdue',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total,
            'due_date' => $this->invoice->due_date->format('Y-m-d'),
            'days_overdue' => abs(now()->diffInDays($this->invoice->due_date, false)),
        ];
    }
}
