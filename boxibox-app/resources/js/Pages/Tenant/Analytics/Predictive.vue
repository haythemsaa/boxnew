<template>
    <AuthenticatedLayout>
        <Head title="Analytics Prédictifs" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-900">Analytics Prédictifs IA</h2>
                    <p class="mt-1 text-sm text-gray-600">Prévisions et recommandations basées sur le Machine Learning</p>
                </div>

                <!-- Occupation Forecast -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">📈 Prévision d'Occupation</h3>
                        <div class="flex space-x-2">
                            <button
                                v-for="period in [30, 60, 90]"
                                :key="period"
                                @click="forecastPeriod = period; loadForecast()"
                                :class="[
                                    forecastPeriod === period
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                    'px-3 py-1 rounded text-sm font-medium transition'
                                ]"
                            >
                                {{ period }} jours
                            </button>
                        </div>
                    </div>

                    <div v-if="occupationForecast">
                        <!-- Trend indicator -->
                        <div class="mb-4 p-4 rounded-lg" :class="{
                            'bg-green-50 border border-green-200': occupationForecast.trend === 'increasing',
                            'bg-red-50 border border-red-200': occupationForecast.trend === 'decreasing',
                            'bg-gray-50 border border-gray-200': occupationForecast.trend === 'stable'
                        }">
                            <div class="flex items-center">
                                <svg v-if="occupationForecast.trend === 'increasing'" class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                                <svg v-if="occupationForecast.trend === 'decreasing'" class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                                </svg>
                                <span class="text-sm font-medium">
                                    Tendance: <strong>{{ getTrendLabel(occupationForecast.trend) }}</strong>
                                    (Précision: {{ occupationForecast.accuracy }}%)
                                </span>
                            </div>
                        </div>

                        <!-- Chart -->
                        <canvas ref="forecastChart" class="w-full" style="max-height: 300px;"></canvas>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 mt-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Occupation actuelle</p>
                                <p class="text-2xl font-bold text-gray-900">{{ currentOccupation }}%</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Prévision J+{{ forecastPeriod }}</p>
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.predicted.toFixed(1) }}%
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Intervalle confiance</p>
                                <p class="text-sm font-medium text-gray-700">
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.lower_bound.toFixed(1) }}% -
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.upper_bound.toFixed(1) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-600">Chargement des prévisions...</p>
                    </div>
                </div>

                <!-- Churn Risk -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">⚠️ Clients à Risque de Churn</h3>

                    <div v-if="churnPredictions && churnPredictions.length > 0">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score Churn</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Risque</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Facteurs</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions recommandées</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="prediction in churnPredictions.slice(0, 10)" :key="prediction.customer_id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ prediction.customer_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2" style="width: 100px;">
                                                    <div
                                                        :class="{
                                                            'bg-red-600': prediction.churn_score >= 80,
                                                            'bg-orange-600': prediction.churn_score >= 60 && prediction.churn_score < 80,
                                                            'bg-yellow-600': prediction.churn_score >= 40 && prediction.churn_score < 60,
                                                            'bg-green-600': prediction.churn_score < 40
                                                        }"
                                                        class="h-2 rounded-full"
                                                        :style="{ width: prediction.churn_score + '%' }"
                                                    ></div>
                                                </div>
                                                <span class="text-sm font-medium">{{ prediction.churn_score.toFixed(0) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="{
                                                    'bg-red-100 text-red-800': prediction.risk_level === 'critical',
                                                    'bg-orange-100 text-orange-800': prediction.risk_level === 'high',
                                                    'bg-yellow-100 text-yellow-800': prediction.risk_level === 'medium',
                                                    'bg-green-100 text-green-800': prediction.risk_level === 'low'
                                                }"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ getRiskLabel(prediction.risk_level) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="(value, factor) in prediction.factors"
                                                    :key="factor"
                                                    class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded"
                                                >
                                                    {{ getFactorLabel(factor) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <ul class="text-xs text-gray-700 space-y-1">
                                                <li v-for="(action, idx) in prediction.recommended_actions.slice(0, 2)" :key="idx">
                                                    • {{ action }}
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Aucun client à risque élevé détecté 🎉
                    </div>
                </div>

                <!-- Upsell Opportunities -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">💰 Opportunités d'Upsell</h3>

                    <div v-if="upsellOpportunities && upsellOpportunities.length > 0">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="opportunity in upsellOpportunities.slice(0, 6)"
                                :key="opportunity.customer_id"
                                class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition"
                            >
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ opportunity.customer_name }}</h4>
                                        <p class="text-xs text-gray-500">Box: {{ opportunity.current_box }}</p>
                                    </div>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">
                                        Score: {{ opportunity.upsell_score.toFixed(0) }}
                                    </span>
                                </div>

                                <div class="mt-3 space-y-2">
                                    <div
                                        v-for="(rec, idx) in opportunity.recommendations.slice(0, 2)"
                                        :key="idx"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="text-gray-700">{{ rec.description }}</span>
                                        <span class="font-semibold text-green-600">+{{ rec.monthly_increase }}€</span>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <p class="text-sm font-medium text-gray-900">
                                        Revenu potentiel: +{{ opportunity.estimated_additional_revenue }}€/mois
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Aucune opportunité d'upsell détectée pour le moment
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    currentOccupation: {
        type: Number,
        default: 0,
    },
});

