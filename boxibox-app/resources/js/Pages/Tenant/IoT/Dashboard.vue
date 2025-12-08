<script setup>
/**
 * IoT Dashboard - Complete monitoring interface for sensors and smart locks
 */
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CpuChipIcon,
    SignalIcon,
    SignalSlashIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    LockClosedIcon,
    LockOpenIcon,
    ArrowPathIcon,
    BellAlertIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    PlusIcon,
    EyeIcon,
    BoltIcon,
    ClockIcon,
    MapPinIcon,
    Battery50Icon,
    WifiIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    currentSiteId: Number,
    site: Object,
    sensors: Array,
    smartLocks: Array,
    alerts: Array,
    stats: Object,
    recentActivity: Array,
})

// State
const selectedTab = ref('overview')
const refreshing = ref(false)
const selectedSensor = ref(null)
const showSensorModal = ref(false)
const selectedSiteId = ref(props.currentSiteId)

// Change site
const changeSite = (siteId) => {
    router.get('/tenant/iot', { site_id: siteId }, { preserveState: false })
}

// Auto-refresh
let refreshInterval = null
const lastRefresh = ref(new Date())

// Tabs
const tabs = [
    { id: 'overview', name: 'Vue d\'ensemble', icon: ChartBarIcon },
    { id: 'sensors', name: 'Capteurs', icon: CpuChipIcon },
    { id: 'locks', name: 'Serrures', icon: LockClosedIcon },
    { id: 'alerts', name: 'Alertes', icon: BellAlertIcon },
    { id: 'activity', name: 'Activité', icon: ClockIcon },
]

// Computed
const onlineSensors = computed(() => props.sensors?.filter(s => s.status === 'active') || [])
const offlineSensors = computed(() => props.sensors?.filter(s => s.status === 'offline') || [])
const activeAlerts = computed(() => props.alerts?.filter(a => a.status === 'active') || [])
const criticalAlerts = computed(() => activeAlerts.value.filter(a => a.severity === 'critical'))

const sensorsByType = computed(() => {
    if (!props.sensors) return {}
    return props.sensors.reduce((acc, sensor) => {
        const type = sensor.sensor_type?.slug || 'other'
        if (!acc[type]) acc[type] = []
        acc[type].push(sensor)
        return acc
    }, {})
})

// Helpers
const formatValue = (value, unit) => {
    if (value === null || value === undefined) return '-'
    return `${Math.round(value * 10) / 10}${unit || ''}`
}

