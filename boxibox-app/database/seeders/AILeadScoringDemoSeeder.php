<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Tenant;
use App\Models\Site;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AILeadScoringDemoSeeder extends Seeder
{
    /**
     * Create demo leads with various profiles to demonstrate AI Lead Scoring
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('<fg=cyan>╔══════════════════════════════════════════════════════╗</>');
        $this->command->info('<fg=cyan>║</>  <fg=white;options=bold>AI Lead Scoring - Demo Data Generation</>            <fg=cyan>║</>');
        $this->command->info('<fg=cyan>╚══════════════════════════════════════════════════════╝</>');
        $this->command->info('');

        // Get first active tenant
        $tenant = Tenant::where('is_active', true)->first();
        if (!$tenant) {
            $this->command->error('No active tenant found. Please run DemoTenantSeeder first.');
            return;
        }

        $site = Site::where('tenant_id', $tenant->id)->first();
        $assignedUser = User::where('tenant_id', $tenant->id)->first();

        $this->command->info("<fg=yellow>Creating demo leads for tenant:</> {$tenant->name}");

        // Clear existing demo leads (those with email containing @demo-scoring)
        Lead::where('email', 'like', '%@demo-scoring%')->forceDelete();
        Prospect::where('email', 'like', '%@demo-scoring%')->forceDelete();

        // ============================================
        // VERY HOT LEADS (Score 85-100)
        // ============================================
        $this->command->info('');
        $this->command->info('<fg=red;options=bold>🔥🔥 Creating VERY HOT leads...</>');

        $veryHotLeads = [
            [
                'first_name' => 'Marie',
                'last_name' => 'Dupont',
                'email' => 'marie.dupont@demo-scoring.fr',
                'phone' => '0612345001',
                'company' => 'Dupont Consulting',
                'type' => 'business',
                'source' => 'referral',
                'budget_min' => 200,
                'budget_max' => 400,
                'move_in_date' => Carbon::now()->addDays(5),
                'notes' => 'Urgence demenagement - Referree par client existant M. Lambert',
                'metadata' => [
                    'calculator_used' => true,
                    'calculator_result' => ['recommended_size' => '10m2', 'monthly_price' => 189],
                    'visited_pricing' => true,
                    'visited_faq' => true,
                    'quote_requested' => true,
                    'quote_amount' => 189,
                    'visit_scheduled' => true,
                    'visit_date' => Carbon::now()->addDays(2)->toDateString(),
                    'emails_opened' => 5,
                    'emails_clicked' => 3,
                    'page_views' => 28,
                    'time_on_site' => 1850,
                    'return_visits' => 6,
                    'referred_by' => 'customer_123',
                    'urgency_mentioned' => true,
                    'contract_duration_interest' => 12,
                ],
                'first_contacted_at' => Carbon::now()->subDays(3),
                'last_contacted_at' => Carbon::now()->subHours(6),
            ],
            [
                'first_name' => 'Philippe',
                'last_name' => 'Martin',
                'email' => 'philippe.martin@demo-scoring.fr',
                'phone' => '0612345002',
                'company' => 'Martin & Fils SARL',
                'type' => 'business',
                'source' => 'google_ads',
                'budget_min' => 500,
                'budget_max' => 800,
                'move_in_date' => Carbon::now()->addDays(3),
                'notes' => 'Besoin stockage commercial urgent - 3 boxes souhaites',
                'metadata' => [
                    'calculator_used' => true,
                    'calculator_result' => ['recommended_size' => '25m2', 'monthly_price' => 450],
                    'visited_pricing' => true,
                    'quote_requested' => true,
                    'quote_amount' => 1350,
                    'visit_scheduled' => true,
                    'visit_date' => Carbon::now()->addDay()->toDateString(),
                    'emails_opened' => 8,
                    'emails_clicked' => 5,
                    'page_views' => 45,
                    'time_on_site' => 2400,
                    'return_visits' => 8,
                    'business_customer' => true,
                    'multiple_units_interest' => 3,
                    'urgency_mentioned' => true,
                    'contract_duration_interest' => 24,
                    'insurance_interest' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(5),
                'last_contacted_at' => Carbon::now()->subHours(2),
            ],
            [
                'first_name' => 'Sophie',
                'last_name' => 'Bernard',
                'email' => 'sophie.bernard@demo-scoring.fr',
                'phone' => '0612345003',
                'company' => null,
                'type' => 'individual',
                'source' => 'referral',
                'budget_min' => 100,
                'budget_max' => 200,
                'move_in_date' => Carbon::now()->addDays(7),
                'notes' => 'Divorce en cours - Besoin urgent stockage meubles. Recommandee par sa soeur cliente.',
                'metadata' => [
                    'calculator_used' => true,
                    'calculator_result' => ['recommended_size' => '8m2', 'monthly_price' => 149],
                    'visited_pricing' => true,
                    'visited_faq' => true,
                    'visited_access_hours' => true,
                    'quote_requested' => true,
                    'quote_amount' => 149,
                    'visit_completed' => true,
                    'visit_rating' => 5,
                    'emails_opened' => 6,
                    'emails_clicked' => 4,
                    'page_views' => 32,
                    'time_on_site' => 1950,
                    'return_visits' => 5,
                    'referred_by' => 'customer_456',
                    'urgency_mentioned' => true,
                    'emotional_trigger' => 'life_change',
                ],
                'first_contacted_at' => Carbon::now()->subDays(4),
                'last_contacted_at' => Carbon::now()->subHours(12),
            ],
        ];

        foreach ($veryHotLeads as $data) {
            $this->createLead($tenant, $site, $assignedUser, $data);
        }

        // ============================================
        // HOT LEADS (Score 70-84)
        // ============================================
        $this->command->info('<fg=yellow;options=bold>🔥 Creating HOT leads...</>');

        $hotLeads = [
            [
                'first_name' => 'Jean',
                'last_name' => 'Lefevre',
                'email' => 'jean.lefevre@demo-scoring.fr',
                'phone' => '0612345004',
                'company' => 'Lefevre Design',
                'type' => 'business',
                'source' => 'google_organic',
                'budget_min' => 150,
                'budget_max' => 300,
                'move_in_date' => Carbon::now()->addDays(14),
                'notes' => 'Besoin stockage archives et materiel',
                'metadata' => [
                    'calculator_used' => true,
                    'calculator_result' => ['recommended_size' => '12m2', 'monthly_price' => 229],
                    'visited_pricing' => true,
                    'quote_requested' => true,
                    'quote_amount' => 229,
                    'emails_opened' => 4,
                    'emails_clicked' => 2,
                    'page_views' => 18,
                    'time_on_site' => 1200,
                    'return_visits' => 4,
                    'business_customer' => true,
                    'contract_duration_interest' => 12,
                ],
                'first_contacted_at' => Carbon::now()->subDays(7),
                'last_contacted_at' => Carbon::now()->subDays(1),
            ],
            [
                'first_name' => 'Isabelle',
                'last_name' => 'Moreau',
                'email' => 'isabelle.moreau@demo-scoring.fr',
                'phone' => '0612345005',
                'company' => null,
                'type' => 'individual',
                'source' => 'facebook_ads',
                'budget_min' => 80,
                'budget_max' => 150,
                'move_in_date' => Carbon::now()->addDays(21),
                'notes' => 'Renovation maison - stockage temporaire',
                'metadata' => [
                    'calculator_used' => true,
                    'calculator_result' => ['recommended_size' => '6m2', 'monthly_price' => 99],
                    'visited_pricing' => true,
                    'visited_faq' => true,
                    'visit_scheduled' => true,
                    'visit_date' => Carbon::now()->addDays(5)->toDateString(),
                    'emails_opened' => 5,
                    'emails_clicked' => 3,
                    'page_views' => 22,
                    'time_on_site' => 1450,
                    'return_visits' => 5,
                ],
                'first_contacted_at' => Carbon::now()->subDays(6),
                'last_contacted_at' => Carbon::now()->subDays(2),
            ],
            [
                'first_name' => 'Laurent',
                'last_name' => 'Rousseau',
                'email' => 'laurent.rousseau@demo-scoring.fr',
                'phone' => '0612345006',
                'company' => 'Rousseau Import',
                'type' => 'business',
                'source' => 'linkedin',
                'budget_min' => 300,
                'budget_max' => 500,
                'move_in_date' => Carbon::now()->addDays(10),
                'notes' => 'Stockage marchandises import',
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'quote_requested' => true,
                    'quote_amount' => 389,
                    'emails_opened' => 3,
                    'emails_clicked' => 2,
                    'page_views' => 15,
                    'time_on_site' => 980,
                    'return_visits' => 3,
                    'business_customer' => true,
                    'insurance_interest' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(4),
                'last_contacted_at' => Carbon::now()->subHours(18),
            ],
            [
                'first_name' => 'Claire',
                'last_name' => 'Petit',
                'email' => 'claire.petit@demo-scoring.fr',
                'phone' => '0612345007',
                'company' => null,
                'type' => 'individual',
                'source' => 'referral',
                'budget_min' => 100,
                'budget_max' => 180,
                'move_in_date' => Carbon::now()->addDays(14),
                'notes' => 'Recommandee par collegue',
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'visited_access_hours' => true,
                    'emails_opened' => 4,
                    'emails_clicked' => 2,
                    'page_views' => 20,
                    'time_on_site' => 1100,
                    'return_visits' => 4,
                    'referred_by' => 'customer_789',
                ],
                'first_contacted_at' => Carbon::now()->subDays(5),
                'last_contacted_at' => Carbon::now()->subDays(1),
            ],
        ];

        foreach ($hotLeads as $data) {
            $this->createLead($tenant, $site, $assignedUser, $data);
        }

        // ============================================
        // WARM LEADS (Score 50-69)
        // ============================================
        $this->command->info('<fg=green;options=bold>🌡️ Creating WARM leads...</>');

        $warmLeads = [
            [
                'first_name' => 'Nicolas',
                'last_name' => 'Girard',
                'email' => 'nicolas.girard@demo-scoring.fr',
                'phone' => '0612345008',
                'company' => null,
                'type' => 'individual',
                'source' => 'google_organic',
                'budget_min' => 60,
                'budget_max' => 120,
                'move_in_date' => Carbon::now()->addDays(30),
                'notes' => 'Compare les options',
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'emails_opened' => 2,
                    'emails_clicked' => 1,
                    'page_views' => 12,
                    'time_on_site' => 650,
                    'return_visits' => 2,
                ],
                'first_contacted_at' => Carbon::now()->subDays(8),
                'last_contacted_at' => Carbon::now()->subDays(3),
            ],
            [
                'first_name' => 'Emilie',
                'last_name' => 'Robert',
                'email' => 'emilie.robert@demo-scoring.fr',
                'phone' => '0612345009',
                'company' => null,
                'type' => 'individual',
                'source' => 'instagram',
                'budget_min' => 80,
                'budget_max' => 140,
                'move_in_date' => Carbon::now()->addDays(45),
                'notes' => 'Demenagement prevu dans 2 mois',
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'visited_faq' => true,
                    'emails_opened' => 3,
                    'emails_clicked' => 1,
                    'page_views' => 14,
                    'time_on_site' => 780,
                    'return_visits' => 3,
                ],
                'first_contacted_at' => Carbon::now()->subDays(10),
                'last_contacted_at' => Carbon::now()->subDays(4),
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Simon',
                'email' => 'thomas.simon@demo-scoring.fr',
                'phone' => '0612345010',
                'company' => 'Simon Events',
                'type' => 'business',
                'source' => 'google_ads',
                'budget_min' => 150,
                'budget_max' => 250,
                'move_in_date' => Carbon::now()->addDays(60),
                'notes' => 'Stockage materiel evenementiel saisonnier',
                'metadata' => [
                    'visited_pricing' => true,
                    'emails_opened' => 2,
                    'page_views' => 10,
                    'time_on_site' => 540,
                    'return_visits' => 2,
                    'business_customer' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(12),
                'last_contacted_at' => Carbon::now()->subDays(5),
            ],
            [
                'first_name' => 'Camille',
                'last_name' => 'Michel',
                'email' => 'camille.michel@demo-scoring.fr',
                'phone' => '0612345011',
                'company' => null,
                'type' => 'individual',
                'source' => 'facebook_organic',
                'budget_min' => 50,
                'budget_max' => 100,
                'move_in_date' => Carbon::now()->addDays(35),
                'notes' => 'Etudiant - stage a l etranger',
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'emails_opened' => 3,
                    'emails_clicked' => 1,
                    'page_views' => 16,
                    'time_on_site' => 720,
                    'return_visits' => 3,
                    'student' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(7),
                'last_contacted_at' => Carbon::now()->subDays(2),
            ],
        ];

        foreach ($warmLeads as $data) {
            $this->createLead($tenant, $site, $assignedUser, $data);
        }

        // ============================================
        // LUKEWARM LEADS (Score 30-49)
        // ============================================
        $this->command->info('<fg=blue;options=bold>💧 Creating LUKEWARM leads...</>');

        $lukewarmLeads = [
            [
                'first_name' => 'Alexandre',
                'last_name' => 'Garcia',
                'email' => 'alexandre.garcia@demo-scoring.fr',
                'phone' => '0612345012',
                'company' => null,
                'type' => 'individual',
                'source' => 'google_organic',
                'budget_min' => 40,
                'budget_max' => 80,
                'move_in_date' => Carbon::now()->addDays(90),
                'notes' => 'Juste en recherche d info pour le moment',
                'metadata' => [
                    'visited_pricing' => true,
                    'emails_opened' => 1,
                    'page_views' => 6,
                    'time_on_site' => 280,
                    'return_visits' => 1,
                ],
                'first_contacted_at' => Carbon::now()->subDays(15),
                'last_contacted_at' => Carbon::now()->subDays(10),
            ],
            [
                'first_name' => 'Julie',
                'last_name' => 'Martinez',
                'email' => 'julie.martinez@demo-scoring.fr',
                'phone' => '0612345013',
                'company' => null,
                'type' => 'individual',
                'source' => 'bing',
                'budget_min' => 50,
                'budget_max' => 90,
                'move_in_date' => null,
                'notes' => 'Pas de date precise',
                'metadata' => [
                    'page_views' => 4,
                    'time_on_site' => 180,
                    'return_visits' => 1,
                ],
                'first_contacted_at' => Carbon::now()->subDays(20),
                'last_contacted_at' => Carbon::now()->subDays(12),
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Lopez',
                'email' => 'david.lopez@demo-scoring.fr',
                'phone' => null,
                'company' => null,
                'type' => 'individual',
                'source' => 'facebook_organic',
                'budget_min' => null,
                'budget_max' => null,
                'move_in_date' => Carbon::now()->addDays(120),
                'notes' => 'Curieux - compare les prix',
                'metadata' => [
                    'visited_pricing' => true,
                    'page_views' => 5,
                    'time_on_site' => 220,
                    'return_visits' => 1,
                ],
                'first_contacted_at' => Carbon::now()->subDays(18),
                'last_contacted_at' => Carbon::now()->subDays(14),
            ],
        ];

        foreach ($lukewarmLeads as $data) {
            $this->createLead($tenant, $site, $assignedUser, $data);
        }

        // ============================================
        // COLD LEADS (Score 0-29)
        // ============================================
        $this->command->info('<fg=gray;options=bold>❄️ Creating COLD leads...</>');

        $coldLeads = [
            [
                'first_name' => 'Pierre',
                'last_name' => 'Hernandez',
                'email' => 'pierre.hernandez@demo-scoring.fr',
                'phone' => null,
                'company' => null,
                'type' => 'individual',
                'source' => 'direct',
                'budget_min' => null,
                'budget_max' => null,
                'move_in_date' => null,
                'notes' => 'Formulaire contact rempli brievement',
                'metadata' => [
                    'page_views' => 2,
                    'time_on_site' => 45,
                ],
                'first_contacted_at' => Carbon::now()->subDays(30),
                'last_contacted_at' => Carbon::now()->subDays(25),
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Gonzalez',
                'email' => 'sarah.gonzalez@demo-scoring.fr',
                'phone' => '0612345014',
                'company' => null,
                'type' => 'individual',
                'source' => 'other',
                'budget_min' => null,
                'budget_max' => null,
                'move_in_date' => null,
                'notes' => 'Ne repond pas aux emails',
                'metadata' => [
                    'emails_opened' => 0,
                    'page_views' => 1,
                    'time_on_site' => 30,
                    'bounce' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(45),
                'last_contacted_at' => Carbon::now()->subDays(30),
            ],
            [
                'first_name' => 'Antoine',
                'last_name' => 'Blanc',
                'email' => 'antoine.blanc@demo-scoring.fr',
                'phone' => null,
                'company' => null,
                'type' => 'individual',
                'source' => 'google_organic',
                'budget_min' => 20,
                'budget_max' => 40,
                'move_in_date' => null,
                'notes' => 'Budget tres limite',
                'metadata' => [
                    'page_views' => 3,
                    'time_on_site' => 120,
                    'low_budget' => true,
                ],
                'first_contacted_at' => Carbon::now()->subDays(35),
                'last_contacted_at' => Carbon::now()->subDays(28),
            ],
        ];

        foreach ($coldLeads as $data) {
            $this->createLead($tenant, $site, $assignedUser, $data);
        }

        // ============================================
        // PROSPECTS (Different pipeline)
        // ============================================
        $this->command->info('');
        $this->command->info('<fg=magenta;options=bold>📋 Creating demo PROSPECTS...</>');

        $this->createDemoProspects($tenant, $site);

        // Summary
        $this->command->info('');
        $this->command->info('<fg=cyan>╔══════════════════════════════════════════════════════╗</>');
        $this->command->info('<fg=cyan>║</>                    <fg=white;options=bold>SUMMARY</>                        <fg=cyan>║</>');
        $this->command->info('<fg=cyan>╠══════════════════════════════════════════════════════╣</>');
        $this->command->info('<fg=cyan>║</>  <fg=red>🔥🔥 Very Hot leads:  3</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>║</>  <fg=yellow>🔥 Hot leads:         4</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>║</>  <fg=green>🌡️ Warm leads:        4</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>║</>  <fg=blue>💧 Lukewarm leads:    3</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>║</>  <fg=gray>❄️ Cold leads:        3</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>║</>  <fg=magenta>📋 Prospects:         6</>                             <fg=cyan>║</>');
        $this->command->info('<fg=cyan>╠══════════════════════════════════════════════════════╣</>');
        $this->command->info('<fg=cyan>║</>  <fg=white;options=bold>Total: 23 records created</>                        <fg=cyan>║</>');
        $this->command->info('<fg=cyan>╚══════════════════════════════════════════════════════╝</>');
        $this->command->info('');
        $this->command->info('<fg=yellow>Next step:</> Run <fg=white>php artisan leads:calculate-scores</> to calculate AI scores');
        $this->command->info('');
    }

    private function createLead(Tenant $tenant, ?Site $site, ?User $assignedUser, array $data): Lead
    {
        $lead = Lead::create([
            'tenant_id' => $tenant->id,
            'site_id' => $site?->id,
            'assigned_to' => $assignedUser?->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'company' => $data['company'] ?? null,
            'type' => $data['type'] ?? 'individual',
            'status' => 'new',
            'source' => $data['source'] ?? 'direct',
            'score' => 0,
            'priority' => 'cold',
            'box_type_interest' => $data['box_type_interest'] ?? null,
            'budget_min' => $data['budget_min'] ?? null,
            'budget_max' => $data['budget_max'] ?? null,
            'move_in_date' => $data['move_in_date'] ?? null,
            'notes' => $data['notes'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'first_contacted_at' => $data['first_contacted_at'] ?? null,
            'last_contacted_at' => $data['last_contacted_at'] ?? null,
            'created_at' => Carbon::now()->subDays(rand(1, 30)),
        ]);

        $this->command->line("  <fg=gray>+</> Created lead: {$data['first_name']} {$data['last_name']} <fg=gray>({$data['email']})</>");

        return $lead;
    }

    private function createDemoProspects(Tenant $tenant, ?Site $site): void
    {
        $prospects = [
            // Very Hot Prospect
            [
                'first_name' => 'Francois',
                'last_name' => 'Dubois',
                'email' => 'francois.dubois@demo-scoring.fr',
                'phone' => '0612345020',
                'company_name' => 'Dubois Immobilier',
                'type' => 'company',
                'status' => 'qualified',
                'source' => 'referral',
                'budget' => 450,
                'metadata' => [
                    'calculator_used' => true,
                    'quote_requested' => true,
                    'visit_scheduled' => true,
                    'emails_opened' => 7,
                    'emails_clicked' => 4,
                    'page_views' => 35,
                    'time_on_site' => 2100,
                    'return_visits' => 7,
                    'business_customer' => true,
                    'urgency_mentioned' => true,
                ],
            ],
            // Hot Prospect
            [
                'first_name' => 'Nathalie',
                'last_name' => 'Mercier',
                'email' => 'nathalie.mercier@demo-scoring.fr',
                'phone' => '0612345021',
                'company_name' => null,
                'type' => 'individual',
                'status' => 'contacted',
                'source' => 'website',
                'budget' => 189,
                'metadata' => [
                    'calculator_used' => true,
                    'quote_requested' => true,
                    'emails_opened' => 4,
                    'emails_clicked' => 2,
                    'page_views' => 18,
                    'time_on_site' => 1100,
                    'return_visits' => 4,
                ],
            ],
            // Warm Prospects
            [
                'first_name' => 'Olivier',
                'last_name' => 'Lemaire',
                'email' => 'olivier.lemaire@demo-scoring.fr',
                'phone' => '0612345022',
                'company_name' => 'Lemaire Tech',
                'type' => 'company',
                'status' => 'new',
                'source' => 'social_media',
                'budget' => 280,
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'emails_opened' => 2,
                    'page_views' => 12,
                    'time_on_site' => 650,
                    'return_visits' => 2,
                    'business_customer' => true,
                ],
            ],
            [
                'first_name' => 'Aurelie',
                'last_name' => 'Fontaine',
                'email' => 'aurelie.fontaine@demo-scoring.fr',
                'phone' => '0612345023',
                'company_name' => null,
                'type' => 'individual',
                'status' => 'contacted',
                'source' => 'social_media',
                'budget' => 129,
                'metadata' => [
                    'calculator_used' => true,
                    'visited_pricing' => true,
                    'emails_opened' => 3,
                    'emails_clicked' => 1,
                    'page_views' => 14,
                    'time_on_site' => 720,
                    'return_visits' => 3,
                ],
            ],
            // Lukewarm Prospect
            [
                'first_name' => 'Sebastien',
                'last_name' => 'Morel',
                'email' => 'sebastien.morel@demo-scoring.fr',
                'phone' => '0612345024',
                'company_name' => null,
                'type' => 'individual',
                'status' => 'new',
                'source' => 'website',
                'budget' => 99,
                'metadata' => [
                    'visited_pricing' => true,
                    'emails_opened' => 1,
                    'page_views' => 6,
                    'time_on_site' => 300,
                ],
            ],
            // Cold Prospect
            [
                'first_name' => 'Valerie',
                'last_name' => 'Roux',
                'email' => 'valerie.roux@demo-scoring.fr',
                'phone' => null,
                'company_name' => null,
                'type' => 'individual',
                'status' => 'new',
                'source' => 'website',
                'budget' => null,
                'metadata' => [
                    'page_views' => 2,
                    'time_on_site' => 60,
                ],
            ],
        ];

        foreach ($prospects as $data) {
            Prospect::create([
                'tenant_id' => $tenant->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'type' => $data['type'] ?? 'individual',
                'status' => $data['status'] ?? 'new',
                'source' => $data['source'] ?? 'website',
                'score' => 0,
                'priority' => 'cold',
                'budget' => $data['budget'] ?? null,
                'notes' => $data['notes'] ?? null,
                'metadata' => $data['metadata'] ?? null,
                'created_at' => Carbon::now()->subDays(rand(1, 20)),
            ]);

            $this->command->line("  <fg=gray>+</> Created prospect: {$data['first_name']} {$data['last_name']} <fg=gray>({$data['email']})</>");
        }
    }
}
