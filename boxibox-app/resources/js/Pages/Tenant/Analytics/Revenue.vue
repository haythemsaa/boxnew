<template>
    <TenantLayout title="Analytics - Revenus">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <CurrencyEuroIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Analyse des Revenus</h1>
                        <p class="text-emerald-100 mt-1">Performance financière et métriques de revenus</p>
                    </div>
                </div>

                <!-- MRR/ARR Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-200 text-sm font-medium">Revenu Mensuel Récurrent</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(analytics.recurring.mrr) }}</p>
                                <p class="text-emerald-200 text-sm mt-1">{{ analytics.recurring.active_contracts }} contrats actifs</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <CalendarIcon class="w-7 h-7 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-200 text-sm font-medium">Revenu Annuel Récurrent</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(analytics.recurring.arr) }}</p>
                                <p class="text-emerald-200 text-sm mt-1">Revenu annuel projeté</p>
                            </div>
                            <div class="w-14 h-14 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <ArrowTrendingUpIcon class="w-7 h-7 text-emerald-200" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-emerald-200 text-sm font-medium">Revenu Période Actuelle</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(analytics.current_period.total_revenue) }}</p>
                                <p class="text-emerald-200 text-sm mt-1">{{ analytics.current_period.collected_rate.toFixed(1) }}% encaissé</p>
                            </div>
                            <div class="w-14 h-14 bg-purple-400/30 rounded-xl flex items-center justify-center">
                                <BanknotesIcon class="w-7 h-7 text-purple-200" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Revenu en attente</p>
                            <p class="text-2xl font-bold text-amber-600 mt-2">{{ formatCurrency(analytics.current_period.pending_revenue) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <ClockIcon class="w-6 h-6 text-amber-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Revenu en retard</p>
                            <p class="text-2xl font-bold text-red-600 mt-2">{{ formatCurrency(analytics.current_period.overdue_revenue) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                            <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Taux d'encaissement</p>
                            <p class="text-2xl font-bold text-emerald-600 mt-2">{{ analytics.current_period.collected_rate.toFixed(1) }}%</p>
                        </div>
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <CheckCircleIcon class="w-6 h-6 text-emerald-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Metrics -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ChartBarIcon class="w-5 h-5 text-emerald-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Indicateurs Clés de Performance</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <CubeIcon class="w-5 h-5 text-blue-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">RevPAU</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.metrics.revpau) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu par unité disponible</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <Square3Stack3DIcon class="w-5 h-5 text-purple-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">RevPAF</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.metrics.revpaf) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu par m² disponible</p>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-5">
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                    <UserIcon class="w-5 h-5 text-emerald-600" />
                                </div>
                                <span class="text-sm font-medium text-gray-600">ARPU</span>
                            </div>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(analytics.metrics.arpu) }}</p>
                            <p class="text-xs text-gray-500 mt-1">Revenu moyen par client</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Trend Chart -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ArrowTrendingUpIcon class="w-5 h-5 text-emerald-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Tendance des Revenus sur 12 Mois</h2>
                </div>
                <div class="p-6">
                    <div class="h-80">
                        <canvas ref="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ChartPieIcon class="w-5 h-5 text-emerald-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Répartition des Revenus</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-for="(amount, type) in analytics.breakdown" :key="type" class="bg-gray-50 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-gray-900 capitalize">{{ translateType(type) }}</span>
                                <span class="text-emerald-600 font-bold">{{ formatCurrency(amount) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2.5 rounded-full transition-all duration-500"
                                    :style="{width: getPercentage(amount, Object.values(analytics.breakdown).reduce((a,b) => a+b, 0)) + '%'}"
                                ></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 text-right">
                                {{ getPercentage(amount, Object.values(analytics.breakdown).reduce((a,b) => a+b, 0)).toFixed(1) }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Chart, registerables } from 'chart.js'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CurrencyEuroIcon,
    CalendarIcon,
    ArrowTrendingUpIcon,
    BanknotesIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    ChartBarIcon,
    ChartPieIcon,
    CubeIcon,
    Square3Stack3DIcon,
    UserIcon,
} from '@heroicons/vue/24/outline'

Chart.register(...registerables)

const props = defineProps({
    analytics: Object,
})

const revenueChart = ref(null)

const formatCurrency = (num) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(num)
}

const getPercentage = (value, total) => {
    return total > 0 ? (value / total) * 100 : 0
}

const translateType = (type) => {
    const translations = {
        'rent': 'Loyer',
        'deposit': 'Dépôt',
        'insurance': 'Assurance',
        'fees': 'Frais',
        'other': 'Autre',
    }
    return translations[type] || type
}

onMounted(() => {
    if (revenueChart.value) {
        new Chart(revenueChart.value, {
            type: 'bar',
            data: {
                labels: props.analytics.trend.map(t => t.month_name),
                datasets: [{
                    label: 'Revenu (€)',
                    data: props.analytics.trend.map(t => t.revenue),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 1,
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                family: 'Inter',
                                weight: 500
                            }
                        }
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(value),
                            font: {
                                family: 'Inter'
                            }
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
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
                }
            }
        })
    }
})
</script>
