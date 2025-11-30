<template>
    <AuthenticatedLayout :title="customerName">
        <div class="min-h-screen bg-gray-50">
            <!-- Header with customer info -->
            <div class="bg-white shadow">
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <Link :href="route('tenant.customers.index')" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">{{ customerName }}</h1>
                                <p class="text-sm text-gray-500">{{ customer.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <Link
                                :href="route('tenant.customers.edit', customer.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Modifier
                            </Link>
                            <span :class="statusClasses[customer.status]" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ customer.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick stats -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-xs text-blue-700">Contrats actifs</p>
                            <p class="text-2xl font-bold text-blue-900">{{ stats.active_contracts }}</p>
                        </div>
                        <div class="bg-emerald-50 p-4 rounded-lg">
                            <p class="text-xs text-emerald-700">Revenu total</p>
                            <p class="text-2xl font-bold text-emerald-900">{{ formatCurrency(stats.total_revenue) }}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <p class="text-xs text-purple-700">Factures</p>
                            <p class="text-2xl font-bold text-purple-900">{{ stats.total_invoices }}</p>
                        </div>
                        <div class="bg-orange-50 p-4 rounded-lg">
                            <p class="text-xs text-orange-700">Solde impayé</p>
                            <p class="text-2xl font-bold text-orange-900">{{ formatCurrency(stats.outstanding_balance) }}</p>
                        </div>
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <p class="text-xs text-indigo-700">Total contrats</p>
                            <p class="text-2xl font-bold text-indigo-900">{{ stats.total_contracts }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs Navigation -->
                <div class="border-t border-gray-200">
                    <nav class="px-4 sm:px-6 lg:px-8 flex space-x-8" aria-label="Tabs">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                activeTab === tab.id
                                    ? 'border-indigo-500 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center'
                            ]"
                        >
                            <component :is="tab.icon" class="w-5 h-5 mr-2" />
                            {{ tab.name }}
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <!-- Coordonnées Tab -->
                <div v-if="activeTab === 'coordonnees'" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Coordonnées</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type de client</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.type === 'individual' ? 'Particulier' : 'Entreprise' }}</p>
                        </div>
                        <div v-if="customer.type === 'company'">
                            <label class="block text-sm font-medium text-gray-700">Nom de l'entreprise</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.company_name || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Prénom</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.phone || '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.address || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code postal</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.postal_code || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Ville</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.city || '-' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contrats Tab -->
                <div v-if="activeTab === 'contrats'" class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Contrats</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Numéro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Box</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date début</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date fin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prix mensuel</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="contract in customer.contracts" :key="contract.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ contract.contract_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ contract.box?.name || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ contract.start_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ contract.end_date || '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(contract.monthly_price) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="contractStatusClasses[contract.status]" class="px-2 py-1 text-xs rounded-full">
                                            {{ contract.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="customer.contracts.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucun contrat
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Factures Tab -->
                <div v-if="activeTab === 'factures'" class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Factures</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Numéro</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Échéance</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="invoice in customer.invoices" :key="invoice.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ invoice.invoice_number }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ invoice.invoice_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ invoice.due_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(invoice.total) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="invoiceStatusClasses[invoice.status]" class="px-2 py-1 text-xs rounded-full">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="customer.invoices.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        Aucune facture
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Informations Tab -->
                <div v-if="activeTab === 'informations'" class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de facturation</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div v-if="customer.type === 'company'">
                            <label class="block text-sm font-medium text-gray-700">Numéro SIRET</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.siret || '-' }}</p>
                        </div>
                        <div v-if="customer.type === 'company'">
                            <label class="block text-sm font-medium text-gray-700">N° TVA</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.vat_number || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mode de paiement préféré</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.preferred_payment_method || '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">IBAN</label>
                            <p class="mt-1 text-sm text-gray-900">{{ customer.iban || '-' }}</p>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-4 mt-8">Notes</h3>
                    <p class="text-sm text-gray-600">{{ customer.notes || 'Aucune note' }}</p>
                </div>

                <!-- Fichiers Tab -->
                <div v-if="activeTab === 'fichiers'" class="bg-white rounded-lg shadow p-6">
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun fichier</h3>
                        <p class="mt-1 text-sm text-gray-500">Commencez par télécharger un fichier</p>
                        <div class="mt-6">
                            <button class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Télécharger un fichier
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    customer: Object,
    stats: Object,
})

const activeTab = ref('coordonnees')

const tabs = [
    { id: 'coordonnees', name: 'Coordonnées', icon: 'UserIcon' },
    { id: 'contrats', name: 'Contrats', icon: 'DocumentIcon' },
    { id: 'factures', name: 'Factures', icon: 'ReceiptIcon' },
    { id: 'informations', name: 'Informations', icon: 'InfoIcon' },
    { id: 'fichiers', name: 'Fichiers', icon: 'FolderIcon' },
]

const customerName = computed(() => {
    if (props.customer.type === 'company' && props.customer.company_name) {
        return props.customer.company_name
    }
    return `${props.customer.first_name} ${props.customer.last_name}`
})

const statusClasses = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    prospect: 'bg-blue-100 text-blue-800',
}

const contractStatusClasses = {
    active: 'bg-green-100 text-green-800',
    pending_signature: 'bg-yellow-100 text-yellow-800',
    expired: 'bg-gray-100 text-gray-800',
    terminated: 'bg-red-100 text-red-800',
    draft: 'bg-blue-100 text-blue-800',
}

const invoiceStatusClasses = {
    draft: 'bg-gray-100 text-gray-800',
    sent: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800',
    cancelled: 'bg-gray-100 text-gray-800',
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

// Simple icon components
const UserIcon = {
    template: `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
    `
}

const DocumentIcon = {
    template: `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    `
}

const ReceiptIcon = {
    template: `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
        </svg>
    `
}

const InfoIcon = {
    template: `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    `
}

const FolderIcon = {
    template: `
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
        </svg>
    `
}
</script>
