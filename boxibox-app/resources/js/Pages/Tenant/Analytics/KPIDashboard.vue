<template>
    <TenantLayout title="KPIs Self-Storage">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <ChartBarSquareIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">KPIs Self-Storage</h1>
                            <p class="text-indigo-100 mt-1">Indicateurs de performance professionnels</p>
                        </div>
                    </div>

                    <!-- Site Filter -->
                    <div class="flex items-center space-x-4">
                        <select
                            v-model="selectedSite"
                            @change="refreshKpis"
                            class="px-4 py-2 bg-white/20 border border-white/30 rounded-xl text-white placeholder-white/70 focus:ring-2 focus:ring-white/50 focus:border-transparent backdrop-blur-sm"
                        >
                            <option value="" class="text-gray-900">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id" class="text-gray-900">
                                {{ site.name }}
                            </option>
                        </select>
                        <button
                            @click="refreshKpis"
                            :disabled="loading"
                            class="px-4 py-2 bg-white/20 rounded-xl text-white hover:bg-white/30 transition flex items-center space-x-2"
                        >
                            <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" />
                            <span>Actualiser</span>
                        </button>
                    </div>
                </div>

                <!-- Key Metrics Header Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm font-medium">Occupation Physique</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatPercent(kpis.occupancy?.physical_occupancy) }}</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <BuildingStorefrontIcon class="w-7 h-7 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm font-medium">Occupation Economique</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatPercent(kpis.occupancy?.economic_occupancy) }}</p>
                            </div>
                            <div class="w-14 h-14 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <CurrencyEuroIcon class="w-7 h-7 text-emerald-200" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm font-medium">NOI Mensuel</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(kpis.revenue?.noi) }}</p>
                            </div>
                            <div class="w-14 h-14 bg-amber-400/30 rounded-xl flex items-center justify-center">
                                <BanknotesIcon class="w-7 h-7 text-amber-200" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm font-medium">Marge NOI</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatPercent(kpis.financial?.noi_margin) }}</p>
                            </div>
                            <div class="w-14 h-14 bg-purple-400/30 rounded-xl flex items-center justify-center">
                                <ChartPieIcon class="w-7 h-7 text-purple-200" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Revenue Metrics Section -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <CurrencyEuroIcon class="w-5 h-5 text-indigo-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Indicateurs de Revenu</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <CalendarIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">MRR</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.revenue?.mrr) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu Mensuel Récurrent</p>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <Square3Stack3DIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">RevPASF</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.revenue?.revpasf) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu par m² disponible</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <CubeIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">RevPOSF</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.revenue?.revposf) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu par m² occupé</p>
                        </div>

                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <BuildingOffice2Icon class="w-5 h-5 text-amber-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">RevPAB</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(kpis.revenue?.revpab) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu par box disponible</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Operations Metrics -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <ArrowsRightLeftIcon class="w-5 h-5 text-indigo-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Indicateurs Opérationnels</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <ArrowUpIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Move-ins ce mois</p>
                                    <p class="text-sm text-gray-500">Nouvelles entrées</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-emerald-600">{{ kpis.operations?.move_ins_this_month || 0 }}</p>
                                <p class="text-sm text-gray-500">{{ formatPercent(kpis.operations?.move_in_rate) }} taux</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                    <ArrowDownIcon class="w-5 h-5 text-red-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Move-outs ce mois</p>
                                    <p class="text-sm text-gray-500">Départs</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-red-600">{{ kpis.operations?.move_outs_this_month || 0 }}</p>
                                <p class="text-sm text-gray-500">{{ formatPercent(kpis.operations?.move_out_rate) }} taux</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                    <ArrowPathIcon class="w-5 h-5 text-amber-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Taux de Churn</p>
                                    <p class="text-sm text-gray-500">Rotation mensuelle</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold" :class="kpis.operations?.churn_rate > 5 ? 'text-red-600' : 'text-amber-600'">
                                    {{ formatPercent(kpis.operations?.churn_rate) }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <ClockIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Durée Moyenne de Séjour</p>
                                    <p class="text-sm text-gray-500">Length of Stay</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-blue-600">{{ (kpis.operations?.avg_length_of_stay || 0).toFixed(1) }}</p>
                                <p class="text-sm text-gray-500">mois</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Metrics -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <UserGroupIcon class="w-5 h-5 text-indigo-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Indicateurs Client</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <HeartIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Customer Lifetime Value</p>
                                    <p class="text-sm text-gray-500">CLV moyen</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-purple-600">{{ formatCurrency(kpis.customer?.clv) }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                    <CurrencyEuroIcon class="w-5 h-5 text-indigo-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">ARPU</p>
                                    <p class="text-sm text-gray-500">Revenu Moyen par Client</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-indigo-600">{{ formatCurrency(kpis.customer?.arpu) }}</p>
                                <p class="text-sm text-gray-500">/ mois</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <ChartBarIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Ratio CLV/CAC</p>
                                    <p class="text-sm text-gray-500">Rentabilité acquisition</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold" :class="kpis.customer?.clv_cac_ratio >= 3 ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ (kpis.customer?.clv_cac_ratio || 0).toFixed(1) }}x
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <UsersIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Clients Actifs</p>
                                    <p class="text-sm text-gray-500">Avec contrats en cours</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-blue-600">{{ kpis.customer?.active_customers || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Metrics -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <BanknotesIcon class="w-5 h-5 text-indigo-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Indicateurs Financiers</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div class="p-5 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500 mb-2">Taux de Recouvrement</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ formatPercent(kpis.financial?.collection_rate) }}</p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-emerald-500 h-2 rounded-full" :style="{ width: `${kpis.financial?.collection_rate || 0}%` }"></div>
                            </div>
                        </div>

                        <div class="p-5 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500 mb-2">Taux d'Impayés</p>
                            <p class="text-2xl font-bold" :class="kpis.financial?.delinquency_rate > 5 ? 'text-red-600' : 'text-amber-600'">
                                {{ formatPercent(kpis.financial?.delinquency_rate) }}
                            </p>
                            <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full" :style="{ width: `${Math.min(kpis.financial?.delinquency_rate || 0, 100)}%` }"></div>
                            </div>
                        </div>

                        <div class="p-5 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500 mb-2">Montant en Retard</p>
                            <p class="text-2xl font-bold text-red-600">{{ formatCurrency(kpis.financial?.total_delinquent) }}</p>
                        </div>

                        <div class="p-5 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500 mb-2">DSO (Days Sales Outstanding)</p>
                            <p class="text-2xl font-bold text-blue-600">{{ (kpis.financial?.dso || 0).toFixed(0) }}</p>
                            <p class="text-xs text-gray-500 mt-1">jours</p>
                        </div>
                    </div>

                    <!-- Delinquency Breakdown -->
                    <div class="mt-6 pt-6 border-t border-gray-100" v-if="kpis.financial?.delinquency_buckets">
                        <h3 class="font-medium text-gray-900 mb-4">Répartition des Impayés par Ancienneté</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="p-4 bg-amber-50 rounded-xl text-center">
                                <p class="text-sm text-amber-700">1-30 jours</p>
                                <p class="text-xl font-bold text-amber-600">{{ formatCurrency(kpis.financial.delinquency_buckets['1-30'] || 0) }}</p>
                            </div>
                            <div class="p-4 bg-orange-50 rounded-xl text-center">
                                <p class="text-sm text-orange-700">31-60 jours</p>
                                <p class="text-xl font-bold text-orange-600">{{ formatCurrency(kpis.financial.delinquency_buckets['31-60'] || 0) }}</p>
                            </div>
                            <div class="p-4 bg-red-50 rounded-xl text-center">
                                <p class="text-sm text-red-700">61-90 jours</p>
                                <p class="text-xl font-bold text-red-600">{{ formatCurrency(kpis.financial.delinquency_buckets['61-90'] || 0) }}</p>
                            </div>
                            <div class="p-4 bg-red-100 rounded-xl text-center">
                                <p class="text-sm text-red-800">90+ jours</p>
                                <p class="text-xl font-bold text-red-700">{{ formatCurrency(kpis.financial.delinquency_buckets['90+'] || 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trends Section -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden" v-if="kpis.trends">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ArrowTrendingUpIcon class="w-5 h-5 text-indigo-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Tendances sur 12 Mois</h2>
                </div>
                <div class="p-6">
                    <div class="h-80 mb-6">
                        <canvas ref="trendChart"></canvas>
                    </div>

                    <!-- YoY Comparison -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-6 border-t border-gray-100" v-if="kpis.trends?.yoy">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="text-gray-600">Revenu vs N-1</span>
                            <span class="font-bold" :class="kpis.trends.yoy.revenue_growth >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ kpis.trends.yoy.revenue_growth >= 0 ? '+' : '' }}{{ formatPercent(kpis.trends.yoy.revenue_growth) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="text-gray-600">Occupation vs N-1</span>
                            <span class="font-bold" :class="kpis.trends.yoy.occupancy_change >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ kpis.trends.yoy.occupancy_change >= 0 ? '+' : '' }}{{ (kpis.trends.yoy.occupancy_change || 0).toFixed(1) }} pts
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <span class="text-gray-600">Clients vs N-1</span>
                            <span class="font-bold" :class="kpis.trends.yoy.customer_growth >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                {{ kpis.trends.yoy.customer_growth >= 0 ? '+' : '' }}{{ formatPercent(kpis.trends.yoy.customer_growth) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChartBarSquareIcon,
    ChartBarIcon,
    ChartPieIcon,
    BuildingStorefrontIcon,
    CurrencyEuroIcon,
    BanknotesIcon,
    CalendarIcon,
    ClockIcon,
    Square3Stack3DIcon,
    CubeIcon,
    BuildingOffice2Icon,
    ArrowsRightLeftIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    ArrowPathIcon,
    ArrowTrendingUpIcon,
    UserGroupIcon,
    UsersIcon,
    HeartIcon,
} from '@heroicons/vue/24/outline'

Chart.register(...registerables)

const props = defineProps({
    kpis: {
        type: Object,
        default: () => ({})
    },
    sites: {
        type: Array,
        default: () => []
    },
    selectedSiteId: {
        type: [Number, String],
        default: null
    }
})

const selectedSite = ref(props.selectedSiteId || '')
const loading = ref(false)
const trendChart = ref(null)
let chartInstance = null

const formatCurrency = (num) => {
    if (num === null || num === undefined) return '0 €'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(num)
}

const formatPercent = (num) => {
    if (num === null || num === undefined) return '0%'
    return `${parseFloat(num).toFixed(1)}%`
}

const refreshKpis = () => {
    loading.value = true
    router.get(route('tenant.analytics.kpis'), {
        site_id: selectedSite.value || null
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            loading.value = false
        }
    })
}

const initChart = () => {
    if (!trendChart.value || !props.kpis.trends?.monthly) return

    if (chartInstance) {
        chartInstance.destroy()
    }

    const monthly = props.kpis.trends.monthly

    chartInstance = new Chart(trendChart.value, {
        type: 'line',
        data: {
            labels: monthly.map(m => m.month),
            datasets: [
                {
                    label: 'Revenu (€)',
                    data: monthly.map(m => m.revenue),
                    borderColor: 'rgb(79, 70, 229)',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y',
                },
                {
                    label: 'Occupation (%)',
                    data: monthly.map(m => m.occupancy),
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: false,
                    tension: 0.4,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenu (€)'
                    },
                    ticks: {
                        callback: (value) => new Intl.NumberFormat('fr-FR', {
                            style: 'currency',
                            currency: 'EUR',
                            maximumFractionDigits: 0
                        }).format(value),
                    },
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Occupation (%)'
                    },
                    min: 0,
                    max: 100,
                    grid: {
                        drawOnChartArea: false,
                    },
                },
            }
        }
    })
}

onMounted(() => {
    nextTick(() => {
        initChart()
    })
})

watch(() => props.kpis, () => {
    nextTick(() => {
        initChart()
    })
}, { deep: true })
</script>
