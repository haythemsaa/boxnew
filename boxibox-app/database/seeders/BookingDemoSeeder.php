<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\BookingSettings;
use App\Models\BookingWidget;
use App\Models\BookingApiKey;
use App\Models\BookingPromoCode;
use App\Models\BookingPayment;
use App\Models\BookingStatusHistory;
use App\Models\Tenant;
use App\Models\Site;
use App\Models\Box;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating Booking Demo Data...');

        // Get first tenant
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found. Please run main seeders first.');
            return;
        }

        $sites = Site::where('tenant_id', $tenant->id)->get();
        if ($sites->isEmpty()) {
            $this->command->error('No sites found. Please run main seeders first.');
            return;
        }

        // 1. Create Booking Settings
        $this->command->info('Creating booking settings...');
        $settings = BookingSettings::updateOrCreate(
            ['tenant_id' => $tenant->id, 'site_id' => null],
            [
                'is_enabled' => true,
                'booking_url_slug' => 'demo-storage',
                'company_name' => 'BoxiBox Storage',
                'company_logo' => null,
                'primary_color' => '#0D9488',
                'secondary_color' => '#0F766E',
                'welcome_message' => 'Bienvenue ! Réservez votre espace de stockage en quelques clics. Des box de toutes tailles disponibles 24h/24.',
                'terms_conditions' => "CONDITIONS GÉNÉRALES DE LOCATION\n\n1. OBJET\nLes présentes conditions générales régissent la location d'espaces de stockage.\n\n2. DURÉE\nLa location est conclue pour une durée minimale d'un mois, renouvelable tacitement.\n\n3. PRIX\nLe prix est celui indiqué lors de la réservation. Il est payable mensuellement d'avance.\n\n4. DÉPÔT DE GARANTIE\nUn dépôt de garantie équivalent à un mois de loyer est demandé.\n\n5. ACCÈS\nL'accès au box est possible 24h/24 et 7j/7 avec le code personnel.\n\n6. ASSURANCE\nLe locataire doit assurer ses biens entreposés.\n\n7. RÉSILIATION\nLa résiliation se fait avec un préavis d'un mois.",
                'require_deposit' => true,
                'deposit_amount' => 0,
                'deposit_percentage' => 100, // 1 month deposit
                'min_rental_days' => 30,
                'max_advance_booking_days' => 90,
                'auto_confirm' => false,
                'require_id_verification' => true,
                'allow_promo_codes' => true,
                'available_payment_methods' => ['card', 'bank_transfer', 'sepa'],
                'business_hours' => [
                    'monday' => ['09:00', '18:00'],
                    'tuesday' => ['09:00', '18:00'],
                    'wednesday' => ['09:00', '18:00'],
                    'thursday' => ['09:00', '18:00'],
                    'friday' => ['09:00', '18:00'],
                    'saturday' => ['10:00', '16:00'],
                    'sunday' => null,
                ],
                'contact_email' => 'contact@boxibox-demo.fr',
                'contact_phone' => '+33 1 23 45 67 89',
            ]
        );

        // 2. Create Promo Codes
        $this->command->info('Creating promo codes...');
        $promoCodes = [
            [
                'code' => 'BIENVENUE20',
                'name' => 'Offre Bienvenue',
                'description' => '20% de réduction sur le premier mois pour les nouveaux clients',
                'discount_type' => 'percentage',
                'discount_value' => 20,
                'max_uses' => 100,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(6),
                'first_time_only' => true,
            ],
            [
                'code' => 'NOEL2025',
                'name' => 'Promo Noël',
                'description' => 'Offre spéciale fêtes de fin d\'année',
                'discount_type' => 'percentage',
                'discount_value' => 15,
                'max_uses' => 50,
                'valid_from' => Carbon::create(2025, 12, 1),
                'valid_until' => Carbon::create(2025, 12, 31),
                'first_time_only' => false,
            ],
            [
                'code' => 'MOIS-GRATUIT',
                'name' => '1 Mois Gratuit',
                'description' => '1 mois gratuit pour une location de 6 mois minimum',
                'discount_type' => 'free_months',
                'discount_value' => 1,
                'min_rental_months' => 6,
                'max_uses' => 20,
                'valid_from' => now(),
                'valid_until' => now()->addMonths(3),
                'first_time_only' => false,
            ],
            [
                'code' => 'PARRAIN50',
                'name' => 'Parrainage',
                'description' => '50€ de réduction avec un parrainage',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'max_uses' => null,
                'valid_from' => now(),
                'valid_until' => null,
                'first_time_only' => true,
            ],
            [
                'code' => 'FLASH25',
                'name' => 'Vente Flash',
                'description' => '25% de réduction - offre limitée 48h',
                'discount_type' => 'percentage',
                'discount_value' => 25,
                'max_uses' => 10,
                'uses_count' => 7,
                'valid_from' => now()->subDay(),
                'valid_until' => now()->addDay(),
                'first_time_only' => false,
            ],
        ];

        foreach ($promoCodes as $promoData) {
            BookingPromoCode::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $promoData['code']],
                array_merge($promoData, ['tenant_id' => $tenant->id, 'is_active' => true])
            );
        }

        // 3. Create Widgets
        $this->command->info('Creating booking widgets...');
        $widgets = [
            [
                'name' => 'Widget Principal',
                'widget_type' => 'full',
                'allowed_domains' => ['boxibox-demo.fr', 'www.boxibox-demo.fr', 'localhost'],
                'views_count' => 1245,
                'bookings_count' => 67,
            ],
            [
                'name' => 'Widget Compact Blog',
                'widget_type' => 'compact',
                'allowed_domains' => ['blog.boxibox-demo.fr'],
                'views_count' => 523,
                'bookings_count' => 23,
            ],
            [
                'name' => 'Bouton Landing Page',
                'widget_type' => 'button',
                'allowed_domains' => null,
                'views_count' => 2156,
                'bookings_count' => 89,
            ],
        ];

        foreach ($widgets as $widgetData) {
            BookingWidget::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $widgetData['name']],
                array_merge($widgetData, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ])
            );
        }

        // 4. Create API Keys
        $this->command->info('Creating API keys...');
        $apiKeys = [
            [
                'name' => 'Intégration CRM',
                'permissions' => ['bookings.read', 'bookings.create', 'sites.read', 'boxes.read'],
                'requests_count' => 3456,
                'last_used_at' => now()->subHours(2),
            ],
            [
                'name' => 'Application Mobile',
                'permissions' => ['*'],
                'requests_count' => 12089,
                'last_used_at' => now()->subMinutes(15),
            ],
            [
                'name' => 'Partenaire Immobilier',
                'permissions' => ['sites.read', 'boxes.read', 'bookings.create'],
                'ip_whitelist' => ['192.168.1.100', '10.0.0.50'],
                'requests_count' => 234,
                'last_used_at' => now()->subDays(3),
            ],
        ];

        foreach ($apiKeys as $keyData) {
            BookingApiKey::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $keyData['name']],
                array_merge($keyData, [
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ])
            );
        }

        // 5. Create Demo Bookings
        $this->command->info('Creating demo bookings...');

        $boxes = Box::whereIn('site_id', $sites->pluck('id'))->get();
        if ($boxes->isEmpty()) {
            $this->command->warn('No boxes found. Skipping booking creation.');
            return;
        }

        $bookingData = [
            // Pending bookings
            [
                'status' => 'pending',
                'customer_first_name' => 'Marie',
                'customer_last_name' => 'Dupont',
                'customer_email' => 'marie.dupont@email.com',
                'customer_phone' => '+33 6 12 34 56 78',
                'customer_address' => '15 Rue de la Paix',
                'customer_city' => 'Paris',
                'customer_postal_code' => '75001',
                'start_date' => now()->addDays(5),
                'source' => 'website',
                'created_at' => now()->subHours(3),
            ],
            [
                'status' => 'pending',
                'customer_first_name' => 'Pierre',
                'customer_last_name' => 'Martin',
                'customer_email' => 'p.martin@gmail.com',
                'customer_phone' => '+33 6 98 76 54 32',
                'customer_company' => 'Martin & Fils SARL',
                'customer_address' => '28 Avenue des Champs',
                'customer_city' => 'Lyon',
                'customer_postal_code' => '69001',
                'start_date' => now()->addDays(7),
                'source' => 'widget',
                'promo_code' => 'BIENVENUE20',
                'created_at' => now()->subHours(1),
            ],

            // Confirmed bookings
            [
                'status' => 'confirmed',
                'customer_first_name' => 'Sophie',
                'customer_last_name' => 'Bernard',
                'customer_email' => 'sophie.bernard@outlook.fr',
                'customer_phone' => '+33 6 55 44 33 22',
                'customer_address' => '42 Boulevard Haussmann',
                'customer_city' => 'Paris',
                'customer_postal_code' => '75008',
                'start_date' => now()->addDays(3),
                'source' => 'website',
                'created_at' => now()->subDays(2),
            ],
            [
                'status' => 'confirmed',
                'customer_first_name' => 'Jean',
                'customer_last_name' => 'Petit',
                'customer_email' => 'jean.petit@yahoo.fr',
                'customer_phone' => '+33 7 11 22 33 44',
                'customer_address' => '8 Rue Victor Hugo',
                'customer_city' => 'Marseille',
                'customer_postal_code' => '13001',
                'start_date' => now()->addDays(10),
                'source' => 'api',
                'created_at' => now()->subDays(1),
            ],

            // Deposit paid
            [
                'status' => 'deposit_paid',
                'customer_first_name' => 'Claire',
                'customer_last_name' => 'Moreau',
                'customer_email' => 'claire.moreau@email.fr',
                'customer_phone' => '+33 6 77 88 99 00',
                'customer_company' => 'Moreau Design',
                'customer_address' => '55 Rue du Commerce',
                'customer_city' => 'Bordeaux',
                'customer_postal_code' => '33000',
                'start_date' => now()->addDays(2),
                'source' => 'website',
                'promo_code' => 'NOEL2025',
                'created_at' => now()->subDays(5),
            ],

            // Active bookings (converted to contracts)
            [
                'status' => 'active',
                'customer_first_name' => 'Luc',
                'customer_last_name' => 'Girard',
                'customer_email' => 'luc.girard@entreprise.com',
                'customer_phone' => '+33 6 11 22 33 44',
                'customer_company' => 'Girard Consulting',
                'customer_address' => '100 Avenue de la République',
                'customer_city' => 'Nantes',
                'customer_postal_code' => '44000',
                'start_date' => now()->subDays(15),
                'source' => 'website',
                'created_at' => now()->subDays(20),
            ],
            [
                'status' => 'active',
                'customer_first_name' => 'Emma',
                'customer_last_name' => 'Leroy',
                'customer_email' => 'emma.leroy@free.fr',
                'customer_phone' => '+33 6 99 88 77 66',
                'customer_address' => '12 Place de la Mairie',
                'customer_city' => 'Toulouse',
                'customer_postal_code' => '31000',
                'start_date' => now()->subDays(30),
                'source' => 'widget',
                'created_at' => now()->subDays(35),
            ],

            // Completed
            [
                'status' => 'completed',
                'customer_first_name' => 'Thomas',
                'customer_last_name' => 'Roux',
                'customer_email' => 'thomas.roux@mail.com',
                'customer_phone' => '+33 6 44 55 66 77',
                'customer_address' => '25 Rue des Lilas',
                'customer_city' => 'Nice',
                'customer_postal_code' => '06000',
                'start_date' => now()->subMonths(3),
                'end_date' => now()->subDays(10),
                'source' => 'website',
                'created_at' => now()->subMonths(3)->subDays(5),
            ],

            // Cancelled
            [
                'status' => 'cancelled',
                'customer_first_name' => 'Julie',
                'customer_last_name' => 'Simon',
                'customer_email' => 'julie.simon@gmail.com',
                'customer_phone' => '+33 6 33 22 11 00',
                'customer_address' => '5 Rue de la Gare',
                'customer_city' => 'Lille',
                'customer_postal_code' => '59000',
                'start_date' => now()->addDays(14),
                'source' => 'website',
                'notes' => 'Annulation demandée par le client - déménagement reporté',
                'created_at' => now()->subDays(3),
            ],

            // Rejected
            [
                'status' => 'rejected',
                'customer_first_name' => 'Marc',
                'customer_last_name' => 'Blanc',
                'customer_email' => 'marc.blanc@test.com',
                'customer_phone' => '+33 6 00 00 00 00',
                'customer_address' => '1 Rue Test',
                'customer_city' => 'Paris',
                'customer_postal_code' => '75000',
                'start_date' => now()->addDays(2),
                'source' => 'api',
                'internal_notes' => 'Informations incomplètes - email de test suspect',
                'created_at' => now()->subDays(1),
            ],
        ];

        foreach ($bookingData as $index => $data) {
            $site = $sites->random();
            $box = Box::where('site_id', $site->id)->inRandomOrder()->first();

            if (!$box) continue;

            $discountAmount = 0;
            if (!empty($data['promo_code'])) {
                $promo = BookingPromoCode::where('code', $data['promo_code'])->first();
                if ($promo) {
                    $discountAmount = $promo->calculateDiscount($box->current_price);
                }
            }

            $depositAmount = $box->current_price; // 1 month deposit

            $booking = Booking::create([
                'tenant_id' => $tenant->id,
                'site_id' => $site->id,
                'box_id' => $box->id,
                'customer_first_name' => $data['customer_first_name'],
                'customer_last_name' => $data['customer_last_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'customer_city' => $data['customer_city'] ?? null,
                'customer_postal_code' => $data['customer_postal_code'] ?? null,
                'customer_country' => 'FR',
                'customer_company' => $data['customer_company'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'rental_type' => 'month_to_month',
                'monthly_price' => $box->current_price - $discountAmount,
                'deposit_amount' => $depositAmount,
                'status' => $data['status'],
                'source' => $data['source'],
                'source_url' => $data['source'] === 'website' ? 'https://boxibox-demo.fr/book/demo-storage' : null,
                'utm_source' => $data['source'] === 'website' ? 'google' : null,
                'utm_medium' => $data['source'] === 'website' ? 'cpc' : null,
                'promo_code' => $data['promo_code'] ?? null,
                'discount_amount' => $discountAmount,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'terms_accepted' => true,
                'terms_accepted_at' => $data['created_at'],
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);

            // Create status history
            $this->createStatusHistory($booking, $data['status'], $data['created_at']);

            // Create payment for deposit_paid and active bookings
            if (in_array($data['status'], ['deposit_paid', 'active', 'completed'])) {
                BookingPayment::create([
                    'booking_id' => $booking->id,
                    'type' => 'deposit',
                    'amount' => $depositAmount,
                    'payment_method' => 'card',
                    'transaction_id' => 'pi_' . Str::random(24),
                    'status' => 'completed',
                    'created_at' => $data['created_at']->addDays(1),
                ]);
            }
        }

        // Update stats
        $this->command->info('Updating promo code usage counts...');
        BookingPromoCode::where('code', 'BIENVENUE20')->increment('uses_count', 12);
        BookingPromoCode::where('code', 'NOEL2025')->increment('uses_count', 5);

        $this->command->info('Booking demo data created successfully!');
        $this->command->newLine();
        $this->command->info('Summary:');
        $this->command->line('- Booking Settings: 1');
        $this->command->line('- Promo Codes: ' . count($promoCodes));
        $this->command->line('- Widgets: ' . count($widgets));
        $this->command->line('- API Keys: ' . count($apiKeys));
        $this->command->line('- Bookings: ' . count($bookingData));
        $this->command->newLine();
        $this->command->info('Public booking URL: http://127.0.0.1:8000/book/demo-storage');
    }

    private function createStatusHistory(Booking $booking, string $finalStatus, Carbon $createdAt): void
    {
        $statuses = ['pending'];

        if (in_array($finalStatus, ['confirmed', 'deposit_paid', 'active', 'completed'])) {
            $statuses[] = 'confirmed';
        }
        if (in_array($finalStatus, ['deposit_paid', 'active', 'completed'])) {
            $statuses[] = 'deposit_paid';
        }
        if (in_array($finalStatus, ['active', 'completed'])) {
            $statuses[] = 'active';
        }
        if ($finalStatus === 'completed') {
            $statuses[] = 'completed';
        }
        if ($finalStatus === 'cancelled') {
            $statuses[] = 'cancelled';
        }
        if ($finalStatus === 'rejected') {
            $statuses[] = 'rejected';
        }

        $previousStatus = null;
        $date = $createdAt->copy();

        foreach ($statuses as $status) {
            BookingStatusHistory::create([
                'booking_id' => $booking->id,
                'from_status' => $previousStatus,
                'to_status' => $status,
                'notes' => $this->getStatusNote($status),
                'user_id' => $status !== 'pending' ? 1 : null,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
            $previousStatus = $status;
            $date = $date->addHours(rand(2, 24));
        }
    }

    private function getStatusNote(string $status): string
    {
        return match ($status) {
            'pending' => 'Réservation créée',
            'confirmed' => 'Réservation confirmée par l\'équipe',
            'deposit_paid' => 'Acompte reçu',
            'active' => 'Contrat démarré',
            'completed' => 'Contrat terminé normalement',
            'cancelled' => 'Annulé à la demande du client',
            'rejected' => 'Réservation refusée - informations invalides',
            default => '',
        };
    }
}
