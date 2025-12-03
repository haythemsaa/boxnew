<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    UserIcon,
    BuildingOfficeIcon,
    EnvelopeIcon,
    PhoneIcon,
    MapPinIcon,
    PencilSquareIcon,
    DocumentTextIcon,
    CurrencyEuroIcon,
    DocumentDuplicateIcon,
    FolderIcon,
    InformationCircleIcon,
    CalendarDaysIcon,
    ClockIcon,
    EyeIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customer: Object,
    stats: Object,
})

const activeTab = ref('coordonnees')

const tabs = [
    { id: 'coordonnees', name: 'Coordonnées', icon: UserIcon },
    { id: 'contrats', name: 'Contrats', icon: DocumentTextIcon },
    { id: 'factures', name: 'Factures', icon: DocumentDuplicateIcon },
    { id: 'informations', name: 'Informations', icon: InformationCircleIcon },
    { id: 'fichiers', name: 'Fichiers', icon: FolderIcon },
]

const customerName = computed(() => {
    if (props.customer.type === 'company' && props.customer.company_name) {
        return props.customer.company_name
    }
    return `${props.customer.first_name} ${props.customer.last_name}`
})

const statusConfig = {
    active: { label: 'Actif', color: 'bg-green-100 text-green-700 border-green-200' },
    inactive: { label: 'Inactif', color: 'bg-gray-100 text-gray-600 border-gray-200' },
    prospect: { label: 'Prospect', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    suspended: { label: 'Suspendu', color: 'bg-red-100 text-red-700 border-red-200' },
}

const contractStatusConfig = {
    active: { label: 'Actif', color: 'bg-green-100 text-green-700' },
    pending_signature: { label: 'En attente de signature', color: 'bg-yellow-100 text-yellow-700' },
    expired: { label: 'Expiré', color: 'bg-gray-100 text-gray-600' },
    terminated: { label: 'Résilié', color: 'bg-red-100 text-red-700' },
    draft: { label: 'Brouillon', color: 'bg-blue-100 text-blue-700' },
}

const invoiceStatusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-600' },
    sent: { label: 'Envoyée', color: 'bg-blue-100 text-blue-700' },
    paid: { label: 'Payée', color: 'bg-green-100 text-green-700' },
    overdue: { label: 'En retard', color: 'bg-red-100 text-red-700' },
    cancelled: { label: 'Annulée', color: 'bg-gray-100 text-gray-600' },
    partial: { label: 'Partielle', color: 'bg-orange-100 text-orange-700' },
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusStyle = (status) => {
    return statusConfig[status]?.color || 'bg-gray-100 text-gray-600'
}

const getStatusLabel = (status) => {
    return statusConfig[status]?.label || status
}
</script>

<template>
    <TenantLayout :title="customerName">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <Link :href="route('tenant.customers.index')" class="p-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors">
                                <ArrowLeftIcon class="h-5 w-5 text-white" />
                            </Link>
                            <div class="flex items-center gap-4">
                                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                                    <component :is="customer.type === 'company' ? BuildingOfficeIcon : UserIcon" class="h-8 w-8 text-white" />
                                </div>
                                <div>
                                    <h1 class="text-2xl font-bold text-white">{{ customerName }}</h1>
                                    <p class="text-blue-100 flex items-center gap-2 mt-1">
                                        <EnvelopeIcon class="h-4 w-4" />
                                        {{ customer.email }}
                                    </p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold border"
                                            :class="getStatusStyle(customer.status)"
                                        >
                                            {{ getStatusLabel(customer.status) }}
                                        </span>
                                        <span class="text-blue-200 text-sm">
                                            {{ customer.type === 'company' ? 'Entreprise' : 'Particulier' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Link
                            :href="route('tenant.customers.edit', customer.id)"
                            class="inline-flex items-center px-5 py-2.5 bg-white text-indigo-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <PencilSquareIcon class="h-5 w-5 mr-2" />
                            Modifier
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.active_contracts || 0 }}</p>
                            <p class="text-xs text-blue-100 font-medium mt-1">Contrats actifs</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.total_revenue || 0) }}</p>
                            <p class="text-xs text-blue-100 font-medium mt-1">Revenu total</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.total_invoices || 0 }}</p>
                            <p class="text-xs text-blue-100 font-medium mt-1">Factures</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.outstanding_balance || 0) }}</p>
                            <p class="text-xs text-blue-100 font-medium mt-1">Solde impayé</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.total_contracts || 0 }}</p>
                            <p class="text-xs text-blue-100 font-medium mt-1">Total contrats</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="border-b border-gray-100">
                    <nav class="flex space-x-1 px-4" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'flex items-center gap-2 px-4 py-3 border-b-2 font-medium text-sm transition-all',
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <component :is="tab.icon" class="h-5 w-5" />
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Coordonnées Tab -->
                    <div v-if="activeTab === 'coordonnees'" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <UserIcon class="h-5 w-5 text-indigo-500" />
                                    Informations personnelles
                                </h4>
                                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Type</span>
                                        <span class="text-sm font-medium text-gray-900">{{ customer.type === 'company' ? 'Entreprise' : 'Particulier' }}</span>
                                    </div>
                                    <div v-if="customer.type === 'company'" class="flex justify-between">
                                        <span class="text-sm text-gray-500">Raison sociale</span>
                                        <span class="text-sm font-medium text-gray-900">{{ customer.company_name || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Prénom</span>
                                        <span class="text-sm font-medium text-gray-900">{{ customer.first_name || '-' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Nom</span>
                                        <span class="text-sm font-medium text-gray-900">{{ customer.last_name || '-' }}</span>
                                    </div>
                                    <div v-if="customer.birth_date" class="flex justify-between">
                                        <span class="text-sm text-gray-500">Date de naissance</span>
                                        <span class="text-sm font-medium text-gray-900">{{ formatDate(customer.birth_date) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                    <EnvelopeIcon class="h-5 w-5 text-indigo-500" />
                                    Contact
                                </h4>
                                <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500">Email</span>
                                        <a :href="`mailto:${customer.email}`" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">{{ customer.email }}</a>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500">Téléphone</span>
                                        <a v-if="customer.phone" :href="`tel:${customer.phone}`" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ customer.phone }}</a>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-500">Mobile</span>
                                        <a v-if="customer.mobile" :href="`tel:${customer.mobile}`" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ customer.mobile }}</a>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                <MapPinIcon class="h-5 w-5 text-indigo-500" />
                                Adresse
                            </h4>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-sm text-gray-900">{{ customer.address || '-' }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ customer.postal_code }} {{ customer.city }}</p>
                                <p class="text-sm text-gray-500">{{ customer.country || 'France' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contrats Tab -->
                    <div v-if="activeTab === 'contrats'">
                        <div v-if="customer.contracts?.length === 0" class="text-center py-12">
                            <DocumentTextIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun contrat</h3>
                            <p class="text-gray-500">Ce client n'a pas encore de contrat</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Numéro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Box</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date début</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date fin</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Prix mensuel</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Statut</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="contract in customer.contracts" :key="contract.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ contract.contract_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ contract.box?.name || '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(contract.start_date) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(contract.end_date) }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatCurrency(contract.monthly_price) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="contractStatusConfig[contract.status]?.color">
                                                {{ contractStatusConfig[contract.status]?.label || contract.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <Link :href="route('tenant.contracts.show', contract.id)" class="text-indigo-600 hover:text-indigo-700">
                                                <EyeIcon class="h-5 w-5" />
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Factures Tab -->
                    <div v-if="activeTab === 'factures'">
                        <div v-if="customer.invoices?.length === 0" class="text-center py-12">
                            <DocumentDuplicateIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune facture</h3>
                            <p class="text-gray-500">Ce client n'a pas encore de facture</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Numéro</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Échéance</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Montant</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Statut</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="invoice in customer.invoices" :key="invoice.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ invoice.invoice_number }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(invoice.invoice_date) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(invoice.due_date) }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ formatCurrency(invoice.total) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full" :class="invoiceStatusConfig[invoice.status]?.color">
                                                {{ invoiceStatusConfig[invoice.status]?.label || invoice.status }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <Link :href="route('tenant.invoices.show', invoice.id)" class="text-indigo-600 hover:text-indigo-700">
                                                    <EyeIcon class="h-5 w-5" />
                                                </Link>
                                                <a :href="route('tenant.invoices.pdf', invoice.id)" target="_blank" class="text-gray-500 hover:text-gray-700">
                                                    <ArrowDownTrayIcon class="h-5 w-5" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Informations Tab -->
                    <div v-if="activeTab === 'informations'" class="space-y-6">
                        <div v-if="customer.type === 'company'" class="space-y-4">
                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                <BuildingOfficeIcon class="h-5 w-5 text-indigo-500" />
                                Informations entreprise
                            </h4>
                            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">SIRET</span>
                                    <span class="text-sm font-medium text-gray-900 font-mono">{{ customer.siret || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">N° TVA</span>
                                    <span class="text-sm font-medium text-gray-900 font-mono">{{ customer.vat_number || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                <CurrencyEuroIcon class="h-5 w-5 text-indigo-500" />
                                Facturation
                            </h4>
                            <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Mode de paiement préféré</span>
                                    <span class="text-sm font-medium text-gray-900">{{ customer.preferred_payment_method || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">IBAN</span>
                                    <span class="text-sm font-medium text-gray-900 font-mono">{{ customer.iban || '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-500">Score de crédit</span>
                                    <span class="text-sm font-medium text-gray-900">{{ customer.credit_score || '-' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                                <DocumentTextIcon class="h-5 w-5 text-indigo-500" />
                                Notes
                            </h4>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ customer.notes || 'Aucune note' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Fichiers Tab -->
                    <div v-if="activeTab === 'fichiers'" class="text-center py-12">
                        <FolderIcon class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun fichier</h3>
                        <p class="text-gray-500 mb-6">Téléchargez des documents pour ce client</p>
                        <button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 transition-colors">
                            <ArrowDownTrayIcon class="h-5 w-5 mr-2" />
                            Télécharger un fichier
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
