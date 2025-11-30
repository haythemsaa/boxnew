<template>
    <MobileLayout title="Mes Factures">
        <!-- Stats Summary -->
        <div class="grid grid-cols-3 gap-3 mb-4">
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                <p class="text-xs text-gray-500">Total</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                <p class="text-xs text-gray-500">En attente</p>
            </div>
            <div class="bg-white rounded-xl p-3 text-center shadow-sm">
                <p class="text-2xl font-bold text-red-600">{{ stats.overdue }}</p>
                <p class="text-xs text-gray-500">En retard</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeFilter === filter.value
                        ? `${filter.activeClass} text-white`
                        : 'bg-white text-gray-700'
                ]"
            >
                {{ filter.label }}
            </button>
        </div>

        <!-- Search -->
        <div class="relative mb-4">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher une facture..."
                class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
            />
        </div>

        <!-- Empty State -->
        <div v-if="filteredInvoices.length === 0" class="text-center py-12">
            <DocumentTextIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune facture</h3>
            <p class="text-gray-500">
                {{ searchQuery ? 'Aucun resultat pour votre recherche' : 'Aucune facture disponible' }}
            </p>
        </div>

        <!-- Invoice List -->
        <div v-else class="space-y-3">
            <Link
                v-for="invoice in filteredInvoices"
                :key="invoice.id"
                :href="route('mobile.invoices.show', invoice.id)"
                class="block bg-white rounded-xl shadow-sm overflow-hidden"
            >
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center mr-3"
                                :class="getStatusBgClass(invoice.status)"
                            >
                                <DocumentTextIcon
                                    class="w-6 h-6"
                                    :class="getStatusTextClass(invoice.status)"
                                />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ invoice.invoice_number }}</h3>
                                <p class="text-sm text-gray-500">{{ formatDate(invoice.invoice_date) }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-bold text-gray-900">{{ formatCurrency(invoice.total) }}</p>
                            <span
                                class="text-xs px-2 py-1 rounded-full"
                                :class="getStatusBadgeClass(invoice.status)"
                            >
                                {{ getStatusLabel(invoice.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Due date warning for pending/overdue -->
                    <div
                        v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                        class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between"
                    >
                        <div class="flex items-center text-sm">
                            <CalendarIcon class="w-4 h-4 mr-1.5 text-gray-400" />
                            <span class="text-gray-500">Echeance: {{ formatDate(invoice.due_date) }}</span>
                        </div>
                        <div v-if="invoice.status === 'overdue'" class="flex items-center text-red-600 text-sm">
                            <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                            {{ getDaysOverdue(invoice.due_date) }} jours de retard
                        </div>
                    </div>

                    <!-- Related box info -->
                    <div class="mt-3 pt-3 border-t border-gray-100 flex items-center text-sm text-gray-500">
                        <CubeIcon class="w-4 h-4 mr-1.5" />
                        {{ invoice.contract?.box?.name || 'N/A' }}
                    </div>
                </div>

                <!-- Action bar for unpaid invoices -->
                <div
                    v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                    class="px-4 py-3 bg-gray-50 flex items-center justify-between"
                >
                    <span class="text-sm text-gray-500">Solde restant: {{ formatCurrency(invoice.balance) }}</span>
                    <button
                        @click.prevent="payInvoice(invoice)"
                        class="px-4 py-1.5 bg-primary-600 text-white text-sm font-medium rounded-lg flex items-center"
                    >
                        <CreditCardIcon class="w-4 h-4 mr-1.5" />
                        Payer
                    </button>
                </div>
            </Link>
        </div>

        <!-- Total Outstanding -->
        <div v-if="totalOutstanding > 0" class="fixed bottom-24 left-4 right-4">
            <div class="bg-primary-600 rounded-xl p-4 shadow-lg flex items-center justify-between">
                <div class="text-white">
                    <p class="text-sm opacity-80">Total a payer</p>
                    <p class="text-xl font-bold">{{ formatCurrency(totalOutstanding) }}</p>
                </div>
                <Link
                    :href="route('mobile.pay')"
                    class="px-4 py-2 bg-white text-primary-600 font-semibold rounded-lg"
                >
                    Tout payer
                </Link>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentTextIcon,
    MagnifyingGlassIcon,
    CalendarIcon,
    ExclamationCircleIcon,
    CubeIcon,
    CreditCardIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: Array,
    stats: Object,
})

const searchQuery = ref('')
const activeFilter = ref('all')

const filters = [
    { value: 'all', label: 'Toutes', activeClass: 'bg-primary-600' },
    { value: 'sent', label: 'En attente', activeClass: 'bg-yellow-600' },
    { value: 'overdue', label: 'En retard', activeClass: 'bg-red-600' },
    { value: 'paid', label: 'Payees', activeClass: 'bg-green-600' },
]

const filteredInvoices = computed(() => {
    let result = props.invoices || []

    if (activeFilter.value !== 'all') {
        result = result.filter(i => i.status === activeFilter.value)
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(i =>
            i.invoice_number?.toLowerCase().includes(query) ||
            i.contract?.box?.name?.toLowerCase().includes(query)
        )
    }

    return result
})

const totalOutstanding = computed(() => {
    return props.invoices
        ?.filter(i => i.status === 'sent' || i.status === 'overdue')
        .reduce((sum, i) => sum + parseFloat(i.balance || 0), 0) || 0
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        sent: 'En attente',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getStatusBgClass = (status) => {
    const classes = {
        draft: 'bg-gray-100',
        sent: 'bg-yellow-100',
        paid: 'bg-green-100',
        overdue: 'bg-red-100',
        cancelled: 'bg-gray-100',
    }
    return classes[status] || 'bg-gray-100'
}

const getStatusTextClass = (status) => {
    const classes = {
        draft: 'text-gray-600',
        sent: 'text-yellow-600',
        paid: 'text-green-600',
        overdue: 'text-red-600',
        cancelled: 'text-gray-600',
    }
    return classes[status] || 'text-gray-600'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-yellow-100 text-yellow-700',
        paid: 'bg-green-100 text-green-700',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getDaysOverdue = (dueDate) => {
    if (!dueDate) return 0
    const due = new Date(dueDate)
    const now = new Date()
    const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24))
    return Math.max(0, diff)
}

const payInvoice = (invoice) => {
    router.visit(route('mobile.pay', { invoice_id: invoice.id }))
}
</script>
