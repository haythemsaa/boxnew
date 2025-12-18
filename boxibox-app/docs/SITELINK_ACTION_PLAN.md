# Plan d'Action: Implémenter les Fonctionnalités Clés de SiteLink

**Date:** 14 décembre 2025
**Objectif:** Roadmap détaillée pour combler les gaps vs SiteLink
**Timeline:** 12 mois

---

## PHASE 1: PRICE OPTIMIZER (6-8 semaines)

### Objectif
Implémenter un système de tarification dynamique automatique basé sur l'occupation, la demande et les données historiques.

### Architecture Technique

```php
// app/Services/PriceOptimizerService.php
class PriceOptimizerService
{
    public function calculateOptimalPrice(Box $box, Site $site): array
    {
        $occupancyRate = $this->getOccupancyRate($site, $box->box_type_id);
        $seasonalFactor = $this->getSeasonalFactor($site);
        $demandScore = $this->calculateDemandScore($box);
        $competitorPricing = $this->getCompetitorPricing($site, $box->box_type_id);

        $basePrice = $box->current_price;
        $recommendedPrice = $this->applyPricingAlgorithm(
            $basePrice,
            $occupancyRate,
            $seasonalFactor,
            $demandScore,
            $competitorPricing
        );

        return [
            'current_price' => $basePrice,
            'recommended_price' => $recommendedPrice,
            'confidence' => $this->calculateConfidence(),
            'expected_revenue_impact' => $this->calculateRevenueImpact(),
            'risk_score' => $this->calculateRiskScore(),
            'reasoning' => $this->explainRecommendation()
        ];
    }

    private function applyPricingAlgorithm($base, $occupancy, $seasonal, $demand, $competitor)
    {
        // Algorithme de base
        if ($occupancy < 0.70) {
            // Basse occupation: baisse prix pour attirer
            $adjustment = -0.10; // -10%
        } elseif ($occupancy > 0.90) {
            // Haute occupation: augmentation prix
            $adjustment = 0.15; // +15%
        } else {
            // Occupation normale: ajustement léger
            $adjustment = 0.05; // +5%
        }

        // Facteur saisonnier
        $adjustment += $seasonal * 0.1;

        // Facteur demande
        $adjustment += $demand * 0.05;

        // Comparaison compétiteurs
        if ($competitor > $base * 1.1) {
            // On est 10%+ moins cher: opportunité d'augmenter
            $adjustment += 0.05;
        }

        // Limites de sécurité
        $adjustment = max(-0.20, min(0.25, $adjustment)); // Entre -20% et +25%

        return round($base * (1 + $adjustment), 2);
    }
}
```

### Database Schema

```php
// Migration: create_price_optimizer_tables.php
Schema::create('pricing_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('site_id')->nullable();
    $table->enum('rule_type', ['occupancy', 'seasonal', 'demand', 'competitor', 'custom']);
    $table->json('conditions'); // {occupancy_threshold: 0.90, action: 'increase', amount: 0.15}
    $table->integer('priority')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('price_recommendations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('site_id');
    $table->foreignId('box_id');
    $table->decimal('current_price', 10, 2);
    $table->decimal('recommended_price', 10, 2);
    $table->decimal('confidence_score', 5, 2); // 0-100
    $table->decimal('expected_revenue_impact', 10, 2);
    $table->json('reasoning');
    $table->enum('status', ['pending', 'approved', 'rejected', 'applied'])->default('pending');
    $table->timestamp('applied_at')->nullable();
    $table->timestamps();
});

Schema::create('price_changes_history', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('box_id');
    $table->foreignId('contract_id')->nullable();
    $table->decimal('old_price', 10, 2);
    $table->decimal('new_price', 10, 2);
    $table->decimal('change_percentage', 5, 2);
    $table->enum('change_reason', ['optimizer', 'manual', 'contract', 'promotion']);
    $table->json('metadata');
    $table->foreignId('user_id')->nullable(); // Qui a appliqué
    $table->timestamp('effective_date');
    $table->timestamps();
});

Schema::create('pricing_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('site_id');
    $table->date('date');
    $table->decimal('avg_occupancy_rate', 5, 2);
    $table->decimal('avg_price_per_sqm', 10, 2);
    $table->decimal('revenue_per_available_unit', 10, 2); // RevPAU
    $table->integer('price_increases_count')->default(0);
    $table->integer('price_decreases_count')->default(0);
    $table->decimal('optimizer_revenue_impact', 10, 2);
    $table->timestamps();
});
```

