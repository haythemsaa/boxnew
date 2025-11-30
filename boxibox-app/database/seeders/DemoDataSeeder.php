<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Site;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Signature;
use App\Models\SepaMandate;
use App\Models\PaymentReminder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating demo data...');

        // Get or create demo tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo-storage'],
            [
                'name' => 'Demo Storage Company',
                'email' => 'admin@demo-storage.com',
                'domain' => 'demo-storage.com',
                'phone' => '+1234567890',
                'address' => '123 Storage Street, Demo City',
                'is_active' => true,
            ]
        );

        // Clean existing data for this tenant to avoid duplicates (force delete to remove soft-deleted records)
        Payment::where('tenant_id', $tenant->id)->forceDelete();
        Invoice::where('tenant_id', $tenant->id)->forceDelete();
        Contract::where('tenant_id', $tenant->id)->forceDelete();
        Customer::where('tenant_id', $tenant->id)->forceDelete();
        Box::where('tenant_id', $tenant->id)->forceDelete();
        Site::where('tenant_id', $tenant->id)->forceDelete();

        $this->command->info('✓ Tenant created and existing data cleaned');

        // Create admin user if not exists
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo-storage.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create permissions
        $permissions = [
            'view_boxes',
            'create_boxes',
            'edit_boxes',
            'delete_boxes',
            'view_sites',
            'create_sites',
            'edit_sites',
            'delete_sites',
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
            'view_contracts',
            'create_contracts',
            'edit_contracts',
            'delete_contracts',
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create admin role and assign all permissions
        $adminRole = Role::firstOrCreate(['name' => 'tenant_admin']);
        $adminRole->syncPermissions($permissions);

        // Assign role to admin user
        if (!$adminUser->hasRole('tenant_admin')) {
            $adminUser->assignRole('tenant_admin');
        }

        $this->command->info('✓ Admin user created with permissions');

        // Create sites (code is globally unique, so use updateOrCreate)
        $mainSite = Site::updateOrCreate(
            ['code' => 'DEMO-MAIN'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Site Principal',
                'address' => '123 Main Storage Street',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
                'phone' => '+33123456789',
                'email' => 'main@demo-storage.com',
                'is_active' => true,
            ]
        );

        $northSite = Site::updateOrCreate(
            ['code' => 'DEMO-NORTH'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Site Nord',
                'address' => '456 North Avenue',
                'city' => 'Lille',
                'postal_code' => '59000',
                'country' => 'France',
                'phone' => '+33987654321',
                'email' => 'north@demo-storage.com',
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Sites created');

        // Create boxes for main site with plan1.png layout
        $this->createMainSiteBoxes($tenant, $mainSite);

        // Create boxes for north site (simpler grid)
        $this->createNorthSiteBoxes($tenant, $northSite);

        $this->command->info('✓ Boxes created');

        // Create customers
        $this->createCustomers($tenant);

        $this->command->info('✓ Customers created');

        // Create contracts for occupied boxes
        $this->createContracts($tenant);

        $this->command->info('✓ Contracts created');

        // Create invoices and payments
        $this->createInvoicesAndPayments($tenant);

        $this->command->info('✓ Invoices and payments created');

        // Create prospects
        $this->createProspects($tenant);

        $this->command->info('✓ Prospects created');

        // Create signatures
        $this->createSignatures($tenant);

        $this->command->info('✓ Signatures created');

        // Create SEPA mandates
        $this->createSepaMandates($tenant);

        $this->command->info('✓ SEPA mandates created');

        // Create payment reminders
        $this->createPaymentReminders($tenant);

        $this->command->info('✓ Payment reminders created');

        $this->command->info('Demo data seeding completed!');
    }

    private function createMainSiteBoxes($tenant, $site)
    {
        // Based on plan1.png layout
        // This creates boxes with specific positions to match the plan
        $boxes = $this->getMainSiteBoxLayout();

        foreach ($boxes as $boxData) {
            Box::create([
                'tenant_id' => $tenant->id,
                'site_id' => $site->id,
                'number' => $boxData['number'],
                'name' => $boxData['name'] ?? null,
                'length' => $boxData['length'],
                'width' => $boxData['width'],
                'height' => $boxData['height'],
                'status' => $boxData['status'],
                'base_price' => $boxData['price'],
                'current_price' => $boxData['price'],
                'position' => $boxData['position'],
                'climate_controlled' => rand(0, 1) == 1,
                'has_electricity' => rand(0, 1) == 1,
                'has_alarm' => true,
            ]);
        }
    }

    private function createNorthSiteBoxes($tenant, $site)
    {
        // Create a simpler grid layout for the north site
        $boxNumber = 1;
        for ($row = 0; $row < 10; $row++) {
            for ($col = 0; $col < 12; $col++) {
                $volume = [9, 12, 18, 25][rand(0, 3)];
                $status = rand(1, 100) <= 75 ? 'occupied' : 'available';

                Box::create([
                    'tenant_id' => $tenant->id,
                    'site_id' => $site->id,
                    'number' => 'N' . str_pad($boxNumber, 3, '0', STR_PAD_LEFT),
                    'length' => 3,
                    'width' => 3,
                    'height' => $volume / 9,
                    'status' => $status,
                    'base_price' => $volume * 8,
                    'current_price' => $volume * 8,
                    'position' => [
                        'x' => 20 + ($col * 80),
                        'y' => 20 + ($row * 70),
                        'width' => 70,
                        'height' => 60,
                    ],
                    'climate_controlled' => rand(0, 1) == 1,
                    'has_electricity' => rand(0, 1) == 1,
                    'has_alarm' => true,
                ]);

                $boxNumber++;
            }
        }
    }

    private function createCustomers($tenant)
    {
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Emma', 'Thomas', 'Julie', 'Nicolas', 'Camille', 'Alexandre', 'Léa', 'Antoine', 'Chloé', 'Maxime', 'Sarah', 'Baptiste', 'Laura', 'Hugo', 'Manon'];
        $lastNames = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent'];

        for ($i = 0; $i < 100; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];

            Customer::create([
                'tenant_id' => $tenant->id,
                'type' => 'individual',
                'civility' => ['mr', 'mrs', 'ms'][rand(0, 2)],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName . '.' . $lastName . $i . '@example.com'),
                'phone' => '+336' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'address' => rand(1, 999) . ' Rue de la Paix',
                'city' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice'][rand(0, 4)],
                'postal_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'country' => 'FR',
                'birth_date' => Carbon::now()->subYears(rand(25, 65))->format('Y-m-d'),
                'id_type' => 'id_card',
                'id_number' => 'FR' . rand(100000000, 999999999),
                'status' => 'active',
                'notes' => 'Client créé automatiquement pour la démonstration',
            ]);
        }
    }

    private function createContracts($tenant)
    {
        $customers = Customer::where('tenant_id', $tenant->id)->get();
        $occupiedBoxes = Box::where('tenant_id', $tenant->id)->where('status', 'occupied')->get();

        foreach ($occupiedBoxes as $box) {
            $customer = $customers->random();
            $startDate = Carbon::now()->subMonths(rand(1, 24));

            Contract::create([
                'tenant_id' => $tenant->id,
                'site_id' => $box->site_id,
                'box_id' => $box->id,
                'customer_id' => $customer->id,
                'contract_number' => 'CO' . strtoupper(uniqid()),
                'start_date' => $startDate,
                'end_date' => null,
                'billing_frequency' => 'monthly',
                'monthly_price' => $box->current_price,
                'deposit_amount' => $box->current_price,
                'status' => 'active',
                'payment_method' => ['bank_transfer', 'card', 'cash'][rand(0, 2)],
                'auto_renew' => rand(0, 1) == 1,
            ]);
        }
    }

    private function createInvoicesAndPayments($tenant)
    {
        $contracts = Contract::where('tenant_id', $tenant->id)->where('status', 'active')->get();

        foreach ($contracts as $contract) {
            $monthsSinceStart = Carbon::parse($contract->start_date)->diffInMonths(now());

            // Create invoices for each month
            for ($i = 0; $i <= min($monthsSinceStart, 12); $i++) {
                $invoiceDate = Carbon::parse($contract->start_date)->addMonths($i);
                $dueDate = $invoiceDate->copy()->addDays(15);

                $subtotal = $contract->monthly_price;
                $taxAmount = $subtotal * 0.20;
                $total = $subtotal + $taxAmount;

                $invoice = Invoice::create([
                    'tenant_id' => $tenant->id,
                    'contract_id' => $contract->id,
                    'customer_id' => $contract->customer_id,
                    'invoice_number' => 'INV' . strtoupper(uniqid()),
                    'invoice_date' => $invoiceDate,
                    'due_date' => $dueDate,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'total' => $total,
                    'status' => rand(0, 100) > 10 ? 'paid' : 'sent',
                    'items' => [
                        [
                            'description' => 'Location box ' . $contract->box->number,
                            'quantity' => 1,
                            'unit_price' => $subtotal,
                            'total' => $subtotal,
                        ]
                    ],
                    'notes' => 'Facture mensuelle',
                ]);

                // Create payment if invoice is paid
                if ($invoice->status === 'paid') {
                    Payment::create([
                        'tenant_id' => $tenant->id,
                        'invoice_id' => $invoice->id,
                        'contract_id' => $contract->id,
                        'customer_id' => $contract->customer_id,
                        'payment_number' => 'PAY' . strtoupper(uniqid()),
                        'amount' => $total,
                        'paid_at' => $invoiceDate->copy()->addDays(rand(1, 10)),
                        'method' => $contract->payment_method,
                        'status' => 'completed',
                    ]);
                }
            }
        }
    }

    private function getMainSiteBoxLayout()
    {
        // This layout is based on plan1.png
        // Returns an array of box configurations with positions
        return $this->generateBoxesFromPlan();
    }

    private function generateBoxesFromPlan()
    {
        $boxes = [];
        $baseX = 20;
        $baseY = 20;
        $boxWidth = 70;
        $boxHeight = 60;
        $spacing = 10;

        // Generate boxes based on plan1.png layout
        // Zone M (left side)
        $mBoxes = ['M14', 'M12', 'M10', 'M8', 'M6', 'M4', 'M2'];
        foreach ($mBoxes as $index => $number) {
            $boxes[] = [
                'number' => $number,
                'length' => 3,
                'width' => 3,
                'height' => 2,
                'volume' => 18,
                'status' => rand(0, 100) <= 75 ? 'occupied' : 'available',
                'price' => 144,
                'position' => [
                    'x' => $baseX,
                    'y' => $baseY + ($index * ($boxHeight + $spacing)),
                    'width' => $boxWidth,
                    'height' => $boxHeight,
                ],
            ];
        }

        // Zone K (next column)
        $kBoxes = ['K12', 'K10', 'K8', 'K6', 'K4', 'K2'];
        foreach ($kBoxes as $index => $number) {
            $boxes[] = [
                'number' => $number,
                'length' => 3,
                'width' => 3,
                'height' => 1,
                'volume' => 9,
                'status' => rand(0, 100) <= 75 ? 'occupied' : 'available',
                'price' => 72,
                'position' => [
                    'x' => $baseX + $boxWidth + $spacing,
                    'y' => $baseY + ($index * ($boxHeight + $spacing)),
                    'width' => $boxWidth,
                    'height' => $boxHeight,
                ],
            ];
        }

        // Continue with more zones...
        // For demonstration, I'll create a comprehensive grid

        // Generate more boxes in a grid pattern
        $boxPrefixes = ['J', 'I', 'H', 'G', 'F', 'E', 'D', 'C', 'X', 'Y', 'Z'];
        $col = 2;
        foreach ($boxPrefixes as $prefix) {
            for ($row = 1; $row <= 14; $row++) {
                $number = $prefix . $row;
                $volume = [9, 12, 18, 25, 30][rand(0, 4)];

                $boxes[] = [
                    'number' => $number,
                    'length' => 3,
                    'width' => 3,
                    'height' => $volume / 9,
                    'volume' => $volume,
                    'status' => rand(0, 100) <= 70 ? 'occupied' : (rand(0, 100) <= 10 ? 'reserved' : 'available'),
                    'price' => $volume * 8,
                    'position' => [
                        'x' => $baseX + ($col * ($boxWidth + $spacing)),
                        'y' => $baseY + (($row - 1) * ($boxHeight + $spacing)),
                        'width' => $boxWidth,
                        'height' => $boxHeight,
                    ],
                ];
            }
            $col++;
        }

        return $boxes;
    }

    private function createProspects($tenant)
    {
        $firstNames = ['Antoine', 'Sophie', 'Marc', 'Claire', 'Lucas', 'Emma', 'Hugo', 'Léa', 'Gabriel', 'Chloé'];
        $lastNames = ['Lefèvre', 'Girard', 'Bonnet', 'Mercier', 'Blanc', 'Guérin', 'Muller', 'Faure', 'Roux', 'Lambert'];
        $sources = ['website', 'phone', 'email', 'referral', 'walk_in', 'social_media'];
        $statuses = ['new', 'contacted', 'qualified', 'quoted', 'converted', 'lost'];
        $boxSizes = ['S (1-3m²)', 'M (4-8m²)', 'L (9-15m²)', 'XL (16-30m²)'];

        $adminUser = User::where('tenant_id', $tenant->id)->first();

        for ($i = 0; $i < 25; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $status = $statuses[array_rand($statuses)];

            Prospect::create([
                'tenant_id' => $tenant->id,
                'user_id' => $adminUser->id,
                'type' => rand(0, 100) > 80 ? 'company' : 'individual',
                'first_name' => $firstName,
                'last_name' => $lastName,
                'company_name' => rand(0, 100) > 80 ? $lastName . ' SARL' : null,
                'email' => strtolower($firstName . '.' . $lastName . $i . '@prospect.com'),
                'phone' => '+336' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                'address' => rand(1, 200) . ' Avenue de la République',
                'postal_code' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                'city' => ['Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Nantes'][rand(0, 4)],
                'source' => $sources[array_rand($sources)],
                'status' => $status,
                'box_size_interested' => $boxSizes[array_rand($boxSizes)],
                'move_in_date' => $status !== 'lost' ? Carbon::now()->addDays(rand(1, 60)) : null,
                'budget' => rand(50, 300),
                'follow_up_count' => $status === 'new' ? 0 : rand(1, 5),
                'last_contact_at' => $status !== 'new' ? Carbon::now()->subDays(rand(1, 30)) : null,
                'notes' => 'Prospect créé pour la démonstration',
            ]);
        }
    }

    private function createSignatures($tenant)
    {
        $contracts = Contract::where('tenant_id', $tenant->id)
            ->with('customer')
            ->limit(15)
            ->get();

        $statuses = ['pending', 'sent', 'viewed', 'signed', 'refused', 'expired'];

        foreach ($contracts as $contract) {
            $status = $statuses[array_rand($statuses)];

            Signature::create([
                'tenant_id' => $tenant->id,
                'contract_id' => $contract->id,
                'customer_id' => $contract->customer_id,
                'type' => rand(0, 100) > 30 ? 'contract' : 'mandate',
                'status' => $status,
                'email_sent_to' => $contract->customer->email,
                'sent_at' => in_array($status, ['sent', 'viewed', 'signed', 'refused']) ? Carbon::now()->subDays(rand(1, 30)) : null,
                'viewed_at' => in_array($status, ['viewed', 'signed', 'refused']) ? Carbon::now()->subDays(rand(1, 20)) : null,
                'signed_at' => $status === 'signed' ? Carbon::now()->subDays(rand(1, 10)) : null,
                'expires_at' => Carbon::now()->addDays(rand(10, 30)),
                'reminder_count' => rand(0, 3),
                'ip_address' => $status === 'signed' ? '192.168.1.' . rand(1, 255) : null,
            ]);
        }
    }

    private function createSepaMandates($tenant)
    {
        $customers = Customer::where('tenant_id', $tenant->id)
            ->limit(20)
            ->get();

        $statuses = ['pending', 'active', 'active', 'active', 'suspended', 'cancelled'];

        foreach ($customers as $customer) {
            $status = $statuses[array_rand($statuses)];
            $contract = Contract::where('customer_id', $customer->id)->first();

            SepaMandate::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'contract_id' => $contract?->id,
                'rum' => 'RUM' . strtoupper(substr(md5($customer->id . time()), 0, 12)),
                'ics' => 'FR00ZZZ123456',
                'type' => rand(0, 100) > 20 ? 'recurrent' : 'one_time',
                'status' => $status,
                'iban' => 'FR76' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT) . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT) . str_pad((string)rand(100, 999), 3, '0', STR_PAD_LEFT),
                'bic' => ['BNPAFRPP', 'CEPAFRPP', 'AGRIFRPP', 'SOGEFRPP'][rand(0, 3)],
                'account_holder' => $customer->first_name . ' ' . $customer->last_name,
                'signature_date' => Carbon::now()->subMonths(rand(1, 24)),
                'signature_place' => ['Paris', 'Lyon', 'Marseille', 'Bordeaux'][rand(0, 3)],
                'activated_at' => $status !== 'pending' ? Carbon::now()->subMonths(rand(1, 12)) : null,
                'collection_count' => $status === 'active' ? rand(1, 24) : 0,
                'total_collected' => $status === 'active' ? rand(100, 5000) : 0,
                'last_collection_at' => $status === 'active' ? Carbon::now()->subDays(rand(1, 30)) : null,
            ]);
        }
    }

    private function createPaymentReminders($tenant)
    {
        // Get overdue invoices
        $overdueInvoices = Invoice::where('tenant_id', $tenant->id)
            ->where('status', 'sent')
            ->where('due_date', '<', now())
            ->with('customer')
            ->limit(15)
            ->get();

        $types = ['pre_due', 'overdue'];
        $methods = ['email', 'sms', 'letter'];
        $statuses = ['pending', 'sent', 'failed'];

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(now());
            $level = min(3, ceil($daysOverdue / 15));

            PaymentReminder::create([
                'tenant_id' => $tenant->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'type' => 'overdue',
                'level' => $level,
                'days_after_due' => $daysOverdue,
                'status' => $statuses[array_rand($statuses)],
                'message' => 'Rappel de paiement niveau ' . $level,
                'method' => $methods[array_rand($methods)],
                'amount_due' => $invoice->total - $invoice->paid_amount,
                'scheduled_at' => Carbon::now()->subDays(rand(1, 10)),
                'sent_at' => rand(0, 100) > 30 ? Carbon::now()->subDays(rand(1, 5)) : null,
            ]);
        }
    }
}
