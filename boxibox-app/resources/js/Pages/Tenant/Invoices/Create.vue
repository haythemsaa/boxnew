<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    customers: Array,
    contracts: Array,
})

const currentStep = ref(1)
const totalSteps = 4

const form = useForm({
    customer_id: '',
    contract_id: '',
    invoice_number: '',
    type: 'invoice',
    status: 'draft',
    invoice_date: new Date().toISOString().split('T')[0],
    due_date: '',
    paid_at: '',
    period_start: '',
    period_end: '',
    items: [
        { description: '', quantity: 1, unit_price: 0, total: 0 },
    ],
    subtotal: 0,
    tax_rate: 20,
    tax_amount: 0,
    discount_amount: 0,
    total: 0,
    paid_amount: 0,
    currency: 'EUR',
    notes: '',
    is_recurring: false,
})

const steps = [
    { number: 1, title: 'Client', description: 'Sélection du client' },
    { number: 2, title: 'Dates', description: 'Périodes et échéances' },
    { number: 3, title: 'Articles', description: 'Lignes de facture' },
    { number: 4, title: 'Validation', description: 'Récapitulatif' },
]

const filteredContracts = computed(() => {
    if (!form.customer_id) return props.contracts
    return props.contracts.filter((contract) => contract.customer_id == form.customer_id)
})

const selectedCustomer = computed(() => {
    if (!form.customer_id) return null
    return props.customers.find((c) => c.id == form.customer_id)
})

const getCustomerName = (customer) => {
    if (!customer) return ''
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getCustomerInitials = (customer) => {
    if (!customer) return '?'
    if (customer.type === 'company') {
        return customer.company_name?.substring(0, 2).toUpperCase() || 'EN'
    }
    return `${customer.first_name?.charAt(0) || ''}${customer.last_name?.charAt(0) || ''}`.toUpperCase()
}

const addItem = () => {
    form.items.push({ description: '', quantity: 1, unit_price: 0, total: 0 })
}

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
        calculateTotals()
    }
}

const calculateItemTotal = (index) => {
    const item = form.items[index]
    item.total = (item.quantity * item.unit_price).toFixed(2)
    calculateTotals()
}

const calculateTotals = () => {
    form.subtotal = form.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
    form.tax_amount = (form.subtotal * (form.tax_rate / 100)).toFixed(2)
    form.total = (
        parseFloat(form.subtotal) +
        parseFloat(form.tax_amount) -
        parseFloat(form.discount_amount || 0)
    ).toFixed(2)
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: form.currency || 'EUR',
    }).format(amount || 0)
}

watch(() => form.tax_rate, calculateTotals)
watch(() => form.discount_amount, calculateTotals)

watch(() => form.contract_id, (contractId) => {
    if (contractId) {
        const contract = props.contracts.find((c) => c.id == contractId)
        if (contract) {
            if (form.items.length === 1 && !form.items[0].description) {
                form.items[0].description = `Location box - ${contract.box?.code || 'Box'}`
                form.items[0].quantity = 1
                form.items[0].unit_price = contract.monthly_price
                calculateItemTotal(0)
            }
        }
    }
})

// Auto-set due date 30 days after invoice date
watch(() => form.invoice_date, (date) => {
    if (date && !form.due_date) {
        const invoiceDate = new Date(date)
        invoiceDate.setDate(invoiceDate.getDate() + 30)
        form.due_date = invoiceDate.toISOString().split('T')[0]
    }
})

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.customer_id
        case 2:
            return form.invoice_date && form.due_date
        case 3:
            return form.items.length > 0 && form.items.every(item => item.description && item.quantity > 0)
        case 4:
            return true
        default:
            return false
    }
})

