<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    guestCodes: Object,
    sites: Array,
    filters: Object,
})

const applyFilters = () => {
    router.get(route('tenant.self-service.guest-codes'), props.filters, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    router.get(route('tenant.self-service.guest-codes'))
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const statusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        active: 'Actif',
        used: 'Utilisé',
        expired: 'Expiré',
        cancelled: 'Annulé'
    }
    return labels[status] || status
}

const statusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        used: 'bg-blue-100 text-blue-800',
        expired: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const isExpired = (code) => {
    return new Date(code.valid_until) < new Date()
}

const isValidNow = (code) => {
    const now = new Date()
    return new Date(code.valid_from) <= now && new Date(code.valid_until) > now
}
</script>

<template>
    <Head title="Accès invités" />

    <TenantLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Accès invités</h2>
                    <p class="text-sm text-gray-600 mt-1">Codes temporaires créés par vos clients pour leurs visiteurs</p>
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
                <!-- Filters -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select
                                v-model="filters.status"
                                @change="applyFilters"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            >
                                <option value="">Tous</option>
                                <option value="pending">En attente</option>
                                <option value="active">Actif</option>
                                <option value="used">Utilisé</option>
                                <option value="expired">Expiré</option>
                                <option value="cancelled">Annulé</option>
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
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invité</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invité par</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Site</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Validité</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raison</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="code in guestCodes.data" :key="code.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="font-medium text-gray-900">{{ code.guest_name }}</div>
                                            <div v-if="code.guest_email" class="text-sm text-gray-500">{{ code.guest_email }}</div>
                                            <div v-if="code.guest_phone" class="text-sm text-gray-500">{{ code.guest_phone }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                <span class="text-purple-600 text-xs font-medium">
                                                    {{ code.customer?.first_name?.charAt(0) }}{{ code.customer?.last_name?.charAt(0) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ code.customer?.full_name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="px-2 py-1 bg-gray-100 rounded font-mono text-sm">
                                            {{ code.access_code }}
                                        </code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ code.site?.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div :class="isExpired(code) ? 'text-red-500' : isValidNow(code) ? 'text-green-600' : 'text-gray-500'">
                                            {{ formatDateTime(code.valid_from) }}
                                            <br>
                                            → {{ formatDateTime(code.valid_until) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ code.use_count }} / {{ code.max_uses }}
                                        <div v-if="code.last_used_at" class="text-xs text-gray-400">
                                            Dernière: {{ formatDateTime(code.last_used_at) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs rounded-full', statusColor(code.status)]">
                                            {{ statusLabel(code.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" :title="code.purpose">
                                        {{ code.purpose || '-' }}
                                    </td>
                                </tr>
                                <tr v-if="guestCodes.data.length === 0">
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                        <p>Aucun code invité trouvé</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="guestCodes.links && guestCodes.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-between items-center">
                        <p class="text-sm text-gray-500">
                            Affichage de {{ guestCodes.from }} à {{ guestCodes.to }} sur {{ guestCodes.total }} résultats
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in guestCodes.links"
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
