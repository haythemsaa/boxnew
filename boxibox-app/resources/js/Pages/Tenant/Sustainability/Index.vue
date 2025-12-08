<template>
    <TenantLayout :title="$t('sustainability.dashboard')">
        <div class="space-y-6">
            <!-- Header with filters -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.dashboard') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.dashboard_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.site_id" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all_sites') }}</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
                    </select>
                    <select v-model="filterForm.year" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                    </select>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total CO2 -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.total_co2') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(yearlyTotals.total_co2 / 1000) }} t
                            </p>
                        </div>
                        <div :class="[
                            'px-2.5 py-1 rounded-full text-sm font-medium',
                            co2Change <= 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        ]">
                            {{ co2Change > 0 ? '+' : '' }}{{ co2Change }}%
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        {{ $t('sustainability.vs_last_year') }}
                    </div>
                </div>

                <!-- Energy Consumption -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.energy_consumption') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(energyTotals.electricity_kwh) }} kWh
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707"/>
                        </svg>
                        {{ formatNumber(energyTotals.solar_generated) }} kWh {{ $t('sustainability.solar') }}
                    </div>
                </div>

                <!-- Recycling Rate -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.recycling_rate') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatNumber(wasteTotals.avg_recycling_rate) }}%
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" :style="{ width: wasteTotals.avg_recycling_rate + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Certifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.certifications') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ certifications.length }}
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-1">
                        <span v-for="cert in certifications.slice(0, 3)" :key="cert.id"
                            class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded text-xs">
                            {{ constants.certificationTypes[cert.type] || cert.type }}
                        </span>
                        <span v-if="certifications.length > 3" class="text-xs text-gray-500">
                            +{{ certifications.length - 3 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- CO2 Emissions Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $t('sustainability.co2_emissions_monthly') }}
                    </h3>
                    <div class="h-64">
                        <canvas ref="emissionsChart"></canvas>
                    </div>
                </div>

                <!-- Waste Breakdown Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        {{ $t('sustainability.waste_breakdown') }}
                    </h3>
                    <div class="h-64">
                        <canvas ref="wasteChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Goals and Initiatives -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sustainability Goals -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $t('sustainability.goals') }}
                        </h3>
                        <Link :href="route('tenant.sustainability.goals')" class="text-sm text-primary-600 hover:text-primary-700">
                            {{ $t('common.view_all') }}
                        </Link>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-if="goals.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                            {{ $t('sustainability.no_goals') }}
                        </div>
                        <div v-for="goal in goals.slice(0, 4)" :key="goal.id" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-gray-900 dark:text-white">{{ goal.name }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ goal.progress_percent }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div :class="[
                                    'h-2 rounded-full transition-all',
                                    goal.is_on_track ? 'bg-green-600' : 'bg-yellow-600'
                                ]" :style="{ width: goal.progress_percent + '%' }"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ constants.metrics[goal.metric] || goal.metric }}</span>
                                <span>{{ $t('sustainability.target') }}: {{ goal.target_year }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Initiatives -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $t('sustainability.active_initiatives') }}
                        </h3>
                        <Link :href="route('tenant.sustainability.initiatives')" class="text-sm text-primary-600 hover:text-primary-700">
                            {{ $t('common.view_all') }}
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-if="initiatives.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                            {{ $t('sustainability.no_initiatives') }}
                        </div>
                        <div v-for="initiative in initiatives.slice(0, 4)" :key="initiative.id" class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-start gap-4">
                                <div :class="[
                                    'w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0',
                                    categoryColors[initiative.category] || 'bg-gray-100 dark:bg-gray-700'
                                ]">
                                    <component :is="categoryIcons[initiative.category] || 'span'" class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 dark:text-white truncate">{{ initiative.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ constants.categories[initiative.category] || initiative.category }}
                                        <span v-if="initiative.site">- {{ initiative.site.name }}</span>
                                    </p>
                                </div>
                                <div class="flex flex-col items-end">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-xs font-medium',
                                        statusColors[initiative.status]
                                    ]">
                                        {{ constants.statuses[initiative.status] || initiative.status }}
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ initiative.progress_percent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiring Certifications Alert -->
            <div v-if="expiringCertifications.length > 0" class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <h4 class="font-medium text-yellow-800 dark:text-yellow-200">
                            {{ $t('sustainability.expiring_certifications') }}
                        </h4>
                        <ul class="mt-2 space-y-1">
                            <li v-for="cert in expiringCertifications" :key="cert.id" class="text-sm text-yellow-700 dark:text-yellow-300">
                                <strong>{{ cert.name }}</strong> - {{ $t('sustainability.expires_in') }} {{ cert.days_until_expiry }} {{ $t('common.days') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link :href="route('tenant.sustainability.energy')"
                    class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 transition-colors">
                    <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $t('sustainability.energy_readings') }}</span>
                </Link>

                <Link :href="route('tenant.sustainability.waste')"
                    class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 transition-colors">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $t('sustainability.waste_records') }}</span>
                </Link>

                <Link :href="route('tenant.sustainability.initiatives')"
                    class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 transition-colors">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $t('sustainability.initiatives') }}</span>
                </Link>

                <Link :href="route('tenant.sustainability.certifications')"
                    class="flex items-center gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-500 transition-colors">
                    <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                        </svg>
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $t('sustainability.certifications') }}</span>
                </Link>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    sites: Array,
    filters: Object,
    yearlyTotals: Object,
    co2Change: Number,
    energyTotals: Object,
    wasteTotals: Object,
    goals: Array,
    initiatives: Array,
    certifications: Array,
    charts: Object,
    constants: Object,
});

