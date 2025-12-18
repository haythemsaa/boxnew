<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    logs: Object,
    sites: Array,
    stats: Object,
    filters: Object,
})

const applyFilters = () => {
    router.get(route('tenant.self-service.logs'), props.filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    router.get(route('tenant.self-service.logs'))
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    })
}

const statusLabel = (status) => {
    const labels = {
        granted: 'Accordé',
        denied: 'Refusé',
        pending: 'En attente',
    }
    return labels[status] || status
}

const statusColor = (status) => {
    const colors = {
        granted: 'bg-green-100 text-green-800',
        denied: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const statusIcon = (status) => {
    const icons = {
        granted: 'M5 13l4 4L19 7',
        denied: 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        pending: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    }
    return icons[status] || icons.pending
}

const methodLabel = (method) => {
    const labels = {
        pin: 'Code PIN',
        qr: 'QR Code',
        rfid: 'RFID',
        app: 'Application',
        manual: 'Manuel',
        guest: 'Invité'
    }
    return labels[method] || method
}
</script>

<template>
    <Head title="Historique d'accès" />

    <TenantLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Historique d'accès</h2>
                    <p class="text-sm text-gray-600 mt-1">Consultez tous les mouvements d'entrée et sortie</p>
                </div>
                <Link
                    :href="route('tenant.self-service.index')"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                    Retour
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Accès accordés</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_granted }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Accès refusés</p>
                                <p class="text-2xl font-bold text-red-600">{{ stats.total_denied }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
                        <div class="flex items-center gap-4">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">En attente</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ stats.total_pending }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Tous</option>
                                <option value="granted">Accordé</option>
                                <option value="denied">Refusé</option>
                                <option value="pending">En attente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                            <select
                                v-model="filters.site_id"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                            <input
                                type="date"
                                v-model="filters.date_from"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                            <input
                                type="date"
                                v-model="filters.date_to"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                            >
                                Effacer filtres
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date/Heure</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Box / Site</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Méthode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Portail</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raison</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDateTime(log.accessed_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <div :class="[
                                                'p-1.5 rounded-lg',
                                                log.status === 'granted' ? 'bg-green-100' :
                                                log.status === 'denied' ? 'bg-red-100' :
                                                'bg-yellow-100'
                                            ]">
                                                <svg :class="[
                                                    'w-4 h-4',
                                                    log.status === 'granted' ? 'text-green-600' :
                                                    log.status === 'denied' ? 'text-red-600' :
                                                    'text-yellow-600'
                                                ]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="statusIcon(log.status)" />
                                                </svg>
                                            </div>
                                            <span :class="['px-2 py-1 text-xs rounded-full', statusColor(log.status)]">
                                                {{ statusLabel(log.status) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="log.customer" class="text-sm">
                                            <div class="font-medium text-gray-900">{{ log.customer.full_name }}</div>
                                            <div class="text-gray-500">{{ log.customer.email }}</div>
                                        </div>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div v-if="log.box">
                                            <div class="font-medium">Box {{ log.box.number }}</div>
                                            <div class="text-xs text-gray-400">{{ log.box.site?.name }}</div>
                                        </div>
                                        <span v-else>-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ methodLabel(log.access_method) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ log.gate_name || log.gate_id || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="log.reason" class="text-red-500">{{ log.reason }}</span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                                <tr v-if="logs.data.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p>Aucun log d'accès trouvé</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.links && logs.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                        <p class="text-sm text-gray-500">
                            Affichage de {{ logs.from }} à {{ logs.to }} sur {{ logs.total }} résultats
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in logs.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
