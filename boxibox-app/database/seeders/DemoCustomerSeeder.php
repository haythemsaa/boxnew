<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Site;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $site = Site::where('tenant_id', $tenantId)->first();

        // 1. Create Demo Customer User Account
        $demoUser = User::updateOrCreate(
            ['email' => 'demo.client@boxibox.be'],
            [
                'name' => 'Jean Demo',
                'email' => 'demo.client@boxibox.be',
                'password' => Hash::make('Demo2024!'),
                'tenant_id' => $tenantId,
                'email_verified_at' => now(),
            ]
        );

        // 2. Create Demo Customer
        $demoCustomer = Customer::updateOrCreate(
            ['email' => 'demo.client@boxibox.be', 'tenant_id' => $tenantId],
            [
                'tenant_id' => $tenantId,
                'user_id' => $demoUser->id,
                'type' => 'individual',
                'civility' => 'mr',
                'first_name' => 'Jean',
                'last_name' => 'Demo',
                'email' => 'demo.client@boxibox.be',
                'phone' => '+32 2 123 45 67',
                'mobile' => '+32 478 12 34 56',
                'birth_date' => '1985-06-15',
                'address' => '123 Rue de la Démonstration',
                'city' => 'Bruxelles',
                'postal_code' => '1000',
                'country' => 'BE',
                'status' => 'active',
                'notes' => 'Compte de démonstration pour tests mobile app',
                'created_at' => now()->subMonths(6),
            ]
        );

        $this->command->info("Demo Customer created: {$demoCustomer->email}");

        // 3. Get available box for contract
        $availableBox = Box::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->first();

        if (!$availableBox) {
            $availableBox = Box::where('tenant_id', $tenantId)->first();
        }

        // 4. Create Active Contract
        $contract = Contract::updateOrCreate(
            ['customer_id' => $demoCustomer->id, 'tenant_id' => $tenantId, 'status' => 'active'],
            [
                'tenant_id' => $tenantId,
                'site_id' => $site->id,
                'customer_id' => $demoCustomer->id,
                'box_id' => $availableBox?->id,
                'contract_number' => 'CTR-DEMO-' . date('Y') . '-001',
                'status' => 'active',
                'type' => 'standard',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->addMonths(9),
                'monthly_price' => 89.00,
                'deposit_amount' => 178.00,
                'billing_frequency' => 'monthly',
                'payment_method' => 'card',
                'auto_pay' => true,
                'access_code' => '1234',
                'key_given' => true,
                'signed_by_customer' => true,
                'customer_signed_at' => now()->subMonths(3),
                'signed_by_staff' => true,
                'staff_signed_at' => now()->subMonths(3),
            ]
        );

        $this->command->info("Demo Contract created: {$contract->contract_number}");

        // 5. Create Invoices (past months)
        $invoiceData = [
            ['months_ago' => 3, 'status' => 'paid'],
            ['months_ago' => 2, 'status' => 'paid'],
            ['months_ago' => 1, 'status' => 'paid'],
            ['months_ago' => 0, 'status' => 'sent'], // Current month - unpaid
        ];

        foreach ($invoiceData as $index => $data) {
            $invoiceDate = now()->subMonths($data['months_ago'])->startOfMonth();
            $invoice = Invoice::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'customer_id' => $demoCustomer->id,
                    'invoice_date' => $invoiceDate,
                ],
                [
                    'tenant_id' => $tenantId,
                    'customer_id' => $demoCustomer->id,
                    'contract_id' => $contract->id,
                    'invoice_number' => 'FAC-DEMO-' . $invoiceDate->format('Ym') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                    'type' => 'invoice',
                    'is_recurring' => true,
                    'status' => $data['status'],
                    'invoice_date' => $invoiceDate,
                    'due_date' => $invoiceDate->copy()->addDays(30),
                    'period_start' => $invoiceDate,
                    'period_end' => $invoiceDate->copy()->endOfMonth(),
                    'items' => json_encode([
                        ['description' => 'Location Box ' . ($availableBox?->name ?? 'A1'), 'quantity' => 1, 'unit_price' => 89.00, 'total' => 89.00]
                    ]),
                    'subtotal' => 89.00,
                    'tax_rate' => 21.00,
                    'tax_amount' => 18.69,
                    'total' => 107.69,
                    'paid_amount' => $data['status'] === 'paid' ? 107.69 : 0,
                    'paid_at' => $data['status'] === 'paid' ? $invoiceDate->copy()->addDays(5) : null,
                    'currency' => 'EUR',
                ]
            );

            // Create payment for paid invoices
            if ($data['status'] === 'paid') {
                Payment::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'tenant_id' => $tenantId],
                    [
                        'tenant_id' => $tenantId,
                        'customer_id' => $demoCustomer->id,
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract->id,
                        'payment_number' => 'PAY-DEMO-' . $invoiceDate->format('Ymd') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                        'type' => 'payment',
                        'status' => 'completed',
                        'amount' => 107.69,
                        'currency' => 'EUR',
                        'method' => 'card',
                        'gateway' => 'stripe',
                        'card_brand' => 'visa',
                        'card_last_four' => '4242',
                        'paid_at' => $invoiceDate->copy()->addDays(5),
                        'processed_at' => $invoiceDate->copy()->addDays(5),
                    ]
                );
            }
        }

        $this->command->info("Demo Invoices and Payments created");

        // 6. Create Pending Booking (nouvelle réservation)
        // Get an available box for booking
        $bookingBox = Box::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->where('id', '!=', $availableBox?->id)
            ->first() ?? $availableBox;

        $booking = Booking::updateOrCreate(
            ['customer_id' => $demoCustomer->id, 'tenant_id' => $tenantId, 'status' => 'pending'],
            [
                'tenant_id' => $tenantId,
                'site_id' => $site->id,
                'box_id' => $bookingBox?->id ?? 1,
                'customer_id' => $demoCustomer->id,
                'booking_number' => 'BKG-DEMO-' . date('Ymd') . '-001',
                'customer_first_name' => 'Jean',
                'customer_last_name' => 'Demo',
                'customer_email' => 'demo.client@boxibox.be',
                'customer_phone' => '+32 478 12 34 56',
                'customer_address' => '123 Rue de la Démonstration',
                'customer_city' => 'Bruxelles',
                'customer_postal_code' => '1000',
                'customer_country' => 'BE',
                'start_date' => now()->addDays(7),
                'planned_duration_months' => 6,
                'monthly_price' => 129.00,
                'deposit_amount' => 258.00,
                'status' => 'pending',
                'source' => 'website',
                'special_requests' => 'Je souhaite un box au rez-de-chaussée si possible.',
                'needs_24h_access' => true,
                'needs_climate_control' => false,
                'storage_contents' => 'Meubles, cartons de livres, vélo',
                'estimated_value' => '1000_5000',
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ]
        );

        $this->command->info("Demo Booking created: {$booking->booking_number}");

        // 7. Create Support Tickets with Discussions
        $tickets = [
            [
                'subject' => 'Question sur les horaires d\'accès',
                'description' => 'Bonjour, je voudrais savoir si je peux accéder à mon box le dimanche matin ?',
                'category' => 'question',
                'priority' => 'medium',
                'status' => 'resolved',
                'messages' => [
                    ['sender' => 'customer', 'message' => 'Bonjour, je voudrais savoir si je peux accéder à mon box le dimanche matin ?'],
                    ['sender' => 'staff', 'message' => 'Bonjour M. Demo, oui bien sûr ! Nos locaux sont accessibles 24h/24, 7j/7 avec votre code d\'accès personnel.'],
                    ['sender' => 'customer', 'message' => 'Parfait, merci beaucoup pour cette réponse rapide !'],
                ]
            ],
            [
                'subject' => 'Demande de changement de box',
                'description' => 'Je souhaiterais changer pour un box plus grand.',
                'category' => 'other',
                'priority' => 'medium',
                'status' => 'in_progress',
                'messages' => [
                    ['sender' => 'customer', 'message' => 'Bonjour, mon box actuel devient trop petit. Est-il possible de passer à une taille supérieure ?'],
                    ['sender' => 'staff', 'message' => 'Bonjour M. Demo, oui c\'est tout à fait possible ! Nous avons actuellement des box de 10m² et 15m² disponibles. Souhaitez-vous planifier une visite ?'],
                    ['sender' => 'customer', 'message' => 'Oui, je serais intéressé par le 10m². Quand pourrait-on se voir ?'],
                ]
            ],
            [
                'subject' => 'Problème avec la serrure',
                'description' => 'Ma clé ne fonctionne plus correctement.',
                'category' => 'technical',
                'priority' => 'high',
                'status' => 'open',
                'messages' => [
                    ['sender' => 'customer', 'message' => 'Bonjour, depuis hier ma clé a du mal à tourner dans la serrure. Pouvez-vous envoyer quelqu\'un ?'],
                ]
            ],
        ];

        foreach ($tickets as $ticketData) {
            $ticket = SupportTicket::updateOrCreate(
                ['tenant_id' => $tenantId, 'customer_id' => $demoCustomer->id, 'subject' => $ticketData['subject']],
                [
                    'tenant_id' => $tenantId,
                    'customer_id' => $demoCustomer->id,
                    'created_by' => $demoCustomer->id,
                    'ticket_number' => 'TKT-' . strtoupper(substr(md5($ticketData['subject']), 0, 8)),
                    'type' => 'tenant_customer',
                    'subject' => $ticketData['subject'],
                    'description' => $ticketData['description'],
                    'category' => $ticketData['category'],
                    'priority' => $ticketData['priority'],
                    'status' => $ticketData['status'],
                    'created_at' => now()->subDays(rand(1, 30)),
                ]
            );

            // Add messages to ticket
            foreach ($ticketData['messages'] as $msgIndex => $msg) {
                SupportMessage::updateOrCreate(
                    ['ticket_id' => $ticket->id, 'message' => $msg['message']],
                    [
                        'ticket_id' => $ticket->id,
                        'sender_type' => $msg['sender'] === 'customer' ? 'customer' : 'user',
                        'customer_id' => $msg['sender'] === 'customer' ? $demoCustomer->id : null,
                        'user_id' => $msg['sender'] === 'staff' ? 1 : null,
                        'message' => $msg['message'],
                        'is_internal' => false,
                        'created_at' => $ticket->created_at->addHours($msgIndex * 2),
                    ]
                );
            }
        }

        $this->command->info("Demo Support Tickets created");

        // 8. Create Notifications
        $notifications = [
            [
                'type' => 'invoice',
                'title' => 'Nouvelle facture disponible',
                'message' => 'Votre facture du mois de ' . now()->format('F Y') . ' est disponible.',
                'is_read' => false,
            ],
            [
                'type' => 'payment',
                'title' => 'Paiement reçu',
                'message' => 'Nous avons bien reçu votre paiement de 107,69€. Merci !',
                'is_read' => true,
            ],
            [
                'type' => 'reminder',
                'title' => 'Rappel: Visite programmée',
                'message' => 'N\'oubliez pas votre rendez-vous demain à 14h pour visiter le nouveau box.',
                'is_read' => false,
            ],
            [
                'type' => 'promotion',
                'title' => 'Offre spéciale fidélité',
                'message' => 'En tant que client fidèle, bénéficiez de -10% sur votre prochain mois !',
                'is_read' => false,
            ],
            [
                'type' => 'system',
                'title' => 'Mise à jour des conditions',
                'message' => 'Nos conditions générales ont été mises à jour. Consultez-les dans votre espace client.',
                'is_read' => true,
            ],
        ];

        foreach ($notifications as $notifData) {
            Notification::updateOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $demoUser->id, 'title' => $notifData['title']],
                [
                    'tenant_id' => $tenantId,
                    'user_id' => $demoUser->id,
                    'type' => $notifData['type'],
                    'title' => $notifData['title'],
                    'message' => $notifData['message'],
                    'channels' => json_encode(['in_app', 'email']),
                    'status' => 'sent',
                    'email_sent' => true,
                    'email_sent_at' => now()->subDays(rand(0, 7)),
                    'is_read' => $notifData['is_read'],
                    'read_at' => $notifData['is_read'] ? now()->subDays(rand(0, 3)) : null,
                    'priority' => 'medium',
                ]
            );
        }

        $this->command->info("Demo Notifications created");

        // Summary
        $this->command->newLine();
        $this->command->info("========================================");
        $this->command->info("DEMO CLIENT ACCOUNT CREATED SUCCESSFULLY");
        $this->command->info("========================================");
        $this->command->info("Email: demo.client@boxibox.be");
        $this->command->info("Password: Demo2024!");
        $this->command->info("========================================");
        $this->command->info("Data created:");
        $this->command->info("- 1 Customer profile");
        $this->command->info("- 1 Active contract");
        $this->command->info("- 4 Invoices (3 paid, 1 pending)");
        $this->command->info("- 3 Payments");
        $this->command->info("- 1 Pending booking");
        $this->command->info("- 3 Support tickets with discussions");
        $this->command->info("- 5 Notifications");
        $this->command->info("========================================");
    }
}
