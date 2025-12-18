<template>
    <Head :title="$t('pricing.competitors')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <svg class="w-8 h-8 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Analyse Concurrentielle
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Surveillez les prix de vos concurrents et optimisez votre positionnement
                        </p>
                    </div>
                    <button @click="showAddModal = true"
                        class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un concurrent
                    </button>
                </div>

                <!-- Market Position Overview -->
                <div class="bg-gradient-to-r from-orange-500 to-amber-500 rounded-xl p-6 mb-8 text-white">
                    <h3 class="text-lg font-semibold mb-4">Positionnement Marché</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <div class="text-orange-100 text-sm">Votre prix moyen</div>
                            <div class="text-3xl font-bold">{{ formatCurrency(analysis.your_average_price) }}</div>
                            <div class="text-sm text-orange-100">par m²/mois</div>
                        </div>
                        <div>
                            <div class="text-orange-100 text-sm">Prix marché moyen</div>
                            <div class="text-3xl font-bold">{{ formatCurrency(analysis.market_average_price) }}</div>
                            <div class="text-sm text-orange-100">par m²/mois</div>
                        </div>
                        <div>
                            <div class="text-orange-100 text-sm">Votre position</div>
                            <div class="text-3xl font-bold">{{ analysis.position_label }}</div>
                            <div class="text-sm" :class="analysis.price_difference >= 0 ? 'text-green-200' : 'text-red-200'">
                                {{ analysis.price_difference >= 0 ? '+' : '' }}{{ analysis.price_difference }}% vs marché
                            </div>
                        </div>
                        <div>
                            <div class="text-orange-100 text-sm">Opportunité</div>
                            <div class="text-3xl font-bold">{{ formatCurrency(analysis.revenue_opportunity) }}</div>
                            <div class="text-sm text-orange-100">revenus potentiels/mois</div>
                        </div>
                    </div>
                </div>

                <!-- Price Comparison by Size -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Chart -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Comparaison par taille</h3>
                        <canvas ref="comparisonChart" height="250"></canvas>
                    </div>

                    <!-- Price Index -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Indice de prix par catégorie</h3>
                        <div class="space-y-4">
                            <div v-for="category in priceIndex" :key="category.name">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ category.name }}</span>
                                    <span class="text-sm" :class="getIndexColor(category.index)">
                                        {{ category.index }}% du marché
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                        <div class="h-3 rounded-full transition-all duration-500"
                                            :class="getIndexBarColor(category.index)"
                                            :style="{ width: Math.min(category.index, 150) + '%', maxWidth: '100%' }"></div>
                                    </div>
                                    <!-- Market average indicator -->
                                    <div class="absolute top-0 left-[66.66%] w-0.5 h-3 bg-gray-900 dark:bg-white"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500 mt-1">
                                    <span>Vous: {{ formatCurrency(category.your_price) }}/m²</span>
                                    <span>Marché: {{ formatCurrency(category.market_price) }}/m²</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Competitors List -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-8">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Concurrents suivis</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Concurrent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Distance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix petit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix moyen</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix grand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">vs Vous</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dernière MAJ</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="competitor in competitors" :key="competitor.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                                                <span class="text-orange-600 font-semibold">{{ competitor.name.charAt(0) }}</span>
                                            </div>
                                            <div class="ml-3">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ competitor.name }}</div>
                                                <div class="text-sm text-gray-500">{{ competitor.address }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                        {{ competitor.distance_km }} km
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(competitor.price_small) }}/m²</div>
                                        <div class="text-xs" :class="getPriceDiffColor(competitor.diff_small)">
                                            {{ competitor.diff_small >= 0 ? '+' : '' }}{{ competitor.diff_small }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(competitor.price_medium) }}/m²</div>
                                        <div class="text-xs" :class="getPriceDiffColor(competitor.diff_medium)">
                                            {{ competitor.diff_medium >= 0 ? '+' : '' }}{{ competitor.diff_medium }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(competitor.price_large) }}/m²</div>
                                        <div class="text-xs" :class="getPriceDiffColor(competitor.diff_large)">
                                            {{ competitor.diff_large >= 0 ? '+' : '' }}{{ competitor.diff_large }}%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getOverallDiffClass(competitor.overall_diff)"
                                            class="px-2 py-1 text-xs font-medium rounded-full">
                                            {{ competitor.overall_diff >= 0 ? 'Plus cher' : 'Moins cher' }}
                                            ({{ Math.abs(competitor.overall_diff) }}%)
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(competitor.updated_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <button @click="editCompetitor(competitor)" class="text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button @click="deleteCompetitor(competitor)" class="text-red-600 hover:text-red-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-if="competitors.length === 0" class="p-8 text-center text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <p class="mt-2">Aucun concurrent suivi. Ajoutez vos premiers concurrents !</p>
                    </div>
                </div>

                <!-- AI Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Recommandations IA
                    </h3>
                    <div class="space-y-4">
                        <div v-for="(rec, index) in recommendations" :key="index"
                            class="p-4 rounded-lg border-l-4"
                            :class="getRecommendationClass(rec.type)">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ rec.title }}</h4>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ rec.description }}</p>
                                    <div class="mt-2 flex items-center gap-4 text-sm">
                                        <span class="text-green-600 font-medium">Impact: {{ rec.impact }}</span>
                                        <span class="text-gray-500">Confiance: {{ rec.confidence }}%</span>
                                    </div>
                                </div>
                                <button @click="applyRecommendation(rec)"
                                    class="px-3 py-1.5 bg-orange-600 text-white text-sm rounded hover:bg-orange-700">
                                    Appliquer
                                </button>
                            </div>
                        </div>

                        <div v-if="recommendations.length === 0" class="text-center text-gray-500 py-4">
                            Ajoutez des concurrents pour obtenir des recommandations personnalisées.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Competitor Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>

                    <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full p-6 z-10">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-6">
                            {{ editingCompetitor ? 'Modifier le concurrent' : 'Ajouter un concurrent' }}
                        </h3>

                        <form @submit.prevent="saveCompetitor">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                                    <input v-model="competitorForm.name" type="text" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse</label>
                                    <input v-model="competitorForm.address" type="text"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Distance (km)</label>
                                        <input v-model.number="competitorForm.distance_km" type="number" step="0.1"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site Web</label>
                                        <input v-model="competitorForm.website" type="url"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Prix par m² / mois</h4>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Petit (&lt;5m²)</label>
                                            <input v-model.number="competitorForm.price_small" type="number" step="0.01"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Moyen (5-15m²)</label>
                                            <input v-model.number="competitorForm.price_medium" type="number" step="0.01"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-500 mb-1">Grand (&gt;15m²)</label>
                                            <input v-model.number="competitorForm.price_large" type="number" step="0.01"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                                    <textarea v-model="competitorForm.notes" rows="2"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="closeModal"
                                    class="px-4 py-2 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Annuler
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50">
                                    {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import TenantLayout from '@/Layouts/TenantLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    competitors: {
        type: Array,
        default: () => [],
    },
    analysis: {
        type: Object,
        default: () => ({
            your_average_price: 0,
            market_average_price: 0,
            position_label: 'N/A',
            price_difference: 0,
            revenue_opportunity: 0,
        }),
    },
    priceIndex: {
        type: Array,
        default: () => [],
    },
    recommendations: {
        type: Array,
        default: () => [],
    },
});