const forecastPeriod = ref(30);
const occupationForecast = ref(null);
const churnPredictions = ref([]);
const upsellOpportunities = ref([]);
const forecastChart = ref(null);
let chartInstance = null;

const loadForecast = async () => {
    try {
        const response = await axios.get(`/tenant/analytics/predictive/occupation-forecast?days=${forecastPeriod.value}`);
        occupationForecast.value = response.data;
        await nextTick();
        renderChart();
    } catch (error) {
        console.error('Error loading forecast:', error);
    }
};

const loadChurnPredictions = async () => {
    try {
        const response = await axios.get('/tenant/analytics/predictive/churn-predictions');
        churnPredictions.value = response.data;
    } catch (error) {
        console.error('Error loading churn predictions:', error);
    }
};

const loadUpsellOpportunities = async () => {
    try {
        const response = await axios.get('/tenant/analytics/predictive/upsell-opportunities');
        upsellOpportunities.value = response.data;
    } catch (error) {
        console.error('Error loading upsell opportunities:', error);
    }
};

const renderChart = () => {
    if (!forecastChart.value || !occupationForecast.value) return;

    // Destroy existing chart
    if (chartInstance) {
        chartInstance.destroy();
    }

    const ctx = forecastChart.value.getContext('2d');
    const forecast = occupationForecast.value.forecast;

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: forecast.map(f => new Date(f.date).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' })),
            datasets: [
                {
                    label: 'Prévision',
                    data: forecast.map(f => f.predicted),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                },
                {
                    label: 'Borne supérieure (95%)',
                    data: forecast.map(f => f.upper_bound),
                    borderColor: 'rgb(156, 163, 175)',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    pointRadius: 0,
                },
                {
                    label: 'Borne inférieure (95%)',
                    data: forecast.map(f => f.lower_bound),
                    borderColor: 'rgb(156, 163, 175)',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    pointRadius: 0,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    title: {
                        display: true,
                        text: 'Taux d\'occupation (%)',
                    },
                },
            },
        },
    });
};

const getTrendLabel = (trend) => {
    const labels = {
        increasing: 'Hausse',
        decreasing: 'Baisse',
        stable: 'Stable',
    };
    return labels[trend] || trend;
};

const getRiskLabel = (level) => {
    const labels = {
        critical: 'Critique',
        high: 'Élevé',
        medium: 'Moyen',
        low: 'Faible',
    };
    return labels[level] || level;
};

const getFactorLabel = (factor) => {
    const labels = {
        late_payments: 'Retards paiement',
        contract_expiring: 'Contrat expire',
        low_engagement: 'Faible engagement',
        high_support: 'Tickets support',
        price_sensitive: 'Sensible au prix',
    };
    return labels[factor] || factor;
};

onMounted(() => {
    loadForecast();
    loadChurnPredictions();
    loadUpsellOpportunities();
});
</script>