const nextStep = () => {
    if (currentStep.value < totalSteps && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const goToStep = (step) => {
    if (step <= currentStep.value || canProceed.value) {
        currentStep.value = step
    }
}

const submit = () => {
    form.post(route('tenant.invoices.store'))
}

const typeOptions = [
    { value: 'invoice', label: 'Facture', icon: '📄', description: 'Facture classique' },
    { value: 'credit_note', label: 'Avoir', icon: '↩️', description: 'Note de crédit' },
    { value: 'proforma', label: 'Proforma', icon: '📋', description: 'Facture provisoire' },
]

const statusOptions = [
    { value: 'draft', label: 'Brouillon', color: 'gray' },
    { value: 'sent', label: 'Envoyée', color: 'blue' },
    { value: 'paid', label: 'Payée', color: 'emerald' },
]
</script>

<template>
    <TenantLayout title="Nouvelle Facture">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50/30">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="mb-8 animate-fade-in-up">
                    <Link
                        :href="route('tenant.invoices.index')"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-blue-600 transition-colors mb-4"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux factures
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouvelle Facture</h1>
                            <p class="text-gray-500 mt-1">Créez une nouvelle facture client</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.1s">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <template v-for="(step, index) in steps" :key="step.number">
                                <button
                                    @click="goToStep(step.number)"
                                    :class="[
                                        'flex flex-col items-center transition-all duration-200',
                                        step.number <= currentStep ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg transition-all duration-300',
                                            currentStep === step.number
                                                ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/25'
                                                : currentStep > step.number
                                                    ? 'bg-emerald-500 text-white'
                                                    : 'bg-gray-100 text-gray-400'
                                        ]"
                                    >
                                        <svg v-if="currentStep > step.number" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span v-else>{{ step.number }}</span>
                                    </div>
                                    <span
                                        :class="[
                                            'mt-2 text-sm font-medium hidden sm:block',
                                            currentStep === step.number ? 'text-blue-600' : 'text-gray-500'
                                        ]"
                                    >
                                        {{ step.title }}
                                    </span>
                                </button>
                                <div
                                    v-if="index < steps.length - 1"
                                    :class="[
                                        'flex-1 h-1 mx-4 rounded-full transition-all duration-300',
                                        currentStep > step.number ? 'bg-emerald-500' : 'bg-gray-200'
                                    ]"
                                />
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Form Content -->
                <form @submit.prevent="submit">
                    <!-- Step 1: Client -->
                    <div v-show="currentStep === 1" class="space-y-6 animate-fade-in-up">
                        <!-- Invoice Type -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Type de document</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <button
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    type="button"
                                    @click="form.type = option.value"
                                    :class="[
                                        'p-5 rounded-xl border-2 text-left transition-all duration-200',
                                        form.type === option.value
                                            ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20'
                                            : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <span class="text-2xl mb-2 block">{{ option.icon }}</span>
                                    <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                    <p class="text-sm text-gray-500">{{ option.description }}</p>
                                </button>
                            </div>
                        </div>

                        <!-- Customer Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                Sélection du client <span class="text-red-500">*</span>
                            </h3>
                            <select
                                v-model="form.customer_id"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white mb-4"
                            >
                                <option value="">Sélectionner un client...</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ getCustomerName(customer) }} - {{ customer.email }}
                                </option>
                            </select>
                            <div v-if="selectedCustomer" class="flex items-center gap-4 p-4 bg-blue-50 rounded-xl">
                                <div
                                    class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                    :class="selectedCustomer.type === 'company' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'"
                                >
                                    {{ getCustomerInitials(selectedCustomer) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ getCustomerName(selectedCustomer) }}</p>
                                    <p class="text-sm text-gray-500">{{ selectedCustomer.email }}</p>
                                </div>
                            </div>
                            <div v-if="form.errors.customer_id" class="mt-2 text-sm text-red-600">{{ form.errors.customer_id }}</div>
                        </div>

                        <!-- Contract (Optional) -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                Contrat associé (optionnel)
                            </h3>
                            <select
                                v-model="form.contract_id"
                                :disabled="!form.customer_id"
                                class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <option value="">Aucun contrat</option>
                                <option v-for="contract in filteredContracts" :key="contract.id" :value="contract.id">
                                    {{ contract.contract_number }} - {{ contract.box?.code }}
                                </option>
                            </select>
                            <p class="mt-2 text-sm text-gray-500">
                                Sélectionner un contrat pour pré-remplir automatiquement les articles
                            </p>
                        </div>
                    </div>

                    <!-- Step 2: Dates -->
                    <div v-show="currentStep === 2" class="space-y-6 animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Dates de la facture</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de facturation <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.invoice_date"
                                        type="date"
                                        required
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date d'échéance <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.due_date"
                                        type="date"
                                        required
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Période de facturation (optionnel)</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Début de période</label>
                                    <input
                                        v-model="form.period_start"
                                        type="date"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fin de période</label>
                                    <input
                                        v-model="form.period_end"
                                        type="date"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statut de la facture</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <button
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    type="button"
                                    @click="form.status = option.value"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-center transition-all duration-200',
                                        form.status === option.value
                                            ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500/20'
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Articles -->
                    <div v-show="currentStep === 3" class="space-y-6 animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-lg font-semibold text-gray-900">Articles de la facture</h3>
                                <button
                                    type="button"
                                    @click="addItem"
                                    class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-sm"
                                >
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Ajouter un article
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="p-4 bg-gray-50 rounded-xl border border-gray-200"
                                >
                                    <div class="grid grid-cols-12 gap-4 items-end">
                                        <div class="col-span-12 sm:col-span-5">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Description <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                v-model="item.description"
                                                type="text"
                                                required
                                                placeholder="Description de l'article"
                                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                        </div>

                                        <div class="col-span-4 sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Quantité</label>
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                step="1"
                                                min="1"
                                                required
                                                @input="calculateItemTotal(index)"
                                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            />
                                        </div>

                                        <div class="col-span-4 sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Prix unitaire</label>
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                                                <input
                                                    v-model.number="item.unit_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    required
                                                    @input="calculateItemTotal(index)"
                                                    class="block w-full pl-8 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div class="col-span-3 sm:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                                            <div class="px-4 py-2.5 bg-blue-50 border border-blue-200 rounded-xl text-sm font-semibold text-blue-700">
                                                {{ formatCurrency(item.total) }}
                                            </div>
                                        </div>

                                        <div class="col-span-1 flex justify-center">
                                            <button
                                                v-if="form.items.length > 1"
                                                type="button"
                                                @click="removeItem(index)"
                                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Calcul des totaux</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Taux de TVA (%)</label>
                                    <input
                                        v-model.number="form.tax_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remise (€)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                        <input
                                            v-model.number="form.discount_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-gray-50 rounded-xl space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Sous-total HT</span>
                                    <span class="font-medium text-gray-900">{{ formatCurrency(form.subtotal) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">TVA ({{ form.tax_rate }}%)</span>
                                    <span class="font-medium text-gray-900">{{ formatCurrency(form.tax_amount) }}</span>
                                </div>
                                <div v-if="form.discount_amount > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-600">Remise</span>
                                    <span class="font-medium text-red-600">-{{ formatCurrency(form.discount_amount) }}</span>
                                </div>
                                <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                    <span class="text-gray-900">Total TTC</span>
                                    <span class="text-blue-600">{{ formatCurrency(form.total) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes et options</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes (visible sur la facture)</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Conditions particulières, mentions légales..."
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm"
                                    ></textarea>
                                </div>
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input v-model="form.is_recurring" type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                    <div>
                                        <p class="font-medium text-gray-900">Facture récurrente</p>
                                        <p class="text-sm text-gray-500">Générer automatiquement chaque mois</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Summary -->
                    <div v-show="currentStep === 4" class="space-y-6 animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Récapitulatif de la facture</h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Client Info -->
                                <div class="p-4 bg-blue-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-blue-700 mb-3 uppercase tracking-wider">Client</h4>
                                    <div v-if="selectedCustomer" class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                            :class="selectedCustomer.type === 'company' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'"
                                        >
                                            {{ getCustomerInitials(selectedCustomer) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ getCustomerName(selectedCustomer) }}</p>
                                            <p class="text-sm text-gray-500">{{ selectedCustomer.email }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Type & Status -->
                                <div class="p-4 bg-purple-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-purple-700 mb-3 uppercase tracking-wider">Type & Statut</h4>
                                    <p class="font-semibold text-gray-900">{{ typeOptions.find(t => t.value === form.type)?.label }}</p>
                                    <p class="text-sm text-gray-500">Statut : {{ statusOptions.find(s => s.value === form.status)?.label }}</p>
                                </div>

                                <!-- Dates -->
                                <div class="p-4 bg-amber-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Dates</h4>
                                    <p class="text-sm text-gray-900">Facturation : {{ form.invoice_date }}</p>
                                    <p class="text-sm text-gray-500">Échéance : {{ form.due_date }}</p>
                                </div>

                                <!-- Total -->
                                <div class="p-4 bg-emerald-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-emerald-700 mb-3 uppercase tracking-wider">Montant</h4>
                                    <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(form.total) }}</p>
                                    <p class="text-sm text-gray-500">{{ form.items.length }} article(s)</p>
                                </div>
                            </div>

                            <!-- Items Summary -->
                            <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Articles</h4>
                                <div class="space-y-2">
                                    <div v-for="(item, index) in form.items" :key="index" class="flex justify-between text-sm">
                                        <span class="text-gray-600">{{ item.description }} x {{ item.quantity }}</span>
                                        <span class="font-medium text-gray-900">{{ formatCurrency(item.total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                        <button
                            v-if="currentStep > 1"
                            type="button"
                            @click="prevStep"
                            class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Précédent
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-3">
                            <Link
                                :href="route('tenant.invoices.index')"
                                class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Annuler
                            </Link>
                            <button
                                v-if="currentStep < totalSteps"
                                type="button"
                                @click="nextStep"
                                :disabled="!canProceed"
                                class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg shadow-blue-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Suivant
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center px-8 py-3 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 transition-all duration-200 shadow-lg shadow-emerald-500/25 disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Création...' : 'Créer la facture' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
