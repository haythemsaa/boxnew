<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationLog;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NotificationsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        // Get first admin user
        $user = User::where('tenant_id', $tenantId)->first();

        if (!$user) {
            $this->command->warn('No user found, skipping notifications seeding');
            return;
        }

        // Create notification preferences if not exists
        $this->createNotificationPreferences($tenantId);

        // Create demo notifications
        $this->createDemoNotifications($tenantId, $user);

        // Create notification logs
        $this->createNotificationLogs($tenantId);

        $this->command->info('Demo notifications created successfully!');
    }

    private function createNotificationPreferences(int $tenantId): void
    {
        NotificationPreference::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                // Global channel toggles
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true,

                // Invoice notifications
                'invoice_created_email' => true,
                'invoice_created_sms' => false,
                'invoice_created_push' => true,

                // Payment notifications
                'payment_received_email' => true,
                'payment_received_sms' => false,
                'payment_received_push' => true,

                // Payment reminders
                'payment_reminder_email' => true,
                'payment_reminder_sms' => false,
                'payment_reminder_push' => true,

                // Contract notifications
                'contract_expiring_email' => true,
                'contract_expiring_sms' => false,
                'contract_expiring_push' => true,

                // Access alerts
                'access_alert_email' => true,
                'access_alert_sms' => true,
                'access_alert_push' => true,

                // IoT alerts
                'iot_alert_email' => true,
                'iot_alert_sms' => false,
                'iot_alert_push' => true,

                // Booking notifications
                'booking_confirmed_email' => true,
                'booking_confirmed_sms' => false,
                'booking_confirmed_push' => true,

                // Welcome notifications
                'welcome_email' => true,
                'welcome_sms' => false,
                'welcome_push' => false,
            ]
        );

        $this->command->info('Notification preferences configured');
    }

    private function createDemoNotifications(int $tenantId, User $user): void
    {
        // Clear existing demo notifications
        Notification::where('tenant_id', $tenantId)->delete();

        $notifications = [];

        // Get some real data for related entities
        $invoices = Invoice::where('tenant_id', $tenantId)->take(5)->get();
        $contracts = Contract::where('tenant_id', $tenantId)->take(5)->get();
        $customers = Customer::where('tenant_id', $tenantId)->take(5)->get();

        // Critical: Overdue payment notifications
        foreach ($invoices->take(3) as $index => $invoice) {
            $daysOverdue = rand(5, 30);
            $notifications[] = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'type' => Notification::TYPE_PAYMENT,
                'title' => 'Facture en retard',
                'message' => "La facture #{$invoice->invoice_number} est en retard de {$daysOverdue} jours. Montant: " . number_format($invoice->total, 2) . ' €',
                'channels' => ['in_app', 'email'],
                'status' => Notification::STATUS_SENT,
                'sent_at' => now()->subDays(rand(1, 5)),
                'priority' => $daysOverdue > 15 ? Notification::PRIORITY_CRITICAL : Notification::PRIORITY_HIGH,
                'is_read' => false,
                'related_type' => 'invoice',
                'related_id' => $invoice->id,
                'alert_key' => 'overdue_invoice_' . $invoice->id,
                'data' => [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total,
                    'days_overdue' => $daysOverdue,
                    'color' => $daysOverdue > 15 ? 'red' : 'orange',
                    'icon' => 'currency-euro',
                ],
                'created_at' => now()->subDays(rand(1, 5)),
            ];
        }

        // Contract expiring soon
        foreach ($contracts->take(2) as $contract) {
            $daysUntilExpiry = rand(7, 30);
            $notifications[] = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'type' => Notification::TYPE_CONTRACT,
                'title' => 'Contrat expire bientôt',
                'message' => "Le contrat #{$contract->contract_number} expire dans {$daysUntilExpiry} jours.",
                'channels' => ['in_app', 'email'],
                'status' => Notification::STATUS_SENT,
                'sent_at' => now()->subDays(rand(0, 3)),
                'priority' => $daysUntilExpiry < 14 ? Notification::PRIORITY_HIGH : Notification::PRIORITY_MEDIUM,
                'is_read' => rand(0, 1) === 1,
                'read_at' => rand(0, 1) === 1 ? now()->subHours(rand(1, 24)) : null,
                'related_type' => 'contract',
                'related_id' => $contract->id,
                'alert_key' => 'expiring_contract_' . $contract->id,
                'data' => [
                    'contract_number' => $contract->contract_number,
                    'days_until_expiry' => $daysUntilExpiry,
                    'color' => $daysUntilExpiry < 14 ? 'orange' : 'yellow',
                    'icon' => 'document',
                ],
                'created_at' => now()->subDays(rand(0, 3)),
            ];
        }

        // New customer notifications
        foreach ($customers->take(3) as $customer) {
            $notifications[] = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'type' => Notification::TYPE_BOOKING,
                'title' => 'Nouveau client',
                'message' => "{$customer->full_name} vient de s'inscrire.",
                'channels' => ['in_app'],
                'status' => Notification::STATUS_SENT,
                'sent_at' => now()->subDays(rand(0, 7)),
                'priority' => Notification::PRIORITY_LOW,
                'is_read' => rand(0, 1) === 1,
                'read_at' => rand(0, 1) === 1 ? now()->subHours(rand(1, 48)) : null,
                'related_type' => 'customer',
                'related_id' => $customer->id,
                'data' => [
                    'customer_name' => $customer->full_name,
                    'color' => 'green',
                    'icon' => 'user-plus',
                ],
                'created_at' => now()->subDays(rand(0, 7)),
            ];
        }

        // Payment received notifications
        foreach ($invoices->take(2) as $invoice) {
            $notifications[] = [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'type' => Notification::TYPE_PAYMENT,
                'title' => 'Paiement reçu',
                'message' => "Paiement de " . number_format($invoice->total, 2) . " € reçu pour la facture #{$invoice->invoice_number}.",
                'channels' => ['in_app', 'email'],
                'status' => Notification::STATUS_SENT,
                'sent_at' => now()->subDays(rand(1, 10)),
                'priority' => Notification::PRIORITY_LOW,
                'is_read' => true,
                'read_at' => now()->subDays(rand(0, 5)),
                'related_type' => 'invoice',
                'related_id' => $invoice->id,
                'data' => [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->total,
                    'color' => 'green',
                    'icon' => 'check-circle',
                ],
                'created_at' => now()->subDays(rand(1, 10)),
            ];
        }

        // System alerts
        $notifications[] = [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => Notification::TYPE_ALERT,
            'title' => 'Taux d\'occupation élevé',
            'message' => 'Votre taux d\'occupation a atteint 92%. Pensez à ajuster vos tarifs.',
            'channels' => ['in_app'],
            'status' => Notification::STATUS_SENT,
            'sent_at' => now()->subHours(12),
            'priority' => Notification::PRIORITY_MEDIUM,
            'is_read' => false,
            'data' => [
                'occupancy_rate' => 92,
                'color' => 'blue',
                'icon' => 'chart-bar',
            ],
            'created_at' => now()->subHours(12),
        ];

        $notifications[] = [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => Notification::TYPE_SYSTEM,
            'title' => 'Mise à jour système',
            'message' => 'Une nouvelle version de BoxiBox est disponible avec de nouvelles fonctionnalités.',
            'channels' => ['in_app'],
            'status' => Notification::STATUS_SENT,
            'sent_at' => now()->subDays(2),
            'priority' => Notification::PRIORITY_LOW,
            'is_read' => true,
            'read_at' => now()->subDays(1),
            'data' => [
                'version' => '2.5.0',
                'color' => 'blue',
                'icon' => 'bell',
            ],
            'created_at' => now()->subDays(2),
        ];

        // Reminder notification
        $notifications[] = [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => Notification::TYPE_REMINDER,
            'title' => 'Rappel: Facturation mensuelle',
            'message' => 'N\'oubliez pas de générer les factures du mois.',
            'channels' => ['in_app', 'email'],
            'status' => Notification::STATUS_SENT,
            'sent_at' => now()->subHours(3),
            'priority' => Notification::PRIORITY_MEDIUM,
            'is_read' => false,
            'data' => [
                'color' => 'yellow',
                'icon' => 'bell',
            ],
            'created_at' => now()->subHours(3),
        ];

        // Promotional notification
        $notifications[] = [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'type' => Notification::TYPE_PROMOTION,
            'title' => 'Offre spéciale',
            'message' => 'Profitez de -20% sur le module d\'enchères jusqu\'à fin décembre!',
            'channels' => ['in_app'],
            'status' => Notification::STATUS_SENT,
            'sent_at' => now()->subDays(5),
            'priority' => Notification::PRIORITY_LOW,
            'is_read' => true,
            'read_at' => now()->subDays(4),
            'data' => [
                'discount' => 20,
                'color' => 'purple',
                'icon' => 'gift',
            ],
            'created_at' => now()->subDays(5),
        ];

        // Insert all notifications
        foreach ($notifications as $notification) {
            Notification::create($notification);
        }

        $this->command->info('Created ' . count($notifications) . ' demo notifications');
    }

    private function createNotificationLogs(int $tenantId): void
    {
        // Get user
        $user = User::where('tenant_id', $tenantId)->first();

        if (!$user) return;

        // Create some notification logs for audit trail
        $logs = [
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'type' => 'payment_reminder',
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => 'Rappel de paiement - Facture en retard',
                'body' => 'Votre facture est en retard de paiement...',
                'status' => 'sent',
                'sent_at' => now()->subDays(3),
                'created_at' => now()->subDays(3),
            ],
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'type' => 'contract_expiring',
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => 'Contrat arrivant à expiration',
                'body' => 'Votre contrat expire dans 30 jours...',
                'status' => 'sent',
                'sent_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'type' => 'payment_received',
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => 'Confirmation de paiement',
                'body' => 'Nous avons bien reçu votre paiement...',
                'status' => 'sent',
                'sent_at' => now()->subDay(),
                'created_at' => now()->subDay(),
            ],
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'type' => 'invoice_created',
                'channel' => 'email',
                'recipient' => $user->email,
                'subject' => 'Nouvelle facture disponible',
                'body' => 'Votre facture mensuelle est disponible...',
                'status' => 'failed',
                'error_message' => 'SMTP connection timeout',
                'created_at' => now()->subHours(6),
            ],
            [
                'tenant_id' => $tenantId,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'type' => 'booking_confirmed',
                'channel' => 'push',
                'recipient' => 'device_token_xxx',
                'subject' => 'Réservation confirmée',
                'body' => 'Votre réservation a été confirmée.',
                'status' => 'sent',
                'sent_at' => now()->subHours(2),
                'created_at' => now()->subHours(2),
            ],
        ];

        foreach ($logs as $log) {
            NotificationLog::create($log);
        }

        $this->command->info('Created ' . count($logs) . ' notification logs');
    }
}
