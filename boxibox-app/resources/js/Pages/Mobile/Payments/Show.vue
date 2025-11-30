<template>
    <MobileLayout title="Detail Paiement" :show-back="true">
        <!-- Payment Success Card -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="bg-gradient-to-br from-green-500 to-green-600 p-6 text-white text-center">
                <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <CheckCircleIcon class="w-10 h-10" />
                </div>
                <p class="text-green-100 text-sm mb-1">Paiement confirme</p>
                <p class="text-4xl font-bold">{{ formatCurrency(payment.amount) }}</p>
                <p class="text-green-100 mt-2">{{ formatDateTime(payment.paid_at) }}</p>
            </div>

            <div class="p-5">
                <!-- Payment Details -->
                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Numero</span>
                        <span class="font-medium text-gray-900">{{ payment.payment_number }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Methode</span>
                        <div class="flex items-center">
                            <component
                                :is="getMethodIcon(payment.method)"
                                class="w-5 h-5 mr-2 text-gray-600"
                            />
                            <span class="font-medium text-gray-900">{{ getMethodLabel(payment.method) }}</span>
                        </div>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Statut</span>
                        <span
                            class="px-3 py-1 rounded-full text-sm font-medium"
                            :class="getStatusBadgeClass(payment.status)"
                        >
                            {{ getStatusLabel(payment.status) }}
                        </span>
                    </div>
                    <div v-if="payment.reference" class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Reference</span>
                        <span class="font-medium text-gray-900 text-sm">{{ payment.reference }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Invoice -->
        <div v-if="payment.invoice" class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Facture associee</h3>
                <Link
                    :href="route('mobile.invoices.show', payment.invoice.id)"
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mr-3">
                            <DocumentTextIcon class="w-6 h-6 text-primary-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ payment.invoice.invoice_number }}</p>
                            <p class="text-sm text-gray-500">{{ formatDate(payment.invoice.invoice_date) }}</p>
                        </div>
                    </div>
                    <div class="text-right flex items-center">
                        <div class="mr-2">
                            <p class="font-semibold text-gray-900">{{ formatCurrency(payment.invoice.total) }}</p>
                            <span
                                class="text-xs px-2 py-0.5 rounded-full"
                                :class="getInvoiceStatusClass(payment.invoice.status)"
                            >
                                {{ getInvoiceStatusLabel(payment.invoice.status) }}
                            </span>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Related Contract -->
        <div v-if="payment.contract" class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contrat associe</h3>
                <Link
                    :href="route('mobile.contracts.show', payment.contract.id)"
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mr-3">
                            <CubeIcon class="w-6 h-6 text-purple-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ payment.contract.box?.name }}</p>
                            <p class="text-sm text-gray-500">{{ payment.contract.contract_number }}</p>
                        </div>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </Link>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 mb-6">
            <!-- Download Receipt -->
            <a
                :href="route('mobile.payments.receipt', payment.id)"
                target="_blank"
                class="w-full py-3.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                Telecharger le recu
            </a>

            <!-- Share Receipt -->
            <button
                @click="shareReceipt"
                class="w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ShareIcon class="w-5 h-5 mr-2" />
                Partager le recu
            </button>

            <!-- Contact Support -->
            <Link
                :href="route('mobile.support', { payment_id: payment.id })"
                class="w-full py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ChatBubbleLeftRightIcon class="w-5 h-5 mr-2" />
                Contacter le support
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CheckCircleIcon,
    DocumentTextIcon,
    CubeIcon,
    ChevronRightIcon,
    ArrowDownTrayIcon,
    ShareIcon,
    ChatBubbleLeftRightIcon,
    CreditCardIcon,
    BuildingLibraryIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    payment: Object,
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

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const getMethodLabel = (method) => {
    const labels = {
        card: 'Carte bancaire',
        bank_transfer: 'Virement bancaire',
        sepa: 'Prelevement SEPA',
        cash: 'Especes',
        check: 'Cheque',
    }
    return labels[method] || method
}

const getMethodIcon = (method) => {
    const icons = {
        card: CreditCardIcon,
        bank_transfer: BuildingLibraryIcon,
        sepa: BuildingLibraryIcon,
        cash: BanknotesIcon,
        check: DocumentTextIcon,
    }
    return icons[method] || CreditCardIcon
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        completed: 'Confirme',
        failed: 'Echoue',
        refunded: 'Rembourse',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
        refunded: 'bg-purple-100 text-purple-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getInvoiceStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        sent: 'En attente',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getInvoiceStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-yellow-100 text-yellow-700',
        paid: 'bg-green-100 text-green-700',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const shareReceipt = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: `Recu de paiement ${props.payment.payment_number}`,
                text: `Paiement de ${formatCurrency(props.payment.amount)} effectue le ${formatDate(props.payment.paid_at)}`,
                url: window.location.href,
            })
        } catch (err) {
            console.log('Share cancelled')
        }
    } else {
        // Fallback: copy to clipboard
        await navigator.clipboard.writeText(window.location.href)
        alert('Lien copie dans le presse-papier')
    }
}
</script>
