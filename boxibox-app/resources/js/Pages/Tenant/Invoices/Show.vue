<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    DocumentArrowDownIcon,
    PencilSquareIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    ClockIcon,
    CheckCircleIcon,
    CurrencyEuroIcon,
    UserIcon,
    PhoneIcon,
    MapPinIcon,
    DocumentDuplicateIcon,
    CubeIcon,
    BellAlertIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: Object,
})

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

const statusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 border-gray-200' },
    sent: { label: 'Envoyée', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    paid: { label: 'Payée', color: 'bg-green-100 text-green-700 border-green-200' },
    partial: { label: 'Partiel', color: 'bg-amber-100 text-amber-700 border-amber-200' },
    overdue: { label: 'En retard', color: 'bg-red-100 text-red-700 border-red-200' },
    cancelled: { label: 'Annulée', color: 'bg-gray-100 text-gray-500 border-gray-200' },
}

const typeConfig = {
    invoice: { label: 'Facture', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    credit_note: { label: 'Avoir', color: 'bg-purple-100 text-purple-700 border-purple-200' },
    proforma: { label: 'Proforma', color: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const items = ref(typeof props.invoice.items === 'string' ? JSON.parse(props.invoice.items) : props.invoice.items || [])

const remainingAmount = props.invoice.total - props.invoice.paid_amount

const paymentMethodLabels = {
    bank_transfer: 'Virement bancaire',
    card: 'Carte bancaire',
    cash: 'Espèces',
    check: 'Chèque',
    sepa: 'Prélèvement SEPA',
}
</script>

<template>
    <TenantLayout :title="`Facture ${invoice.invoice_number}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header avec gradient -->
                <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 via-blue-700 to-indigo-700 p-8 shadow-xl">
                    <div class="absolute inset-0 bg-grid-white/10"></div>
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 h-40 w-40 rounded-full bg-indigo-400/20 blur-3xl"></div>

                    <div class="relative">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('tenant.invoices.index')"
                                    class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 transition-all"
                                >
                                    <ArrowLeftIcon class="w-5 h-5" />
                                </Link>
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h1 class="text-2xl lg:text-3xl font-bold text-white">{{ invoice.invoice_number }}</h1>
                                        <span :class="[statusConfig[invoice.status]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ statusConfig[invoice.status]?.label || invoice.status }}
                                        </span>
                                        <span :class="[typeConfig[invoice.type]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ typeConfig[invoice.type]?.label || invoice.type }}
                                        </span>
                                    </div>
                                    <p class="text-blue-100">
                                        {{ getCustomerName(invoice.customer) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    :href="route('tenant.invoices.pdf', invoice.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 text-white hover:bg-white/20 border border-white/20 transition-all font-medium"
                                >
                                    <DocumentArrowDownIcon class="w-5 h-5" />
                                    <span>Télécharger PDF</span>
                                </a>
                                <Link
                                    :href="route('tenant.invoices.edit', invoice.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-blue-700 hover:bg-blue-50 transition-all font-medium shadow-lg"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                    <span>Modifier</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Stats rapides -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CurrencyEuroIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-200">Total TTC</p>
                                        <p class="text-lg font-bold text-white">{{ formatCurrency(invoice.total) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CheckCircleIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-200">Montant payé</p>
                                        <p class="text-lg font-bold text-green-300">{{ formatCurrency(invoice.paid_amount) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <ClockIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-200">Restant dû</p>
                                        <p :class="['text-lg font-bold', remainingAmount > 0 ? 'text-red-300' : 'text-green-300']">
                                            {{ formatCurrency(remainingAmount) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CalendarDaysIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-blue-200">Échéance</p>
                                        <p class="text-lg font-bold text-white">{{ formatDate(invoice.due_date) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Détails de la facture -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Détails de la facture</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            Date de facturation
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(invoice.invoice_date) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <ClockIcon class="w-4 h-4" />
                                            Date d'échéance
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(invoice.due_date) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            Période
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">
                                            {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CheckCircleIcon class="w-4 h-4" />
                                            Date de paiement
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(invoice.paid_at) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Lignes de facturation -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 rounded-lg">
                                        <DocumentDuplicateIcon class="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Lignes de facturation</h2>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Qté</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix unit.</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(item, index) in items" :key="index" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ item.description }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ item.quantity }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ formatCurrency(item.quantity * item.unit_price) }}</td>
                                        </tr>
                                        <tr v-if="items.length === 0">
                                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">
                                                Aucune ligne de facturation
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totaux -->
                            <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-5 border-t border-gray-100">
                                <dl class="space-y-3 max-w-xs ml-auto">
                                    <div class="flex justify-between text-sm">
                                        <dt class="text-gray-600">Sous-total HT</dt>
                                        <dd class="font-medium text-gray-900">{{ formatCurrency(invoice.subtotal) }}</dd>
                                    </div>
                                    <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                                        <dt class="text-gray-600">Remise</dt>
                                        <dd class="font-medium text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <dt class="text-gray-600">TVA ({{ invoice.tax_rate }}%)</dt>
                                        <dd class="font-medium text-gray-900">{{ formatCurrency(invoice.tax_amount) }}</dd>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                        <dt class="text-gray-900">Total TTC</dt>
                                        <dd class="text-blue-600">{{ formatCurrency(invoice.total) }}</dd>
                                    </div>
                                    <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                                        <dt class="text-gray-600">Montant payé</dt>
                                        <dd class="font-medium text-green-600">{{ formatCurrency(invoice.paid_amount) }}</dd>
                                    </div>
                                    <div class="flex justify-between text-base font-bold">
                                        <dt class="text-gray-900">Restant dû</dt>
                                        <dd :class="remainingAmount > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(remainingAmount) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Paiements -->
                        <div v-if="invoice.payments && invoice.payments.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <CurrencyEuroIcon class="w-5 h-5 text-green-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Paiements</h2>
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
                                        {{ invoice.payments.length }}
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Méthode</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Référence</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="payment in invoice.payments" :key="payment.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(payment.payment_date) }}</td>
                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                {{ paymentMethodLabels[payment.payment_method] || payment.payment_method?.replace('_', ' ') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">{{ payment.reference || '-' }}</td>
                                            <td class="px-6 py-4 text-sm font-semibold text-green-600 text-right">{{ formatCurrency(payment.amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="invoice.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-amber-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ invoice.notes }}</p>
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
                                <div class="flex items-center gap-4 mb-6">
                                    <div class="flex-shrink-0 h-14 w-14 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center shadow-lg">
                                        <span class="text-white text-xl font-bold">
                                            {{ (invoice.customer?.first_name?.[0] || invoice.customer?.company_name?.[0] || '?').toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ getCustomerName(invoice.customer) }}</p>
                                        <p class="text-sm text-gray-500">{{ invoice.customer?.email }}</p>
                                    </div>
                                </div>
                                <dl class="space-y-4">
                                    <div v-if="invoice.customer?.phone" class="flex items-start gap-3">
                                        <PhoneIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ invoice.customer.phone }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="invoice.customer?.address" class="flex items-start gap-3">
                                        <MapPinIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                                {{ invoice.customer.address }}<br>
                                                {{ invoice.customer.postal_code }} {{ invoice.customer.city }}
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.customers.show', invoice.customer?.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        Voir le profil client
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Informations contrat -->
                        <div v-if="invoice.contract" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 rounded-lg">
                                        <DocumentDuplicateIcon class="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Contrat</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">N° de contrat</dt>
                                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ invoice.contract.contract_number }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <CubeIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Box</dt>
                                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ invoice.contract.box?.code }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <CurrencyEuroIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Loyer mensuel</dt>
                                            <dd class="mt-1 text-sm font-semibold text-emerald-600">{{ formatCurrency(invoice.contract.monthly_price) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.contracts.show', invoice.contract.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors"
                                    >
                                        <DocumentDuplicateIcon class="w-4 h-4" />
                                        Voir le contrat
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Relances -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-orange-100 rounded-lg">
                                        <BellAlertIcon class="w-5 h-5 text-orange-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Relances</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <EnvelopeIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre de relances</dt>
                                            <dd class="mt-1">
                                                <span :class="[
                                                    'px-2.5 py-1 rounded-full text-sm font-semibold',
                                                    invoice.reminder_count > 0 ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600'
                                                ]">
                                                    {{ invoice.reminder_count || 0 }}
                                                </span>
                                            </dd>
                                        </div>
                                    </div>
                                    <div v-if="invoice.last_reminder_sent" class="flex items-start gap-3">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière relance</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(invoice.last_reminder_sent) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
