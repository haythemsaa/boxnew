<template>
    <TenantLayout title="Analytics - Prédictif">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-violet-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <SparklesIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Analytics Prédictifs IA</h1>
                            <p class="text-violet-100 mt-1">Prévisions et recommandations basées sur le Machine Learning</p>
                        </div>
                    </div>
                </div>

                <!-- Current Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-violet-200 text-sm">Occupation Actuelle</p>
                                <p class="text-3xl font-bold text-white mt-1">{{ currentOccupation }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <ChartPieIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-violet-200 text-sm">Précision Modèle</p>
                                <p class="text-3xl font-bold text-emerald-300 mt-1">{{ occupationForecast?.accuracy || 0 }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <CheckBadgeIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-violet-200 text-sm">Clients à Risque</p>
                                <p class="text-3xl font-bold text-amber-300 mt-1">{{ churnPredictions?.length || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-400/30 rounded-xl flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-6 h-6 text-amber-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-violet-200 text-sm">Opportunités Upsell</p>
                                <p class="text-3xl font-bold text-blue-300 mt-1">{{ upsellOpportunities?.length || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-400/30 rounded-xl flex items-center justify-center">
                                <CurrencyEuroIcon class="w-6 h-6 text-blue-300" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Occupation Forecast -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center">
                        <ArrowTrendingUpIcon class="w-5 h-5 text-violet-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Prévision d'Occupation</h2>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            v-for="period in [30, 60, 90]"
                            :key="period"
                            @click="forecastPeriod = period; loadForecast()"
                            :class="[
                                forecastPeriod === period
                                    ? 'bg-violet-600 text-white shadow-lg shadow-violet-200'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                'px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-200'
                            ]"
                        >
                            {{ period }} jours
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="occupationForecast">
                        <!-- Trend indicator -->
                        <div class="mb-6 p-4 rounded-xl" :class="{
                            'bg-emerald-50 border border-emerald-200': occupationForecast.trend === 'increasing',
                            'bg-red-50 border border-red-200': occupationForecast.trend === 'decreasing',
                            'bg-gray-50 border border-gray-200': occupationForecast.trend === 'stable'
                        }">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div :class="[
                                        'w-10 h-10 rounded-lg flex items-center justify-center mr-3',
                                        occupationForecast.trend === 'increasing' ? 'bg-emerald-100' :
                                        occupationForecast.trend === 'decreasing' ? 'bg-red-100' : 'bg-gray-100'
                                    ]">
                                        <ArrowTrendingUpIcon v-if="occupationForecast.trend === 'increasing'" class="w-5 h-5 text-emerald-600" />
                                        <ArrowTrendingDownIcon v-else-if="occupationForecast.trend === 'decreasing'" class="w-5 h-5 text-red-600" />
                                        <MinusIcon v-else class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Tendance : {{ getTrendLabel(occupationForecast.trend) }}</p>
                                        <p class="text-sm text-gray-600">Précision du modèle : {{ occupationForecast.accuracy }}%</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Chart -->
                        <div class="h-80 mb-6">
                            <canvas ref="forecastChart"></canvas>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-sm font-medium text-gray-500">Occupation actuelle</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ currentOccupation }}%</p>
                            </div>
                            <div class="bg-violet-50 rounded-xl p-4 text-center">
                                <p class="text-sm font-medium text-violet-600">Prévision J+{{ forecastPeriod }}</p>
                                <p class="text-2xl font-bold text-violet-700 mt-1">
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.predicted.toFixed(1) }}%
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <p class="text-sm font-medium text-gray-500">Intervalle de confiance</p>
                                <p class="text-lg font-semibold text-gray-700 mt-1">
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.lower_bound.toFixed(1) }}% -
                                    {{ occupationForecast.forecast[forecastPeriod - 1]?.upper_bound.toFixed(1) }}%
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="w-12 h-12 bg-violet-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <ArrowPathIcon class="w-6 h-6 text-violet-600 animate-spin" />
                        </div>
                        <p class="text-gray-600">Chargement des prévisions...</p>
                    </div>
                </div>
            </div>

            <!-- Churn Risk -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ExclamationTriangleIcon class="w-5 h-5 text-amber-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Clients à Risque de Churn</h2>
                </div>
                <div class="p-6">
                    <div v-if="churnPredictions && churnPredictions.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Score Churn</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Risque</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Facteurs</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions recommandées</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="prediction in churnPredictions.slice(0, 10)" :key="prediction.customer_id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-sm font-semibold text-gray-900">{{ prediction.customer_name }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                                <div
                                                    :class="{
                                                        'bg-gradient-to-r from-red-500 to-rose-500': prediction.churn_score >= 80,
                                                        'bg-gradient-to-r from-orange-500 to-amber-500': prediction.churn_score >= 60 && prediction.churn_score < 80,
                                                        'bg-gradient-to-r from-yellow-500 to-amber-400': prediction.churn_score >= 40 && prediction.churn_score < 60,
                                                        'bg-gradient-to-r from-emerald-500 to-teal-500': prediction.churn_score < 40
                                                    }"
                                                    class="h-2 rounded-full transition-all duration-500"
                                                    :style="{ width: prediction.churn_score + '%' }"
                                                ></div>
                                            </div>
                                            <span class="text-sm font-bold text-gray-900">{{ prediction.churn_score.toFixed(0) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="{
                                                'bg-red-100 text-red-700 border-red-200': prediction.risk_level === 'critical',
                                                'bg-orange-100 text-orange-700 border-orange-200': prediction.risk_level === 'high',
                                                'bg-amber-100 text-amber-700 border-amber-200': prediction.risk_level === 'medium',
                                                'bg-emerald-100 text-emerald-700 border-emerald-200': prediction.risk_level === 'low'
                                            }"
                                            class="px-2.5 py-1 text-xs font-semibold rounded-lg border"
                                        >
                                            {{ getRiskLabel(prediction.risk_level) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="(value, factor) in prediction.factors"
                                                :key="factor"
                                                class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg"
                                            >
                                                {{ getFactorLabel(factor) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <ul class="text-xs text-gray-700 space-y-1">
                                            <li v-for="(action, idx) in prediction.recommended_actions.slice(0, 2)" :key="idx" class="flex items-start">
                                                <CheckIcon class="w-3 h-3 text-violet-500 mr-1 mt-0.5 flex-shrink-0" />
                                                <span>{{ action }}</span>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <CheckCircleIcon class="w-8 h-8 text-emerald-600" />
                        </div>
                        <p class="text-lg font-semibold text-gray-900">Aucun client à risque élevé</p>
                        <p class="text-gray-500 mt-1">Excellente rétention client !</p>
                    </div>
                </div>
            </div>

            <!-- Upsell Opportunities -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <CurrencyEuroIcon class="w-5 h-5 text-emerald-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Opportunités d'Upsell</h2>
                </div>
                <div class="p-6">
                    <div v-if="upsellOpportunities && upsellOpportunities.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div
                            v-for="opportunity in upsellOpportunities.slice(0, 6)"
                            :key="opportunity.customer_id"
                            class="bg-gradient-to-br from-gray-50 to-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-200"
                        >
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h4 class="font-semibold text-gray-900">{{ opportunity.customer_name }}</h4>
                                    <p class="text-xs text-gray-500">Box : {{ opportunity.current_box }}</p>
                                </div>
                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-lg">
                                    Score : {{ opportunity.upsell_score.toFixed(0) }}
                                </span>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div
                                    v-for="(rec, idx) in opportunity.recommendations.slice(0, 2)"
                                    :key="idx"
                                    class="flex items-center justify-between text-sm p-2 bg-white rounded-lg"
                                >
                                    <span class="text-gray-700">{{ rec.description }}</span>
                                    <span class="font-bold text-emerald-600">+{{ rec.monthly_increase }}€</span>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Revenu potentiel</span>
                                    <span class="text-lg font-bold text-violet-600">+{{ opportunity.estimated_additional_revenue }}€/mois</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <MagnifyingGlassIcon class="w-8 h-8 text-gray-400" />
                        </div>
                        <p class="text-lg font-semibold text-gray-900">Aucune opportunité détectée</p>
                        <p class="text-gray-500 mt-1">Le système analyse en continu vos données</p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { Chart, registerables } from 'chart.js'
import {
    SparklesIcon,
    ChartPieIcon,
    CheckBadgeIcon,
    ExclamationTriangleIcon,
    CurrencyEuroIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    MinusIcon,
    ArrowPathIcon,
    CheckIcon,
    CheckCircleIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'

Chart.register(...registerables)

const props = defineProps({
    currentOccupation: {
        type: Number,
        default: 0,
    },
})

const forecastPeriod = ref(30)
const occupationForecast = ref(null)
const churnPredictions = ref([])
const upsellOpportunities = ref([])
const forecastChart = ref(null)
let chartInstance = null

const loadForecast = async () => {
    try {
        const response = await axios.get(`/tenant/analytics/predictive/occupation-forecast?days=${forecastPeriod.value}`)
        occupationForecast.value = response.data
        await nextTick()
        renderChart()
    } catch (error) {
        console.error('Erreur de chargement des prévisions:', error)
    }
}

const loadChurnPredictions = async () => {
    try {
        const response = await axios.get('/tenant/analytics/predictive/churn-predictions')
        churnPredictions.value = response.data
    } catch (error) {
        console.error('Erreur de chargement des prédictions de churn:', error)
    }
}

const loadUpsellOpportunities = async () => {
    try {
        const response = await axios.get('/tenant/analytics/predictive/upsell-opportunities')
        upsellOpportunities.value = response.data
    } catch (error) {
        console.error('Erreur de chargement des opportunités upsell:', error)
    }
}

const renderChart = () => {
    if (!forecastChart.value || !occupationForecast.value) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const ctx = forecastChart.value.getContext('2d')
    const forecast = occupationForecast.value.forecast

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: forecast.map(f => new Date(f.date).toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' })),
            datasets: [
                {
                    label: 'Prévision',
                    data: forecast.map(f => f.predicted),
                    borderColor: 'rgb(139, 92, 246)',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(139, 92, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 3,
                },
                {
                    label: 'Borne supérieure (95%)',
                    data: forecast.map(f => f.upper_bound),
                    borderColor: 'rgb(209, 213, 219)',
                    backgroundColor: 'transparent',
                    borderDash: [5, 5],
                    pointRadius: 0,
                },
                {
                    label: 'Borne inférieure (95%)',
                    data: forecast.map(f => f.lower_bound),
                    borderColor: 'rgb(209, 213, 219)',
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
                    labels: {
                        font: {
                            family: 'Inter',
                            weight: 500
                        },
                        usePointStyle: true,
                    }
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: (value) => value + '%',
                        font: {
                            family: 'Inter'
                        }
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Taux d\'occupation (%)',
                        font: {
                            family: 'Inter',
                            weight: 500
                        }
                    },
                },
                x: {
                    ticks: {
                        font: {
                            family: 'Inter'
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            },
        },
    })
}

const getTrendLabel = (trend) => {
    const labels = {
        increasing: 'Hausse',
        decreasing: 'Baisse',
        stable: 'Stable',
    }
    return labels[trend] || trend
}

const getRiskLabel = (level) => {
    const labels = {
        critical: 'Critique',
        high: 'Élevé',
        medium: 'Moyen',
        low: 'Faible',
    }
    return labels[level] || level
}

const getFactorLabel = (factor) => {
    const labels = {
        late_payments: 'Retards paiement',
        contract_expiring: 'Contrat expire',
        low_engagement: 'Faible engagement',
        high_support: 'Tickets support',
        price_sensitive: 'Sensible au prix',
    }
    return labels[factor] || factor
}

onMounted(() => {
    loadForecast()
    loadChurnPredictions()
    loadUpsellOpportunities()
})
</script>
