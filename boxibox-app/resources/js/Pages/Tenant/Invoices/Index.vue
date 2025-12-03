<script setup>
import { ref, watch, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    invoices: Object,
    stats: Object,
    customers: Array,
    filters: Object,
})

const search = ref(props.filters.search || '')
const status = ref(props.filters.status || '')
const type = ref(props.filters.type || '')
const customerId = ref(props.filters.customer_id || '')

const showDeleteModal = ref(false)
const invoiceToDelete = ref(null)

const updateFilters = () => {
    router.get(
        route('tenant.invoices.index'),
        {
            search: search.value,
            status: status.value,
            type: type.value,
            customer_id: customerId.value,
        },
        {
            preserveState: true,
            replace: true,
        }
    )
}

watch([search], () => {
    const timer = setTimeout(() => {
        updateFilters()
    }, 300)
    return () => clearTimeout(timer)
})

watch([status, type, customerId], () => {
    updateFilters()
})

const confirmDelete = (invoice) => {
    invoiceToDelete.value = invoice
    showDeleteModal.value = true
}

const deleteInvoice = () => {
    if (invoiceToDelete.value) {
        router.delete(route('tenant.invoices.destroy', invoiceToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false
                invoiceToDelete.value = null
            },
        })
    }
}

const statusLabels = {
    draft: 'Brouillon',
    sent: 'Envoyée',
    paid: 'Payée',
    partial: 'Partielle',
    overdue: 'En retard',
    cancelled: 'Annulée',
}

const typeLabels = {
    invoice: 'Facture',
    credit_note: 'Avoir',
    proforma: 'Proforma',
}

const getStatusStyle = (status) => {
    const styles = {
        draft: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' },
        sent: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
        paid: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
        partial: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
        overdue: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
        cancelled: { bg: 'bg-gray-100', text: 'text-gray-500', dot: 'bg-gray-400' },
    }
    return styles[status] || styles.draft
}