### API Endpoints

```php
// routes/api.php - Tenant
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Price Optimizer
    Route::prefix('price-optimizer')->group(function () {
        Route::get('recommendations', [PriceOptimizerController::class, 'getRecommendations']);
        Route::get('recommendations/{site}', [PriceOptimizerController::class, 'getSiteRecommendations']);
        Route::post('recommendations/{recommendation}/apply', [PriceOptimizerController::class, 'applyRecommendation']);
        Route::post('recommendations/{recommendation}/reject', [PriceOptimizerController::class, 'rejectRecommendation']);
        Route::post('recommendations/apply-batch', [PriceOptimizerController::class, 'applyBatch']);

        // Rules
        Route::get('rules', [PriceOptimizerController::class, 'getRules']);
        Route::post('rules', [PriceOptimizerController::class, 'createRule']);
        Route::put('rules/{rule}', [PriceOptimizerController::class, 'updateRule']);
        Route::delete('rules/{rule}', [PriceOptimizerController::class, 'deleteRule']);

        // Analytics
        Route::get('analytics', [PriceOptimizerController::class, 'getAnalytics']);
        Route::get('impact', [PriceOptimizerController::class, 'getImpact']);
        Route::get('history', [PriceOptimizerController::class, 'getHistory']);
    });
});
```

### Vue.js Component

```vue
<!-- resources/js/Pages/Tenant/PriceOptimizer/Dashboard.vue -->
<template>
  <TenantLayout>
    <Head title="Price Optimizer" />

    <div class="space-y-6">
      <!-- Header -->
      <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Price Optimizer</h1>
        <div class="flex gap-3">
          <button @click="generateRecommendations" class="btn btn-secondary">
            <RefreshIcon class="w-5 h-5 mr-2" />
            Générer Recommandations
          </button>
          <button @click="applyAllRecommendations" class="btn btn-primary">
            <CheckIcon class="w-5 h-5 mr-2" />
            Appliquer Tout
          </button>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-4 gap-6">
        <StatsCard
          title="Recommandations Actives"
          :value="stats.pending_recommendations"
          icon="ClipboardListIcon"
          trend="+12%"
        />
        <StatsCard
          title="Impact Revenu Estimé"
          :value="`${stats.estimated_revenue_impact}€/mois`"
          icon="CurrencyEuroIcon"
          trend="+8.5%"
        />
        <StatsCard
          title="Taux d'Occupation Moyen"
          :value="`${stats.avg_occupancy}%`"
          icon="ChartBarIcon"
          :trend="stats.occupancy_trend"
        />
        <StatsCard
          title="Prix Moyen/m²"
          :value="`${stats.avg_price_sqm}€`"
          icon="TrendingUpIcon"
          trend="+5.2%"
        />
      </div>

      <!-- Recommendations Table -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-xl font-semibold">Recommandations de Prix</h2>
          <div class="flex gap-2">
            <select v-model="filters.site" class="input">
              <option value="">Tous les sites</option>
              <option v-for="site in sites" :key="site.id" :value="site.id">
                {{ site.name }}
              </option>
            </select>
            <select v-model="filters.status" class="input">
              <option value="pending">En attente</option>
              <option value="approved">Approuvées</option>
              <option value="rejected">Rejetées</option>
            </select>
          </div>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>Box</th>
              <th>Prix Actuel</th>
              <th>Prix Recommandé</th>
              <th>Impact</th>
              <th>Confiance</th>
              <th>Raison</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="rec in recommendations" :key="rec.id">
              <td>
                <div class="font-medium">{{ rec.box.reference }}</div>
                <div class="text-sm text-gray-500">{{ rec.site.name }}</div>
              </td>
              <td class="font-mono">{{ rec.current_price }}€</td>
              <td class="font-mono font-bold">
                <span :class="priceChangeClass(rec)">
                  {{ rec.recommended_price }}€
                </span>
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <TrendingUpIcon
                    v-if="rec.expected_revenue_impact > 0"
                    class="w-4 h-4 text-green-500"
                  />
                  <TrendingDownIcon
                    v-else
                    class="w-4 h-4 text-red-500"
                  />
                  <span :class="impactClass(rec)">
                    {{ rec.expected_revenue_impact > 0 ? '+' : '' }}{{ rec.expected_revenue_impact }}€/mois
                  </span>
                </div>
              </td>
              <td>
                <ConfidenceBadge :score="rec.confidence_score" />
              </td>
              <td>
                <div class="text-sm max-w-xs">
                  {{ rec.reasoning.summary }}
                </div>
              </td>
              <td>
                <div class="flex gap-2">
                  <button
                    @click="applyRecommendation(rec.id)"
                    class="btn btn-sm btn-success"
                  >
                    Appliquer
                  </button>
                  <button
                    @click="rejectRecommendation(rec.id)"
                    class="btn btn-sm btn-ghost"
                  >
                    Rejeter
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pricing Rules -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-xl font-semibold">Règles de Tarification</h2>
          <button @click="createRule" class="btn btn-primary">
            <PlusIcon class="w-5 h-5 mr-2" />
            Nouvelle Règle
          </button>
        </div>

        <div class="space-y-3">
          <PricingRule
            v-for="rule in pricingRules"
            :key="rule.id"
            :rule="rule"
            @edit="editRule"
            @delete="deleteRule"
            @toggle="toggleRule"
          />
        </div>
      </div>

      <!-- Analytics Chart -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-xl font-semibold">Impact Price Optimizer</h2>
        </div>
        <RevenueImpactChart :data="chartData" />
      </div>
    </div>
  </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  recommendations: Array,
  stats: Object,
  sites: Array,
  pricingRules: Array,
  chartData: Object
});

const filters = ref({
  site: '',
  status: 'pending'
});

const generateRecommendations = async () => {
  // Call API to generate new recommendations
  await axios.post('/api/price-optimizer/recommendations/generate');
  // Reload data
};

const applyRecommendation = async (id) => {
  await axios.post(`/api/price-optimizer/recommendations/${id}/apply`);
  // Update UI
};

const applyAllRecommendations = async () => {
  const ids = props.recommendations.map(r => r.id);
  await axios.post('/api/price-optimizer/recommendations/apply-batch', { ids });
};

const priceChangeClass = (rec) => {
  const change = rec.recommended_price - rec.current_price;
  return change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-600';
};
</script>
```

