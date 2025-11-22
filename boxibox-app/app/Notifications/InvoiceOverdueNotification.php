<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The invoice instance.
     */
    public function __construct(
        public Invoice $invoice
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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

        return (new MailMessage)
            ->subject('Payment Reminder: Overdue Invoice')
            ->greeting("Hello {$customerName},")
            ->line("This is a friendly reminder that invoice **{$this->invoice->invoice_number}** is now **{$daysOverdue} days overdue**.")
            ->line("**Invoice Details:**")
            ->line("- Invoice Number: {$this->invoice->invoice_number}")
            ->line("- Amount Due: €{$this->invoice->total}")
            ->line("- Original Due Date: {$this->invoice->due_date->format('F j, Y')}")
            ->line("- Days Overdue: {$daysOverdue} days")
            ->action('View Invoice', url("/tenant/invoices/{$this->invoice->id}"))
            ->line('Please arrange payment at your earliest convenience to avoid any service interruption.')
            ->line('If you have any questions or concerns, please contact us immediately.')
            ->line('Thank you for your prompt attention to this matter.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->total,
            'due_date' => $this->invoice->due_date->format('Y-m-d'),
            'days_overdue' => abs(now()->diffInDays($this->invoice->due_date, false)),
        ];
    }
}
