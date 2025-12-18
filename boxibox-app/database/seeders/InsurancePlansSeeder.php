<?php

namespace Database\Seeders;

use App\Models\InsurancePlan;
use App\Models\InsuranceProvider;
use Illuminate\Database\Seeder;

class InsurancePlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default provider
        $provider = InsuranceProvider::firstOrCreate(
            ['slug' => 'boxibox-assurance'],
            [
                'name' => 'BoxiBox Assurance',
                'description' => 'Assurance intégrée proposée par BoxiBox pour la protection de vos biens stockés.',
                'is_active' => true,
            ]
        );

        // Create insurance plans
        $plans = [
            [
                'name' => 'Essentiel',
                'code' => 'ESSENTIAL',
                'description' => 'Protection de base pour vos biens stockés',
                'coverage_amount' => 3000.00,
                'covered_risks' => ['Vol avec effraction', 'Incendie', 'Dégât des eaux'],
                'exclusions' => ['Objets de valeur > 500€ unitaire', 'Espèces et bijoux', 'Documents'],
                'pricing_type' => 'fixed',
                'price_monthly' => 5.90,
                'price_yearly' => 59.00,
                'deductible' => 150.00,
                'min_contract_months' => 1,
                'is_default' => false,
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Confort',
                'code' => 'COMFORT',
                'description' => 'Protection étendue pour une tranquillité d\'esprit',
                'coverage_amount' => 10000.00,
                'covered_risks' => ['Vol avec effraction', 'Incendie', 'Dégât des eaux', 'Catastrophes naturelles', 'Vandalisme'],
                'exclusions' => ['Objets de valeur > 2000€ unitaire', 'Espèces', 'Véhicules motorisés'],
                'pricing_type' => 'fixed',
                'price_monthly' => 12.90,
                'price_yearly' => 129.00,
                'deductible' => 100.00,
                'min_contract_months' => 1,
                'is_default' => true,
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Premium',
                'code' => 'PREMIUM',
                'description' => 'Protection maximale tous risques',
                'coverage_amount' => 30000.00,
                'covered_risks' => ['Vol avec effraction', 'Incendie', 'Dégât des eaux', 'Catastrophes naturelles', 'Vandalisme', 'Bris accidentel', 'Rongeurs et nuisibles'],
                'exclusions' => ['Espèces', 'Véhicules motorisés', 'Matières dangereuses'],
                'pricing_type' => 'fixed',
                'price_monthly' => 24.90,
                'price_yearly' => 249.00,
                'deductible' => 50.00,
                'min_contract_months' => 1,
                'is_default' => false,
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            InsurancePlan::firstOrCreate(
                ['code' => $planData['code']],
                array_merge($planData, ['provider_id' => $provider->id])
            );
        }

        $this->command->info('Insurance plans seeded successfully!');
    }
}