### Commande Artisan

```php
// app/Console/Commands/GeneratePriceRecommendations.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PriceOptimizerService;
use App\Models\Site;

class GeneratePriceRecommendations extends Command
{
    protected $signature = 'price:optimize {--site=} {--auto-apply}';
    protected $description = 'Generate price recommendations for all sites';

    public function handle(PriceOptimizerService $optimizer)
    {
        $this->info('Generating price recommendations...');

        $sites = $this->option('site')
            ? Site::where('id', $this->option('site'))->get()
            : Site::where('is_active', true)->get();

        $bar = $this->output->createProgressBar($sites->count());

        foreach ($sites as $site) {
            $recommendations = $optimizer->generateRecommendations($site);

            if ($this->option('auto-apply')) {
                foreach ($recommendations as $rec) {
                    if ($rec->confidence_score > 80) {
                        $optimizer->applyRecommendation($rec);
                    }
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done!');
    }
}
```

### Scheduler

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    // Générer recommandations quotidiennes
    $schedule->command('price:optimize')->dailyAt('02:00');

    // Auto-appliquer les recommandations haute confiance
    $schedule->command('price:optimize --auto-apply')
        ->weeklyOn(1, '03:00') // Lundi 3h
        ->when(function () {
            return Setting::get('price_optimizer_auto_apply', false);
        });
}
```

---

## PHASE 2: XPRESSCOLLECT - COLLECTIONS AUTOMATISÉES (4-6 semaines)

### Objectif
Système complet de relances automatiques multi-canal pour impayés.

### Architecture

```php
// app/Services/CollectionsService.php
class CollectionsService
{
    public function scanDelinquentAccounts(): Collection
    {
        return Contract::query()
            ->where('status', 'active')
            ->whereHas('invoices', function ($query) {
                $query->where('status', 'overdue')
                    ->where('due_date', '<', now());
            })
            ->with(['customer', 'invoices', 'site'])
            ->get()
            ->map(function ($contract) {
                return $this->createCollectionCase($contract);
            });
    }

