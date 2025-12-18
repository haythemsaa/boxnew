<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    tickets: Object,
    statuses: Object,
    categories: Object,
})

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        pending: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-purple-100 text-purple-800',
        waiting_customer: 'bg-orange-100 text-orange-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
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
    <Head title="Mes Demandes de Support" />

    <PortalLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Mes Demandes de Support</h1>
                        <p class="text-gray-600">Consultez et gerez vos tickets de support</p>
                    </div>
                    <Link
                        :href="route('customer.portal.support.create')"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
                    >
                        Nouvelle Demande
                    </Link>
                </div>

                <!-- Tickets List -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div v-if="tickets.data.length === 0" class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-gray-500 mb-4">Vous n'avez aucune demande de support</p>
                        <Link
                            :href="route('customer.portal.support.create')"
                            class="text-primary-600 hover:text-primary-700 font-medium"
                        >
                            Creer votre premiere demande
                        </Link>
                    </div>
                    <div v-else class="divide-y divide-gray-100">
                        <Link
                            v-for="ticket in tickets.data"
                            :key="ticket.id"
                            :href="route('customer.portal.support.show', ticket.id)"
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
                                    <h3 class="font-semibold text-gray-900">{{ ticket.subject }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ categories[ticket.category] || ticket.category }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-2 ml-4">
                                    <span :class="['px-2 py-1 text-xs rounded-full', getStatusColor(ticket.status)]">
                                        {{ statuses[ticket.status] }}
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
    </PortalLayout>
</template>
