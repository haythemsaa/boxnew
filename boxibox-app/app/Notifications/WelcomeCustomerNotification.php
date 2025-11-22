<?php

namespace App\Notifications;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The customer instance.
     */
    public function __construct(
        public Customer $customer
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $customerName = $this->customer->type === 'company'
            ? $this->customer->company_name
            : $this->customer->first_name;

        return (new MailMessage)
            ->subject('Welcome to Our Storage Services!')
            ->greeting("Welcome {$customerName}!")
            ->line('Thank you for choosing us for your storage needs.')
            ->line('We are excited to have you as our customer!')
            ->line("**Your Account Details:**")
            ->line("- Customer ID: {$this->customer->customer_number}")
            ->line("- Email: {$this->customer->email}")
            ->line("- Phone: {$this->customer->phone}")
            ->action('View Your Dashboard', url('/tenant/dashboard'))
            ->line('**What You Can Do:**')
            ->line('✓ View and manage your contracts')
            ->line('✓ Access invoices and payment history')
            ->line('✓ Update your contact information')
            ->line('✓ Request support anytime')
            ->line('If you have any questions or need assistance, our team is here to help!')
            ->line('Thank you for trusting us with your storage needs.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'customer_id' => $this->customer->id,
            'customer_number' => $this->customer->customer_number,
            'type' => $this->customer->type,
        ];
    }
}