    private function createCollectionCase(Contract $contract): CollectionCase
    {
        $overdueAmount = $contract->invoices()
            ->where('status', 'overdue')
            ->sum('total_amount');

        $daysPastDue = $contract->invoices()
            ->where('status', 'overdue')
            ->min('due_date')
            ->diffInDays(now());

        return CollectionCase::firstOrCreate(
            ['contract_id' => $contract->id],
            [
                'tenant_id' => $contract->tenant_id,
                'customer_id' => $contract->customer_id,
                'overdue_amount' => $overdueAmount,
                'days_past_due' => $daysPastDue,
                'status' => 'active',
                'next_action_date' => now()->addDay(),
                'next_action_type' => $this->determineNextAction($daysPastDue)
            ]
        );
    }

    private function determineNextAction(int $daysPastDue): string
    {
        return match(true) {
            $daysPastDue >= 1 && $daysPastDue < 3 => 'sms_friendly',
            $daysPastDue >= 3 && $daysPastDue < 7 => 'email_reminder',
            $daysPastDue >= 7 && $daysPastDue < 10 => 'sms_urgent',
            $daysPastDue >= 10 && $daysPastDue < 14 => 'phone_call',
            $daysPastDue >= 14 => 'final_notice',
            default => 'sms_friendly'
        };
    }

    public function executeCollectionActions(): void
    {
        $cases = CollectionCase::query()
            ->where('status', 'active')
            ->where('next_action_date', '<=', now())
            ->get();

        foreach ($cases as $case) {
            $this->executeAction($case);
        }
    }

    private function executeAction(CollectionCase $case): void
    {
        $action = match($case->next_action_type) {
            'sms_friendly' => $this->sendFriendlySMS($case),
            'email_reminder' => $this->sendEmailReminder($case),
            'sms_urgent' => $this->sendUrgentSMS($case),
            'phone_call' => $this->makeRoboCall($case),
            'final_notice' => $this->sendFinalNotice($case),
        };

        // Log action
        CollectionAction::create([
            'collection_case_id' => $case->id,
            'action_type' => $case->next_action_type,
            'status' => 'completed',
            'sent_at' => now()
        ]);

        // Schedule next action
        $case->update([
            'next_action_date' => $this->calculateNextActionDate($case),
            'next_action_type' => $this->determineNextAction($case->days_past_due + 1)
        ]);
    }

    private function sendFriendlySMS(CollectionCase $case): void
    {
        $message = "Bonjour {$case->customer->first_name},

Petit rappel amical: votre paiement de {$case->overdue_amount}€ pour votre box n'a pas encore été reçu.

Payez facilement en ligne: {$this->getPaymentLink($case)}

Merci!
{$case->site->name}";

        SMS::send($case->customer->phone, $message);
    }

    private function sendUrgentSMS(CollectionCase $case): void
    {
        $message = "URGENT - {$case->customer->first_name},

Votre paiement de {$case->overdue_amount}€ est en retard de {$case->days_past_due} jours.

Pour éviter la suspension d'accès, payez maintenant: {$this->getPaymentLink($case)}

{$case->site->name}";

        SMS::send($case->customer->phone, $message);
    }

    private function makeRoboCall(CollectionCase $case): void
    {
        // Intégration Twilio/Vonage pour appels automatisés
        $twiml = "
        <Response>
            <Say language='fr-FR'>
                Bonjour {$case->customer->first_name}.
                Ceci est un rappel concernant votre paiement en retard de {$case->overdue_amount} euros.
                Pour payer maintenant, appuyez sur 1.
                Pour parler à un conseiller, appuyez sur 2.
            </Say>
            <Gather numDigits='1' action='/api/collections/ivr-response/{$case->id}'>
            </Gather>
        </Response>";

        Twilio::call($case->customer->phone, $twiml);
    }
}
```

### Database Schema

```php
// Migration: create_collections_tables.php
Schema::create('collection_cases', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('customer_id');
    $table->foreignId('contract_id');
    $table->foreignId('site_id');
    $table->decimal('overdue_amount', 10, 2);
    $table->integer('days_past_due');
    $table->enum('status', ['active', 'resolved', 'escalated', 'written_off'])->default('active');
    $table->timestamp('next_action_date');
    $table->string('next_action_type');
    $table->integer('contact_attempts')->default(0);
    $table->timestamp('last_contact_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();

    $table->index(['status', 'next_action_date']);
});

