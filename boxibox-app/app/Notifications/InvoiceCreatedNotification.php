<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Invoice #{$this->invoice->invoice_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new invoice has been created for your storage rental.")
            ->line("**Invoice Number:** {$this->invoice->invoice_number}")
            ->line("**Amount:** €{$this->invoice->total}")
            ->line("**Due Date:** {$this->invoice->due_date->format('F j, Y')}")
            ->action('View Invoice', url("/portal/invoices/{$this->invoice->id}"))
            ->line('Please ensure payment is made by the due date to avoid late fees.')
            ->line('Thank you for your business!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'total' => $this->invoice->total,
            'due_date' => $this->invoice->due_date->format('Y-m-d'),
        ];
    }
}
