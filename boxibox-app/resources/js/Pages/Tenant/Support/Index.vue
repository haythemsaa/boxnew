<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    tickets: Object,
    stats: Object,
    filters: Object,
    statuses: Object,
    priorities: Object,
})

const statusFilter = ref(props.filters?.status || '')
const priorityFilter = ref(props.filters?.priority || '')

const applyFilters = () => {
    router.get(route('tenant.support.index'), {
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    }, { preserveState: true })
}

const clearFilters = () => {
    statusFilter.value = ''
    priorityFilter.value = ''
    router.get(route('tenant.support.index'))
}

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-purple-100 text-purple-800',
        waiting_customer: 'bg-orange-100 text-orange-800',
        waiting_tenant: 'bg-orange-100 text-orange-800',
        waiting_internal: 'bg-gray-100 text-gray-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getPriorityColor = (priority) => {
    const colors = {
        low: 'bg-gray-100 text-gray-600',
        medium: 'bg-blue-100 text-blue-600',
        high: 'bg-orange-100 text-orange-600',
        critical: 'bg-red-100 text-red-600',
        urgent: 'bg-red-200 text-red-800',
    }
    return colors[priority] || 'bg-gray-100 text-gray-600'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>

<template>
    <Head title="Support Client" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Support Client</h1>
                        <p class="text-gray-600">Gerez les demandes de support de vos clients</p>
                    </div>
                    <Link
                        :href="route('tenant.support.create')"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                    >
                        Nouveau Ticket
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                        <div class="text-sm text-gray-500">Total Tickets</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.open }}</div>
                        <div class="text-sm text-gray-500">Tickets Ouverts</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.unread }}</div>
                        <div class="text-sm text-gray-500">Non Lus</div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border">
                        <div class="text-2xl font-bold text-green-600">{{ stats.resolved_today }}</div>
                        <div class="text-sm text-gray-500">Resolus Aujourd'hui</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-xl p-4 shadow-sm border mb-6">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select
                                v-model="statusFilter"
                                @change="applyFilters"
                                class="rounded-lg border-gray-300 text-sm"
                            >
                                <option value="">Tous</option>
                                <option v-for="(label, key) in statuses" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priorite</label>
                            <select
                                v-model="priorityFilter"
                                @change="applyFilters"
                                class="rounded-lg border-gray-300 text-sm"
                            >
                                <option value="">Toutes</option>
                                <option v-for="(label, key) in priorities" :key="key" :value="key">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <button
                            @click="clearFilters"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Reinitialiser
                        </button>
                    </div>
                </div>

                <!-- Tickets List -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div v-if="tickets.data.length === 0" class="p-8 text-center text-gray-500">
                        Aucun ticket de support pour le moment.
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <Link
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            :href="route('tenant.support.show', ticket.id)"
                            class="block p-4 hover:bg-gray-50 transition"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-mono text-gray-400">{{ ticket.ticket_number }}</span>
                                        <span
                                            v-if="ticket.unread_count > 0"
                                            class="px-2 py-0.5 text-xs bg-red-500 text-white rounded-full"
                                        >
                                            {{ ticket.unread_count }} nouveau(x)
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-semibold text-gray-900 truncate">
                                        {{ ticket.subject }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ ticket.customer?.full_name || 'Client inconnu' }}
                                    </p>
                                    <p v-if="ticket.messages?.[0]" class="text-xs text-gray-400 mt-1 truncate">
                                        {{ ticket.messages[0].message }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2 ml-4">
                                    <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(ticket.status)]">
                                        {{ statuses[ticket.status] }}
                                    </span>
                                    <span :class="['px-2 py-1 text-xs rounded-full', getPriorityColor(ticket.priority)]">
                                        {{ priorities[ticket.priority] }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ formatDate(ticket.last_message_at || ticket.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </div>

                    <!-- Pagination -->
                    <div v-if="tickets.links?.length > 3" class="px-4 py-3 border-t bg-gray-50">
                        <div class="flex justify-center gap-1">
                            <Link
                                v-for="link in tickets.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-primary-600 text-white' : 'text-gray-600 hover:bg-gray-200',
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