const comparisonChart = ref(null);
const showAddModal = ref(false);
const editingCompetitor = ref(null);
const isSubmitting = ref(false);

const competitorForm = ref({
    name: '',
    address: '',
    distance_km: 0,
    website: '',
    price_small: 0,
    price_medium: 0,
    price_large: 0,
    notes: '',
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 2,
    }).format(value || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
};

const getIndexColor = (index) => {
    if (index > 110) return 'text-green-600 font-medium';
    if (index < 90) return 'text-red-600 font-medium';
    return 'text-gray-600';
};

const getIndexBarColor = (index) => {
    if (index > 110) return 'bg-green-500';
    if (index < 90) return 'bg-red-500';
    return 'bg-blue-500';
};

const getPriceDiffColor = (diff) => {
    if (diff > 0) return 'text-green-600';
    if (diff < 0) return 'text-red-600';
    return 'text-gray-500';
};

const getOverallDiffClass = (diff) => {
    if (diff >= 0) return 'bg-green-100 text-green-800';
    return 'bg-red-100 text-red-800';
};

const getRecommendationClass = (type) => {
    const classes = {
        increase: 'border-green-500 bg-green-50 dark:bg-green-900/10',
        decrease: 'border-red-500 bg-red-50 dark:bg-red-900/10',
        maintain: 'border-blue-500 bg-blue-50 dark:bg-blue-900/10',
    };
    return classes[type] || classes.maintain;
};

const editCompetitor = (competitor) => {
    editingCompetitor.value = competitor;
    competitorForm.value = { ...competitor };
    showAddModal.value = true;
};

const closeModal = () => {
    showAddModal.value = false;
    editingCompetitor.value = null;
    competitorForm.value = {
        name: '',
        address: '',
        distance_km: 0,
        website: '',
        price_small: 0,
        price_medium: 0,
        price_large: 0,
        notes: '',
    };
};

const saveCompetitor = () => {
    isSubmitting.value = true;
    const url = editingCompetitor.value
        ? route('tenant.pricing.competitors.update', editingCompetitor.value.id)
        : route('tenant.pricing.competitors.add');

    const method = editingCompetitor.value ? 'put' : 'post';

    router[method](url, competitorForm.value, {
        onSuccess: () => {
            closeModal();
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const deleteCompetitor = (competitor) => {
    if (confirm(`Voulez-vous supprimer ${competitor.name} ?`)) {
        router.delete(route('tenant.pricing.competitors.delete', competitor.id));
    }
};

const applyRecommendation = (rec) => {
    if (confirm(`Voulez-vous appliquer cette recommandation : ${rec.title} ?`)) {
        router.post(route('tenant.pricing.apply-recommendation'), {
            recommendation_id: rec.id,
        });
    }
};

onMounted(() => {
    if (comparisonChart.value && props.priceIndex.length > 0) {
        new Chart(comparisonChart.value, {
            type: 'bar',
            data: {
                labels: props.priceIndex.map(p => p.name),
                datasets: [
                    {
                        label: 'Vos prix',
                        data: props.priceIndex.map(p => p.your_price),
                        backgroundColor: 'rgba(249, 115, 22, 0.8)',
                        borderColor: 'rgb(249, 115, 22)',
                        borderWidth: 1,
                    },
                    {
                        label: 'Prix marché',
                        data: props.priceIndex.map(p => p.market_price),
                        backgroundColor: 'rgba(107, 114, 128, 0.5)',
                        borderColor: 'rgb(107, 114, 128)',
                        borderWidth: 1,
                    },
                ],
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
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => value + '€',
                        },
                    },
                },
            },
        });
    }
});
</script>
