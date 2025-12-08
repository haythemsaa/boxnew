<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import axios from 'axios'
import {
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    CurrencyEuroIcon,
    DocumentDuplicateIcon,
    ClockIcon,
    CalendarDaysIcon,
    EnvelopeIcon,
    XMarkIcon,
    EyeIcon,
    CheckIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

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
    period_start: props.currentPeriod?.start || new Date().toISOString().split('T')[0],
    period_end: props.currentPeriod?.end || new Date().toISOString().split('T')[0],
    invoice_date: new Date().toISOString().split('T')[0],
    due_days: 30,
    send_email: false,
})

const allSelected = computed(() => {
    return props.contracts?.length > 0 && selectedContracts.value.length === props.contracts.length
})

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedContracts.value = []
    } else {
        selectedContracts.value = props.contracts?.map(c => c.id) || []
    }
}

const selectedTotal = computed(() => {
    return (props.contracts || [])
        .filter(c => selectedContracts.value.includes(c.id))
        .reduce((sum, c) => sum + (parseFloat(c.monthly_price) || 0), 0)
})

const previewInvoices = async () => {
    if (selectedContracts.value.length === 0) {
        alert('Veuillez sélectionner au moins un contrat.')
        return
    }

    isLoading.value = true
    form.contract_ids = selectedContracts.value

    try {
        const response = await axios.post(route('tenant.bulk-invoicing.preview'), {
            contract_ids: selectedContracts.value,
            period_start: form.period_start,
            period_end: form.period_end,
            invoice_date: form.invoice_date,
            due_days: form.due_days,
        })

        previewData.value = response.data
        showPreviewModal.value = true
    } catch (error) {
        console.error('Preview error:', error)
        alert('Erreur lors de la prévisualisation. Veuillez réessayer.')
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
    <TenantLayout title="Facturation en masse">
        <!-- Page Header - NOA Style -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Facturation en masse</h1>
                <p class="text-sm text-gray-500 mt-1">Générez des factures pour plusieurs contrats</p>
            </div>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('tenant.invoices.index')"
                    class="btn-secondary"
                >
                    <DocumentTextIcon class="w-4 h-4 mr-2" />
                    Voir les factures
                </Link>
            </div>
        </div>

        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
            <CheckCircleIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
            <p class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
            <ExclamationTriangleIcon class="w-5 h-5 text-red-500 flex-shrink-0" />
            <p class="text-sm text-red-700">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Stats Cards - NOA Style -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="noa-stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="noa-stat-card-value">{{ stats?.total_active_contracts || 0 }}</p>
                        <p class="noa-stat-card-label">Contrats actifs</p>
                    </div>
                    <div class="noa-stat-card-icon noa-stat-card-icon-green">
                        <DocumentDuplicateIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="noa-stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="noa-stat-card-value">{{ stats?.contracts_to_invoice || 0 }}</p>
                        <p class="noa-stat-card-label">À facturer</p>
                    </div>
                    <div class="noa-stat-card-icon noa-stat-card-icon-orange">
                        <ClockIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="noa-stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="noa-stat-card-value">{{ stats?.already_invoiced || 0 }}</p>
                        <p class="noa-stat-card-label">Déjà facturés</p>
                    </div>
                    <div class="noa-stat-card-icon noa-stat-card-icon-cyan">
                        <CheckCircleIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <div class="noa-stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="noa-stat-card-value">{{ formatCurrency(stats?.total_monthly_revenue) }}</p>
                        <p class="noa-stat-card-label">Revenu mensuel</p>
                    </div>
                    <div class="noa-stat-card-icon noa-stat-card-icon-pink">
                        <CurrencyEuroIcon class="w-6 h-6" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Period & Settings Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                <CalendarDaysIcon class="w-5 h-5 text-primary-500" />
                Paramètres de facturation
            </h3>
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Période début</label>
                    <input
                        type="date"
                        v-model="form.period_start"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-400 focus:border-transparent transition-all bg-gray-50"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Période fin</label>
                    <input
                        type="date"
                        v-model="form.period_end"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-400 focus:border-transparent transition-all bg-gray-50"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Date de facture</label>
                    <input
                        type="date"
                        v-model="form.invoice_date"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-400 focus:border-transparent transition-all bg-gray-50"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Délai de paiement</label>
                    <select
                        v-model="form.due_days"
                        class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-400 focus:border-transparent transition-all bg-gray-50"
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
        <transition name="fade">
            <div v-if="selectedContracts.length > 0" class="bg-gradient-to-r from-primary-50 to-accent-50 rounded-xl p-4 mb-6 flex justify-between items-center border border-primary-200">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center shadow-sm">
                        <CheckIcon class="w-6 h-6 text-primary-500" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ selectedContracts.length }} contrat(s) sélectionné(s)
                        </p>
                        <p class="text-sm text-gray-600">
                            Montant total: <span class="font-semibold text-primary-600">{{ formatCurrency(selectedTotal) }}</span>
                        </p>
                    </div>
                </div>
                <button
                    @click="previewInvoices"
                    :disabled="isLoading"
                    class="btn-primary"
                >
                    <EyeIcon v-if="!isLoading" class="w-4 h-4 mr-2" />
                    <ArrowPathIcon v-else class="w-4 h-4 mr-2 animate-spin" />
                    {{ isLoading ? 'Chargement...' : 'Aperçu des factures' }}
                </button>
            </div>
        </transition>

        <!-- Contracts Table -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    @change="toggleSelectAll"
                                    class="h-4 w-4 text-primary-500 focus:ring-primary-400 border-gray-300 rounded cursor-pointer"
                                />
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Contrat
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Box
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Site
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Loyer mensuel
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <tr v-if="!contracts || contracts.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mb-4">
                                        <CheckCircleIcon class="w-8 h-8 text-green-500" />
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 mb-1">Tous les contrats sont à jour</p>
                                    <p class="text-sm text-gray-500">Aucune facture à générer pour la période actuelle</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="contract in contracts"
                            :key="contract.id"
                            :class="[
                                'transition-colors cursor-pointer',
                                selectedContracts.includes(contract.id)
                                    ? 'bg-primary-50/50'
                                    : 'hover:bg-gray-50'
                            ]"
                            @click="selectedContracts.includes(contract.id)
                                ? selectedContracts = selectedContracts.filter(id => id !== contract.id)
                                : selectedContracts.push(contract.id)"
                        >
                            <td class="px-6 py-4" @click.stop>
                                <input
                                    type="checkbox"
                                    :value="contract.id"
                                    v-model="selectedContracts"
                                    class="h-4 w-4 text-primary-500 focus:ring-primary-400 border-gray-300 rounded cursor-pointer"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-sm font-medium text-gray-800">
                                    {{ contract.contract_number }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ getCustomerName(contract.customer) }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ contract.customer?.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-xs font-medium text-blue-800">
                                    {{ contract.box?.code || '-' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ contract.site?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <span class="text-sm font-semibold text-gray-900">
                                    {{ formatCurrency(contract.monthly_price) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Preview Modal -->
        <teleport to="body">
            <transition name="fade">
                <div v-if="showPreviewModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showPreviewModal = false"></div>
                    <div class="relative min-h-screen flex items-center justify-center p-4">
                        <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden">
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <EyeIcon class="w-5 h-5 text-primary-500" />
                                    Aperçu des factures à générer
                                </h3>
                                <button @click="showPreviewModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100">
                                    <XMarkIcon class="w-5 h-5" />
                                </button>
                            </div>

                            <!-- Modal Body -->
                            <div class="p-6">
                                <!-- Loading state -->
                                <div v-if="!previewData" class="flex flex-col items-center justify-center py-12">
                                    <ArrowPathIcon class="w-8 h-8 text-gray-400 animate-spin mb-4" />
                                    <p class="text-gray-500">Chargement des données...</p>
                                </div>

                                <!-- Empty state -->
                                <div v-else-if="!previewData.invoices || previewData.invoices.length === 0" class="flex flex-col items-center justify-center py-12">
                                    <div class="w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center mb-4">
                                        <ExclamationTriangleIcon class="w-8 h-8 text-yellow-500" />
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 mb-2">Aucune facture à générer</p>
                                    <p class="text-sm text-gray-500 text-center">Les contrats sélectionnés n'ont pas de données valides pour la facturation.</p>
                                </div>

                                <!-- Preview data -->
                                <div v-else class="space-y-6">
                                    <!-- Summary Cards -->
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div class="bg-gray-50 rounded-xl p-4 text-center">
                                            <div class="flex items-center justify-center mb-2">
                                                <DocumentDuplicateIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <p class="text-2xl font-bold text-gray-900">{{ previewData.summary?.count || 0 }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Factures</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-xl p-4 text-center">
                                            <div class="flex items-center justify-center mb-2">
                                                <CurrencyEuroIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <p class="text-lg font-bold text-gray-900">{{ formatCurrency(previewData.summary?.total_ht) }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Total HT</p>
                                        </div>
                                        <div class="bg-gray-50 rounded-xl p-4 text-center">
                                            <div class="flex items-center justify-center mb-2">
                                                <DocumentTextIcon class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <p class="text-lg font-bold text-gray-900">{{ formatCurrency(previewData.summary?.total_tax) }}</p>
                                            <p class="text-xs text-gray-500 mt-1">TVA (20%)</p>
                                        </div>
                                        <div class="bg-gradient-to-r from-primary-500 to-accent-500 rounded-xl p-4 text-center">
                                            <div class="flex items-center justify-center mb-2">
                                                <CheckCircleIcon class="w-5 h-5 text-white/80" />
                                            </div>
                                            <p class="text-lg font-bold text-white">{{ formatCurrency(previewData.summary?.total_ttc) }}</p>
                                            <p class="text-xs text-white/80 mt-1">Total TTC</p>
                                        </div>
                                    </div>

                                    <!-- Period Info -->
                                    <div class="flex items-center gap-2 px-4 py-3 bg-blue-50 rounded-xl border border-blue-100">
                                        <CalendarDaysIcon class="w-5 h-5 text-blue-500 flex-shrink-0" />
                                        <span class="text-sm text-blue-700">
                                            Période de facturation : <strong>{{ form.period_start }}</strong> au <strong>{{ form.period_end }}</strong>
                                        </span>
                                    </div>

                                    <!-- Invoice List -->
                                    <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-200">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50 sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contrat</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Box / Site</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">HT</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">TVA</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">TTC</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                <tr v-for="(invoice, index) in previewData.invoices" :key="invoice.contract_id || index" class="hover:bg-gray-50 transition-colors">
                                                    <td class="px-4 py-3 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-gray-100 text-sm font-medium text-gray-800">
                                                            {{ invoice.contract_number || '-' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <div class="text-sm font-medium text-gray-900">{{ invoice.customer_name || '-' }}</div>
                                                        <div class="text-xs text-gray-500">{{ invoice.customer_email || '' }}</div>
                                                    </td>
                                                    <td class="px-4 py-3">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-100 text-xs font-medium text-blue-800">
                                                            {{ invoice.box_code || '-' }}
                                                        </span>
                                                        <div class="text-xs text-gray-500 mt-1">{{ invoice.site_name || '' }}</div>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ formatCurrency(invoice.subtotal) }}</td>
                                                    <td class="px-4 py-3 text-sm text-right text-gray-500">{{ formatCurrency(invoice.tax_amount) }}</td>
                                                    <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ formatCurrency(invoice.total) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Options -->
                                    <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                        <input
                                            type="checkbox"
                                            id="send_email"
                                            v-model="form.send_email"
                                            class="h-4 w-4 text-primary-500 focus:ring-primary-400 border-gray-300 rounded cursor-pointer"
                                        />
                                        <label for="send_email" class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <EnvelopeIcon class="w-4 h-4 text-gray-400" />
                                            Envoyer les factures par email aux clients
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex items-center justify-end gap-3 px-6 py-4 bg-gray-50 border-t border-gray-200">
                                <button
                                    @click="showPreviewModal = false"
                                    class="btn-secondary"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="generateInvoices"
                                    :disabled="form.processing"
                                    class="btn-primary"
                                >
                                    <ArrowPathIcon v-if="form.processing" class="w-4 h-4 mr-2 animate-spin" />
                                    <DocumentTextIcon v-else class="w-4 h-4 mr-2" />
                                    {{ form.processing ? 'Génération...' : 'Générer les factures' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>
    </TenantLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
