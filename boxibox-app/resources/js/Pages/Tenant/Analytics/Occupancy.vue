<template>
    <TenantLayout title="Analytics - Occupation">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <ChartBarSquareIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Analyse de l'Occupation</h1>
                            <p class="text-blue-100 mt-1">Suivi en temps réel et tendances d'occupation</p>
                        </div>
                    </div>
                    <div class="mt-4 lg:mt-0">
                        <select
                            v-model="selectedPeriod"
                            @change="loadData"
                            class="px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl border border-white/30 focus:outline-none focus:ring-2 focus:ring-white/50"
                        >
                            <option value="week" class="text-gray-900">Dernière semaine</option>
                            <option value="month" class="text-gray-900">Dernier mois</option>
                            <option value="quarter" class="text-gray-900">Dernier trimestre</option>
                            <option value="year" class="text-gray-900">Dernière année</option>
                        </select>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Taux d'occupation</p>
                                <p class="text-3xl font-bold mt-1" :class="getOccupancyColor(analytics.current.occupancy_rate)">
                                    {{ analytics.current.occupancy_rate.toFixed(1) }}%
                                </p>
                                <span :class="getStatusBadgeClass(analytics.current.occupancy_status)" class="text-xs mt-1 inline-block">
                                    {{ translateStatus(analytics.current.occupancy_status) }}
                                </span>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <ChartPieIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Boxes occupés</p>
                                <p class="text-3xl font-bold text-white mt-1">
                                    {{ analytics.current.occupied }}
                                </p>
                                <p class="text-blue-200 text-xs mt-1">sur {{ analytics.current.total_boxes }} boxes</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-400/30 rounded-xl flex items-center justify-center">
                                <CubeIcon class="w-6 h-6 text-blue-200" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Disponibles</p>
                                <p class="text-3xl font-bold text-emerald-300 mt-1">
                                    {{ analytics.current.available }}
                                </p>
                                <p class="text-blue-200 text-xs mt-1">prêts à louer</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Réservés</p>
                                <p class="text-3xl font-bold text-amber-300 mt-1">
                                    {{ analytics.current.reserved }}
                                </p>
                                <p class="text-blue-200 text-xs mt-1">en attente</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-400/30 rounded-xl flex items-center justify-center">
                                <ClockIcon class="w-6 h-6 text-amber-300" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Trend Chart -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ArrowTrendingUpIcon class="w-5 h-5 text-blue-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Tendance d'Occupation sur 12 Mois</h2>
                </div>
                <div class="p-6">
                    <div class="h-80">
                        <canvas ref="trendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- By Type & Size -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- By Type -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <Square3Stack3DIcon class="w-5 h-5 text-blue-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Occupation par Type</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-for="(data, type) in analytics.by_type" :key="type" class="bg-gray-50 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-gray-900">{{ type }}</span>
                                <span class="text-sm">
                                    <span class="text-blue-600 font-bold">{{ data.occupied }}</span>
                                    <span class="text-gray-400"> / {{ data.total }}</span>
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium" :class="getOccupancyBadgeClass(data.occupancy_rate)">
                                        {{ data.occupancy_rate.toFixed(1) }}%
                                    </span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2.5 rounded-full transition-all duration-500"
                                    :style="{width: data.occupancy_rate + '%'}"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- By Size -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <CubeIcon class="w-5 h-5 text-blue-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Occupation par Taille</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div v-for="(data, size) in analytics.by_size" :key="size" class="bg-gray-50 rounded-xl p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="font-semibold text-gray-900">{{ size }} m²</span>
                                <span class="text-sm">
                                    <span class="text-emerald-600 font-bold">{{ data.occupied }}</span>
                                    <span class="text-gray-400"> / {{ data.total }}</span>
                                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium" :class="getOccupancyBadgeClass(data.occupancy_rate)">
                                        {{ data.occupancy_rate.toFixed(1) }}%
                                    </span>
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2.5 rounded-full transition-all duration-500"
                                    :style="{width: data.occupancy_rate + '%'}"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Move-ins vs Move-outs -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ArrowsRightLeftIcon class="w-5 h-5 text-blue-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Entrées vs Sorties ce Mois</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-6 text-center">
                            <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <ArrowDownTrayIcon class="w-7 h-7 text-emerald-600" />
                            </div>
                            <p class="text-sm font-medium text-gray-600">Entrées</p>
                            <p class="text-4xl font-bold text-emerald-600 mt-2">{{ analytics.move_ins_this_month }}</p>
                            <p class="text-xs text-gray-500 mt-1">nouveaux contrats</p>
                        </div>
                        <div class="bg-gradient-to-br from-red-50 to-orange-50 rounded-xl p-6 text-center">
                            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <ArrowUpTrayIcon class="w-7 h-7 text-red-600" />
                            </div>
                            <p class="text-sm font-medium text-gray-600">Sorties</p>
                            <p class="text-4xl font-bold text-red-600 mt-2">{{ analytics.move_outs_this_month }}</p>
                            <p class="text-xs text-gray-500 mt-1">contrats terminés</p>
                        </div>
                    </div>
                    <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Balance nette</span>
                            <span :class="[
                                'text-lg font-bold',
                                analytics.move_ins_this_month - analytics.move_outs_this_month >= 0 ? 'text-emerald-600' : 'text-red-600'
                            ]">
                                {{ analytics.move_ins_this_month - analytics.move_outs_this_month >= 0 ? '+' : '' }}{{ analytics.move_ins_this_month - analytics.move_outs_this_month }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Chart, registerables } from 'chart.js'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChartBarSquareIcon,
    ChartPieIcon,
    CubeIcon,
    CheckCircleIcon,
    ClockIcon,
    ArrowTrendingUpIcon,
    Square3Stack3DIcon,
    ArrowsRightLeftIcon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
} from '@heroicons/vue/24/outline'

Chart.register(...registerables)

const props = defineProps({
    analytics: Object,
})

const selectedPeriod = ref('month')
const trendChart = ref(null)

const loadData = () => {
    router.reload({ data: { period: selectedPeriod.value } })
}

const getOccupancyColor = (rate) => {
    if (rate >= 90) return 'text-emerald-300'
    if (rate >= 70) return 'text-amber-300'
    return 'text-red-300'
}

const getOccupancyBadgeClass = (rate) => {
    if (rate >= 90) return 'bg-emerald-100 text-emerald-700'
    if (rate >= 70) return 'bg-amber-100 text-amber-700'
    return 'bg-red-100 text-red-700'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        'excellent': 'bg-emerald-500/30 text-emerald-200 px-2 py-0.5 rounded-full',
        'good': 'bg-blue-500/30 text-blue-200 px-2 py-0.5 rounded-full',
        'medium': 'bg-amber-500/30 text-amber-200 px-2 py-0.5 rounded-full',
        'low': 'bg-red-500/30 text-red-200 px-2 py-0.5 rounded-full',
    }
    return classes[status] || 'bg-gray-500/30 text-gray-200 px-2 py-0.5 rounded-full'
}

const translateStatus = (status) => {
    const translations = {
        'excellent': 'Excellent',
        'good': 'Bon',
        'medium': 'Moyen',
        'low': 'Faible',
    }
    return translations[status] || status
}

onMounted(() => {
    if (trendChart.value) {
        new Chart(trendChart.value, {
            type: 'line',
            data: {
                labels: props.analytics.trend.map(t => t.month_name),
                datasets: [{
                    label: 'Taux d\'occupation (%)',
                    data: props.analytics.trend.map(t => t.occupancy_rate),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
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
                        max: 100,
                        ticks: {
                            callback: (value) => value + '%',
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
