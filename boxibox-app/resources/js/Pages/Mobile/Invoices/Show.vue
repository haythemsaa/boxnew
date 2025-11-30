<template>
    <MobileLayout title="Facture" :show-back="true">
        <!-- Invoice Header -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">{{ invoice.invoice_number }}</h2>
                        <p class="text-sm text-gray-500">Emise le {{ formatDate(invoice.invoice_date) }}</p>
                    </div>
                    <span
                        class="px-3 py-1.5 rounded-full text-sm font-medium"
                        :class="getStatusBadgeClass(invoice.status)"
                    >
                        {{ getStatusLabel(invoice.status) }}
                    </span>
                </div>

                <!-- Amount -->
                <div class="text-center py-6 border-y border-gray-100">
                    <p class="text-sm text-gray-500 mb-1">Montant total</p>
                    <p class="text-4xl font-bold text-gray-900">{{ formatCurrency(invoice.total) }}</p>
                    <div v-if="invoice.balance > 0 && invoice.balance !== invoice.total" class="mt-2">
                        <p class="text-sm text-gray-500">Reste a payer: <span class="font-semibold text-red-600">{{ formatCurrency(invoice.balance) }}</span></p>
                    </div>
                </div>

                <!-- Due Date -->
                <div v-if="invoice.status === 'sent' || invoice.status === 'overdue'" class="mt-4">
                    <div
                        class="flex items-center justify-between p-3 rounded-xl"
                        :class="invoice.status === 'overdue' ? 'bg-red-50' : 'bg-yellow-50'"
                    >
                        <div class="flex items-center">
                            <CalendarIcon
                                class="w-5 h-5 mr-2"
                                :class="invoice.status === 'overdue' ? 'text-red-500' : 'text-yellow-500'"
                            />
                            <span :class="invoice.status === 'overdue' ? 'text-red-700' : 'text-yellow-700'">
                                Echeance: {{ formatDate(invoice.due_date) }}
                            </span>
                        </div>
                        <span
                            v-if="invoice.status === 'overdue'"
                            class="text-sm font-medium text-red-600"
                        >
                            {{ getDaysOverdue(invoice.due_date) }}j de retard
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>

                <!-- Line items -->
                <div class="space-y-3">
                    <div
                        v-for="(item, index) in invoice.items"
                        :key="index"
                        class="flex justify-between items-start py-3 border-b border-gray-100 last:border-0"
                    >
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ item.description }}</p>
                            <p class="text-sm text-gray-500">
                                {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                            </p>
                        </div>
                        <p class="font-semibold text-gray-900">{{ formatCurrency(item.total) }}</p>
                    </div>
                </div>

                <!-- Totals -->
                <div class="mt-4 pt-4 border-t border-gray-200 space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Sous-total HT</span>
                        <span class="text-gray-900">{{ formatCurrency(invoice.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">TVA ({{ invoice.tax_rate || 20 }}%)</span>
                        <span class="text-gray-900">{{ formatCurrency(invoice.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t border-gray-200">
                        <span class="text-gray-900">Total TTC</span>
                        <span class="text-gray-900">{{ formatCurrency(invoice.total) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Info -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contrat associe</h3>
                <Link
                    v-if="invoice.contract"
                    :href="route('mobile.contracts.show', invoice.contract.id)"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mr-3">
                            <CubeIcon class="w-5 h-5 text-primary-600" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ invoice.contract.box?.name }}</p>
                            <p class="text-sm text-gray-500">{{ invoice.contract.contract_number }}</p>
                        </div>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </Link>
            </div>
        </div>

        <!-- Payment History -->
        <div v-if="invoice.payments?.length > 0" class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Historique des paiements</h3>
                <div class="space-y-3">
                    <div
                        v-for="payment in invoice.payments"
                        :key="payment.id"
                        class="flex items-center justify-between p-3 bg-green-50 rounded-xl"
                    >
                        <div class="flex items-center">
                            <CheckCircleIcon class="w-5 h-5 text-green-500 mr-2" />
                            <div>
                                <p class="font-medium text-gray-900">{{ formatCurrency(payment.amount) }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(payment.paid_at) }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">{{ getMethodLabel(payment.method) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 mb-6">
            <!-- Pay Button -->
            <button
                v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                @click="payInvoice"
                class="w-full py-3.5 bg-primary-600 text-white font-semibold rounded-xl flex items-center justify-center"
            >
                <CreditCardIcon class="w-5 h-5 mr-2" />
                Payer {{ formatCurrency(invoice.balance || invoice.total) }}
            </button>

            <!-- Download PDF -->
            <a
                :href="route('mobile.invoices.pdf', invoice.id)"
                target="_blank"
                class="w-full py-3.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                Telecharger PDF
            </a>

            <!-- Contact Support -->
            <Link
                :href="route('mobile.support', { invoice_id: invoice.id })"
                class="w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ChatBubbleLeftRightIcon class="w-5 h-5 mr-2" />
                Contacter le support
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CalendarIcon,
    CubeIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    CreditCardIcon,
    ArrowDownTrayIcon,
    ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: Object,
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
        month: 'long',
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

const getMethodLabel = (method) => {
    const labels = {
        card: 'Carte bancaire',
        bank_transfer: 'Virement',
        sepa: 'Prelevement SEPA',
        cash: 'Especes',
        check: 'Cheque',
    }
    return labels[method] || method
}

const getDaysOverdue = (dueDate) => {
    if (!dueDate) return 0
    const due = new Date(dueDate)
    const now = new Date()
    const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24))
    return Math.max(0, diff)
}

const payInvoice = () => {
    router.visit(route('mobile.pay', { invoice_id: props.invoice.id }))
}
</script>
