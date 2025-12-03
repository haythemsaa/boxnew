<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    CurrencyEuroIcon,
    UserIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    CreditCardIcon,
    PrinterIcon,
    EnvelopeIcon,
    ClockIcon,
    CheckCircleIcon,
    BanknotesIcon,
    ReceiptRefundIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    payment: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const statusConfig = {
    pending: { label: 'En attente', color: 'bg-amber-100 text-amber-700 border-amber-200', icon: ClockIcon },
    processing: { label: 'En cours', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: ClockIcon },
    completed: { label: 'Complété', color: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCircleIcon },
    failed: { label: 'Échoué', color: 'bg-red-100 text-red-700 border-red-200', icon: ClockIcon },
    refunded: { label: 'Remboursé', color: 'bg-purple-100 text-purple-700 border-purple-200', icon: ReceiptRefundIcon },
    cancelled: { label: 'Annulé', color: 'bg-gray-100 text-gray-600 border-gray-200', icon: ClockIcon },
}

const typeConfig = {
    payment: { label: 'Paiement', color: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: BanknotesIcon },
    refund: { label: 'Remboursement', color: 'bg-red-100 text-red-700 border-red-200', icon: ReceiptRefundIcon },
    deposit: { label: 'Caution', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: ShieldCheckIcon },
}

const methodLabels = {
    card: 'Carte bancaire',
    bank_transfer: 'Virement bancaire',
    cash: 'Espèces',
    cheque: 'Chèque',
    sepa: 'Prélèvement SEPA',
    stripe: 'Stripe',
    paypal: 'PayPal',
}

const gatewayLabels = {
    stripe: 'Stripe',
    paypal: 'PayPal',
    sepa: 'SEPA',
    manual: 'Manuel',
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <TenantLayout :title="`Paiement ${payment.payment_number || payment.id}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50 to-teal-50 py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header avec gradient -->
                <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-700 to-teal-700 p-8 shadow-xl">
                    <div class="absolute inset-0 bg-grid-white/10"></div>
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 h-40 w-40 rounded-full bg-teal-400/20 blur-3xl"></div>

                    <div class="relative">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('tenant.payments.index')"
                                    class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 transition-all"
                                >
                                    <ArrowLeftIcon class="w-5 h-5" />
                                </Link>
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h1 class="text-2xl lg:text-3xl font-bold text-white">
                                            {{ payment.payment_number || `#${payment.id}` }}
                                        </h1>
                                        <span :class="[statusConfig[payment.status]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ statusConfig[payment.status]?.label || payment.status }}
                                        </span>
                                        <span :class="[typeConfig[payment.type]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ typeConfig[payment.type]?.label || payment.type }}
                                        </span>
                                    </div>
                                    <p class="text-emerald-100">
                                        {{ getCustomerName(payment.customer) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Link
                                    :href="route('tenant.payments.edit', payment.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-emerald-700 hover:bg-emerald-50 transition-all font-medium shadow-lg"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                    <span>Modifier</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Montant principal -->
                        <div class="mt-8 text-center py-6">
                            <p class="text-emerald-200 text-sm font-medium uppercase tracking-wide mb-2">Montant</p>
                            <p class="text-5xl font-bold text-white">{{ formatCurrency(payment.amount) }}</p>
                            <div class="mt-4 flex items-center justify-center gap-3">
                                <CreditCardIcon class="w-5 h-5 text-emerald-200" />
                                <span class="text-emerald-100">{{ methodLabels[payment.method] || payment.method }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Détails du paiement -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Détails du paiement</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            Date de paiement
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(payment.paid_at) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CreditCardIcon class="w-4 h-4" />
                                            Méthode
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ methodLabels[payment.method] || payment.method }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <DocumentTextIcon class="w-4 h-4" />
                                            Référence
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ payment.reference || '-' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CurrencyEuroIcon class="w-4 h-4" />
                                            Passerelle
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ gatewayLabels[payment.gateway] || payment.gateway }}</dd>
                                    </div>
                                </dl>

                                <div v-if="payment.gateway_payment_id" class="mt-6 p-4 bg-gray-50 rounded-xl">
                                    <dt class="text-sm font-medium text-gray-500 mb-2">ID de transaction</dt>
                                    <dd class="text-sm font-mono text-gray-900 bg-white px-3 py-2 rounded-lg border border-gray-200">
                                        {{ payment.gateway_payment_id }}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        <!-- Facture associée -->
                        <div v-if="payment.invoice" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Facture associée</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ payment.invoice.invoice_number }}</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(payment.invoice.invoice_date) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-gray-900">{{ formatCurrency(payment.invoice.total) }}</p>
                                        <span :class="[
                                            payment.invoice.status === 'paid' ? 'text-emerald-600' : 'text-amber-600'
                                        ]" class="text-sm font-medium">
                                            {{ payment.invoice.status === 'paid' ? 'Payée' : 'En attente' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <Link
                                        :href="route('tenant.invoices.show', payment.invoice.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                                    >
                                        <DocumentTextIcon class="w-4 h-4" />
                                        Voir la facture
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="payment.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-amber-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ payment.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Informations client -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <UserIcon class="w-5 h-5 text-purple-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Client</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="flex-shrink-0 h-14 w-14 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                        <span class="text-white text-xl font-bold">
                                            {{ (payment.customer?.first_name?.[0] || payment.customer?.company_name?.[0] || '?').toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ getCustomerName(payment.customer) }}</p>
                                        <p class="text-sm text-gray-500">{{ payment.customer?.email }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.customers.show', payment.customer?.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        Voir le profil client
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Chronologie -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 rounded-lg">
                                        <ClockIcon class="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Chronologie</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Création</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(payment.created_at) }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <ClockIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière modification</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(payment.updated_at) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Actions rapides -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Actions</h2>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                <button class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <PrinterIcon class="w-5 h-5 text-gray-500" />
                                    Imprimer le reçu
                                </button>
                                <button class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <EnvelopeIcon class="w-5 h-5 text-gray-500" />
                                    Envoyer une confirmation
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
