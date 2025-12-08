<template>
    <TenantLayout title="Gestion des impayés" :breadcrumbs="[{ label: 'Impayés' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Factures impayées</p>
                            <p class="text-3xl font-bold text-red-600">{{ stats.total_overdue }}</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-xl">
                            <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Montant total dû</p>
                            <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(stats.total_amount) }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <CurrencyEuroIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">0-30 jours</p>
                            <p class="text-3xl font-bold text-yellow-600">{{ stats.overdue_0_30 }}</p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-xl">
                            <ClockIcon class="w-6 h-6 text-yellow-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">+90 jours</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.overdue_90_plus }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <ExclamationCircleIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aging Breakdown -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Balance âgée</h3>
                <div class="grid grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-green-50 rounded-xl">
                        <p class="text-sm text-gray-600">0-30 jours</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.overdue_0_30 }}</p>
                    </div>
                    <div class="text-center p-4 bg-yellow-50 rounded-xl">
                        <p class="text-sm text-gray-600">30-60 jours</p>
                        <p class="text-2xl font-bold text-yellow-600">{{ stats.overdue_30_60 }}</p>
                    </div>
                    <div class="text-center p-4 bg-orange-50 rounded-xl">
                        <p class="text-sm text-gray-600">60-90 jours</p>
                        <p class="text-2xl font-bold text-orange-600">{{ stats.overdue_60_90 }}</p>
                    </div>
                    <div class="text-center p-4 bg-red-50 rounded-xl">
                        <p class="text-sm text-gray-600">+90 jours</p>
                        <p class="text-2xl font-bold text-red-600">{{ stats.overdue_90_plus }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex gap-3">
                        <select v-model="filterDays" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les retards</option>
                            <option value="30">+30 jours</option>
                            <option value="60">+60 jours</option>
                            <option value="90">+90 jours</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <Link :href="route('tenant.overdue.workflows')" class="btn-secondary">
                            <CogIcon class="w-4 h-4 mr-2" />
                            Workflows
                        </Link>
                        <Link :href="route('tenant.overdue.actions')" class="btn-secondary">
                            <BoltIcon class="w-4 h-4 mr-2" />
                            Actions
                        </Link>
                        <Link :href="route('tenant.overdue.aging-report')" class="btn-primary">
                            <DocumentChartBarIcon class="w-4 h-4 mr-2" />
                            Rapport détaillé
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Overdue Invoices List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Factures impayées</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Facture</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Site / Box</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Échéance</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Retard</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="invoice in overdueInvoices.data" :key="invoice.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <Link :href="route('tenant.invoices.show', invoice.id)" class="font-medium text-primary-600 hover:text-primary-800">
                                        {{ invoice.invoice_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ invoice.customer?.full_name }}</p>
                                    <p class="text-xs text-gray-500">{{ invoice.customer?.email }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ invoice.contract?.site?.name }}</p>
                                    <p class="text-xs text-gray-500">Box {{ invoice.contract?.box?.code }}</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <p class="font-semibold text-gray-900">{{ formatCurrency(invoice.total_amount) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getDaysOverdueClass(invoice)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getDaysOverdue(invoice) }} jours
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="sendReminder(invoice)" class="text-blue-600 hover:text-blue-800 text-sm">
                                            Relancer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="overdueInvoices.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="overdueInvoices.links" />
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
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    CurrencyEuroIcon,
    ClockIcon,
    CogIcon,
    BoltIcon,
    DocumentChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    overdueInvoices: Object,
    stats: Object,
    filters: Object,
})

const filterDays = ref(props.filters?.days_overdue || '')

watch(filterDays, () => {
    router.get(route('tenant.overdue.index'), {
        days_overdue: filterDays.value,
    }, { preserveState: true, replace: true })
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const getDaysOverdue = (invoice) => {
    const dueDate = new Date(invoice.due_date)
    const today = new Date()
    const diffTime = today - dueDate
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const getDaysOverdueClass = (invoice) => {
    const days = getDaysOverdue(invoice)
    if (days > 90) return 'bg-red-100 text-red-700'
    if (days > 60) return 'bg-orange-100 text-orange-700'
    if (days > 30) return 'bg-yellow-100 text-yellow-700'
    return 'bg-green-100 text-green-700'
}

const sendReminder = (invoice) => {
    if (confirm('Envoyer une relance pour cette facture ?')) {
        router.post(route('tenant.overdue.send-reminder', invoice.id), {
            channel: 'email'
        })
    }
}
</script>