const formatTimeAgo = (date) => {
    if (!date) return 'jamais'
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    if (seconds < 60) return 'à l\'instant'
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)}h`
    return `il y a ${Math.floor(seconds / 86400)}j`
}

const getStatusColor = (status) => {
    switch (status) {
        case 'active': return 'text-green-500 bg-green-50'
        case 'offline': return 'text-gray-400 bg-gray-50'
        case 'error': return 'text-red-500 bg-red-50'
        default: return 'text-gray-500 bg-gray-50'
    }
}

const getSeverityColor = (severity) => {
    switch (severity) {
        case 'critical': return 'bg-red-100 text-red-700 border-red-200'
        case 'warning': return 'bg-amber-100 text-amber-700 border-amber-200'
        default: return 'bg-blue-100 text-blue-700 border-blue-200'
    }
}

const getSensorIcon = (type) => {
    switch (type) {
        case 'temperature': return '🌡️'
        case 'humidity': return '💧'
        case 'motion': return '👁️'
        case 'door': return '🚪'
        case 'smoke': return '🔥'
        case 'water': return '💦'
        default: return '📊'
    }
}

const getBatteryColor = (level) => {
    if (level > 50) return 'text-green-500'
    if (level > 20) return 'text-amber-500'
    return 'text-red-500'
}

// Actions
const refresh = () => {
    refreshing.value = true
    router.reload({
        only: ['sensors', 'smartLocks', 'alerts', 'stats', 'recentActivity'],
        onFinish: () => {
            refreshing.value = false
            lastRefresh.value = new Date()
        }
    })
}

const viewSensor = (sensor) => {
    selectedSensor.value = sensor
    showSensorModal.value = true
}

const unlockDoor = async (lock) => {
    if (!confirm(`Déverrouiller ${lock.name} ?`)) return

    try {
        await router.post(`/tenant/iot/locks/${lock.id}/unlock`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Success notification handled by backend
            }
        })
    } catch (e) {
        console.error('Unlock failed:', e)
    }
}

const acknowledgeAlert = async (alert) => {
    try {
        await router.post(`/tenant/iot/alerts/${alert.id}/acknowledge`, {}, {
            preserveScroll: true,
        })
    } catch (e) {
        console.error('Acknowledge failed:', e)
    }
}

const resolveAlert = async (alert) => {
    try {
        await router.post(`/tenant/iot/alerts/${alert.id}/resolve`, {}, {
            preserveScroll: true,
        })
    } catch (e) {
        console.error('Resolve failed:', e)
    }
}

// Lifecycle
onMounted(() => {
    // Auto-refresh every 30 seconds
    refreshInterval = setInterval(refresh, 30000)
})

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<template>
    <TenantLayout>
        <Head title="Dashboard IoT" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard IoT</h1>
                    <p class="text-gray-500 mt-1">
                        Mis à jour {{ formatTimeAgo(lastRefresh) }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Site Selector -->
                    <select
                        v-if="sites?.length > 1"
                        v-model="selectedSiteId"
                        @change="changeSite(selectedSiteId)"
                        class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option v-for="s in sites" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                    <button
                        @click="refresh"
                        :disabled="refreshing"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        <ArrowPathIcon :class="['h-5 w-5', refreshing && 'animate-spin']" />
                        Actualiser
                    </button>
                    <Link
                        href="/tenant/iot/reports"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        <ChartBarIcon class="h-5 w-5" />
                        Rapports
                    </Link>
                    <Link
                        href="/tenant/access-control/dashboard"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors"
                    >
                        <Cog6ToothIcon class="h-5 w-5" />
                        Contrôle d'accès
                    </Link>
                </div>
            </div>

            <!-- Alert Banner (if critical alerts) -->
            <div v-if="criticalAlerts.length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <ExclamationTriangleIcon class="h-6 w-6 text-red-500 flex-shrink-0" />
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-800">
                            {{ criticalAlerts.length }} alerte(s) critique(s)
                        </h3>
                        <p class="text-red-600 text-sm">
                            {{ criticalAlerts[0]?.message }}
                        </p>
                    </div>
                    <button
                        @click="selectedTab = 'alerts'"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700"
                    >
                        Voir les alertes
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                            <CpuChipIcon class="h-5 w-5 text-indigo-600" />
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ sensors?.length || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Capteurs total</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <SignalIcon class="h-5 w-5 text-green-600" />
                        </div>
                        <span class="text-2xl font-bold text-green-600">{{ onlineSensors.length }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">En ligne</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <SignalSlashIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <span class="text-2xl font-bold text-gray-400">{{ offlineSensors.length }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Hors ligne</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <LockClosedIcon class="h-5 w-5 text-purple-600" />
                        </div>
                        <span class="text-2xl font-bold text-gray-900">{{ smartLocks?.length || 0 }}</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Serrures</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-center justify-between">
                        <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', activeAlerts.length > 0 ? 'bg-amber-100' : 'bg-green-100']">
                            <BellAlertIcon :class="['h-5 w-5', activeAlerts.length > 0 ? 'text-amber-600' : 'text-green-600']" />
                        </div>
                        <span :class="['text-2xl font-bold', activeAlerts.length > 0 ? 'text-amber-600' : 'text-green-600']">
                            {{ activeAlerts.length }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-2">Alertes actives</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-gray-200">
                <nav class="flex gap-4 overflow-x-auto">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        @click="selectedTab = tab.id"
                        :class="[
                            'flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm whitespace-nowrap transition-colors',
                            selectedTab === tab.id
                                ? 'border-indigo-500 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <component :is="tab.icon" class="h-5 w-5" />
                        {{ tab.name }}
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <!-- Overview Tab -->
                <div v-if="selectedTab === 'overview'" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Sensors by Type -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4">Capteurs par type</h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(typeSensors, type) in sensorsByType"
                                    :key="type"
                                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ getSensorIcon(type) }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 capitalize">{{ type }}</p>
                                            <p class="text-sm text-gray-500">
                                                {{ typeSensors.filter(s => s.status === 'active').length }}/{{ typeSensors.length }} en ligne
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">
                                            {{ formatValue(
                                                typeSensors.filter(s => s.last_value !== null).reduce((sum, s) => sum + s.last_value, 0) / typeSensors.filter(s => s.last_value !== null).length,
                                                typeSensors[0]?.sensor_type?.unit
                                            ) }}
                                        </p>
                                        <p class="text-xs text-gray-500">moyenne</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-4">Activité récente</h3>
                            <div class="space-y-3 max-h-80 overflow-y-auto">
                                <div
                                    v-for="activity in recentActivity?.slice(0, 10)"
                                    :key="activity.id"
                                    class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg"
                                >
                                    <div :class="['w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0', getStatusColor(activity.event_type?.includes('success') ? 'active' : 'error')]">
                                        <component
                                            :is="activity.event_type?.includes('lock') ? LockClosedIcon : activity.event_type?.includes('unlock') ? LockOpenIcon : SignalIcon"
                                            class="h-4 w-4"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ activity.event_type }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ activity.box?.display_name || activity.sensor?.name || 'Système' }}
                                        </p>
                                    </div>
                                    <span class="text-xs text-gray-400">
                                        {{ formatTimeAgo(activity.event_at) }}
                                    </span>
                                </div>
                                <p v-if="!recentActivity?.length" class="text-center text-gray-500 py-8">
                                    Aucune activité récente
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sensors Tab -->
                <div v-else-if="selectedTab === 'sensors'" class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">Tous les capteurs</h3>
                        <Link
                            href="/tenant/iot/sensors/create"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Ajouter un capteur
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="sensor in sensors"
                            :key="sensor.id"
                            class="p-4 border border-gray-200 rounded-xl hover:shadow-md transition-shadow cursor-pointer"
                            @click="viewSensor(sensor)"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl">{{ getSensorIcon(sensor.sensor_type?.slug) }}</span>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ sensor.name }}</h4>
                                        <p class="text-xs text-gray-500">{{ sensor.box?.display_name || 'Non assigné' }}</p>
                                    </div>
                                </div>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(sensor.status)]">
                                    {{ sensor.status }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-2xl font-bold text-gray-900">
                                        {{ formatValue(sensor.last_value, sensor.sensor_type?.unit) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ formatTimeAgo(sensor.last_reading_at) }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <div v-if="sensor.battery_level" :class="['flex items-center gap-1', getBatteryColor(sensor.battery_level)]">
                                        <Battery50Icon class="h-4 w-4" />
                                        {{ sensor.battery_level }}%
                                    </div>
                                    <div v-if="sensor.signal_strength" class="flex items-center gap-1 text-gray-400">
                                        <WifiIcon class="h-4 w-4" />
                                        {{ sensor.signal_strength }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <p v-if="!sensors?.length" class="text-center text-gray-500 py-12">
                        Aucun capteur configuré
                    </p>
                </div>

                <!-- Smart Locks Tab -->
                <div v-else-if="selectedTab === 'locks'" class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900">Serrures connectées</h3>
                        <Link
                            href="/tenant/iot/locks/create"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700"
                        >
                            <PlusIcon class="h-4 w-4" />
                            Ajouter une serrure
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="lock in smartLocks"
                            :key="lock.id"
                            class="p-4 border border-gray-200 rounded-xl"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', lock.is_locked ? 'bg-green-100' : 'bg-amber-100']">
                                        <component
                                            :is="lock.is_locked ? LockClosedIcon : LockOpenIcon"
                                            :class="['h-5 w-5', lock.is_locked ? 'text-green-600' : 'text-amber-600']"
                                        />
                                    </div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ lock.name }}</h4>
                                        <p class="text-xs text-gray-500">{{ lock.box?.display_name }}</p>
                                    </div>
                                </div>
                                <span :class="['px-2 py-1 rounded-full text-xs font-medium', lock.is_locked ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
                                    {{ lock.is_locked ? 'Verrouillé' : 'Déverrouillé' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                <span>{{ lock.provider?.name || 'Generic' }}</span>
                                <div v-if="lock.battery_level" :class="['flex items-center gap-1', getBatteryColor(lock.battery_level)]">
                                    <Battery50Icon class="h-4 w-4" />
                                    {{ lock.battery_level }}%
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button
                                    @click="unlockDoor(lock)"
                                    :disabled="!lock.is_locked"
                                    class="flex-1 px-3 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <LockOpenIcon class="h-4 w-4 inline mr-1" />
                                    Déverrouiller
                                </button>
                                <Link
                                    :href="`/tenant/iot/locks/${lock.id}`"
                                    class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                            </div>
                        </div>
                    </div>

                    <p v-if="!smartLocks?.length" class="text-center text-gray-500 py-12">
                        Aucune serrure configurée
                    </p>
                </div>

                <!-- Alerts Tab -->
                <div v-else-if="selectedTab === 'alerts'" class="p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Alertes actives</h3>

                    <div class="space-y-3">
                        <div
                            v-for="alert in activeAlerts"
                            :key="alert.id"
                            :class="['p-4 rounded-xl border', getSeverityColor(alert.severity)]"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3">
                                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <h4 class="font-medium">{{ alert.title }}</h4>
                                        <p class="text-sm opacity-80">{{ alert.message }}</p>
                                        <p class="text-xs opacity-60 mt-1">
                                            {{ alert.sensor?.name || alert.box?.display_name }} • {{ formatTimeAgo(alert.created_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        v-if="alert.status === 'active'"
                                        @click="acknowledgeAlert(alert)"
                                        class="px-3 py-1 bg-white/50 rounded-lg text-sm hover:bg-white/80"
                                    >
                                        Acquitter
                                    </button>
                                    <button
                                        @click="resolveAlert(alert)"
                                        class="px-3 py-1 bg-white/50 rounded-lg text-sm hover:bg-white/80"
                                    >
                                        Résoudre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!activeAlerts.length" class="text-center py-12">
                        <CheckCircleIcon class="h-12 w-12 mx-auto text-green-500 mb-3" />
                        <p class="text-gray-500">Aucune alerte active</p>
                        <p class="text-sm text-gray-400">Tous les systèmes fonctionnent normalement</p>
                    </div>
                </div>

                <!-- Activity Tab -->
                <div v-else-if="selectedTab === 'activity'" class="p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Historique d'activité</h3>

                    <div class="space-y-2">
                        <div
                            v-for="activity in recentActivity"
                            :key="activity.id"
                            class="flex items-center gap-4 p-3 hover:bg-gray-50 rounded-lg"
                        >
                            <div :class="['w-10 h-10 rounded-full flex items-center justify-center', getStatusColor(activity.event_type?.includes('success') ? 'active' : activity.event_type?.includes('failed') ? 'error' : 'offline')]">
                                <component
                                    :is="activity.event_type?.includes('lock') ? LockClosedIcon : activity.event_type?.includes('unlock') ? LockOpenIcon : BoltIcon"
                                    class="h-5 w-5"
                                />
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ activity.event_type?.replace(/_/g, ' ') }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ activity.box?.display_name || activity.sensor?.name }}
                                    <span v-if="activity.code_used">• Code: {{ activity.code_used }}</span>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500">{{ formatTimeAgo(activity.event_at) }}</p>
                                <p class="text-xs text-gray-400">{{ activity.access_method }}</p>
                            </div>
                        </div>
                    </div>

                    <p v-if="!recentActivity?.length" class="text-center text-gray-500 py-12">
                        Aucune activité enregistrée
                    </p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
