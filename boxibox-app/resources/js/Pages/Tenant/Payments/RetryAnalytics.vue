<script setup>
import { ref, computed, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    period: String,
    recoveryByDay: Object,
    failureReasons: Array,
    heatmap: Array,
    amountRecovered: Number,
    stats: Object,
})

const selectedPeriod = ref(props.period || '30')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const changePeriod = (period) => {
    selectedPeriod.value = period
    router.get(route('tenant.payment-retries.analytics'), { period }, {
        preserveState: true,
        replace: true,
    })
}

const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']

const getFailureLabel = (reason) => {
    const labels = {
        insufficient_funds: 'Fonds insuffisants',
        card_declined: 'Carte refusée',
        expired_card: 'Carte expirée',
        processing_error: 'Erreur technique',
        authentication_required: 'Auth. requise',
        do_not_honor: 'Refusé',
        generic_decline: 'Refus générique',
    }
    return labels[reason] || reason || 'Inconnu'
}

// Generate heatmap data
const heatmapData = computed(() => {
    const data = {}
    // Initialize all slots
    for (let day = 0; day < 7; day++) {
        for (let hour = 6; hour < 22; hour++) {
            data[`${day}-${hour}`] = 0
        }
    }
    // Fill with actual data
    if (props.heatmap) {
        props.heatmap.forEach(item => {
            const key = `${item.day_of_week}-${item.hour_of_day}`
            data[key] = item.count
        })
    }
    return data
})

const maxHeatmapValue = computed(() => {
    return Math.max(...Object.values(heatmapData.value), 1)
})

const getHeatmapColor = (value) => {
    if (value === 0) return 'bg-gray-100'
    const intensity = value / maxHeatmapValue.value
    if (intensity > 0.75) return 'bg-emerald-500'
    if (intensity > 0.5) return 'bg-emerald-400'
    if (intensity > 0.25) return 'bg-emerald-300'
    return 'bg-emerald-200'
}

// Chart data for recovery trend
const chartDays = computed(() => {
    if (!props.recoveryByDay) return []
    return Object.keys(props.recoveryByDay).slice(-14)
})

const chartData = computed(() => {
    if (!props.recoveryByDay) return []
    return chartDays.value.map(day => props.recoveryByDay[day]?.rate || 0)
})

const maxRate = computed(() => Math.max(...chartData.value, 100))
</script>

