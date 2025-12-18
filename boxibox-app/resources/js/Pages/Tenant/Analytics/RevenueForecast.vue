<template>
    <TenantLayout>
        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                        <Link href="/tenant/analytics" class="hover:text-gray-700">Analytics</Link>
                        <span>/</span>
                        <span>Previsions Revenus</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Previsions de Revenus</h1>
                    <p class="text-gray-600 mt-1">
                        Anticipez vos revenus avec des projections basees sur l'IA
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select
                        v-model="selectedMonths"
                        @change="updateForecast"
                        class="text-sm border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500"
                    >
                        <option :value="6">6 mois</option>
                        <option :value="12">12 mois</option>
                        <option :value="24">24 mois</option>
                    </select>
                    <button
                        @click="exportForecast"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exporter
                    </button>
                    <button
                        @click="refreshForecast"
                        :disabled="isRefreshing"
                        class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 flex items-center gap-2 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Actualiser
                    </button>
                </div>
            </div>

            <!-- Current MRR Overview -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl p-5 text-white col-span-2">
                    <div class="text-sm opacity-90 mb-1">MRR Actuel</div>
                    <div class="text-3xl font-bold">{{ formatCurrency(forecast.current_mrr?.total) }}</div>
                    <div class="text-sm opacity-80 mt-1">{{ forecast.current_mrr?.contract_count }} contrats actifs</div>
                </div>
                <div class="bg-white rounded-xl border p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">ARR Projete</div>
                    <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(forecast.growth_metrics?.arr) }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">Croissance mensuelle</div>
                    <div class="text-2xl font-bold" :class="forecast.growth_metrics?.monthly_growth_rate >= 0 ? 'text-emerald-600' : 'text-red-600'">
                        {{ forecast.growth_metrics?.monthly_growth_rate >= 0 ? '+' : '' }}{{ forecast.growth_metrics?.monthly_growth_rate }}%
                    </div>
                </div>
                <div class="bg-white rounded-xl border p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">Nouveau MRR</div>
                    <div class="text-2xl font-bold text-emerald-600">+{{ formatCurrency(forecast.growth_metrics?.new_mrr) }}</div>
                </div>
                <div class="bg-white rounded-xl border p-4 shadow-sm">
                    <div class="text-sm text-gray-500 mb-1">MRR Perdu</div>
                    <div class="text-2xl font-bold text-red-600">-{{ formatCurrency(forecast.growth_metrics?.churned_mrr) }}</div>
                </div>
            </div>

            <!-- Forecast Chart -->
            <div class="bg-white rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="font-semibold text-gray-900">Projection MRR</h3>
                    <div class="flex items-center gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="text-gray-600">Prevu</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-emerald-200"></div>
                            <span class="text-gray-600">Intervalle de confiance</span>
                        </div>
                    </div>
                </div>
                <div class="h-80 relative">
                    <!-- Simplified chart visualization -->
                    <div class="absolute inset-0 flex items-end justify-between gap-1 px-4">
                        <div
                            v-for="(month, index) in forecast.forecast"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center relative group"
                        >
                            <!-- Confidence interval -->
                            <div
                                class="absolute bottom-0 w-full bg-emerald-100 rounded-t opacity-50"
                                :style="{
                                    height: getChartHeight(month.upper_bound) + 'px',
                                    bottom: getChartHeight(month.lower_bound) + 'px'
                                }"
                            ></div>
                            <!-- Predicted value bar -->
                            <div
                                class="w-full bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t transition-all hover:from-emerald-700 hover:to-emerald-500"
                                :style="{ height: getChartHeight(month.predicted_mrr) + 'px' }"
                            ></div>
                            <!-- Label -->
                            <div class="text-xs text-gray-400 mt-2 truncate w-full text-center">
                                {{ month.month_label?.split(' ')[0] }}
                            </div>
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                <div class="font-medium">{{ month.month_label }}</div>
                                <div>MRR: {{ formatCurrency(month.predicted_mrr) }}</div>
                                <div class="text-gray-300">Confiance: {{ month.confidence }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenarios & Pipeline -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Scenarios Comparison -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Scenarios de projection</h3>
                    <div class="space-y-4">
                        <div
                            v-for="(scenario, key) in forecast.scenarios"
                            :key="key"
                            class="p-4 rounded-lg border"
                            :class="{
                                'border-emerald-200 bg-emerald-50': key === 'optimistic',
                                'border-blue-200 bg-blue-50': key === 'expected',
                                'border-red-200 bg-red-50': key === 'pessimistic',
                            }"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-3 h-3 rounded-full"
                                        :class="{
                                            'bg-emerald-500': key === 'optimistic',
                                            'bg-blue-500': key === 'expected',
                                            'bg-red-500': key === 'pessimistic',
                                        }"
                                    ></div>
                                    <span class="font-medium text-gray-900">{{ scenario.name }}</span>
                                </div>
                                <span
                                    class="text-sm font-medium"
                                    :class="{
                                        'text-emerald-600': scenario.growth_percent > 0,
                                        'text-red-600': scenario.growth_percent < 0,
                                        'text-gray-600': scenario.growth_percent === 0,
                                    }"
                                >
                                    {{ scenario.growth_percent > 0 ? '+' : '' }}{{ scenario.growth_percent }}%
                                </span>
                            </div>
                            <div class="text-sm text-gray-600 mb-2">{{ scenario.description }}</div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <div class="text-xs text-gray-500">MRR final</div>
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(scenario.final_mrr) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-gray-500">Revenu total</div>
                                    <div class="text-lg font-bold text-gray-900">{{ formatCurrency(scenario.total_revenue) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pipeline Revenue -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Revenus pipeline</h3>
                    <div class="space-y-4">
                        <!-- Leads -->
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-purple-900">Leads</span>
                                <span class="text-sm text-purple-600">{{ forecast.pipeline_revenue?.leads?.count }} leads</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <div class="text-xs text-purple-600">Valeur ponderee</div>
                                    <div class="text-xl font-bold text-purple-900">{{ formatCurrency(forecast.pipeline_revenue?.leads?.weighted_value) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-purple-600">Potentiel total</div>
                                    <div class="text-sm font-medium text-purple-700">{{ formatCurrency(forecast.pipeline_revenue?.leads?.total_potential) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Prospects -->
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                            <div class="flex items-center justify-between mb-2">
                                <span class="font-medium text-amber-900">Prospects</span>
                                <span class="text-sm text-amber-600">{{ forecast.pipeline_revenue?.prospects?.count }} prospects</span>
                            </div>
                            <div class="flex justify-between items-end">
                                <div>
                                    <div class="text-xs text-amber-600">Valeur ponderee</div>
                                    <div class="text-xl font-bold text-amber-900">{{ formatCurrency(forecast.pipeline_revenue?.prospects?.weighted_value) }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-amber-600">Potentiel total</div>
                                    <div class="text-sm font-medium text-amber-700">{{ formatCurrency(forecast.pipeline_revenue?.prospects?.total_potential) }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Pipeline -->
                        <div class="p-4 bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg text-white">
                            <div class="text-sm opacity-90">Total Pipeline</div>
                            <div class="text-2xl font-bold">{{ formatCurrency(forecast.pipeline_revenue?.total_pipeline) }}</div>
                            <div class="text-sm opacity-80">Revenu potentiel pondere</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue at Risk & Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue at Risk -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Revenus a risque</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(risk, period) in forecast.revenue_at_risk"
                            :key="period"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-2 h-10 rounded-full"
                                    :class="{
                                        'bg-red-500': period === '7_days',
                                        'bg-orange-500': period === '30_days',
                                        'bg-amber-500': period === '60_days',
                                        'bg-gray-400': period === '90_days',
                                    }"
                                ></div>
                                <div>
                                    <div class="font-medium text-gray-900">{{ getPeriodLabel(period) }}</div>
                                    <div class="text-sm text-gray-500">{{ risk.contracts }} contrats expirent</div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-gray-900">{{ formatCurrency(risk.monthly_revenue) }}/mois</div>
                                <div class="text-sm text-gray-500">{{ formatCurrency(risk.annual_revenue) }}/an</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Breakdown -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Repartition du MRR</h3>
                    <div class="space-y-4">
                        <!-- By Box Size -->
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-2">Par taille de box</div>
                            <div class="space-y-2">
                                <div
                                    v-for="(data, size) in forecast.breakdown?.by_size"
                                    :key="size"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                                        <span class="text-sm text-gray-600">{{ size }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900">{{ formatCurrency(data.revenue) }}</span>
                                        <span class="text-gray-500"> ({{ data.count }})</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- By Duration -->
                        <div>
                            <div class="text-sm font-medium text-gray-700 mb-2">Par duree d'engagement</div>
                            <div class="space-y-2">
                                <div
                                    v-for="(data, duration) in forecast.breakdown?.by_duration"
                                    :key="duration"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2">
                                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                        <span class="text-sm text-gray-600">{{ duration }}</span>
                                    </div>
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900">{{ formatCurrency(data.revenue) }}</span>
                                        <span class="text-gray-500"> ({{ data.count }})</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seasonality & Historical -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Seasonality Analysis -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Saisonnalite</h3>
                    <div class="flex items-end justify-between gap-1 h-40">
                        <div
                            v-for="month in forecast.seasonality?.monthly"
                            :key="month.month"
                            class="flex-1 flex flex-col items-center group relative"
                        >
                            <div
                                class="w-full rounded-t transition-all"
                                :class="{
                                    'bg-emerald-500': month.trend === 'high',
                                    'bg-gray-400': month.trend === 'normal',
                                    'bg-red-400': month.trend === 'low',
                                }"
                                :style="{ height: (month.index / 1.5) + 'px' }"
                            ></div>
                            <div class="text-xs text-gray-400 mt-1">{{ month.month_name }}</div>
                            <!-- Tooltip -->
                            <div class="absolute bottom-full mb-2 bg-gray-900 text-white text-xs rounded px-2 py-1 opacity-0 group-hover:opacity-100 whitespace-nowrap z-10">
                                Indice: {{ month.index }}%
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-between mt-4 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                            <span class="text-gray-600">Haute saison: {{ forecast.seasonality?.peak_season?.month }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <span class="text-gray-600">Basse saison: {{ forecast.seasonality?.low_season?.month }}</span>
                        </div>
                    </div>
                </div>

                <!-- Historical Revenue -->
                <div class="bg-white rounded-xl border shadow-sm p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Historique des revenus</h3>
                    <div class="space-y-2">
                        <div
                            v-for="month in forecast.historical?.slice(-6)"
                            :key="month.month"
                            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
                        >
                            <span class="text-sm text-gray-600">{{ month.month_label }}</span>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900">{{ formatCurrency(month.revenue) }}</div>
                                    <div class="text-xs text-gray-500">MRR: {{ formatCurrency(month.mrr) }}</div>
                                </div>
                                <div class="flex items-center gap-1 text-xs">
                                    <span class="text-emerald-600">+{{ month.new_contracts }}</span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-red-600">-{{ month.churned_contracts }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Forecast Confidence -->
            <div class="bg-white rounded-xl border shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Fiabilite des previsions</h3>
                    <span
                        class="px-3 py-1 text-sm font-medium rounded-full"
                        :class="{
                            'bg-emerald-100 text-emerald-800': forecast.confidence?.overall >= 70,
                            'bg-amber-100 text-amber-800': forecast.confidence?.overall >= 50 && forecast.confidence?.overall < 70,
                            'bg-red-100 text-red-800': forecast.confidence?.overall < 50,
                        }"
                    >
                        {{ forecast.confidence?.recommendation }}
                    </span>
                </div>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-emerald-600">{{ forecast.confidence?.overall }}%</div>
                        <div class="text-sm text-gray-500">Confiance globale</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ forecast.confidence?.data_quality }}%</div>
                        <div class="text-sm text-gray-500">Qualite des donnees</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ forecast.confidence?.months_of_data }}</div>
                        <div class="text-sm text-gray-500">Mois de donnees</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-600">{{ forecast.confidence?.data_points }}</div>
                        <div class="text-sm text-gray-500">Points de donnees</div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    forecast: {
        type: Object,
        required: true,
    },
    months: {
        type: Number,
        default: 12,
    },
});

const selectedMonths = ref(props.months);
const isRefreshing = ref(false);

const formatCurrency = (value) => {
    if (!value && value !== 0) return '0 EUR';
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(value);
};

const getChartHeight = (value) => {
    const maxValue = Math.max(...(props.forecast.forecast?.map(m => m.upper_bound) || [1]));
    return Math.max(10, (value / maxValue) * 250);
};

const getPeriodLabel = (period) => {
    const labels = {
        '7_days': '7 prochains jours',
        '30_days': '30 prochains jours',
        '60_days': '60 prochains jours',
        '90_days': '90 prochains jours',
    };
    return labels[period] || period;
};

const updateForecast = () => {
    router.get('/tenant/analytics/revenue-forecast', { months: selectedMonths.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const refreshForecast = () => {
    isRefreshing.value = true;
    router.post('/tenant/analytics/revenue-forecast/refresh', { months: selectedMonths.value }, {
        preserveState: true,
        onFinish: () => isRefreshing.value = false,
    });
};

const exportForecast = () => {
    window.location.href = `/tenant/analytics/revenue-forecast/export?months=${selectedMonths.value}`;
};
</script>
