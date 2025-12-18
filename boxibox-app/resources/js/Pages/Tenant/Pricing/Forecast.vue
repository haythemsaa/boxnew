<template>
    <Head :title="$t('pricing.forecast')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <svg class="w-8 h-8 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                            </svg>
                            Prévision de la Demande
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Anticipez la demande avec l'IA et optimisez vos prix proactivement
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 flex items-center gap-3">
                        <select v-model="selectedSiteId" @change="loadForecast"
                            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                        </select>
                        <button @click="refreshForecast" :disabled="isLoading"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 disabled:opacity-50 text-sm font-medium">
                            <svg :class="{ 'animate-spin': isLoading }" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Actualiser
                        </button>
                    </div>
                </div>

                <!-- Demand Overview -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Demande actuelle</div>
                            <span :class="getDemandBadgeClass(forecast.current_demand_level)"
                                class="px-2 py-1 text-xs font-medium rounded-full">
                                {{ forecast.current_demand_level }}
                            </span>
                        </div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ forecast.current_demand_score }}/100
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Score de demande global</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Prévision 30 jours</div>
                        <div class="mt-2 text-3xl font-semibold" :class="getTrendColor(forecast.trend_30d)">
                            {{ forecast.trend_30d >= 0 ? '+' : '' }}{{ forecast.trend_30d }}%
                        </div>
                        <div class="mt-1 text-xs text-gray-500">{{ forecast.trend_30d_label }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Réservations prévues</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ forecast.predicted_bookings }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Ce mois-ci</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenus prévus</div>
                        <div class="mt-2 text-3xl font-semibold text-cyan-600">
                            {{ formatCurrency(forecast.predicted_revenue) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">+{{ forecast.revenue_growth }}% vs mois dernier</div>
                    </div>
                </div>

                <!-- Forecast Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Prévision sur 6 mois</h3>
                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="showConfidenceInterval"
                                    class="rounded text-cyan-600">
                                <span class="text-gray-600 dark:text-gray-400">Intervalle de confiance</span>
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" v-model="showSeasonality"
                                    class="rounded text-cyan-600">
                                <span class="text-gray-600 dark:text-gray-400">Saisonnalité</span>
                            </label>
                        </div>
                    </div>
                    <canvas ref="forecastChart" height="100"></canvas>
                </div>

                <!-- Demand by Size Category -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Demande par taille</h3>
                        <div class="space-y-4">
                            <div v-for="category in forecast.demand_by_size" :key="category.size" class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ category.label }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-gray-500">{{ category.demand_score }}/100</span>
                                        <span :class="getTrendBadgeClass(category.trend)"
                                            class="px-2 py-0.5 text-xs font-medium rounded">
                                            {{ category.trend >= 0 ? '+' : '' }}{{ category.trend }}%
                                        </span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all duration-500"
                                        :class="getDemandBarColor(category.demand_score)"
                                        :style="{ width: category.demand_score + '%' }"></div>
                                </div>
                                <div class="flex justify-between text-xs text-gray-500">
                                    <span>{{ category.occupied }}/{{ category.total }} occupés ({{ category.occupancy_rate }}%)</span>
                                    <span>Prix recommandé: {{ formatCurrency(category.recommended_price) }}/m²</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seasonality Patterns -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Patterns saisonniers</h3>
                        <canvas ref="seasonalityChart" height="200"></canvas>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                                <div class="text-green-800 dark:text-green-300 font-medium">Haute saison</div>
                                <div class="text-green-600 dark:text-green-400">{{ forecast.high_season_months }}</div>
                                <div class="text-xs text-gray-500 mt-1">+{{ forecast.high_season_premium }}% prix recommandé</div>
                            </div>
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="text-blue-800 dark:text-blue-300 font-medium">Basse saison</div>
                                <div class="text-blue-600 dark:text-blue-400">{{ forecast.low_season_months }}</div>
                                <div class="text-xs text-gray-500 mt-1">-{{ forecast.low_season_discount }}% prix recommandé</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Day-of-Week Patterns -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Patterns hebdomadaires</h3>
                    <div class="grid grid-cols-7 gap-2">
                        <div v-for="day in forecast.weekly_patterns" :key="day.day"
                            class="text-center p-3 rounded-lg"
                            :class="getDayClass(day.demand_index)">
                            <div class="font-medium text-sm">{{ day.day_short }}</div>
                            <div class="text-2xl font-bold mt-1">{{ day.demand_index }}</div>
                            <div class="text-xs mt-1 text-gray-500">{{ day.bookings_avg }} rés./jour</div>
                            <div class="text-xs font-medium mt-1"
                                :class="day.price_adjustment >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ day.price_adjustment >= 0 ? '+' : '' }}{{ day.price_adjustment }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- AI Predictions & Alerts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Predictions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            Prédictions IA
                        </h3>
                        <div class="space-y-4">
                            <div v-for="(prediction, index) in forecast.ai_predictions" :key="index"
                                class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ prediction.title }}</h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ prediction.description }}</p>
                                    </div>
                                    <span :class="getConfidenceBadgeClass(prediction.confidence)"
                                        class="px-2 py-1 text-xs font-medium rounded-full">
                                        {{ prediction.confidence }}% conf.
                                    </span>
                                </div>
                                <div class="mt-3 flex items-center gap-4 text-sm">
                                    <span class="text-gray-500">Impact:</span>
                                    <span class="font-medium" :class="prediction.impact >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ prediction.impact >= 0 ? '+' : '' }}{{ formatCurrency(prediction.impact) }}/mois
                                    </span>
                                    <span class="text-gray-500">Période:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ prediction.period }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts & Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            Alertes & Actions
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(alert, index) in forecast.alerts" :key="index"
                                class="p-4 rounded-lg border-l-4"
                                :class="getAlertClass(alert.severity)">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                            <span :class="getAlertIconColor(alert.severity)">{{ getAlertIcon(alert.severity) }}</span>
                                            {{ alert.title }}
                                        </h4>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ alert.message }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center gap-2">
                                    <button v-if="alert.action" @click="executeAction(alert)"
                                        class="px-3 py-1.5 bg-cyan-600 text-white text-sm rounded hover:bg-cyan-700">
                                        {{ alert.action_label }}
                                    </button>
                                    <button @click="dismissAlert(alert)"
                                        class="px-3 py-1.5 text-gray-600 dark:text-gray-400 text-sm hover:text-gray-800 dark:hover:text-gray-200">
                                        Ignorer
                                    </button>
                                </div>
                            </div>

                            <div v-if="forecast.alerts?.length === 0" class="text-center text-gray-500 py-4">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="mt-2">Aucune alerte pour le moment</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import TenantLayout from '@/Layouts/TenantLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    forecast: {
        type: Object,
        default: () => ({
            current_demand_level: 'Moyen',
            current_demand_score: 65,
            trend_30d: 5,
            trend_30d_label: 'Tendance haussière',
            predicted_bookings: 45,
            predicted_revenue: 12500,
            revenue_growth: 8,
            demand_by_size: [],
            weekly_patterns: [],
            high_season_months: 'Juin - Septembre',
            high_season_premium: 15,
            low_season_months: 'Novembre - Février',
            low_season_discount: 10,
            ai_predictions: [],
            alerts: [],
            monthly_forecast: [],
            seasonality_data: [],
        }),
    },
    sites: {
        type: Array,
        default: () => [],
    },
});

