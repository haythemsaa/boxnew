<?php

namespace Database\Seeders;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\PlanElement;
use App\Models\Site;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BuxidaDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::first();
        if (!$site) {
            $this->command->error('No site found');
            return;
        }

        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('No tenant found');
            return;
        }

        $this->command->info("Creating demo data for site: {$site->name}");

        // Step 1: Link existing boxes to plan elements by matching label/number
        $this->linkBoxesToPlanElements($site);

        // Step 2: Create demo customers if needed
        $customers = $this->createDemoCustomers($tenant);

        // Step 3: Create demo contracts for occupied boxes
        $this->createDemoContracts($site, $tenant, $customers);

        $this->command->info('Demo data created successfully!');
    }

    private function linkBoxesToPlanElements(Site $site): void
    {
        $this->command->info('Linking boxes to plan elements...');

        $planElements = PlanElement::where('site_id', $site->id)
            ->where('element_type', 'box')
            ->get();

        $linked = 0;
        foreach ($planElements as $element) {
            // Find box with matching number
            $box = Box::where('site_id', $site->id)
                ->where('number', $element->label)
                ->first();

            if ($box) {
                $element->box_id = $box->id;
                $element->save();
                $linked++;
            }
        }

        $this->command->info("Linked {$linked} boxes to plan elements");
    }

    private function createDemoCustomers(Tenant $tenant): array
    {
        $this->command->info('Creating demo customers...');

        $demoCustomers = [
            ['first_name' => 'Jean', 'last_name' => 'Dupont', 'email' => 'jean.dupont@example.com', 'phone' => '0601020304'],
            ['first_name' => 'Marie', 'last_name' => 'Martin', 'email' => 'marie.martin@example.com', 'phone' => '0602030405'],
            ['first_name' => 'Pierre', 'last_name' => 'Bernard', 'email' => 'pierre.bernard@example.com', 'phone' => '0603040506'],
            ['first_name' => 'Sophie', 'last_name' => 'Petit', 'email' => 'sophie.petit@example.com', 'phone' => '0604050607'],
            ['first_name' => 'Luc', 'last_name' => 'Robert', 'email' => 'luc.robert@example.com', 'phone' => '0605060708'],
            ['first_name' => 'Anne', 'last_name' => 'Richard', 'email' => 'anne.richard@example.com', 'phone' => '0606070809'],
            ['first_name' => 'Paul', 'last_name' => 'Durand', 'email' => 'paul.durand@example.com', 'phone' => '0607080910'],
            ['first_name' => 'Claire', 'last_name' => 'Leroy', 'email' => 'claire.leroy@example.com', 'phone' => '0608091011'],
            ['first_name' => 'Marc', 'last_name' => 'Moreau', 'email' => 'marc.moreau@example.com', 'phone' => '0609101112'],
            ['first_name' => 'Julie', 'last_name' => 'Simon', 'email' => 'julie.simon@example.com', 'phone' => '0610111213'],
            ['first_name' => 'Thomas', 'last_name' => 'Laurent', 'email' => 'thomas.laurent@example.com', 'phone' => '0611121314'],
            ['first_name' => 'Emma', 'last_name' => 'Michel', 'email' => 'emma.michel@example.com', 'phone' => '0612131415'],
            ['first_name' => 'Lucas', 'last_name' => 'Garcia', 'email' => 'lucas.garcia@example.com', 'phone' => '0613141516'],
            ['first_name' => 'Lea', 'last_name' => 'David', 'email' => 'lea.david@example.com', 'phone' => '0614151617'],
            ['first_name' => 'Hugo', 'last_name' => 'Bertrand', 'email' => 'hugo.bertrand@example.com', 'phone' => '0615161718'],
            ['first_name' => 'Chloe', 'last_name' => 'Roux', 'email' => 'chloe.roux@example.com', 'phone' => '0616171819'],
            ['first_name' => 'Nathan', 'last_name' => 'Vincent', 'email' => 'nathan.vincent@example.com', 'phone' => '0617181920'],
            ['first_name' => 'Manon', 'last_name' => 'Fournier', 'email' => 'manon.fournier@example.com', 'phone' => '0618192021'],
            ['first_name' => 'Enzo', 'last_name' => 'Morel', 'email' => 'enzo.morel@example.com', 'phone' => '0619202122'],
            ['first_name' => 'Camille', 'last_name' => 'Girard', 'email' => 'camille.girard@example.com', 'phone' => '0620212223'],
            ['first_name' => 'Amba', 'last_name' => 'Akatshi', 'email' => 'amba.akatshi@example.com', 'phone' => '0621222324'],
            ['first_name' => 'Fatou', 'last_name' => 'Diallo', 'email' => 'fatou.diallo@example.com', 'phone' => '0622232425'],
            ['first_name' => 'Moussa', 'last_name' => 'Traore', 'email' => 'moussa.traore@example.com', 'phone' => '0623242526'],
            ['first_name' => 'Aissatou', 'last_name' => 'Ba', 'email' => 'aissatou.ba@example.com', 'phone' => '0624252627'],
            ['first_name' => 'Ibrahim', 'last_name' => 'Kone', 'email' => 'ibrahim.kone@example.com', 'phone' => '0625262728'],
        ];

        $customers = [];
        foreach ($demoCustomers as $data) {
            $isCompany = rand(0, 1);
            $customer = Customer::firstOrCreate(
                ['email' => $data['email'], 'tenant_id' => $tenant->id],
                [
                    'tenant_id' => $tenant->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'address' => rand(1, 150) . ' Rue de la Paix',
                    'city' => ['Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Lille'][rand(0, 4)],
                    'postal_code' => '7500' . rand(1, 9),
                    'country' => 'FR',
                    'type' => $isCompany ? 'company' : 'individual',
                    'company_name' => $isCompany ? $data['last_name'] . ' SARL' : null,
                ]
            );
            $customers[] = $customer;
        }

        $this->command->info('Created/found ' . count($customers) . ' demo customers');
        return $customers;
    }

    private function createDemoContracts(Site $site, Tenant $tenant, array $customers): void
    {
        $this->command->info('Creating demo contracts...');

        // Get plan elements that have linked boxes
        $planElements = PlanElement::where('site_id', $site->id)
            ->where('element_type', 'box')
            ->whereNotNull('box_id')
            ->with('box')
            ->get();

        if ($planElements->isEmpty()) {
            $this->command->warn('No plan elements with linked boxes found');
            return;
        }

        // Define which boxes should have contracts (about 60-70% occupation)
        $boxesToOccupy = $planElements->random(min((int)($planElements->count() * 0.65), $planElements->count()));

        $contractsCreated = 0;
        $statuses = ['occupied', 'occupied', 'occupied', 'occupied', 'reserved', 'ending'];

        foreach ($boxesToOccupy as $index => $element) {
            $box = $element->box;
            if (!$box) continue;

            // Check if box already has an active contract
            $existingContract = Contract::where('box_id', $box->id)
                ->where('status', 'active')
                ->first();

            if ($existingContract) {
                // Update plan element status
                $this->updatePlanElementStatus($element, 'occupied');
                continue;
            }

            $customer = $customers[$index % count($customers)];
            $status = $statuses[array_rand($statuses)];

            // Calculate dates
            $startDate = Carbon::now()->subMonths(rand(1, 24));
            $endDate = null;

            if ($status === 'ending') {
                $endDate = Carbon::now()->addDays(rand(5, 25));
            } elseif (rand(0, 1)) {
                $endDate = Carbon::now()->addMonths(rand(3, 24));
            }

            // Calculate price based on volume
            $volume = $box->volume ?? 9;
            $monthlyPrice = $this->calculatePrice($volume);

            // Create contract
            $contractNumber = 'CO' . date('Y') . str_pad($contractsCreated + 1000, 5, '0', STR_PAD_LEFT);

            $contract = Contract::create([
                'tenant_id' => $tenant->id,
                'site_id' => $site->id,
                'customer_id' => $customer->id,
                'box_id' => $box->id,
                'contract_number' => $contractNumber,
                'status' => 'active',
                'type' => ['standard', 'short_term', 'long_term'][rand(0, 2)],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'monthly_price' => $monthlyPrice,
                'deposit_amount' => $monthlyPrice * 2,
                'payment_method' => ['bank_transfer', 'sepa', 'card'][rand(0, 2)],
                'billing_frequency' => 'monthly',
            ]);

            // Update box status
            $box->status = $status === 'ending' ? 'occupied' : ($status === 'reserved' ? 'reserved' : 'occupied');
            $box->save();

            // Update plan element status and color
            $this->updatePlanElementStatus($element, $status);

            $contractsCreated++;
        }

        // Mark remaining elements as available
        $availableElements = PlanElement::where('site_id', $site->id)
            ->where('element_type', 'box')
            ->whereNotNull('box_id')
            ->whereNotIn('id', $boxesToOccupy->pluck('id'))
            ->get();

        foreach ($availableElements as $element) {
            $this->updatePlanElementStatus($element, 'available');
        }

        $this->command->info("Created {$contractsCreated} demo contracts");
    }

    private function updatePlanElementStatus(PlanElement $element, string $status): void
    {
        $statusColors = [
            'available' => '#4CAF50',
            'occupied' => '#2196F3',
            'reserved' => '#FF9800',
            'ending' => '#FFEB3B',
            'maintenance' => '#f44336',
            'unavailable' => '#9E9E9E',
        ];

        $properties = $element->properties ?? [];
        $properties['status'] = $status;

        $element->properties = $properties;
        $element->fill_color = $statusColors[$status] ?? $statusColors['available'];
        $element->save();
    }

    private function calculatePrice(float $volume): float
    {
        // Price based on volume (roughly 15-25€ per m³)
        $pricePerM3 = rand(15, 25);
        return round($volume * $pricePerM3, 2);
    }
}
