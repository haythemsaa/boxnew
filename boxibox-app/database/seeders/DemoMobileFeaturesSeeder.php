<?php

namespace Database\Seeders;

use App\Models\BoxAccessLog;
use App\Models\BoxAccessShare;
use App\Models\BoxInventoryItem;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerReferral;
use App\Models\CustomerReminder;
use App\Models\ReferralSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoMobileFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;

        // Get demo customer
        $customer = Customer::where('email', 'demo.client@boxibox.be')->first();

        if (!$customer) {
            $this->command->error('Demo customer not found. Run DemoCustomerSeeder first.');
            return;
        }

        // Get active contract
        $contract = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if (!$contract) {
            $this->command->error('No active contract found for demo customer.');
            return;
        }

        // Ensure customer has referral code
        if (!$customer->referral_code) {
            $customer->update(['referral_code' => 'DEMO' . strtoupper(Str::random(4))]);
        }

        $this->command->info("Creating demo data for mobile features...");

        // 1. Create Referral Settings
        ReferralSetting::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'is_active' => true,
                'program_name' => 'Programme Parrainage BoxiBox',
                'referrer_reward_amount' => 25.00,
                'referred_reward_amount' => 25.00,
                'reward_type' => 'credit',
                'min_contract_months' => 1,
                'reward_delay_days' => 30,
                'referral_expiry_days' => 90,
                'email_template' => "Bonjour {guest_name},\n\n{referrer_name} vous invite à rejoindre BoxiBox ! Utilisez le code {referral_code} pour bénéficier de {reward_amount}€ de réduction sur votre premier mois.\n\nÀ bientôt !",
                'sms_template' => "{referrer_name} vous offre {reward_amount}€ chez BoxiBox ! Code: {referral_code}",
            ]
        );

        $this->command->info("- Referral settings created");

        // 2. Create Access Logs (history)
        $accessMethods = ['code', 'qr_code', 'nfc', 'smart_lock'];
        $accessLogs = [];

        for ($i = 0; $i < 15; $i++) {
            $accessLogs[] = [
                'tenant_id' => $tenantId,
                'box_id' => $contract->box_id,
                'contract_id' => $contract->id,
                'customer_id' => $customer->id,
                'access_type' => $i % 2 === 0 ? 'entry' : 'exit',
                'method' => $accessMethods[array_rand($accessMethods)],
                'access_code_used' => $contract->access_code,
                'ip_address' => '192.168.1.' . rand(1, 255),
                'status' => 'success',
                'accessed_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BoxAccessLog::insert($accessLogs);
        $this->command->info("- 15 access logs created");

        // 3. Create Access Shares
        $shares = [
            [
                'guest_name' => 'Marie Dupont',
                'guest_email' => 'marie.dupont@example.com',
                'guest_phone' => '+32 470 11 22 33',
                'guest_note' => 'Ma mère - peut récupérer des affaires pour moi',
                'valid_from' => now(),
                'valid_until' => now()->addDays(7),
                'max_uses' => 3,
                'status' => 'active',
            ],
            [
                'guest_name' => 'Déménageur Express',
                'guest_email' => 'contact@demenageur.be',
                'guest_phone' => '+32 2 999 88 77',
                'guest_note' => 'Société de déménagement - livraison meubles',
                'valid_from' => now()->addDays(2),
                'valid_until' => now()->addDays(2)->addHours(4),
                'max_uses' => 1,
                'allowed_hours' => ['start' => '09:00', 'end' => '17:00'],
                'status' => 'active',
            ],
            [
                'guest_name' => 'Jean-Pierre Demo',
                'guest_email' => 'jp@example.com',
                'guest_phone' => null,
                'guest_note' => 'Accès expiré - test',
                'valid_from' => now()->subDays(10),
                'valid_until' => now()->subDays(3),
                'max_uses' => null,
                'status' => 'expired',
            ],
        ];

        foreach ($shares as $shareData) {
            BoxAccessShare::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'box_id' => $contract->box_id,
                'contract_id' => $contract->id,
                'customer_id' => $customer->id,
                'share_code' => strtoupper(Str::random(8)),
                ...$shareData,
            ]);
        }

        $this->command->info("- 3 access shares created");

        // 4. Create Inventory Items
        $inventoryItems = [
            [
                'name' => 'Canapé 3 places',
                'description' => 'Canapé gris en tissu, bon état',
                'category' => 'furniture',
                'quantity' => 1,
                'estimated_value' => 450.00,
                'condition' => 'good',
                'brand' => 'IKEA',
                'model' => 'KIVIK',
                'location_in_box' => 'Centre du box',
                'length' => 228,
                'width' => 95,
                'height' => 83,
            ],
            [
                'name' => 'Cartons de livres',
                'description' => 'Collection de romans et livres techniques',
                'category' => 'books',
                'quantity' => 8,
                'estimated_value' => 200.00,
                'location_in_box' => 'Étagère gauche',
            ],
            [
                'name' => 'Vélo VTT',
                'description' => 'VTT adulte 26 pouces',
                'category' => 'sports',
                'quantity' => 1,
                'estimated_value' => 350.00,
                'condition' => 'excellent',
                'brand' => 'Decathlon',
                'model' => 'Rockrider 540',
                'location_in_box' => 'Mur du fond, accroché',
            ],
            [
                'name' => 'Machine à laver',
                'description' => 'Lave-linge 8kg',
                'category' => 'appliances',
                'quantity' => 1,
                'estimated_value' => 300.00,
                'condition' => 'good',
                'brand' => 'Samsung',
                'model' => 'WW80J5555MW',
                'serial_number' => 'SN123456789',
                'location_in_box' => 'Coin droit',
            ],
            [
                'name' => 'Décorations de Noël',
                'description' => 'Sapin artificiel, guirlandes, boules',
                'category' => 'seasonal',
                'quantity' => 3,
                'estimated_value' => 150.00,
                'location_in_box' => 'Cartons empilés au fond',
            ],
            [
                'name' => 'Outils de jardinage',
                'description' => 'Tondeuse, taille-haie, outils divers',
                'category' => 'tools',
                'quantity' => 1,
                'estimated_value' => 400.00,
                'location_in_box' => 'Près de l\'entrée',
            ],
            [
                'name' => 'TV Samsung 55"',
                'description' => 'Smart TV 4K, carton d\'origine',
                'category' => 'electronics',
                'quantity' => 1,
                'estimated_value' => 600.00,
                'condition' => 'excellent',
                'brand' => 'Samsung',
                'model' => 'QE55Q60R',
                'is_insured' => true,
                'location_in_box' => 'Contre le mur, protégé',
            ],
        ];

        foreach ($inventoryItems as $itemData) {
            BoxInventoryItem::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'box_id' => $contract->box_id,
                'contract_id' => $contract->id,
                'customer_id' => $customer->id,
                'status' => 'stored',
                'stored_at' => now()->subDays(rand(1, 60)),
                ...$itemData,
            ]);
        }

        $this->command->info("- 7 inventory items created");

        // 5. Create Reminders
        $reminders = [
            [
                'type' => 'contract_expiry',
                'title' => 'Votre contrat arrive à échéance',
                'message' => 'Votre contrat expire dans 30 jours. Pensez à le renouveler pour continuer à profiter de votre espace de stockage.',
                'priority' => 'high',
                'remind_at' => now()->addDays(30),
                'channels' => ['in_app', 'email', 'push'],
                'action_url' => '/mobile/contracts/' . $contract->id,
                'action_label' => 'Renouveler',
            ],
            [
                'type' => 'custom',
                'title' => 'Rappel: Récupérer les décorations',
                'message' => 'N\'oubliez pas de récupérer vos décorations de Noël !',
                'priority' => 'low',
                'remind_at' => now()->addDays(5),
                'channels' => ['in_app'],
            ],
            [
                'type' => 'visit_scheduled',
                'title' => 'Visite programmée demain',
                'message' => 'Vous avez prévu de visiter votre box demain. N\'oubliez pas votre code d\'accès: ' . $contract->access_code,
                'priority' => 'medium',
                'remind_at' => now()->addDay(),
                'channels' => ['in_app', 'push'],
                'action_url' => '/mobile/access',
                'action_label' => 'Voir mon accès',
            ],
        ];

        foreach ($reminders as $reminderData) {
            CustomerReminder::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'remindable_type' => Contract::class,
                'remindable_id' => $contract->id,
                'status' => 'pending',
                ...$reminderData,
            ]);
        }

        $this->command->info("- 3 reminders created");

        // 6. Create Referrals
        $referrals = [
            [
                'invited_name' => 'Sophie Martin',
                'invited_email' => 'sophie.martin@example.com',
                'status' => 'rewarded',
                'referrer_reward' => 25.00,
                'referred_reward' => 25.00,
                'referrer_reward_paid' => true,
                'referred_reward_applied' => true,
                'registered_at' => now()->subDays(45),
                'converted_at' => now()->subDays(40),
                'source' => 'email',
            ],
            [
                'invited_name' => 'Pierre Leblanc',
                'invited_email' => 'pierre.lb@example.com',
                'status' => 'converted',
                'referrer_reward' => 25.00,
                'referred_reward' => 25.00,
                'registered_at' => now()->subDays(15),
                'converted_at' => now()->subDays(10),
                'source' => 'whatsapp',
            ],
            [
                'invited_name' => 'Claire Dubois',
                'invited_email' => 'claire.d@example.com',
                'invited_phone' => '+32 476 55 44 33',
                'status' => 'pending',
                'referrer_reward' => 25.00,
                'referred_reward' => 25.00,
                'expires_at' => now()->addDays(60),
                'source' => 'sms',
            ],
        ];

        foreach ($referrals as $referralData) {
            CustomerReferral::create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'referrer_customer_id' => $customer->id,
                'referral_code' => $customer->referral_code,
                'reward_type' => 'credit',
                ...$referralData,
            ]);
        }

        // Update customer credits
        $customer->update(['referral_credits' => 25.00]); // From the rewarded referral

        $this->command->info("- 3 referrals created");

        // Summary
        $this->command->newLine();
        $this->command->info("========================================");
        $this->command->info("DEMO MOBILE FEATURES DATA CREATED");
        $this->command->info("========================================");
        $this->command->info("Customer: {$customer->email}");
        $this->command->info("Referral Code: {$customer->referral_code}");
        $this->command->info("========================================");
        $this->command->info("Data created:");
        $this->command->info("- 1 Referral settings");
        $this->command->info("- 15 Access logs (history)");
        $this->command->info("- 3 Access shares (1 active, 1 scheduled, 1 expired)");
        $this->command->info("- 7 Inventory items");
        $this->command->info("- 3 Reminders");
        $this->command->info("- 3 Referrals (1 rewarded, 1 converted, 1 pending)");
        $this->command->info("- 25€ referral credits");
        $this->command->info("========================================");
    }
}
