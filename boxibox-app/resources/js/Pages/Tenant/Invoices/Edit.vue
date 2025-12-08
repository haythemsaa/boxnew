<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    DocumentTextIcon,
    UserIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    PlusIcon,
    TrashIcon,
    CheckIcon,
    DocumentDuplicateIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: Object,
    customers: Array,
    contracts: Array,
})

const currentStep = ref(1)
const stepErrors = ref({})
const steps = [
    { number: 1, title: 'Informations', icon: DocumentTextIcon },
    { number: 2, title: 'Dates', icon: CalendarDaysIcon },
    { number: 3, title: 'Lignes', icon: DocumentDuplicateIcon },
    { number: 4, title: 'Totaux', icon: CurrencyEuroIcon },
]

const form = useForm({
    customer_id: props.invoice.customer_id,
    contract_id: props.invoice.contract_id,
    invoice_number: props.invoice.invoice_number,
    type: props.invoice.type,
    status: props.invoice.status,
    invoice_date: props.invoice.invoice_date,
    due_date: props.invoice.due_date,
    paid_at: props.invoice.paid_at,
    period_start: props.invoice.period_start,
    period_end: props.invoice.period_end,
    items: props.invoice.items || [{ description: '', quantity: 1, unit_price: 0, total: 0 }],
    subtotal: props.invoice.subtotal,
    tax_rate: props.invoice.tax_rate,
    tax_amount: props.invoice.tax_amount,
    discount_amount: props.invoice.discount_amount || 0,
    total: props.invoice.total,
    paid_amount: props.invoice.paid_amount || 0,
    currency: props.invoice.currency,
    notes: props.invoice.notes,
    is_recurring: props.invoice.is_recurring,
})

const filteredContracts = computed(() => {
    if (!form.customer_id) return props.contracts
    return props.contracts.filter((contract) => contract.customer_id == form.customer_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
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

watch(() => form.tax_rate, calculateTotals)
watch(() => form.discount_amount, calculateTotals)

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: form.currency || 'EUR',
    }).format(amount || 0)
}

const validateStep = (step) => {
    const errors = {}

    switch (step) {
        case 1: // Informations
            if (!form.customer_id) errors.customer_id = 'Veuillez sélectionner un client'
            break
        case 2: // Dates
            if (!form.invoice_date) errors.invoice_date = 'La date de facturation est obligatoire'
            if (!form.due_date) errors.due_date = 'La date d\'échéance est obligatoire'
            if (form.invoice_date && form.due_date && new Date(form.due_date) < new Date(form.invoice_date)) {
                errors.due_date = 'La date d\'échéance doit être après la date de facturation'
            }
            break
        case 3: // Lignes
            if (form.items.length === 0) {
                errors.items = 'Veuillez ajouter au moins un article'
            } else {
                form.items.forEach((item, index) => {
                    if (!item.description) errors[`item_${index}_description`] = 'La description est obligatoire'
                    if (!item.quantity || item.quantity <= 0) errors[`item_${index}_quantity`] = 'Quantité invalide'
                })
            }
            break
        case 4: // Totaux
            break
    }

    return errors
}

