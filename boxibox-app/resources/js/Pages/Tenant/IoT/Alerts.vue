<script setup>
/**
 * IoT Alerts Page - List and manage all IoT alerts
 */
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    BellAlertIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    FunnelIcon,
    ArrowPathIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    alerts: Object,
    sites: Array,
    filters: Object,
})

// State
const loading = ref(false)
const localFilters = ref({
    site_id: props.filters?.site_id || '',
    status: props.filters?.status || 'active',
    severity: props.filters?.severity || '',
})

// Apply filters
const applyFilters = () => {
    loading.value = true
    router.get('/tenant/iot/alerts', localFilters.value, {
        preserveState: true,
        onFinish: () => loading.value = false,
    })
}

// Reset filters
const resetFilters = () => {
    localFilters.value = { site_id: '', status: 'active', severity: '' }
    applyFilters()
}

// Helpers
const formatTimeAgo = (date) => {
    if (!date) return ''
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    if (seconds < 60) return 'à l\'instant'
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)}h`
    return `il y a ${Math.floor(seconds / 86400)}j`
}

const getSeverityColor = (severity) => {
    switch (severity) {
        case 'critical': return 'bg-red-100 text-red-700 border-red-200'
        case 'warning': return 'bg-amber-100 text-amber-700 border-amber-200'
        default: return 'bg-blue-100 text-blue-700 border-blue-200'
    }
}

const getStatusBadge = (status) => {
    switch (status) {
        case 'active': return 'bg-red-100 text-red-700'
        case 'acknowledged': return 'bg-amber-100 text-amber-700'
        case 'resolved': return 'bg-green-100 text-green-700'
        default: return 'bg-gray-100 text-gray-700'
    }
}

// Actions
const acknowledgeAlert = (alert) => {
    router.post(`/tenant/iot/alerts/${alert.id}/acknowledge`, {}, {
        preserveScroll: true,
    })
}

const resolveAlert = (alert) => {
    router.post(`/tenant/iot/alerts/${alert.id}/resolve`, {}, {
        preserveScroll: true,
    })
}
</script>

<template>
    <TenantLayout>
        <Head title="Alertes IoT" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Alertes IoT</h1>
                    <p class="text-gray-500 mt-1">
                        Gérez toutes les alertes des capteurs et serrures
                    </p>
                </div>
                <Link
                    href="/tenant/iot"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                >
                    <ChevronLeftIcon class="h-5 w-5" />
                    Retour au Dashboard
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex items-center gap-2">
                        <FunnelIcon class="h-5 w-5 text-gray-400" />
                        <span class="text-sm font-medium text-gray-700">Filtres:</span>
                    </div>

                    <select
                        v-model="localFilters.site_id"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Tous les sites</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
                    </select>

                    <select
                        v-model="localFilters.status"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="all">Tous les statuts</option>
                        <option value="active">Actives</option>
                        <option value="acknowledged">Acquittées</option>
                        <option value="resolved">Résolues</option>
                    </select>

                    <select
                        v-model="localFilters.severity"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Toutes les sévérités</option>
                        <option value="critical">Critique</option>
                        <option value="warning">Avertissement</option>
                        <option value="info">Information</option>
                    </select>

                    <button
                        @click="resetFilters"
                        class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Alerts List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6">
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <ArrowPathIcon class="h-8 w-8 text-gray-400 animate-spin" />
                    </div>

                    <div v-else-if="alerts?.data?.length" class="space-y-4">
                        <div
                            v-for="alert in alerts.data"
                            :key="alert.id"
                            :class="['p-4 rounded-xl border', getSeverityColor(alert.severity)]"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-3 flex-1">
                                    <ExclamationTriangleIcon class="h-5 w-5 flex-shrink-0 mt-0.5" />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <h4 class="font-semibold">{{ alert.title }}</h4>
                                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusBadge(alert.status)]">
                                                {{ alert.status }}
                                            </span>
                                        </div>
                                        <p class="text-sm opacity-80">{{ alert.message }}</p>
                                        <div class="flex items-center gap-4 mt-2 text-xs opacity-60">
                                            <span v-if="alert.sensor">Capteur: {{ alert.sensor.name }}</span>
                                            <span v-if="alert.box">Box: {{ alert.box.code }}</span>
                                            <span v-if="alert.site">Site: {{ alert.site.name }}</span>
                                            <span>{{ formatTimeAgo(alert.created_at) }}</span>
                                        </div>
                                        <div v-if="alert.acknowledged_at" class="mt-1 text-xs opacity-60">
                                            Acquittée {{ formatTimeAgo(alert.acknowledged_at) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2 flex-shrink-0">
                                    <button
                                        v-if="alert.status === 'active'"
                                        @click="acknowledgeAlert(alert)"
                                        class="px-3 py-1.5 bg-white/50 rounded-lg text-sm font-medium hover:bg-white/80"
                                    >
                                        Acquitter
                                    </button>
                                    <button
                                        v-if="alert.status !== 'resolved'"
                                        @click="resolveAlert(alert)"
                                        class="px-3 py-1.5 bg-white/50 rounded-lg text-sm font-medium hover:bg-white/80"
                                    >
                                        Résoudre
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-12">
                        <CheckCircleIcon class="h-12 w-12 mx-auto text-green-500 mb-3" />
                        <p class="text-gray-500">Aucune alerte trouvée</p>
                        <p class="text-sm text-gray-400">
                            {{ localFilters.status === 'active' ? 'Tous les systèmes fonctionnent normalement' : 'Aucune alerte ne correspond aux filtres' }}
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="alerts?.data?.length" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Affichage de {{ alerts.from }} à {{ alerts.to }} sur {{ alerts.total }} alertes
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="alerts.prev_page_url"
                            :href="alerts.prev_page_url"
                            class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:bg-gray-50"
                        >
                            <ChevronLeftIcon class="h-4 w-4" />
                        </Link>
                        <Link
                            v-if="alerts.next_page_url"
                            :href="alerts.next_page_url"
                            class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:bg-gray-50"
                        >
                            <ChevronRightIcon class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
