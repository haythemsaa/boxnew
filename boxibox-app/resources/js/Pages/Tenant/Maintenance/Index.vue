<template>
    <TenantLayout title="Maintenance" :breadcrumbs="[{ label: 'Maintenance' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total tickets</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.total }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <WrenchScrewdriverIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Tickets ouverts</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.open }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <ExclamationCircleIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Urgents</p>
                            <p class="text-3xl font-bold text-red-600">{{ stats.urgent }}</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl">
                            <FireIcon class="w-6 h-6 text-red-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">En retard</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.overdue }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <ClockIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters & Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <select v-model="filterStatus" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="open">Ouvert</option>
                            <option value="in_progress">En cours</option>
                            <option value="on_hold">En attente</option>
                            <option value="resolved">Résolu</option>
                            <option value="closed">Fermé</option>
                        </select>

                        <select v-model="filterPriority" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Toutes priorités</option>
                            <option value="low">Faible</option>
                            <option value="medium">Moyenne</option>
                            <option value="high">Haute</option>
                            <option value="urgent">Urgente</option>
                        </select>

                        <select v-model="filterSite" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <Link :href="route('tenant.maintenance.categories')" class="btn-secondary">
                            <TagIcon class="w-4 h-4 mr-2" />
                            Catégories
                        </Link>
                        <Link :href="route('tenant.maintenance.vendors')" class="btn-secondary">
                            <UserGroupIcon class="w-4 h-4 mr-2" />
                            Prestataires
                        </Link>
                        <Link :href="route('tenant.maintenance.create')" class="btn-primary">
                            <PlusIcon class="w-4 h-4 mr-2" />
                            Nouveau ticket
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Tickets List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Ticket</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Site / Box</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Priorité</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Assigné</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ ticket.ticket_number }}</p>
                                        <p class="text-sm text-gray-500 truncate max-w-xs">{{ ticket.title }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ ticket.site?.name }}</p>
                                    <p v-if="ticket.box" class="text-xs text-gray-500">Box {{ ticket.box.code }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getPriorityClass(ticket.priority)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getPriorityLabel(ticket.priority) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(ticket.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(ticket.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ ticket.assignee?.name || '-' }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ formatDate(ticket.created_at) }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('tenant.maintenance.show', ticket.id)" class="text-primary-600 hover:text-primary-800">
                                        Voir
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="tickets.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="tickets.links" />
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    WrenchScrewdriverIcon,
    ExclamationCircleIcon,
    FireIcon,
    ClockIcon,
    PlusIcon,
    TagIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tickets: Object,
    stats: Object,
    sites: Array,
    categories: Array,
    filters: Object,
})

const filterStatus = ref(props.filters?.status || '')
const filterPriority = ref(props.filters?.priority || '')
const filterSite = ref(props.filters?.site_id || '')

watch([filterStatus, filterPriority, filterSite], () => {
    router.get(route('tenant.maintenance.index'), {
        status: filterStatus.value,
        priority: filterPriority.value,
        site_id: filterSite.value,
    }, { preserveState: true, replace: true })
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const getPriorityLabel = (priority) => {
    const labels = {
        low: 'Faible',
        medium: 'Moyenne',
        high: 'Haute',
        urgent: 'Urgente'
    }
    return labels[priority] || priority
}

const getPriorityClass = (priority) => {
    const classes = {
        low: 'bg-gray-100 text-gray-700',
        medium: 'bg-yellow-100 text-yellow-700',
        high: 'bg-orange-100 text-orange-700',
        urgent: 'bg-red-100 text-red-700'
    }
    return classes[priority] || 'bg-gray-100 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        open: 'Ouvert',
        in_progress: 'En cours',
        on_hold: 'En attente',
        resolved: 'Résolu',
        closed: 'Fermé'
    }
    return labels[status] || status
}

const getStatusClass = (status) => {
    const classes = {
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        on_hold: 'bg-gray-100 text-gray-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-gray-100 text-gray-700'
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}
</script>