const filterForm = ref({
    site_id: props.filters.site_id || null,
    year: props.filters.year || new Date().getFullYear(),
});

const emissionsChart = ref(null);
const wasteChart = ref(null);
let emissionsChartInstance = null;
let wasteChartInstance = null;

const availableYears = computed(() => {
    const currentYear = new Date().getFullYear();
    return Array.from({ length: 5 }, (_, i) => currentYear - i);
});

const expiringCertifications = computed(() => {
    return props.certifications.filter(cert => cert.is_expiring_soon);
});

const categoryColors = {
    energy: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400',
    waste: 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400',
    transport: 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400',
    water: 'bg-cyan-100 dark:bg-cyan-900 text-cyan-600 dark:text-cyan-400',
    materials: 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400',
    biodiversity: 'bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400',
};

const statusColors = {
    planned: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(num);
};

const applyFilters = () => {
    router.get(route('tenant.sustainability.index'), {
        site_id: filterForm.value.site_id,
        year: filterForm.value.year,
    }, { preserveState: true });
};

const initCharts = () => {
    // Emissions Chart
    if (emissionsChart.value) {
        if (emissionsChartInstance) emissionsChartInstance.destroy();

        const ctx = emissionsChart.value.getContext('2d');
        emissionsChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: props.charts.monthlyEmissions.map(m => m.label),
                datasets: [
                    {
                        label: 'Électricité',
                        data: props.charts.monthlyEmissions.map(m => m.electricity / 1000),
                        backgroundColor: '#fbbf24',
                        borderRadius: 4,
                    },
                    {
                        label: 'Gaz',
                        data: props.charts.monthlyEmissions.map(m => m.gas / 1000),
                        backgroundColor: '#f97316',
                        borderRadius: 4,
                    },
                    {
                        label: 'Déchets',
                        data: props.charts.monthlyEmissions.map(m => m.waste / 1000),
                        backgroundColor: '#6b7280',
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                },
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Tonnes CO2',
                        },
                    },
                },
            },
        });
    }

    // Waste Breakdown Chart
    if (wasteChart.value) {
        if (wasteChartInstance) wasteChartInstance.destroy();

        const ctx = wasteChart.value.getContext('2d');
        wasteChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: props.charts.wasteBreakdown.map(w => w.name),
                datasets: [{
                    data: props.charts.wasteBreakdown.map(w => w.value),
                    backgroundColor: props.charts.wasteBreakdown.map(w => w.color),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                },
                cutout: '60%',
            },
        });
    }
};

onMounted(() => {
    initCharts();
});

watch(() => props.charts, () => {
    initCharts();
}, { deep: true });
</script>