const nextStep = () => {
    const errors = validateStep(currentStep.value)
    stepErrors.value = errors

    if (Object.keys(errors).length > 0) {
        setTimeout(() => {
            const firstErrorField = document.querySelector('.field-error')
            if (firstErrorField) {
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        }, 100)
        return
    }

    if (currentStep.value < steps.length) {
        currentStep.value++
        stepErrors.value = {}
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
        stepErrors.value = {}
    }
}

const typeOptions = [
    { value: 'invoice', label: 'Facture', description: 'Facture standard' },
    { value: 'credit_note', label: 'Avoir', description: 'Note de crédit' },
    { value: 'proforma', label: 'Proforma', description: 'Facture provisoire' },
]

const statusOptions = [
    { value: 'draft', label: 'Brouillon', color: 'bg-gray-100 text-gray-700' },
    { value: 'sent', label: 'Envoyée', color: 'bg-blue-100 text-blue-700' },
    { value: 'paid', label: 'Payée', color: 'bg-green-100 text-green-700' },
    { value: 'partial', label: 'Partiel', color: 'bg-amber-100 text-amber-700' },
    { value: 'overdue', label: 'En retard', color: 'bg-red-100 text-red-700' },
    { value: 'cancelled', label: 'Annulée', color: 'bg-gray-100 text-gray-500' },
]

const submit = () => {
    form.put(route('tenant.invoices.update', props.invoice.id))
}
</script>

<template>
    <TenantLayout title="Modifier la facture">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.invoices.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Retour aux factures
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                            <DocumentTextIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Modifier la facture</h1>
                            <p class="mt-1 text-gray-600">{{ invoice.invoice_number }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <template v-for="(step, index) in steps" :key="step.number">
                            <div class="flex items-center">
                                <div
                                    :class="[
                                        'flex items-center justify-center w-10 h-10 rounded-xl transition-all duration-300',
                                        currentStep >= step.number
                                            ? 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg'
                                            : 'bg-white text-gray-400 border-2 border-gray-200'
                                    ]"
                                >
                                    <component :is="step.icon" class="w-5 h-5" />
                                </div>
                                <span
                                    :class="[
                                        'ml-3 text-sm font-medium hidden sm:block',
                                        currentStep >= step.number ? 'text-gray-900' : 'text-gray-400'
                                    ]"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                :class="[
                                    'flex-1 h-1 mx-4 rounded-full transition-all duration-300',
                                    currentStep > step.number ? 'bg-gradient-to-r from-blue-500 to-indigo-600' : 'bg-gray-200'
                                ]"
                            ></div>
                        </template>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Informations de base -->
                    <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                                Informations générales
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Client <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.customer_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un client</option>
                                        <option
                                            v-for="customer in customers"
                                            :key="customer.id"
                                            :value="customer.id"
                                        >
                                            {{ getCustomerName(customer) }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.customer_id || stepErrors.customer_id" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.customer_id || stepErrors.customer_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Contrat lié (Optionnel)
                                    </label>
                                    <select
                                        v-model="form.contract_id"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    >
                                        <option value="">Aucun contrat</option>
                                        <option
                                            v-for="contract in filteredContracts"
                                            :key="contract.id"
                                            :value="contract.id"
                                        >
                                            {{ contract.contract_number }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        N° de facture
                                    </label>
                                    <input
                                        v-model="form.invoice_number"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Devise <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.currency"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    >
                                        <option value="EUR">EUR (€)</option>
                                        <option value="USD">USD ($)</option>
                                        <option value="GBP">GBP (£)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Type de facture -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Type de document <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <label
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.type"
                                            :value="option.value"
                                            class="peer sr-only"
                                        />
                                        <div class="p-4 rounded-xl border-2 border-gray-200 bg-white hover:border-blue-300 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition-all">
                                            <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                            <p class="text-sm text-gray-500">{{ option.description }}</p>
                                        </div>
                                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                                            <CheckIcon class="w-5 h-5 text-blue-600" />
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Statut -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Statut <span class="text-red-500">*</span>
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <label
                                        v-for="option in statusOptions"
                                        :key="option.value"
                                        class="relative cursor-pointer"
                                    >
                                        <input
                                            type="radio"
                                            v-model="form.status"
                                            :value="option.value"
                                            class="peer sr-only"
                                        />
                                        <div :class="[
                                            'px-4 py-2 rounded-full border-2 font-medium text-sm transition-all',
                                            form.status === option.value
                                                ? option.color + ' border-current'
                                                : 'border-gray-200 text-gray-600 hover:border-gray-300'
                                        ]">
                                            {{ option.label }}
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Dates -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CalendarDaysIcon class="w-5 h-5 text-blue-600" />
                                Dates
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de facturation <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.invoice_date"
                                        name="invoice_date"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                        :class="{ 'border-red-300 bg-red-50': stepErrors.invoice_date }"
                                    />
                                    <p v-if="stepErrors.invoice_date" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ stepErrors.invoice_date }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date d'échéance <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.due_date"
                                        name="due_date"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                        :class="{ 'border-red-300 bg-red-50': stepErrors.due_date }"
                                    />
                                    <p v-if="stepErrors.due_date" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ stepErrors.due_date }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Début de période
                                    </label>
                                    <input
                                        v-model="form.period_start"
                                        name="period_start"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fin de période
                                    </label>
                                    <input
                                        v-model="form.period_end"
                                        name="period_end"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>

                                <div v-if="form.status === 'paid'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de paiement
                                    </label>
                                    <input
                                        v-model="form.paid_at"
                                        name="paid_at"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Lignes de facturation -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <DocumentDuplicateIcon class="w-5 h-5 text-blue-600" />
                                    Lignes de facturation
                                </h3>
                                <button
                                    type="button"
                                    @click="addItem"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-medium hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg"
                                >
                                    <PlusIcon class="w-4 h-4" />
                                    Ajouter une ligne
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(item, index) in form.items"
                                    :key="index"
                                    class="grid grid-cols-12 gap-4 items-end p-4 bg-gray-50 rounded-xl border border-gray-100"
                                >
                                    <div class="col-span-12 sm:col-span-5">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            Description <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="item.description"
                                            type="text"
                                            placeholder="Description du produit/service"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
                                            :class="{ 'border-red-300 bg-red-50': stepErrors[`item_${index}_description`] }"
                                        />
                                        <p v-if="stepErrors[`item_${index}_description`]" class="mt-1 text-xs text-red-600 field-error flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            {{ stepErrors[`item_${index}_description`] }}
                                        </p>
                                    </div>

                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            Quantité <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            @input="calculateItemTotal(index)"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
                                        />
                                    </div>

                                    <div class="col-span-4 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                            Prix unit. <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model.number="item.unit_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            @input="calculateItemTotal(index)"
                                            class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-colors"
                                        />
                                    </div>

                                    <div class="col-span-3 sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Total</label>
                                        <input
                                            :value="formatCurrency(item.total)"
                                            type="text"
                                            readonly
                                            class="block w-full rounded-xl border-gray-200 bg-gray-100 text-sm font-semibold text-gray-700"
                                        />
                                    </div>

                                    <div class="col-span-1 flex justify-center">
                                        <button
                                            v-if="form.items.length > 1"
                                            type="button"
                                            @click="removeItem(index)"
                                            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Totaux et notes -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CurrencyEuroIcon class="w-5 h-5 text-blue-600" />
                                Totaux
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Taux de TVA (%) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.tax_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Montant de la remise ({{ form.currency }})
                                    </label>
                                    <input
                                        v-model.number="form.discount_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    />
                                </div>

                                <!-- Récapitulatif -->
                                <div class="sm:col-span-2">
                                    <div class="bg-gradient-to-r from-gray-50 to-blue-50 rounded-xl p-6 border border-gray-100">
                                        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Récapitulatif</h4>
                                        <dl class="space-y-3">
                                            <div class="flex justify-between text-sm">
                                                <dt class="text-gray-600">Sous-total HT</dt>
                                                <dd class="font-medium text-gray-900">{{ formatCurrency(form.subtotal) }}</dd>
                                            </div>
                                            <div class="flex justify-between text-sm">
                                                <dt class="text-gray-600">TVA ({{ form.tax_rate }}%)</dt>
                                                <dd class="font-medium text-gray-900">{{ formatCurrency(form.tax_amount) }}</dd>
                                            </div>
                                            <div v-if="form.discount_amount > 0" class="flex justify-between text-sm">
                                                <dt class="text-gray-600">Remise</dt>
                                                <dd class="font-medium text-red-600">-{{ formatCurrency(form.discount_amount) }}</dd>
                                            </div>
                                            <div class="flex justify-between text-lg font-bold border-t border-gray-200 pt-3">
                                                <dt class="text-gray-900">Total TTC</dt>
                                                <dd class="text-blue-600">{{ formatCurrency(form.total) }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-blue-600" />
                                Informations complémentaires
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="4"
                                        maxlength="2000"
                                        placeholder="Notes internes ou mentions à afficher sur la facture..."
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-colors"
                                    ></textarea>
                                </div>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.is_recurring"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">Facture récurrente</span>
                                        <p class="text-xs text-gray-500">Cette facture sera générée automatiquement</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
                        <div>
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="px-6 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                            >
                                Précédent
                            </button>
                            <Link
                                v-else
                                :href="route('tenant.invoices.index')"
                                class="px-6 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-50 transition-colors inline-block"
                            >
                                Annuler
                            </Link>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                v-if="currentStep < steps.length"
                                type="button"
                                @click="nextStep"
                                class="px-6 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-xl font-medium hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg"
                            >
                                Suivant
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mise à jour...
                                </span>
                                <span v-else>Mettre à jour la facture</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