const forecastChart = ref(null);
const seasonalityChart = ref(null);
const selectedSiteId = ref('');
const isLoading = ref(false);
const showConfidenceInterval = ref(true);
const showSeasonality = ref(false);

let forecastChartInstance = null;
let seasonalityChartInstance = null;

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const getDemandBadgeClass = (level) => {
    const classes = {
        'Très élevé': 'bg-red-100 text-red-800',
        'Élevé': 'bg-orange-100 text-orange-800',
        'Moyen': 'bg-yellow-100 text-yellow-800',
        'Faible': 'bg-blue-100 text-blue-800',
        'Très faible': 'bg-gray-100 text-gray-800',
    };
    return classes[level] || classes['Moyen'];
};

const getTrendColor = (trend) => {
    if (trend > 10) return 'text-green-600';
    if (trend > 0) return 'text-emerald-600';
    if (trend < -10) return 'text-red-600';
    if (trend < 0) return 'text-orange-600';
    return 'text-gray-600';
};

const getTrendBadgeClass = (trend) => {
    if (trend > 0) return 'bg-green-100 text-green-800';
    if (trend < 0) return 'bg-red-100 text-red-800';
    return 'bg-gray-100 text-gray-800';
};

const getDemandBarColor = (score) => {
    if (score >= 80) return 'bg-red-500';
    if (score >= 60) return 'bg-orange-500';
    if (score >= 40) return 'bg-yellow-500';
    if (score >= 20) return 'bg-blue-500';
    return 'bg-gray-500';
};

