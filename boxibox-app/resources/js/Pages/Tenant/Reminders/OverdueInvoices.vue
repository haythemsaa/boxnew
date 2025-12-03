<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    CurrencyEuroIcon,
    DocumentTextIcon,
    UserIcon,
    CalendarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: Array,
})

const selectedInvoices = ref([])

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}

const createReminders = () => {
    if (selectedInvoices.value.length === 0) {
        alert('Veuillez sélectionner au moins une facture.')
        return
    }

    // Redirect to create reminders page with selected invoices
    const invoiceIds = selectedInvoices.value.join(',')
    router.get(route('tenant.reminders.create'), { invoice_ids: invoiceIds })
}

const toggleAll = () => {
    if (selectedInvoices.value.length === props.invoices.length) {
        selectedInvoices.value = []
    } else {
        selectedInvoices.value = props.invoices.map(i => i.id)
    }
}

const daysOverdue = (dueDate) => {
    const due = new Date(dueDate)
    const today = new Date()
    const diffTime = today - due
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays > 0 ? diffDays : 0
}
</script>

<template>
    <TenantLayout title="Factures en retard">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-red-600 via-orange-600 to-red-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.reminders.index')"
                    class="inline-flex items-center text-red-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux relances
                </Link>

                <div class="flex items-center space-x-4">
                    <ExclamationTriangleIcon class="h-10 w-10 text-white" />
                    <div>
                        <h1 class="text-4xl font-bold text-white">Factures en retard</h1>
                        <p class="mt-2 text-red-100">{{ invoices.length }} facture(s) en attente de paiement</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Action Bar -->
            <div v-if="selectedInvoices.length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center justify-between">
                <div>
                    <p class="font-medium text-red-900">
                        {{ selectedInvoices.length }} facture(s) sélectionnée(s)
                    </p>
                    <p class="text-sm text-red-700">
                        Sélectionnez les factures pour lesquelles vous voulez créer des relances
                    </p>
                </div>
                <button
                    @click="createReminders"
                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors"
                >
                    Créer les relances
                </button>
            </div>

            <!-- Empty State -->
            <div v-if="invoices.length === 0" class="text-center py-12">
                <ExclamationTriangleIcon class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune facture en retard</h3>
                <p class="text-gray-500">Toutes vos factures sont à jour !</p>
            </div>

            <!-- Invoices Table -->
            <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left">
                                    <input
                                        type="checkbox"
                                        :checked="selectedInvoices.length === invoices.length && invoices.length > 0"
                                        @change="toggleAll"
                                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Facture</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Montant</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Échéance</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">En retard</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4">
                                    <input
                                        type="checkbox"
                                        :value="invoice.id"
                                        v-model="selectedInvoices"
                                        class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                    />
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <DocumentTextIcon class="h-5 w-5 text-gray-400" />
                                        <Link
                                            :href="route('tenant.invoices.show', invoice.id)"
                                            class="font-medium text-gray-900 hover:text-red-600"
                                        >
                                            {{ invoice.invoice_number }}
                                        </Link>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <UserIcon class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-900">{{ getCustomerName(invoice.customer) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <CurrencyEuroIcon class="h-4 w-4 text-gray-400" />
                                        <span class="font-medium text-gray-900">{{ formatCurrency(invoice.total - invoice.paid_amount) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <CalendarIcon class="h-4 w-4 text-gray-400" />
                                        <span class="text-gray-600">{{ formatDate(invoice.due_date) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        {{ daysOverdue(invoice.due_date) }} jour(s)
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
