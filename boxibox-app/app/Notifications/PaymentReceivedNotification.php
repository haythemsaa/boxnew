<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The payment instance.
     */
    public function __construct(
        public Payment $payment
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
        $customerName = $this->payment->customer->type === 'company'
            ? $this->payment->customer->company_name
            : $this->payment->customer->first_name;

        $message = (new MailMessage)
            ->subject('Payment Received - Thank You!')
            ->greeting("Hello {$customerName},")
            ->line("We have successfully received your payment of **€{$this->payment->amount}**.")
            ->line("**Payment Details:**")
            ->line("- Payment Number: {$this->payment->payment_number}")
            ->line("- Amount: €{$this->payment->amount}")
            ->line("- Method: " . ucfirst($this->payment->method))
            ->line("- Date: {$this->payment->paid_at->format('F j, Y')}");

        if ($this->payment->invoice) {
            $message->line("- Invoice: {$this->payment->invoice->invoice_number}");
        }

        return $message
            ->action('View Payment', url("/tenant/payments/{$this->payment->id}"))
            ->line('Thank you for your prompt payment!')
            ->line('We appreciate your continued business.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_number' => $this->payment->payment_number,
            'amount' => $this->payment->amount,
            'method' => $this->payment->method,
            'paid_at' => $this->payment->paid_at->format('Y-m-d'),
        ];
    }
}
