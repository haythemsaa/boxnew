<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The contract instance.
     */
    public function __construct(
        public Contract $contract
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
        $daysUntilExpiry = now()->diffInDays($this->contract->end_date);
        $customerName = $this->contract->customer->type === 'company'
            ? $this->contract->customer->company_name
            : $this->contract->customer->first_name;

        return (new MailMessage)
            ->subject('Your Storage Contract is Expiring Soon')
            ->greeting("Hello {$customerName},")
            ->line("Your storage contract **{$this->contract->contract_number}** will expire in **{$daysUntilExpiry} days**.")
            ->line("**Contract Details:**")
            ->line("- Box: {$this->contract->box->name}")
            ->line("- Site: {$this->contract->site->name}")
            ->line("- Expiration Date: {$this->contract->end_date->format('F j, Y')}")
            ->line("- Monthly Price: €{$this->contract->monthly_price}")
            ->action('Renew Contract', url("/tenant/contracts/{$this->contract->id}"))
            ->line('If you wish to continue using your storage unit, please contact us to renew your contract.')
            ->line('Thank you for choosing our storage services!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'box_name' => $this->contract->box->name,
            'end_date' => $this->contract->end_date->format('Y-m-d'),
            'days_until_expiry' => now()->diffInDays($this->contract->end_date),
        ];
    }
}