Schema::create('collection_actions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('collection_case_id');
    $table->enum('action_type', ['sms_friendly', 'email_reminder', 'sms_urgent', 'phone_call', 'final_notice', 'legal_notice']);
    $table->enum('status', ['pending', 'completed', 'failed']);
    $table->text('message_content')->nullable();
    $table->json('metadata')->nullable(); // Twilio response, etc.
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('opened_at')->nullable();
    $table->timestamp('clicked_at')->nullable();
    $table->timestamps();
});

Schema::create('collection_workflows', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->string('name');
    $table->text('description')->nullable();
    $table->json('steps'); // Configuration des étapes
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->timestamps();
});

Schema::create('payment_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id');
    $table->foreignId('collection_case_id');
    $table->foreignId('customer_id');
    $table->decimal('total_amount', 10, 2);
    $table->integer('installments');
    $table->decimal('installment_amount', 10, 2);
    $table->enum('status', ['active', 'completed', 'defaulted'])->default('active');
    $table->date('start_date');
    $table->date('end_date');
    $table->timestamps();
});

Schema::create('payment_plan_installments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('payment_plan_id');
    $table->integer('installment_number');
    $table->decimal('amount', 10, 2);
    $table->date('due_date');
    $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
    $table->timestamp('paid_at')->nullable();
    $table->timestamps();
});
```

### Workflow Configuration

```json
// Collection Workflow Example
{
  "name": "Standard Collection Workflow",
  "steps": [
    {
      "day": 1,
      "action": "sms_friendly",
      "template": "collection_sms_friendly",
      "channels": ["sms"]
    },
    {
      "day": 3,
      "action": "email_reminder",
      "template": "collection_email_1",
      "channels": ["email", "sms"]
    },
    {
      "day": 7,
      "action": "sms_urgent",
      "template": "collection_sms_urgent",
      "channels": ["sms"]
    },
    {
      "day": 10,
      "action": "phone_call",
      "template": "collection_robo_call",
      "channels": ["phone"]
    },
    {
      "day": 14,
      "action": "final_notice",
      "template": "collection_final_notice",
      "channels": ["email", "sms", "postal"]
    },
    {
      "day": 21,
      "action": "legal_notice",
      "template": "collection_legal",
      "channels": ["postal", "email"]
    }
  ],
  "auto_stop_on_payment": true,
  "escalation_threshold_days": 30,
  "escalation_threshold_amount": 500
}
```

### Vue Component

```vue
<!-- resources/js/Pages/Tenant/Collections/Dashboard.vue -->
<template>
  <TenantLayout>
    <Head title="Collections Manager" />

    <div class="space-y-6">
      <!-- Stats -->
      <div class="grid grid-cols-4 gap-6">
        <StatsCard
          title="Cas Actifs"
          :value="stats.active_cases"
          icon="ExclamationCircleIcon"
          color="red"
        />
        <StatsCard
          title="Montant Total Impayé"
          :value="`${stats.total_overdue}€`"
          icon="CurrencyEuroIcon"
          color="orange"
        />
        <StatsCard
          title="Taux de Récupération"
          :value="`${stats.recovery_rate}%`"
          icon="TrendingUpIcon"
          color="green"
        />
        <StatsCard
          title="Temps Moyen Récupération"
          :value="`${stats.avg_recovery_days}j`"
          icon="ClockIcon"
        />
      </div>

      <!-- Collection Cases -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-xl font-semibold">Cas de Relance Actifs</h2>
          <button @click="scanDelinquentAccounts" class="btn btn-primary">
            <RefreshIcon class="w-5 h-5 mr-2" />
            Scanner Comptes
          </button>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>Client</th>
              <th>Montant</th>
              <th>Jours Retard</th>
              <th>Tentatives</th>
              <th>Prochaine Action</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="case in cases" :key="case.id">
              <td>
                <div class="font-medium">{{ case.customer.full_name }}</div>
                <div class="text-sm text-gray-500">{{ case.customer.email }}</div>
              </td>
              <td class="font-mono text-red-600 font-bold">
                {{ case.overdue_amount }}€
              </td>
              <td>
                <UrgencyBadge :days="case.days_past_due" />
              </td>
              <td>{{ case.contact_attempts }}</td>
              <td>
                <div class="text-sm">
                  <div class="font-medium">{{ getActionLabel(case.next_action_type) }}</div>
                  <div class="text-gray-500">{{ formatDate(case.next_action_date) }}</div>
                </div>
              </td>
              <td>
                <StatusBadge :status="case.status" />
              </td>
              <td>
                <div class="flex gap-2">
                  <button @click="executeNow(case.id)" class="btn btn-sm btn-primary">
                    Exécuter
                  </button>
                  <button @click="offerPaymentPlan(case.id)" class="btn btn-sm btn-secondary">
                    Plan Paiement
                  </button>
                  <button @click="markResolved(case.id)" class="btn btn-sm btn-success">
                    Résolu
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Workflow Settings -->
      <div class="card">
        <div class="card-header">
          <h2 class="text-xl font-semibold">Workflows de Relance</h2>
          <button @click="createWorkflow" class="btn btn-primary">
            Nouveau Workflow
          </button>
        </div>

        <WorkflowBuilder :workflows="workflows" />
      </div>
    </div>
  </TenantLayout>