const getTypeStyle = (type) => {
    const styles = {
        invoice: { bg: 'bg-blue-100', text: 'text-blue-700' },
        credit_note: { bg: 'bg-purple-100', text: 'text-purple-700' },
        proforma: { bg: 'bg-indigo-100', text: 'text-indigo-700' },
    }
    return styles[type] || styles.invoice
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getCustomerInitials = (customer) => {
    if (!customer) return '?'
    if (customer.type === 'company') {
        return customer.company_name?.substring(0, 2).toUpperCase() || 'EN'
    }
    return `${customer.first_name?.charAt(0) || ''}${customer.last_name?.charAt(0) || ''}`.toUpperCase()
}

const isOverdue = (invoice) => {
    if (invoice.status === 'paid' || invoice.status === 'cancelled') return false
    return new Date(invoice.due_date) < new Date()
}
</script>

<template>
    <TenantLayout title="Factures">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Gestion des Factures</h1>
                                <p class="text-gray-500 mt-1">Gérez toutes vos factures clients</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-0 flex flex-wrap gap-3 animate-fade-in-up" style="animation-delay: 0.1s">
                        <a
                            :href="route('tenant.invoices.export', { status: status })"
                            class="inline-flex items-center px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 shadow-sm"
                        >
                            <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Exporter Excel
                        </a>
                        <Link
                            :href="route('tenant.invoices.create')"
                            class="inline-flex items-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Nouvelle Facture
                        </Link>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
                    <!-- Total Factures -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Factures</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Payées -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-emerald-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.15s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Payées</p>
                                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.paid || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- En retard -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-red-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">En retard</p>
                                <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.overdue || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Brouillons -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.25s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Brouillons</p>
                                <p class="text-2xl font-bold text-gray-600 mt-1">{{ stats.draft || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-gray-300 to-gray-500 flex items-center justify-center shadow-lg shadow-gray-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Montant en attente -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.3s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">En attente</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.total_outstanding) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 animate-fade-in-up" style="animation-delay: 0.35s">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input
                                    id="search"
                                    v-model="search"
                                    type="text"
                                    placeholder="N° facture, client..."
                                    class="block w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                />
                            </div>
                        </div>

                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                            <select
                                id="status"
                                v-model="status"
                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="draft">Brouillon</option>
                                <option value="sent">Envoyée</option>
                                <option value="paid">Payée</option>
                                <option value="partial">Partielle</option>
                                <option value="overdue">En retard</option>
                                <option value="cancelled">Annulée</option>
                            </select>
                        </div>

                        <!-- Type -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                            <select
                                id="type"
                                v-model="type"
                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white"
                            >
                                <option value="">Tous les types</option>
                                <option value="invoice">Facture</option>
                                <option value="credit_note">Avoir</option>
                                <option value="proforma">Proforma</option>
                            </select>
                        </div>

                        <!-- Customer -->
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-2">Client</label>
                            <select
                                id="customer_id"
                                v-model="customerId"
                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white"
                            >
                                <option value="">Tous les clients</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ getCustomerName(customer) }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.4s">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Facture
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Client
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Échéance
                                    </th>
                                    <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        Montant
                                    </th>
                                    <th scope="col" class="relative px-6 py-4">
                                        <span class="sr-only">Actions</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucune facture trouvée</p>
                                            <p class="text-gray-400 text-sm mt-1">Créez votre première facture</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-else
                                    v-for="invoice in invoices.data"
                                    :key="invoice.id"
                                    class="hover:bg-gray-50/50 transition-colors duration-150"
                                >
                                    <!-- Invoice Number -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-blue-200 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ invoice.invoice_number }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Customer -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                                :class="invoice.customer?.type === 'company' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'"
                                            >
                                                {{ getCustomerInitials(invoice.customer) }}
                                            </div>
                                            <span class="text-sm text-gray-900">{{ getCustomerName(invoice.customer) }}</span>
                                        </div>
                                    </td>

                                    <!-- Type -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getTypeStyle(invoice.type).bg, getTypeStyle(invoice.type).text]"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                                        >
                                            {{ typeLabels[invoice.type] || invoice.type }}
                                        </span>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getStatusStyle(invoice.status).bg, getStatusStyle(invoice.status).text]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                                        >
                                            <span :class="getStatusStyle(invoice.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ statusLabels[invoice.status] || invoice.status }}
                                        </span>
                                    </td>

                                    <!-- Invoice Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600">{{ formatDate(invoice.invoice_date) }}</span>
                                    </td>

                                    <!-- Due Date -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="text-sm"
                                            :class="isOverdue(invoice) ? 'text-red-600 font-medium' : 'text-gray-600'"
                                        >
                                            {{ formatDate(invoice.due_date) }}
                                        </span>
                                    </td>

                                    <!-- Total -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(invoice.total) }}</span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route('tenant.invoices.show', invoice.id)"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-150"
                                                title="Voir"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                            <Link
                                                :href="route('tenant.invoices.edit', invoice.id)"
                                                class="p-2 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors duration-150"
                                                title="Modifier"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                            <button
                                                @click="confirmDelete(invoice)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-150"
                                                title="Supprimer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="invoices.links.length > 3"
                        class="bg-gray-50/50 px-6 py-4 flex items-center justify-between border-t border-gray-100"
                    >
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="invoices.prev_page_url"
                                :href="invoices.prev_page_url"
                                class="px-4 py-2 border border-gray-200 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Précédent
                            </Link>
                            <Link
                                v-if="invoices.next_page_url"
                                :href="invoices.next_page_url"
                                class="ml-3 px-4 py-2 border border-gray-200 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Suivant
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <p class="text-sm text-gray-600">
                                Affichage de
                                <span class="font-semibold text-gray-900">{{ invoices.from }}</span>
                                à
                                <span class="font-semibold text-gray-900">{{ invoices.to }}</span>
                                sur
                                <span class="font-semibold text-gray-900">{{ invoices.total }}</span>
                                résultats
                            </p>
                            <nav class="flex gap-1">
                                <template v-for="(link, index) in invoices.links" :key="index">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            link.active
                                                ? 'bg-blue-600 text-white border-blue-600'
                                                : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
                                            'px-3 py-2 border text-sm font-medium rounded-lg transition-colors',
                                        ]"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        class="px-3 py-2 border border-gray-200 text-sm font-medium rounded-lg bg-gray-50 text-gray-400 cursor-not-allowed"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showDeleteModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div
                            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                            aria-hidden="true"
                            @click="showDeleteModal = false"
                        ></div>

                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        >
                            <div
                                v-if="showDeleteModal"
                                class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                            >
                                <div class="bg-white px-6 pt-6 pb-4">
                                    <div class="flex items-start gap-4">
                                        <div class="flex-shrink-0 w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900" id="modal-title">
                                                Supprimer la facture
                                            </h3>
                                            <p class="mt-2 text-sm text-gray-500">
                                                Êtes-vous sûr de vouloir supprimer la facture
                                                <span class="font-semibold text-gray-700">{{ invoiceToDelete?.invoice_number }}</span> ?
                                                Cette action est irréversible.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                                    <button
                                        type="button"
                                        @click="deleteInvoice"
                                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-red-600 hover:bg-red-700 transition-colors shadow-sm"
                                    >
                                        Supprimer
                                    </button>
                                    <button
                                        type="button"
                                        @click="showDeleteModal = false"
                                        class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </TenantLayout>
</template>
