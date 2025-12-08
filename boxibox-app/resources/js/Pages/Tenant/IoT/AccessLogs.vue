<script setup>
/**
 * IoT Access Logs Page - View all access events from smart locks
 */
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    LockClosedIcon,
    LockOpenIcon,
    FunnelIcon,
    ArrowPathIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: Object,
    sites: Array,
    filters: Object,
})

// State
const loading = ref(false)
const localFilters = ref({
    site_id: props.filters?.site_id || '',
    event_type: props.filters?.event_type || '',
    from: props.filters?.from || '',
    to: props.filters?.to || '',
})

// Apply filters
const applyFilters = () => {
    loading.value = true
    router.get('/tenant/iot/access-logs', localFilters.value, {
        preserveState: true,
        onFinish: () => loading.value = false,
    })
}

// Reset filters
const resetFilters = () => {
    localFilters.value = { site_id: '', event_type: '', from: '', to: '' }
    applyFilters()
}

// Helpers
const formatDateTime = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatTimeAgo = (date) => {
    if (!date) return ''
    const seconds = Math.floor((new Date() - new Date(date)) / 1000)
    if (seconds < 60) return 'à l\'instant'
    if (seconds < 3600) return `il y a ${Math.floor(seconds / 60)} min`
    if (seconds < 86400) return `il y a ${Math.floor(seconds / 3600)}h`
    return `il y a ${Math.floor(seconds / 86400)}j`
}

const getEventIcon = (eventType) => {
    if (eventType?.includes('unlock')) return LockOpenIcon
    if (eventType?.includes('lock')) return LockClosedIcon
    if (eventType?.includes('denied') || eventType?.includes('failed')) return XCircleIcon
    if (eventType?.includes('success')) return CheckCircleIcon
    return KeyIcon
}

const getEventColor = (eventType) => {
    if (eventType?.includes('success') || eventType?.includes('unlock_success')) {
        return 'bg-green-100 text-green-700'
    }
    if (eventType?.includes('denied') || eventType?.includes('failed') || eventType?.includes('invalid')) {
        return 'bg-red-100 text-red-700'
    }
    if (eventType?.includes('lock_success')) {
        return 'bg-blue-100 text-blue-700'
    }
    return 'bg-gray-100 text-gray-700'
}

const getEventLabel = (eventType) => {
    const labels = {
        'unlock_success': 'Déverrouillage',
        'lock_success': 'Verrouillage',
        'access_denied': 'Accès refusé',
        'unlock_failed': 'Échec déverrouillage',
        'code_invalid': 'Code invalide',
        'code_expired': 'Code expiré',
        'door_opened': 'Porte ouverte',
        'door_closed': 'Porte fermée',
        'alarm_triggered': 'Alarme déclenchée',
    }
    return labels[eventType] || eventType?.replace(/_/g, ' ')
}

const getMethodLabel = (method) => {
    const methods = {
        'remote': 'Application',
        'keypad': 'Clavier',
        'noke_app': 'Noke App',
        'salto_ks': 'Salto KS',
        'kisi': 'Kisi',
        'pti': 'PTI',
        'code': 'Code',
        'key': 'Clé',
    }
    return methods[method] || method
}

// Event types for filter
const eventTypes = [
    { value: 'unlock_success', label: 'Déverrouillage' },
    { value: 'lock_success', label: 'Verrouillage' },
    { value: 'access_denied', label: 'Accès refusé' },
    { value: 'unlock_failed', label: 'Échec déverrouillage' },
    { value: 'code_invalid', label: 'Code invalide' },
]
</script>

<template>
    <TenantLayout>
        <Head title="Historique des accès" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Historique des accès</h1>
                    <p class="text-gray-500 mt-1">
                        Tous les événements d'accès des serrures connectées
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
                        v-model="localFilters.event_type"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Tous les types</option>
                        <option v-for="type in eventTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>

                    <input
                        type="date"
                        v-model="localFilters.from"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                        placeholder="Du"
                    />

                    <input
                        type="date"
                        v-model="localFilters.to"
                        @change="applyFilters"
                        class="px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"
                        placeholder="Au"
                    />

                    <button
                        @click="resetFilters"
                        class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900"
                    >
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Logs List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="overflow-x-auto">
                    <div v-if="loading" class="flex items-center justify-center py-12">
                        <ArrowPathIcon class="h-8 w-8 text-gray-400 animate-spin" />
                    </div>

                    <table v-else-if="logs?.data?.length" class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Événement
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Box / Serrure
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Méthode
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code utilisé
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date/Heure
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center', getEventColor(log.event_type)]">
                                            <component :is="getEventIcon(log.event_type)" class="h-4 w-4" />
                                        </div>
                                        <span class="font-medium text-gray-900">
                                            {{ getEventLabel(log.event_type) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ log.box?.code || '-' }}</p>
                                        <p class="text-sm text-gray-500">{{ log.lock?.name || '' }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-medium">
                                        {{ getMethodLabel(log.access_method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                    {{ log.code_used || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <p class="text-gray-900">{{ formatDateTime(log.event_at) }}</p>
                                        <p class="text-sm text-gray-500">{{ formatTimeAgo(log.event_at) }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-else class="text-center py-12">
                        <ClockIcon class="h-12 w-12 mx-auto text-gray-300 mb-3" />
                        <p class="text-gray-500">Aucun accès enregistré</p>
                        <p class="text-sm text-gray-400">
                            Les événements d'accès apparaîtront ici
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="logs?.data?.length" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Affichage de {{ logs.from }} à {{ logs.to }} sur {{ logs.total }} événements
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="logs.prev_page_url"
                            :href="logs.prev_page_url"
                            class="px-3 py-1 border border-gray-200 rounded-lg text-sm hover:bg-gray-50"
                        >
                            <ChevronLeftIcon class="h-4 w-4" />
                        </Link>
                        <Link
                            v-if="logs.next_page_url"
                            :href="logs.next_page_url"
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
