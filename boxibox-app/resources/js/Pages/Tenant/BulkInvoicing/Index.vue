<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    contracts: Array,
    stats: Object,
    currentPeriod: Object,
})

const selectedContracts = ref([])
const showPreviewModal = ref(false)
const previewData = ref(null)
const isLoading = ref(false)

const form = useForm({
    contract_ids: [],
    period_start: props.currentPeriod.start,
    period_end: props.currentPeriod.end,
    invoice_date: new Date().toISOString().split('T')[0],
    due_days: 30,
    send_email: false,
})

const allSelected = computed(() => {
    return props.contracts.length > 0 && selectedContracts.value.length === props.contracts.length
})

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedContracts.value = []
    } else {
        selectedContracts.value = props.contracts.map(c => c.id)
    }
}

const selectedTotal = computed(() => {
    return props.contracts
        .filter(c => selectedContracts.value.includes(c.id))
        .reduce((sum, c) => sum + (c.monthly_price || 0), 0)
})

const previewInvoices = async () => {
    if (selectedContracts.value.length === 0) {
        alert('Veuillez sélectionner au moins un contrat.')
        return
    }

    isLoading.value = true
    form.contract_ids = selectedContracts.value

    try {
        const response = await fetch(route('tenant.bulk-invoicing.preview'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                contract_ids: selectedContracts.value,
                period_start: form.period_start,
                period_end: form.period_end,
                invoice_date: form.invoice_date,
                due_days: form.due_days,
            }),
        })

        if (response.ok) {
            previewData.value = await response.json()
            showPreviewModal.value = true
        }
    } catch (error) {
        console.error('Preview error:', error)
    } finally {
        isLoading.value = false
    }
}

const generateInvoices = () => {
    form.contract_ids = selectedContracts.value
    form.post(route('tenant.bulk-invoicing.generate'), {
        onSuccess: () => {
            showPreviewModal.value = false
            selectedContracts.value = []
        },
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <AuthenticatedLayout title="Facturation en masse">
        <!-- Success/Error Messages -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Facturation en masse</h2>
                <p class="mt-1 text-sm text-gray-500">Générez des factures pour plusieurs contrats en une seule opération</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 md:grid-cols-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ stats.total_active_contracts }}</p>
                <p class="text-sm text-gray-500">Contrats actifs</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-primary-500">
                <p class="text-2xl font-bold text-primary-600">{{ stats.contracts_to_invoice }}</p>
                <p class="text-sm text-gray-500">À facturer</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-green-500">
                <p class="text-2xl font-bold text-green-600">{{ stats.already_invoiced }}</p>
                <p class="text-sm text-gray-500">Déjà facturés</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-indigo-500">
                <p class="text-lg font-bold text-indigo-600">{{ formatCurrency(stats.total_monthly_revenue) }}</p>
                <p class="text-sm text-gray-500">Revenu mensuel</p>
            </div>
        </div>

        <!-- Period & Settings -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Paramètres de facturation</h3>
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période début</label>
                    <input
                        type="date"
                        v-model="form.period_start"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période fin</label>
                    <input
                        type="date"
                        v-model="form.period_end"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date de facture</label>
                    <input
                        type="date"
                        v-model="form.invoice_date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Délai de paiement</label>
                    <select
                        v-model="form.due_days"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                        <option :value="0">Immédiat</option>
                        <option :value="15">15 jours</option>
                        <option :value="30">30 jours</option>
                        <option :value="45">45 jours</option>
                        <option :value="60">60 jours</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Selection Summary -->
        <div v-if="selectedContracts.length > 0" class="bg-primary-50 rounded-lg p-4 mb-6 flex justify-between items-center">
            <div>
                <p class="font-medium text-primary-900">
                    {{ selectedContracts.length }} contrat(s) sélectionné(s)
                </p>
                <p class="text-sm text-primary-700">
                    Montant total: {{ formatCurrency(selectedTotal) }}
                </p>
            </div>
            <button
                @click="previewInvoices"
                :disabled="isLoading"
                class="bg-primary-600 hover:bg-primary-700 text-white px-6 py-2 rounded-lg font-medium transition-colors disabled:opacity-50"
            >
                {{ isLoading ? 'Chargement...' : 'Aperçu des factures' }}
            </button>
        </div>

        <!-- Contracts Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contrat
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Box
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Site
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Loyer mensuel
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="contracts.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Tous les contrats sont à jour</p>
                                    <p class="text-sm text-gray-500">Aucune facture à générer pour la période actuelle</p>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="contract in contracts" :key="contract.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    :value="contract.id"
                                    v-model="selectedContracts"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ contract.contract_number }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ getCustomerName(contract.customer) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ contract.customer?.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ contract.box?.code || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ contract.site?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ formatCurrency(contract.monthly_price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showPreviewModal = false"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Aperçu des factures à générer
                        </h3>

                        <div v-if="previewData" class="space-y-4">
                            <!-- Summary -->
                            <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-4 gap-4">
                                <div class="text-center">
                                    <p class="text-2xl font-bold text-gray-900">{{ previewData.summary.count }}</p>
                                    <p class="text-sm text-gray-500">Factures</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(previewData.summary.total_ht) }}</p>
                                    <p class="text-sm text-gray-500">Total HT</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(previewData.summary.total_tax) }}</p>
                                    <p class="text-sm text-gray-500">TVA</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-lg font-bold text-primary-600">{{ formatCurrency(previewData.summary.total_ttc) }}</p>
                                    <p class="text-sm text-gray-500">Total TTC</p>
                                </div>
                            </div>

                            <!-- Invoice List -->
                            <div class="max-h-60 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Contrat</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Client</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Box</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Total TTC</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <tr v-for="invoice in previewData.invoices" :key="invoice.contract_id">
                                            <td class="px-4 py-2 text-sm">{{ invoice.contract_number }}</td>
                                            <td class="px-4 py-2 text-sm">{{ invoice.customer_name }}</td>
                                            <td class="px-4 py-2 text-sm">{{ invoice.box_code }}</td>
                                            <td class="px-4 py-2 text-sm text-right font-medium">{{ formatCurrency(invoice.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Options -->
                            <div class="border-t pt-4">
                                <label class="flex items-center">
                                    <input
                                        type="checkbox"
                                        v-model="form.send_email"
                                        class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Envoyer les factures par email aux clients</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="generateInvoices"
                            :disabled="form.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            {{ form.processing ? 'Génération...' : 'Générer les factures' }}
                        </button>
                        <button
                            @click="showPreviewModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
