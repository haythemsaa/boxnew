<script setup>
/**
 * IoT Dashboard Widget
 * Real-time monitoring of sensors and smart locks
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    SignalIcon,
    SignalSlashIcon,
    BoltIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    LockClosedIcon,
    LockOpenIcon,
    ArrowPathIcon,
    ChevronRightIcon,
    BellAlertIcon,
    CpuChipIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    siteId: {
        type: Number,
        required: true,
    },
    refreshInterval: {
        type: Number,
        default: 30000, // 30 seconds
    },
})

const loading = ref(true)
const error = ref(null)
const data = ref(null)
const lastUpdate = ref(null)
let refreshTimer = null

// Computed stats
const sensorStats = computed(() => {
    if (!data.value?.sensors) return null
    return data.value.sensors
})

const alerts = computed(() => {
    if (!data.value?.alerts) return []
    return data.value.alerts.active || []
})

const statsByType = computed(() => {
    if (!data.value?.stats_by_type) return {}
    return data.value.stats_by_type
})

// Severity colors
const severityColors = {
    critical: 'bg-red-100 text-red-700 border-red-200',
    warning: 'bg-amber-100 text-amber-700 border-amber-200',
    info: 'bg-blue-100 text-blue-700 border-blue-200',
}

// Fetch IoT data
const fetchData = async () => {
    try {
        const response = await fetch(`/api/tenant/sites/${props.siteId}/iot/dashboard`)
        if (!response.ok) throw new Error('Failed to fetch IoT data')

        data.value = await response.json()
        lastUpdate.value = new Date()
        error.value = null
    } catch (e) {
        error.value = e.message
    } finally {
        loading.value = false
    }
}

// Manual refresh
const refresh = () => {
    loading.value = true
    fetchData()
}

// Format time ago
const formatTimeAgo = (date) => {
    if (!date) return ''
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)

    if (seconds < 60) return 'à l\'instant'
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)}h`
    return `il y a ${Math.floor(seconds / 86400)}j`
}

// Format sensor value with unit
const formatValue = (value, unit) => {
    if (value === null || value === undefined) return '-'
    return `${Math.round(value * 10) / 10}${unit}`
}

// Get status icon component
const getStatusIcon = (status) => {
    switch (status) {
        case 'active':
        case 'online':
            return SignalIcon
        case 'offline':
            return SignalSlashIcon
        case 'error':
            return ExclamationTriangleIcon
        default:
            return CpuChipIcon
    }
}

// Get status color class
const getStatusColor = (status) => {
    switch (status) {
        case 'active':
        case 'online':
            return 'text-green-500'
        case 'offline':
            return 'text-gray-400'
        case 'error':
            return 'text-red-500'
        default:
            return 'text-gray-500'
    }
}

// Lifecycle
onMounted(() => {
    fetchData()

    // Auto-refresh
    if (props.refreshInterval > 0) {
        refreshTimer = setInterval(fetchData, props.refreshInterval)
    }
})

onUnmounted(() => {
    if (refreshTimer) {
        clearInterval(refreshTimer)
    }
})
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <CpuChipIcon class="h-6 w-6 text-indigo-600" />
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900">IoT & Smart Locks</h3>
                    <p v-if="lastUpdate" class="text-xs text-gray-500">
                        Mis à jour {{ formatTimeAgo(lastUpdate) }}
                    </p>
                </div>
            </div>
            <button
                @click="refresh"
                :disabled="loading"
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
            >
                <ArrowPathIcon :class="['h-5 w-5 text-gray-500', loading && 'animate-spin']" />
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading && !data" class="p-8 text-center">
            <ArrowPathIcon class="h-8 w-8 mx-auto text-gray-400 animate-spin mb-2" />
            <p class="text-gray-500">Chargement des données IoT...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="p-8 text-center">
            <ExclamationTriangleIcon class="h-8 w-8 mx-auto text-red-400 mb-2" />
            <p class="text-red-600">{{ error }}</p>
            <button @click="refresh" class="mt-2 text-sm text-indigo-600 hover:underline">
                Réessayer
            </button>
        </div>

        <!-- Content -->
        <div v-else-if="data" class="p-4">
            <!-- Sensor Status Overview -->
            <div class="grid grid-cols-4 gap-3 mb-4">
                <div class="text-center p-3 bg-gray-50 rounded-xl">
                    <p class="text-2xl font-bold text-gray-900">{{ sensorStats?.total || 0 }}</p>
                    <p class="text-xs text-gray-500">Total</p>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-xl">
                    <p class="text-2xl font-bold text-green-600">{{ sensorStats?.online || 0 }}</p>
                    <p class="text-xs text-gray-500">En ligne</p>
                </div>
                <div class="text-center p-3 bg-gray-50 rounded-xl">
                    <p class="text-2xl font-bold text-gray-400">{{ sensorStats?.offline || 0 }}</p>
                    <p class="text-xs text-gray-500">Hors ligne</p>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-xl">
                    <p class="text-2xl font-bold text-red-600">{{ sensorStats?.error || 0 }}</p>
                    <p class="text-xs text-gray-500">Erreur</p>
                </div>
            </div>

            <!-- Sensor Types Stats -->
            <div v-if="Object.keys(statsByType).length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Capteurs par type</h4>
                <div class="space-y-2">
                    <div
                        v-for="(stat, key) in statsByType"
                        :key="key"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shadow-sm">
                                <span v-if="key === 'temperature'" class="text-lg">🌡️</span>
                                <span v-else-if="key === 'humidity'" class="text-lg">💧</span>
                                <span v-else-if="key === 'motion'" class="text-lg">👁️</span>
                                <span v-else-if="key === 'door'" class="text-lg">🚪</span>
                                <span v-else class="text-lg">📊</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ stat.name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ stat.online_count }}/{{ stat.sensor_count }} en ligne
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-900">
                                {{ formatValue(stat.current_avg, stat.unit) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ formatValue(stat.current_min, stat.unit) }} - {{ formatValue(stat.current_max, stat.unit) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Alerts -->
            <div v-if="alerts.length > 0" class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                    <BellAlertIcon class="h-4 w-4 text-amber-500" />
                    Alertes actives ({{ alerts.length }})
                </h4>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <div
                        v-for="alert in alerts.slice(0, 5)"
                        :key="alert.id"
                        :class="['p-3 rounded-xl border', severityColors[alert.severity] || severityColors.warning]"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-sm">{{ alert.title }}</p>
                                <p class="text-xs opacity-75">{{ alert.message }}</p>
                            </div>
                            <span class="text-xs opacity-75">
                                {{ formatTimeAgo(alert.created_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Alerts -->
            <div v-else class="mb-4 p-4 bg-green-50 rounded-xl text-center">
                <CheckCircleIcon class="h-8 w-8 mx-auto text-green-500 mb-1" />
                <p class="text-sm font-medium text-green-700">Aucune alerte active</p>
                <p class="text-xs text-green-600">Tous les systèmes fonctionnent normalement</p>
            </div>

            <!-- Quick Actions -->
            <div class="flex gap-2">
                <a
                    href="/tenant/iot"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-700 rounded-xl text-sm font-medium hover:bg-indigo-100 transition-colors"
                >
                    <CpuChipIcon class="h-4 w-4" />
                    Dashboard IoT
                </a>
                <a
                    href="/tenant/access-control"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-200 transition-colors"
                >
                    <LockClosedIcon class="h-4 w-4" />
                    Contrôle d'accès
                </a>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-8 text-center">
            <CpuChipIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
            <p class="text-gray-500 mb-2">Aucun capteur configuré</p>
            <a
                href="/tenant/iot/setup"
                class="text-sm text-indigo-600 hover:underline"
            >
                Configurer les capteurs IoT
            </a>
        </div>
    </div>
</template>
