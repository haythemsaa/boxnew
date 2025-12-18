<template>
    <TenantLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <Link href="/tenant/analytics/ai" class="text-sm text-gray-500 hover:text-gray-700 mb-2 inline-block">
                    ← Retour aux insights IA
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="text-3xl">💎</span>
                    Optimisation des Prix
                </h1>
                <p class="text-gray-600 mt-1">
                    Maximisez vos revenus avec des prix optimisés par l'IA
                </p>
            </div>

            <!-- Summary Banner -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl p-6 mb-6 text-white">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <div class="text-blue-200 text-sm">Boxes analysées</div>
                        <div class="text-3xl font-bold">{{ summary.boxes_analyzed }}</div>
                    </div>
                    <div>
                        <div class="text-blue-200 text-sm">À optimiser</div>
                        <div class="text-3xl font-bold">{{ summary.boxes_to_optimize }}</div>
                    </div>
                    <div>
                        <div class="text-blue-200 text-sm">Revenus potentiels/an</div>
                        <div class="text-3xl font-bold">+{{ formatCurrency(summary.total_potential_revenue) }}</div>
                    </div>
                    <div>
                        <div class="text-blue-200 text-sm">Ajustement moyen</div>
                        <div class="text-3xl font-bold">{{ summary.average_adjustment > 0 ? '+' : '' }}{{ summary.average_adjustment }}%</div>
                    </div>
                </div>
            </div>

            <!-- Optimizations Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">
                        Recommandations de prix
                    </h3>
                    <button
                        @click="applyAll"
                        class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Appliquer toutes les recommandations
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Box</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix actuel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix optimal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ajustement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Impact/an</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facteurs</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="opt in optimizations" :key="opt.box_id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-medium text-gray-900">{{ opt.box_code }}</div>
                                    <div class="text-sm text-gray-500">{{ opt.box_size }} m²</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ opt.site_name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-gray-900 font-medium">{{ opt.current_price }}€</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-blue-600 font-bold">{{ opt.optimal_price }}€</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 py-1 text-sm font-medium rounded"
                                        :class="opt.adjustment_percent > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ opt.adjustment_percent > 0 ? '+' : '' }}{{ opt.adjustment_percent }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="font-medium"
                                        :class="opt.estimated_revenue_impact > 0 ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ opt.estimated_revenue_impact > 0 ? '+' : '' }}{{ formatCurrency(opt.estimated_revenue_impact) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="(value, factor) in opt.adjustments"
                                            :key="factor"
                                            class="px-2 py-0.5 text-xs rounded"
                                            :class="value > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ formatAdjustmentFactor(factor) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="applyPrice(opt)"
                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                                    >
                                        Appliquer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="optimizations.length === 0" class="p-8 text-center text-gray-500">
                    Aucune optimisation de prix suggérée. Vos prix sont déjà optimaux !
                </div>
            </div>

            <!-- Confidence Note -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">📊</span>
                    <div>
                        <h4 class="font-medium text-blue-800">Confiance: 85%</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Les recommandations sont basées sur le taux d'occupation, la saisonnalité et les tendances du marché.
                            Ajustez selon votre connaissance locale du marché.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    optimizations: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        required: true,
    },
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value);
};

const formatAdjustmentFactor = (factor) => {
    const labels = {
        low_occupation: 'Occupation faible',
        high_demand: 'Forte demande',
        high_demand_box: 'Box populaire',
        high_season: 'Haute saison',
        low_season: 'Basse saison',
    };
    return labels[factor] || factor;
};

const applyPrice = (opt) => {
    if (confirm(`Voulez-vous appliquer le prix ${opt.optimal_price}€ à la box ${opt.box_code} ?`)) {
        router.post(`/tenant/analytics/ai/pricing/${opt.box_id}/apply`, {
            new_price: opt.optimal_price,
        });
    }
};

const applyAll = () => {
    if (confirm('Voulez-vous appliquer toutes les recommandations de prix ?')) {
        // Apply all optimizations
        props.optimizations.forEach(opt => {
            router.post(`/tenant/analytics/ai/pricing/${opt.box_id}/apply`, {
                new_price: opt.optimal_price,
            }, { preserveScroll: true });
        });
    }
};
</script>
