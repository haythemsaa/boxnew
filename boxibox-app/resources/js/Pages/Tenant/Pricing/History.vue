<template>
    <Head :title="$t('pricing.history')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <svg class="w-8 h-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Historique des Prix
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Suivi des ajustements de prix et analyse des performances
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 flex items-center gap-3">
                        <select v-model="filters.period" @change="loadHistory"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="7">7 derniers jours</option>
                            <option value="30">30 derniers jours</option>
                            <option value="90">3 derniers mois</option>
                            <option value="365">Cette année</option>
                        </select>
                        <button @click="exportHistory"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Exporter
                        </button>
                    </div>
                </div>

                <!-- Performance Summary -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total ajustements</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">{{ summary.total_adjustments }}</div>
                        <div class="mt-1 text-xs text-gray-500">Sur la période</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Ajustement moyen</div>
                        <div class="mt-2 text-3xl font-semibold" :class="summary.average_adjustment >= 0 ? 'text-green-600' : 'text-red-600'">
                            {{ summary.average_adjustment >= 0 ? '+' : '' }}{{ summary.average_adjustment }}%
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Par changement</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Impact revenus</div>
                        <div class="mt-2 text-3xl font-semibold text-emerald-600">
                            {{ formatCurrency(summary.revenue_impact) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Gain/mois estimé</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Taux de succès</div>
                        <div class="mt-2 text-3xl font-semibold text-blue-600">{{ summary.success_rate }}%</div>
                        <div class="mt-1 text-xs text-gray-500">Ajustements efficaces</div>
                    </div>
                </div>

                <!-- Price Evolution Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Évolution des prix</h3>
                        <div class="flex items-center gap-4">
                            <select v-model="chartView" @change="updateChart"
                                class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="average">Prix moyen</option>
                                <option value="by_size">Par taille</option>
                                <option value="by_site">Par site</option>
                            </select>
                        </div>
                    </div>
                    <canvas ref="evolutionChart" height="100"></canvas>
                </div>

                <!-- Adjustment Distribution -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- By Reason -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Répartition par raison</h3>
                        <canvas ref="reasonChart" height="200"></canvas>
                        <div class="mt-4 space-y-2">
                            <div v-for="reason in summary.by_reason" :key="reason.reason" class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: reason.color }"></span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ reason.label }}</span>
                                </div>
                                <span class="font-medium text-gray-900 dark:text-white">{{ reason.count }} ({{ reason.percentage }}%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- By Impact -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Répartition par impact</h3>
                        <div class="space-y-4">
                            <div v-for="impact in summary.by_impact" :key="impact.range">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ impact.label }}</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ impact.count }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all"
                                        :class="impact.color"
                                        :style="{ width: impact.percentage + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-800 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-emerald-800 dark:text-emerald-300">ROI des ajustements</div>
                                    <div class="text-2xl font-bold text-emerald-600">{{ summary.roi }}%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed History Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Historique détaillé</h3>
                            <div class="flex items-center gap-3">
                                <select v-model="filters.type" @change="loadHistory"
                                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="">Tous les types</option>
                                    <option value="automatic">Automatique</option>
                                    <option value="manual">Manuel</option>
                                    <option value="experiment">Expérience A/B</option>
                                </select>
                                <input v-model="filters.search" type="text" placeholder="Rechercher..."
                                    class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Box</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Ancien prix</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Nouveau prix</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Variation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Raison</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Impact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="adjustment in filteredAdjustments" :key="adjustment.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatDateTime(adjustment.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ adjustment.box_name }}</div>
                                        <div class="text-xs text-gray-500">{{ adjustment.site_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(adjustment.old_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(adjustment.new_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="adjustment.change_percent >= 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ adjustment.change_percent >= 0 ? '+' : '' }}{{ adjustment.change_percent }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getReasonBadgeClass(adjustment.reason)"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ adjustment.reason_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getTypeBadgeClass(adjustment.type)"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ getTypeLabel(adjustment.type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="adjustment.measured_impact !== null" class="flex items-center gap-1">
                                            <span :class="adjustment.measured_impact >= 0 ? 'text-green-600' : 'text-red-600'" class="font-medium text-sm">
                                                {{ adjustment.measured_impact >= 0 ? '+' : '' }}{{ formatCurrency(adjustment.measured_impact) }}
                                            </span>
                                            <span v-if="adjustment.impact_verified" class="text-green-500">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                        <span v-else class="text-xs text-gray-400">En attente</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <button @click="showDetails(adjustment)" class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button v-if="adjustment.can_revert" @click="revertAdjustment(adjustment)" class="text-orange-600 hover:text-orange-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="filteredAdjustments.length === 0" class="p-8 text-center text-gray-500">
                        Aucun ajustement de prix trouvé
                    </div>

                    <!-- Pagination -->
                    <div v-if="pagination.total > pagination.per_page" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">
                                Affichage de {{ pagination.from }} à {{ pagination.to }} sur {{ pagination.total }} résultats
                            </span>
                            <div class="flex items-center gap-2">
                                <button @click="changePage(pagination.current_page - 1)"
                                    :disabled="pagination.current_page === 1"
                                    class="px-3 py-1 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Précédent
                                </button>
                                <button @click="changePage(pagination.current_page + 1)"
                                    :disabled="pagination.current_page === pagination.last_page"
                                    class="px-3 py-1 text-sm border rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Suivant
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <Teleport to="body">
            <div v-if="showDetailsModal && selectedAdjustment" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showDetailsModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6 z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Détails de l'ajustement</h3>
                                <p class="text-sm text-gray-500">{{ formatDateTime(selectedAdjustment.created_at) }}</p>
                            </div>
                            <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="space-y-6">
                            <!-- Box Info -->
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-2">Box concernée</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Nom:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ selectedAdjustment.box_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Site:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ selectedAdjustment.site_name }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Taille:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ selectedAdjustment.box_size }} m²</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Occupation:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ selectedAdjustment.occupancy_at_time }}%</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Change -->
                            <div class="flex items-center justify-center gap-8">
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Ancien prix</div>
                                    <div class="text-2xl font-bold text-gray-400">{{ formatCurrency(selectedAdjustment.old_price) }}</div>
                                </div>
                                <div class="text-3xl">→</div>
                                <div class="text-center">
                                    <div class="text-sm text-gray-500">Nouveau prix</div>
                                    <div class="text-2xl font-bold text-emerald-600">{{ formatCurrency(selectedAdjustment.new_price) }}</div>
                                </div>
                            </div>

                            <!-- Factors -->
                            <div v-if="selectedAdjustment.factors">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-3">Facteurs de décision</h4>
                                <div class="space-y-2">
                                    <div v-for="(value, factor) in selectedAdjustment.factors" :key="factor"
                                        class="flex items-center justify-between text-sm p-2 bg-gray-50 dark:bg-gray-700/50 rounded">
                                        <span class="text-gray-600 dark:text-gray-400">{{ getFactorLabel(factor) }}</span>
                                        <span class="font-medium" :class="value >= 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ value >= 0 ? '+' : '' }}{{ value }}%
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Impact -->
                            <div v-if="selectedAdjustment.measured_impact !== null" class="p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg">
                                <h4 class="font-medium text-emerald-800 dark:text-emerald-300 mb-2">Impact mesuré</h4>
                                <div class="grid grid-cols-3 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500">Revenus:</span>
                                        <span class="ml-2 font-medium text-emerald-600">{{ formatCurrency(selectedAdjustment.measured_impact) }}/mois</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Occupation:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                            {{ selectedAdjustment.occupancy_change >= 0 ? '+' : '' }}{{ selectedAdjustment.occupancy_change }}%
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Conversions:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                            {{ selectedAdjustment.conversion_change >= 0 ? '+' : '' }}{{ selectedAdjustment.conversion_change }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import TenantLayout from '@/Layouts/TenantLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    adjustments: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_adjustments: 0,
            average_adjustment: 0,
            revenue_impact: 0,
            success_rate: 0,
            roi: 0,
            by_reason: [],
            by_impact: [],
        }),
    },
    chartData: {
        type: Object,
        default: () => ({
            labels: [],
            datasets: [],
        }),
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
});

const evolutionChart = ref(null);
const reasonChart = ref(null);
const showDetailsModal = ref(false);
const selectedAdjustment = ref(null);
const chartView = ref('average');

let evolutionChartInstance = null;
let reasonChartInstance = null;

const filters = ref({
    period: '30',
    type: '',
    search: '',
});

const filteredAdjustments = computed(() => {
    let result = props.adjustments;

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        result = result.filter(a =>
            a.box_name?.toLowerCase().includes(search) ||
            a.site_name?.toLowerCase().includes(search)
        );
    }

    return result;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getReasonBadgeClass = (reason) => {
    const classes = {
        occupancy: 'bg-blue-100 text-blue-800',
        demand: 'bg-green-100 text-green-800',
        competitor: 'bg-orange-100 text-orange-800',
        seasonality: 'bg-purple-100 text-purple-800',
        experiment: 'bg-pink-100 text-pink-800',
        manual: 'bg-gray-100 text-gray-800',
    };
    return classes[reason] || classes.manual;
};

const getTypeBadgeClass = (type) => {
    const classes = {
        automatic: 'bg-cyan-100 text-cyan-800',
        manual: 'bg-gray-100 text-gray-800',
        experiment: 'bg-purple-100 text-purple-800',
    };
    return classes[type] || classes.manual;
};

const getTypeLabel = (type) => {
    const labels = {
        automatic: 'Auto',
        manual: 'Manuel',
        experiment: 'A/B Test',
    };
    return labels[type] || type;
};

const getFactorLabel = (factor) => {
    const labels = {
        occupancy: 'Taux d\'occupation',
        demand: 'Demande',
        competitor: 'Concurrence',
        seasonality: 'Saisonnalité',
        day_of_week: 'Jour de la semaine',
        time_on_market: 'Durée sur le marché',
    };
    return labels[factor] || factor;
};

const loadHistory = () => {
    router.get(route('tenant.pricing.history'), {
        period: filters.value.period,
        type: filters.value.type,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const changePage = (page) => {
    router.get(route('tenant.pricing.history'), {
        page,
        period: filters.value.period,
        type: filters.value.type,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportHistory = () => {
    window.location.href = route('tenant.pricing.history') + '?export=csv&period=' + filters.value.period;
};

const showDetails = (adjustment) => {
    selectedAdjustment.value = adjustment;
    showDetailsModal.value = true;
};

const revertAdjustment = (adjustment) => {
    if (confirm(`Voulez-vous annuler cet ajustement et revenir au prix de ${formatCurrency(adjustment.old_price)} ?`)) {
        router.post(route('tenant.pricing.revert', adjustment.id));
    }
};

const updateChart = () => {
    if (evolutionChartInstance) {
        evolutionChartInstance.destroy();
    }
    initEvolutionChart();
};

const initEvolutionChart = () => {
    if (evolutionChart.value && props.chartData.labels?.length > 0) {
        evolutionChartInstance = new Chart(evolutionChart.value, {
            type: 'line',
            data: {
                labels: props.chartData.labels,
                datasets: props.chartData.datasets.map(ds => ({
                    ...ds,
                    tension: 0.4,
                    fill: false,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            callback: (value) => value + '€',
                        },
                    },
                },
            },
        });
    }
};

const initReasonChart = () => {
    if (reasonChart.value && props.summary.by_reason?.length > 0) {
        reasonChartInstance = new Chart(reasonChart.value, {
            type: 'doughnut',
            data: {
                labels: props.summary.by_reason.map(r => r.label),
                datasets: [{
                    data: props.summary.by_reason.map(r => r.count),
                    backgroundColor: props.summary.by_reason.map(r => r.color),
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
            },
        });
    }
};

watch(() => filters.value.type, loadHistory);

onMounted(() => {
    initEvolutionChart();
    initReasonChart();
});
</script>