const getDayClass = (index) => {
    if (index >= 80) return 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800';
    if (index >= 60) return 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800';
    if (index >= 40) return 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800';
    return 'bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600';
};

const getConfidenceBadgeClass = (confidence) => {
    if (confidence >= 90) return 'bg-green-100 text-green-800';
    if (confidence >= 70) return 'bg-yellow-100 text-yellow-800';
    return 'bg-gray-100 text-gray-800';
};

const getAlertClass = (severity) => {
    const classes = {
        critical: 'border-red-500 bg-red-50 dark:bg-red-900/10',
        warning: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10',
        info: 'border-blue-500 bg-blue-50 dark:bg-blue-900/10',
    };
    return classes[severity] || classes.info;
};

const getAlertIconColor = (severity) => {
    const colors = {
        critical: 'text-red-600',
        warning: 'text-yellow-600',
        info: 'text-blue-600',
    };
    return colors[severity] || colors.info;
};

const getAlertIcon = (severity) => {
    const icons = {
        critical: '🚨',
        warning: '⚠️',
        info: 'ℹ️',
    };
    return icons[severity] || icons.info;
};

const loadForecast = () => {
    router.get(route('tenant.pricing.forecast'), {
        site_id: selectedSiteId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const refreshForecast = async () => {
    isLoading.value = true;
    try {
        router.post(route('tenant.pricing.generate-forecast'), {
            site_id: selectedSiteId.value || undefined,
        }, {
            onFinish: () => {
                isLoading.value = false;
            },
        });
    } catch (error) {
        console.error('Error refreshing forecast:', error);
        isLoading.value = false;
    }
};

const executeAction = (alert) => {
    router.post(route('tenant.pricing.apply-recommendation'), {
        action: alert.action,
        alert_id: alert.id,
    });
};

const dismissAlert = (alert) => {
    router.post(route('tenant.pricing.dismiss-alert'), {
        alert_id: alert.id,
    });
};

const initCharts = () => {
    // Forecast Chart
    if (forecastChart.value && props.forecast.monthly_forecast?.length > 0) {
        if (forecastChartInstance) forecastChartInstance.destroy();

        const datasets = [
            {
                label: 'Demande prévue',
                data: props.forecast.monthly_forecast.map(m => m.demand_score),
                borderColor: 'rgb(6, 182, 212)',
                backgroundColor: 'rgba(6, 182, 212, 0.1)',
                tension: 0.4,
                fill: true,
            },
        ];

        if (showConfidenceInterval.value) {
            datasets.push(
                {
                    label: 'Borne haute',
                    data: props.forecast.monthly_forecast.map(m => m.upper_bound),
                    borderColor: 'rgba(6, 182, 212, 0.3)',
                    borderDash: [5, 5],
                    fill: false,
                    pointRadius: 0,
                },
                {
                    label: 'Borne basse',
                    data: props.forecast.monthly_forecast.map(m => m.lower_bound),
                    borderColor: 'rgba(6, 182, 212, 0.3)',
                    borderDash: [5, 5],
                    fill: '-1',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    pointRadius: 0,
                }
            );
        }

        forecastChartInstance = new Chart(forecastChart.value, {
            type: 'line',
            data: {
                labels: props.forecast.monthly_forecast.map(m => m.month_label),
                datasets,
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
                            text: 'Score de demande',
                        },
                    },
                },
            },
        });
    }

    // Seasonality Chart
    if (seasonalityChart.value && props.forecast.seasonality_data?.length > 0) {
        if (seasonalityChartInstance) seasonalityChartInstance.destroy();

        seasonalityChartInstance = new Chart(seasonalityChart.value, {
            type: 'radar',
            data: {
                labels: props.forecast.seasonality_data.map(s => s.month_short),
                datasets: [{
                    label: 'Indice saisonnier',
                    data: props.forecast.seasonality_data.map(s => s.index),
                    backgroundColor: 'rgba(6, 182, 212, 0.2)',
                    borderColor: 'rgb(6, 182, 212)',
                    pointBackgroundColor: 'rgb(6, 182, 212)',
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 150,
                    },
                },
            },
        });
    }
};

watch([showConfidenceInterval, showSeasonality], () => {
    initCharts();
});

onMounted(() => {
    initCharts();
});
</script>
