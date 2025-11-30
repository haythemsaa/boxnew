<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    reminders: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const levelFilter = ref(props.filters.level || '')
const selectedReminders = ref([])

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.reminders.index'), {
            search: search.value,
            status: statusFilter.value,
            level: levelFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.reminders.index'), {
        search: search.value,
        status: statusFilter.value,
        level: levelFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const sendReminder = (reminder) => {
    router.post(route('tenant.reminders.send', reminder.id))
}

const sendBulkReminders = () => {
    if (selectedReminders.value.length === 0) {
        alert('Veuillez sélectionner au moins une relance.')
        return
    }
    router.post(route('tenant.reminders.send-bulk'), {
        reminder_ids: selectedReminders.value,
    })
}

const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedReminders.value = props.reminders.data
            .filter(r => r.status === 'pending')
            .map(r => r.id)
    } else {
        selectedReminders.value = []
    }
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        sent: 'Envoyée',
        paid: 'Payée',
        cancelled: 'Annulée',
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        sent: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
        cancelled: 'bg-gray-100 text-gray-600',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getLevelLabel = (level) => {
    const labels = {
        1: 'Relance 1',
        2: 'Relance 2',
        3: 'Relance 3',
        4: 'Mise en demeure',
    }
    return labels[level] || `Niveau ${level}`
}

const getLevelColor = (level) => {
    const colors = {
        1: 'bg-blue-100 text-blue-800',
        2: 'bg-yellow-100 text-yellow-800',
        3: 'bg-orange-100 text-orange-800',
        4: 'bg-red-100 text-red-800',
    }
    return colors[level] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <AuthenticatedLayout title="Relances">
        <!-- Success/Error Messages -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Relances</h2>
                <p class="mt-1 text-sm text-gray-500">Gérez les relances de paiement pour les factures impayées</p>
            </div>
            <div class="flex gap-3">
                <button
                    v-if="selectedReminders.length > 0"
                    @click="sendBulkReminders"
                    class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Envoyer ({{ selectedReminders.length }})
                </button>
                <Link
                    :href="route('tenant.reminders.overdue-invoices')"
                    class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouvelle Relance
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 md:grid-cols-6 mb-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-500">
                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                <p class="text-sm text-gray-500">En attente</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-blue-500">
                <p class="text-2xl font-bold text-blue-600">{{ stats.sent }}</p>
                <p class="text-sm text-gray-500">Envoyées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-green-500">
                <p class="text-2xl font-bold text-green-600">{{ stats.paid }}</p>
                <p class="text-sm text-gray-500">Payées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
                <p class="text-2xl font-bold text-red-600">{{ stats.overdue_invoices }}</p>
                <p class="text-sm text-gray-500">Factures impayées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-700">
                <p class="text-lg font-bold text-red-700">{{ formatCurrency(stats.total_overdue) }}</p>
                <p class="text-sm text-gray-500">Montant impayé</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par facture, client..."
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @input="handleSearch"
                />
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="sent">Envoyée</option>
                    <option value="paid">Payée</option>
                </select>
                <select
                    v-model="levelFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Tous les niveaux</option>
                    <option value="1">Relance 1</option>
                    <option value="2">Relance 2</option>
                    <option value="3">Relance 3</option>
                    <option value="4">Mise en demeure</option>
                </select>
            </div>
        </div>

        <!-- Reminders Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Facture
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Montant dû
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Niveau
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="reminders.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Aucune relance</p>
                                    <Link
                                        :href="route('tenant.reminders.overdue-invoices')"
                                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        + Créer une relance
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="reminder in reminders.data" :key="reminder.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    v-if="reminder.status === 'pending'"
                                    type="checkbox"
                                    :value="reminder.id"
                                    v-model="selectedReminders"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ reminder.invoice?.invoice_number || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ getCustomerName(reminder.invoice?.customer) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">
                                {{ formatCurrency(reminder.amount_due) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getLevelColor(reminder.level)"
                                >
                                    {{ getLevelLabel(reminder.level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ reminder.type === 'email' ? 'Email' : reminder.type === 'sms' ? 'SMS' : 'Courrier' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getStatusColor(reminder.status)"
                                >
                                    {{ getStatusLabel(reminder.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(reminder.sent_at || reminder.scheduled_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    v-if="reminder.status === 'pending'"
                                    @click="sendReminder(reminder)"
                                    class="text-blue-600 hover:text-blue-900"
                                    title="Envoyer"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="reminders.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Affichage de <span class="font-medium">{{ reminders.from }}</span> à <span class="font-medium">{{ reminders.to }}</span> sur <span class="font-medium">{{ reminders.total }}</span> résultats
                    </div>
                    <div class="flex space-x-2">
                        <Link
                            v-for="link in reminders.links"
                            :key="link.label"
                            :href="link.url"
                            :class="{
                                'px-4 py-2 border rounded-lg transition-colors': true,
                                'bg-primary-600 text-white border-primary-600': link.active,
                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50': !link.active && link.url,
                                'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed': !link.url
                            }"
                            :preserve-scroll="true"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