</template>
```

---

## PHASE 3: API PUBLIQUE (8 semaines)

### Architecture REST API

```php
// routes/api_public.php
Route::prefix('v1')->middleware(['api', 'api_key'])->group(function () {

    // Sites
    Route::get('sites', [API\SiteController::class, 'index']);
    Route::get('sites/{site}', [API\SiteController::class, 'show']);

    // Boxes & Availability
    Route::get('sites/{site}/boxes', [API\BoxController::class, 'index']);
    Route::get('sites/{site}/boxes/availability', [API\BoxController::class, 'availability']);
    Route::get('boxes/{box}', [API\BoxController::class, 'show']);

    // Customers
    Route::get('customers', [API\CustomerController::class, 'index']);
    Route::post('customers', [API\CustomerController::class, 'store']);
    Route::get('customers/{customer}', [API\CustomerController::class, 'show']);
    Route::put('customers/{customer}', [API\CustomerController::class, 'update']);

    // Contracts
    Route::get('contracts', [API\ContractController::class, 'index']);
    Route::post('contracts', [API\ContractController::class, 'store']);
    Route::get('contracts/{contract}', [API\ContractController::class, 'show']);
    Route::put('contracts/{contract}', [API\ContractController::class, 'update']);

    // Invoices
    Route::get('invoices', [API\InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [API\InvoiceController::class, 'show']);

    // Payments
    Route::post('payments', [API\PaymentController::class, 'store']);
    Route::get('payments/{payment}', [API\PaymentController::class, 'show']);

    // Webhooks
    Route::get('webhooks', [API\WebhookController::class, 'index']);
    Route::post('webhooks', [API\WebhookController::class, 'store']);
    Route::put('webhooks/{webhook}', [API\WebhookController::class, 'update']);
    Route::delete('webhooks/{webhook}', [API\WebhookController::class, 'destroy']);
});
```

### OpenAPI Documentation

```yaml
# public/api-docs/openapi.yaml
openapi: 3.0.0
info:
  title: Boxibox API
  description: API publique pour intégration Boxibox
  version: 1.0.0
  contact:
    name: Support Boxibox
    email: api@boxibox.com

servers:
  - url: https://api.boxibox.com/v1
    description: Production
  - url: https://sandbox-api.boxibox.com/v1
    description: Sandbox

security:
  - ApiKeyAuth: []

paths:
  /sites:
    get:
      summary: Liste des sites
      tags: [Sites]
      parameters:
        - name: page
          in: query
          schema:
            type: integer
        - name: per_page
          in: query
          schema:
            type: integer
            maximum: 100
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: array
                    items:
                      $ref: '#/components/schemas/Site'
                  meta:
                    $ref: '#/components/schemas/PaginationMeta'

  /sites/{site_id}/boxes/availability:
    get:
      summary: Disponibilité des boxes
      tags: [Boxes]
      parameters:
        - name: site_id
          in: path
          required: true
          schema:
            type: integer
        - name: box_type_id
          in: query
          schema:
            type: integer
        - name: start_date
          in: query
          schema:
            type: string
            format: date
      responses:
        '200':
          description: Success
          content:
            application/json:
              schema:
                type: object
                properties:
                  available_boxes:
                    type: integer
                  boxes:
                    type: array
                    items:
                      $ref: '#/components/schemas/Box'

components:
  securitySchemes:
    ApiKeyAuth:
      type: apiKey
      in: header
      name: X-API-Key

  schemas:
    Site:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
        address:
          type: string
        city:
          type: string
        postal_code:
          type: string
        phone:
          type: string
        email:
          type: string
        latitude:
          type: number
        longitude:
          type: number
        opening_hours:
          type: object
        features:
          type: array
          items:
            type: string

    Box:
      type: object
      properties:
        id:
          type: integer
        reference:
          type: string
        box_type:
          $ref: '#/components/schemas/BoxType'
        status:
          type: string
          enum: [available, occupied, reserved, maintenance]
        monthly_price:
          type: number
        size_sqm:
          type: number
        floor:
          type: integer
        features:
          type: array
          items:
            type: string
```

### Developer Portal

Créer un portail développeur avec:

1. **Documentation Interactive** (Swagger UI)
2. **Code Examples** (cURL, PHP, JavaScript, Python)
3. **Sandbox Environment**
4. **API Key Management**
5. **Usage Analytics**
6. **Support Forum**

---

## TIMELINE ET RESSOURCES

### Sprint Planning (Sprints de 2 semaines)

#### Sprints 1-4: Price Optimizer (8 semaines)
- **Sprint 1**: Database schema, modèles, service de base
- **Sprint 2**: Algorithme pricing, règles, recommandations
- **Sprint 3**: API endpoints, Vue components
- **Sprint 4**: Analytics, tests, documentation

**Ressources:** 1 Backend Dev + 1 Frontend Dev + 1 QA

#### Sprints 5-7: XpressCollect (6 semaines)
- **Sprint 5**: Database, modèles, workflows
- **Sprint 6**: Intégrations SMS/Email/Twilio, automation
- **Sprint 7**: Dashboard, reporting, tests

**Ressources:** 1 Backend Dev + 1 Frontend Dev + 1 Devops (pour Twilio)

#### Sprints 8-11: API Publique (8 semaines)
- **Sprint 8**: Architecture API, authentication, rate limiting
- **Sprint 9**: Endpoints core (sites, boxes, customers)
- **Sprint 10**: Webhooks, OpenAPI docs
- **Sprint 11**: Developer portal, SDKs, sandbox

**Ressources:** 1 Backend Dev + 1 Frontend Dev (portal) + 1 Tech Writer (docs)

### Budget Estimé

- **Développement**: 22 semaines × 3 personnes = 66 semaines-personne
- **Coût moyen**: 66 × 800€/semaine = **52,800€**
- **Services tiers**:
  - Twilio (robo-calls): ~200€/mois
  - SMS provider: ~150€/mois
  - Sandbox infrastructure: ~100€/mois
- **Total Year 1**: ~57,000€

---

## MÉTRIQUES DE SUCCÈS

### Price Optimizer
- ✅ Recommandations générées quotidiennement
- ✅ >80% confiance sur recommandations
- ✅ +10-15% revenue impact mesuré
- ✅ <5% augmentation churn après price changes
- ✅ 90%+ adoption utilisateurs

### XpressCollect
- ✅ Scan quotidien automatique 100% fiable
- ✅ >70% taux de récupération
- ✅ Réduction 50% temps manager sur collections
- ✅ -30% montant moyen impayé
- ✅ Délai moyen récupération <14 jours

### API Publique
- ✅ 99.9% uptime
- ✅ <200ms latency p95
- ✅ Documentation complète et à jour
- ✅ 10+ intégrations partenaires Year 1
- ✅ 100+ API calls/jour actifs

---

**Document préparé pour:** Équipe Technique Boxibox
**Prochaine étape:** Review avec CTO et priorisation finale
