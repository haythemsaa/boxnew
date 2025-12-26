<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    DocumentArrowDownIcon,
    PencilSquareIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    UserIcon,
    CubeIcon,
    BuildingOfficeIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    TrashIcon,
    KeyIcon,
    PhoneIcon,
    MapPinIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
})

const showTerminationModal = ref(false)
const terminationForm = useForm({
    termination_reason: 'customer_request',
    termination_notes: '',
    effective_date: new Date().toISOString().split('T')[0],
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const statusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700 border-gray-200' },
    pending_signature: { label: 'En attente de signature', color: 'bg-amber-100 text-amber-700 border-amber-200' },
    active: { label: 'Actif', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    expired: { label: 'Expiré', color: 'bg-red-100 text-red-700 border-red-200' },
    terminated: { label: 'Résilié', color: 'bg-red-100 text-red-700 border-red-200' },
    cancelled: { label: 'Annulé', color: 'bg-gray-100 text-gray-500 border-gray-200' },
}

const typeConfig = {
    standard: { label: 'Standard', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    short_term: { label: 'Court terme', color: 'bg-purple-100 text-purple-700 border-purple-200' },
    long_term: { label: 'Long terme', color: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
}

const invoiceStatusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700' },
    sent: { label: 'Envoyée', color: 'bg-blue-100 text-blue-700' },
    paid: { label: 'Payée', color: 'bg-emerald-100 text-emerald-700' },
    partial: { label: 'Partiel', color: 'bg-amber-100 text-amber-700' },
    overdue: { label: 'En retard', color: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Annulée', color: 'bg-gray-100 text-gray-500' },
}

const paymentMethodLabels = {
    card: 'Carte bancaire',
    bank_transfer: 'Virement bancaire',
    cash: 'Espèces',
    sepa: 'Prélèvement SEPA',
}

const terminationReasons = [
    { value: 'customer_request', label: 'Demande du client' },
    { value: 'non_payment', label: 'Non-paiement' },
    { value: 'breach', label: 'Rupture de contrat' },
    { value: 'end_of_term', label: 'Fin de terme' },
    { value: 'other', label: 'Autre' },
]

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const submitTermination = () => {
    terminationForm.post(route('tenant.contracts.terminate', props.contract.id), {
        onSuccess: () => {
            showTerminationModal.value = false
            terminationForm.reset()
        },
    })
}
</script>

<template>
    <TenantLayout :title="`Contrat ${contract.contract_number}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header avec gradient -->
                <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 p-8 shadow-xl">
                    <div class="absolute inset-0 bg-grid-white/10"></div>
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 h-40 w-40 rounded-full bg-purple-400/20 blur-3xl"></div>

                    <div class="relative">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('tenant.contracts.index')"
                                    class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 transition-all"
                                >
                                    <ArrowLeftIcon class="w-5 h-5" />
                                </Link>
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h1 class="text-2xl lg:text-3xl font-bold text-white">{{ contract.contract_number }}</h1>
                                        <span :class="[statusConfig[contract.status]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ statusConfig[contract.status]?.label || contract.status }}
                                        </span>
                                        <span :class="[typeConfig[contract.type]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ typeConfig[contract.type]?.label || contract.type }}
                                        </span>
                                    </div>
                                    <p class="text-indigo-100">
                                        {{ getCustomerName(contract.customer) }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-wrap">
                                <a
                                    :href="route('tenant.contracts.pdf', contract.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/10 text-white hover:bg-white/20 border border-white/20 transition-all font-medium"
                                >
                                    <DocumentArrowDownIcon class="w-5 h-5" />
                                    <span>Télécharger PDF</span>
                                </a>
                                <Link
                                    v-if="contract.status === 'pending_signature'"
                                    :href="route('tenant.contracts.sign', contract.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-500 text-white hover:bg-amber-600 transition-all font-medium shadow-lg"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                    <span>Signer le contrat</span>
                                </Link>
                                <Link
                                    :href="route('tenant.contracts.edit', contract.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-indigo-700 hover:bg-indigo-50 transition-all font-medium shadow-lg"
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
                                        <p class="text-xs text-indigo-200">Loyer mensuel</p>
                                        <p class="text-lg font-bold text-white">{{ formatCurrency(contract.monthly_price) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CalendarDaysIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-indigo-200">Date de début</p>
                                        <p class="text-lg font-bold text-white">{{ formatDate(contract.start_date) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CubeIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-indigo-200">Box</p>
                                        <p class="text-lg font-bold text-white">{{ contract.box?.code || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <KeyIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-indigo-200">Dépôt</p>
                                        <p class="text-lg font-bold text-white">{{ formatCurrency(contract.deposit_amount) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Détails du contrat -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-indigo-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Détails du contrat</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            Date de début
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(contract.start_date) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CalendarDaysIcon class="w-4 h-4" />
                                            Date de fin
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatDate(contract.end_date) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CurrencyEuroIcon class="w-4 h-4" />
                                            Loyer mensuel
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-emerald-600">{{ formatCurrency(contract.monthly_price) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <KeyIcon class="w-4 h-4" />
                                            Dépôt de garantie
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ formatCurrency(contract.deposit_amount) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CurrencyEuroIcon class="w-4 h-4" />
                                            Mode de paiement
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ paymentMethodLabels[contract.payment_method] || contract.payment_method || '-' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <ClockIcon class="w-4 h-4" />
                                            Jour de facturation
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ contract.billing_day || 1 }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Assurance</dt>
                                        <dd class="mt-2">
                                            <span v-if="contract.insurance_included" class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                                <CheckCircleIcon class="w-4 h-4" /> Incluse
                                            </span>
                                            <span v-else class="text-gray-500">Non incluse</span>
                                        </dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Renouvellement auto</dt>
                                        <dd class="mt-2">
                                            <span v-if="contract.auto_renewal" class="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                                <CheckCircleIcon class="w-4 h-4" /> Oui
                                            </span>
                                            <span v-else class="text-gray-500">Non</span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Informations Box -->
                        <div v-if="contract.box" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-cyan-100 rounded-lg">
                                        <CubeIcon class="w-5 h-5 text-cyan-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Informations Box</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Code du box</dt>
                                        <dd class="mt-2 text-base font-bold text-gray-900">{{ contract.box.code }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Surface</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ contract.box.size }} m²</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Site</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ contract.site?.name || '-' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Étage</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ contract.box.floor?.name || 'RDC' }}</dd>
                                    </div>
                                </dl>
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.boxes.index')"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-cyan-600 hover:text-cyan-700 transition-colors"
                                    >
                                        <CubeIcon class="w-4 h-4" />
                                        Voir tous les boxes
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Services additionnels (Addons) -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-purple-100 rounded-lg">
                                            <CubeIcon class="w-5 h-5 text-purple-600" />
                                        </div>
                                        <h2 class="text-lg font-semibold text-gray-900">Services additionnels</h2>
                                    </div>
                                    <span v-if="contract.addons && contract.addons.length > 0" class="px-3 py-1 bg-purple-100 text-purple-700 text-sm font-semibold rounded-full">
                                        {{ contract.addons.filter(a => a.status === 'active').length }} actif(s)
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <div v-if="contract.addons && contract.addons.length > 0" class="space-y-3 mb-4">
                                    <div
                                        v-for="addon in contract.addons.filter(a => a.status === 'active').slice(0, 3)"
                                        :key="addon.id"
                                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-white rounded-lg shadow-sm">
                                                <CubeIcon class="w-4 h-4 text-purple-600" />
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ addon.product_name }}</p>
                                                <p class="text-xs text-gray-500">{{ addon.quantity }} x {{ formatCurrency(addon.unit_price) }}</p>
                                            </div>
                                        </div>
                                        <span class="font-semibold text-gray-900">{{ formatCurrency(addon.quantity * addon.unit_price) }}</span>
                                    </div>
                                    <div v-if="contract.addons.filter(a => a.status === 'active').length > 3" class="text-center text-sm text-gray-500">
                                        + {{ contract.addons.filter(a => a.status === 'active').length - 3 }} autre(s) service(s)
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-gray-500 mb-4">
                                    Aucun service additionnel actif
                                </div>
                                <Link
                                    :href="route('tenant.contracts.addons.index', contract.id)"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white rounded-xl text-sm font-medium hover:from-purple-600 hover:to-indigo-700 transition-all shadow-lg"
                                >
                                    <CubeIcon class="w-5 h-5" />
                                    Gérer les services
                                </Link>
                            </div>
                        </div>

                        <!-- Factures -->
                        <div v-if="contract.invoices && contract.invoices.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-100 rounded-lg">
                                            <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                                        </div>
                                        <h2 class="text-lg font-semibold text-gray-900">Factures</h2>
                                    </div>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm font-semibold rounded-full">
                                        {{ contract.invoices.length }} facture(s)
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Numéro</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="invoice in contract.invoices" :key="invoice.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Link :href="route('tenant.invoices.show', invoice.id)" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                                    {{ invoice.invoice_number }}
                                                </Link>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDate(invoice.invoice_date) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="[invoiceStatusConfig[invoice.status]?.color || 'bg-gray-100 text-gray-700', 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                    {{ invoiceStatusConfig[invoice.status]?.label || invoice.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">{{ formatCurrency(invoice.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Conditions spéciales -->
                        <div v-if="contract.special_conditions" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-amber-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Conditions spéciales</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ contract.special_conditions }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="contract.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ contract.notes }}</p>
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
                                            {{ (contract.customer?.first_name?.[0] || contract.customer?.company_name?.[0] || '?').toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ getCustomerName(contract.customer) }}</p>
                                        <p class="text-sm text-gray-500">{{ contract.customer?.email }}</p>
                                    </div>
                                </div>
                                <dl class="space-y-4">
                                    <div v-if="contract.customer?.phone" class="flex items-start gap-3">
                                        <PhoneIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ contract.customer.phone }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="contract.customer?.address" class="flex items-start gap-3">
                                        <MapPinIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                                {{ contract.customer.address }}<br>
                                                {{ contract.customer.postal_code }} {{ contract.customer.city }}
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.customers.show', contract.customer?.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-purple-600 hover:text-purple-700 transition-colors"
                                    >
                                        <UserIcon class="w-4 h-4" />
                                        Voir le profil client
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Signature -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 rounded-lg">
                                        <PencilSquareIcon class="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Signature</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date de signature</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(contract.signed_at) }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="contract.signature" class="flex items-start gap-3">
                                        <CheckCircleIcon class="w-5 h-5 text-emerald-500 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</dt>
                                            <dd class="mt-1 text-sm font-medium text-emerald-600">Signé</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Chronologie -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <ClockIcon class="w-5 h-5 text-gray-600" />
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
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(contract.created_at) }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <ClockIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière modification</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(contract.updated_at) }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="contract.terminated_at" class="flex items-start gap-3">
                                        <XCircleIcon class="w-5 h-5 text-red-500 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Résilié le</dt>
                                            <dd class="mt-1 text-sm font-medium text-red-600">{{ formatDate(contract.terminated_at) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="contract.status === 'active'" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Actions</h2>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                <Link
                                    :href="route('tenant.contracts.renewal-options', contract.id)"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl text-sm font-medium hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg"
                                >
                                    <ArrowPathIcon class="w-5 h-5" />
                                    Renouveler
                                </Link>
                                <button
                                    @click="showTerminationModal = true"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl text-sm font-medium hover:from-red-600 hover:to-rose-700 transition-all shadow-lg"
                                >
                                    <TrashIcon class="w-5 h-5" />
                                    Résilier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal de résiliation -->
                <div v-if="showTerminationModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl animate-fade-in">
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="p-3 bg-red-100 rounded-xl">
                                    <TrashIcon class="w-6 h-6 text-red-600" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">Résilier le contrat</h3>
                                    <p class="text-sm text-gray-500">Cette action est irréversible</p>
                                </div>
                            </div>
                            <form @submit.prevent="submitTermination" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Motif de résiliation</label>
                                    <select
                                        v-model="terminationForm.termination_reason"
                                        required
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    >
                                        <option v-for="reason in terminationReasons" :key="reason.value" :value="reason.value">
                                            {{ reason.label }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date effective</label>
                                    <input
                                        v-model="terminationForm.effective_date"
                                        type="date"
                                        required
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                    <textarea
                                        v-model="terminationForm.termination_notes"
                                        rows="3"
                                        placeholder="Informations complémentaires..."
                                        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                    ></textarea>
                                </div>
                                <div class="flex gap-3 pt-4">
                                    <button
                                        type="submit"
                                        :disabled="terminationForm.processing"
                                        class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-xl font-medium hover:from-red-600 hover:to-rose-700 transition-all shadow-lg disabled:opacity-50"
                                    >
                                        Confirmer la résiliation
                                    </button>
                                    <button
                                        type="button"
                                        @click="showTerminationModal = false"
                                        class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors"
                                    >
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