<template>
    <TenantLayout title="Analytics Retry">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <Link
                                :href="route('tenant.payment-retries.index')"
                                class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
                            >
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Analytics de Récupération</h1>
                                <p class="text-gray-500 mt-1">Analysez les performances de vos relances automatiques</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-0 flex gap-2 animate-fade-in-up" style="animation-delay: 0.1s">
                        <button
                            v-for="p in ['7', '30', '90']"
                            :key="p"
                            @click="changePeriod(p)"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                selectedPeriod === p
                                    ? 'bg-purple-600 text-white'
                                    : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'
                            ]"
                        >
                            {{ p }} jours
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Montant Récupéré</p>
                                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatCurrency(amountRecovered) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.15s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Taux de Récupération</p>
                                <p class="text-2xl font-bold text-purple-600 mt-1">{{ stats?.recovery_rate || 0 }}%</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tentative Moyenne</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats?.average_recovery_attempt || '-' }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.25s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">En Attente</p>
                                <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats?.total_pending || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Recovery Trend Chart -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.3s">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                                </svg>
                                Évolution du Taux de Récupération
                            </h2>
                        </div>
                        <div class="p-6">
                            <div v-if="chartDays.length > 0" class="h-48 flex items-end gap-1">
                                <div
                                    v-for="(rate, index) in chartData"
                                    :key="chartDays[index]"
                                    class="flex-1 flex flex-col items-center gap-1"
                                >
                                    <span class="text-xs font-medium text-gray-600">{{ rate }}%</span>
                                    <div
                                        class="w-full rounded-t-lg transition-all duration-500"
                                        :class="rate >= 50 ? 'bg-emerald-500' : rate > 0 ? 'bg-amber-500' : 'bg-gray-200'"
                                        :style="{ height: `${Math.max((rate / maxRate) * 150, 4)}px` }"
                                    ></div>
                                    <span class="text-xs text-gray-400 -rotate-45 origin-top-left">
                                        {{ chartDays[index].slice(5) }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="h-48 flex items-center justify-center text-gray-400">
                                Pas de données pour cette période
                            </div>
                        </div>
                    </div>

                    <!-- Failure Reasons -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.35s">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Raisons d'Échec
                            </h2>
                        </div>
                        <div class="p-6">
                            <div v-if="failureReasons && failureReasons.length > 0" class="space-y-4">
                                <div
                                    v-for="reason in failureReasons"
                                    :key="reason.reason"
                                    class="space-y-2"
                                >
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="font-medium text-gray-700">{{ getFailureLabel(reason.reason) }}</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-gray-500">{{ reason.recovered }}/{{ reason.count }}</span>
                                            <span
                                                class="font-semibold"
                                                :class="reason.rate >= 50 ? 'text-emerald-600' : 'text-red-600'"
                                            >
                                                {{ reason.rate }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                        <div
                                            class="h-2.5 rounded-full transition-all duration-500"
                                            :class="reason.rate >= 50 ? 'bg-emerald-500' : 'bg-red-500'"
                                            :style="{ width: `${reason.rate}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="h-48 flex items-center justify-center text-gray-400">
                                Pas de données pour cette période
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Heatmap -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.4s">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Meilleurs Moments pour les Relances
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Intensité des paiements récupérés par jour/heure</p>
                    </div>
                    <div class="p-6 overflow-x-auto">
                        <div class="min-w-[600px]">
                            <!-- Hours header -->
                            <div class="flex items-center mb-2">
                                <div class="w-12"></div>
                                <div class="flex-1 grid grid-cols-16 gap-1">
                                    <div
                                        v-for="hour in 16"
                                        :key="hour"
                                        class="text-center text-xs text-gray-400"
                                    >
                                        {{ hour + 5 }}h
                                    </div>
                                </div>
                            </div>
                            <!-- Days -->
                            <div
                                v-for="day in 7"
                                :key="day"
                                class="flex items-center mb-1"
                            >
                                <div class="w-12 text-xs font-medium text-gray-500">{{ dayNames[day - 1] }}</div>
                                <div class="flex-1 grid grid-cols-16 gap-1">
                                    <div
                                        v-for="hour in 16"
                                        :key="hour"
                                        :class="[
                                            'h-6 rounded-sm transition-colors cursor-default',
                                            getHeatmapColor(heatmapData[`${day - 1}-${hour + 5}`] || 0)
                                        ]"
                                        :title="`${dayNames[day - 1]} ${hour + 5}h: ${heatmapData[`${day - 1}-${hour + 5}`] || 0} récupérés`"
                                    ></div>
                                </div>
                            </div>
                            <!-- Legend -->
                            <div class="flex items-center justify-end mt-4 gap-2">
                                <span class="text-xs text-gray-500">Moins</span>
                                <div class="flex gap-1">
                                    <div class="w-4 h-4 rounded-sm bg-gray-100"></div>
                                    <div class="w-4 h-4 rounded-sm bg-emerald-200"></div>
                                    <div class="w-4 h-4 rounded-sm bg-emerald-300"></div>
                                    <div class="w-4 h-4 rounded-sm bg-emerald-400"></div>
                                    <div class="w-4 h-4 rounded-sm bg-emerald-500"></div>
                                </div>
                                <span class="text-xs text-gray-500">Plus</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Best Recovery Times -->
                <div v-if="stats?.best_times && stats.best_times.length > 0" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.45s">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Top 5 Créneaux Optimaux
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                            <div
                                v-for="(slot, index) in stats.best_times.slice(0, 5)"
                                :key="index"
                                class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 text-center"
                            >
                                <div class="text-2xl font-bold text-blue-600">{{ slot.hour_of_day }}h</div>
                                <div class="text-sm font-medium text-gray-700 mt-1">{{ dayNames[slot.day_of_week] }}</div>
                                <div class="text-xs text-gray-500 mt-2">{{ slot.success_count }} récupérés</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
