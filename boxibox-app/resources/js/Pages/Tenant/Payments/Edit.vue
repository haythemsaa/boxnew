<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    CurrencyEuroIcon,
    UserIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    CreditCardIcon,
    CheckIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    payment: Object,
    customers: Array,
    invoices: Array,
    contracts: Array,
})

const currentStep = ref(1)
const stepErrors = ref({})
const steps = [
    { number: 1, title: 'Client', icon: UserIcon },
    { number: 2, title: 'Montant', icon: CurrencyEuroIcon },
    { number: 3, title: 'Détails', icon: DocumentTextIcon },
]

const form = useForm({
    customer_id: props.payment.customer_id,
    invoice_id: props.payment.invoice_id,
    contract_id: props.payment.contract_id,
    payment_number: props.payment.payment_number,
    type: props.payment.type,
    status: props.payment.status,
    amount: props.payment.amount,
    fee: props.payment.fee || 0,
    currency: props.payment.currency,
    method: props.payment.method,
    gateway: props.payment.gateway,
    gateway_payment_id: props.payment.gateway_payment_id,
    card_brand: props.payment.card_brand,
    card_last_four: props.payment.card_last_four,
    paid_at: props.payment.paid_at,
    notes: props.payment.notes,
})

const filteredInvoices = computed(() => {
    if (!form.customer_id) return props.invoices
    return props.invoices.filter((invoice) => invoice.customer_id == form.customer_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const typeOptions = [
    { value: 'payment', label: 'Paiement', description: 'Règlement standard', color: 'bg-emerald-100 text-emerald-700' },
    { value: 'refund', label: 'Remboursement', description: 'Retour de fonds', color: 'bg-red-100 text-red-700' },
    { value: 'deposit', label: 'Caution', description: 'Dépôt de garantie', color: 'bg-blue-100 text-blue-700' },
]

const statusOptions = [
    { value: 'pending', label: 'En attente', color: 'bg-amber-100 text-amber-700' },
    { value: 'processing', label: 'En cours', color: 'bg-blue-100 text-blue-700' },
    { value: 'completed', label: 'Complété', color: 'bg-emerald-100 text-emerald-700' },
    { value: 'failed', label: 'Échoué', color: 'bg-red-100 text-red-700' },
    { value: 'refunded', label: 'Remboursé', color: 'bg-purple-100 text-purple-700' },
]

const methodOptions = [
    { value: 'card', label: 'Carte bancaire' },
    { value: 'bank_transfer', label: 'Virement' },
    { value: 'cash', label: 'Espèces' },
    { value: 'cheque', label: 'Chèque' },
    { value: 'sepa', label: 'Prélèvement SEPA' },
    { value: 'stripe', label: 'Stripe' },
    { value: 'paypal', label: 'PayPal' },
]

const gatewayOptions = [
    { value: 'stripe', label: 'Stripe' },
    { value: 'paypal', label: 'PayPal' },
    { value: 'sepa', label: 'SEPA' },
    { value: 'manual', label: 'Manuel' },
]

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: form.currency || 'EUR',
    }).format(amount || 0)
}

const validateStep = (step) => {
    stepErrors.value = {}

    if (step === 1) {
        if (!form.customer_id) {
            stepErrors.value.customer_id = 'Veuillez sélectionner un client'
        }
        if (!form.type) {
            stepErrors.value.type = 'Veuillez sélectionner un type de transaction'
        }
    }

    if (step === 2) {
        if (!form.amount || form.amount <= 0) {
            stepErrors.value.amount = 'Le montant doit être supérieur à 0'
        }
        if (!form.method) {
            stepErrors.value.method = 'Veuillez sélectionner une méthode de paiement'
        }
        if (!form.gateway) {
            stepErrors.value.gateway = 'Veuillez sélectionner une passerelle'
        }
        if (!form.status) {
            stepErrors.value.status = 'Veuillez sélectionner un statut'
        }
    }

    return Object.keys(stepErrors.value).length === 0
}

const nextStep = () => {
    if (currentStep.value < steps.length) {
        if (validateStep(currentStep.value)) {
            currentStep.value++
            window.scrollTo({ top: 0, behavior: 'smooth' })
        } else {
            const firstErrorField = Object.keys(stepErrors.value)[0]
            const errorElement = document.querySelector(`[name="${firstErrorField}"], #${firstErrorField}`)
            if (errorElement) {
                errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
                errorElement.focus()
            }
        }
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        stepErrors.value = {}
        currentStep.value--
    }
}

const submit = () => {
    form.put(route('tenant.payments.update', props.payment.id))
}
</script>

<template>
    <TenantLayout title="Modifier le paiement">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50 to-teal-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.payments.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Retour aux paiements
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg">
                            <BanknotesIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Modifier le paiement</h1>
                            <p class="mt-1 text-gray-600">{{ payment.payment_number }}</p>
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
                                            ? 'bg-gradient-to-br from-emerald-500 to-teal-600 text-white shadow-lg'
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
                                    currentStep > step.number ? 'bg-gradient-to-r from-emerald-500 to-teal-600' : 'bg-gray-200'
                                ]"
                            ></div>
                        </template>
                    </div>
                </div>

                <form @submit.prevent="submit">
                    <!-- Step 1: Client & Facture -->
                    <div v-if="currentStep === 1" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <UserIcon class="w-5 h-5 text-emerald-600" />
                                Client & Facture
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Client <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.customer_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un client</option>
                                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                            {{ getCustomerName(customer) }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Facture liée (Optionnel)
                                    </label>
                                    <select
                                        v-model="form.invoice_id"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    >
                                        <option value="">Aucune facture</option>
                                        <option v-for="invoice in filteredInvoices" :key="invoice.id" :value="invoice.id">
                                            {{ invoice.invoice_number }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        N° de paiement
                                    </label>
                                    <input
                                        v-model="form.payment_number"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    />
                                </div>
                            </div>

                            <!-- Type de paiement -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Type de transaction <span class="text-red-500">*</span>
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
                                        <div class="p-4 rounded-xl border-2 border-gray-200 bg-white hover:border-emerald-300 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 transition-all">
                                            <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                            <p class="text-sm text-gray-500">{{ option.description }}</p>
                                        </div>
                                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                                            <CheckIcon class="w-5 h-5 text-emerald-600" />
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Montant & Méthode -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CurrencyEuroIcon class="w-5 h-5 text-emerald-600" />
                                Montant & Méthode
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Montant <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">€</span>
                                        </div>
                                        <input
                                            v-model.number="form.amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="block w-full pl-10 rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-xl font-bold transition-colors"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de paiement
                                    </label>
                                    <input
                                        v-model="form.paid_at"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Méthode de paiement <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.method"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    >
                                        <option v-for="option in methodOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Passerelle <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.gateway"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                    >
                                        <option v-for="option in gatewayOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
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

                    <!-- Step 3: Notes & Récapitulatif -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <DocumentTextIcon class="w-5 h-5 text-emerald-600" />
                                Notes & Récapitulatif
                            </h3>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Notes (optionnel)
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="4"
                                    maxlength="2000"
                                    placeholder="Ajoutez des notes ou commentaires..."
                                    class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 transition-colors"
                                ></textarea>
                            </div>

                            <!-- Récapitulatif -->
                            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-6 border border-emerald-100">
                                <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Récapitulatif</h4>
                                <dl class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Montant</dt>
                                        <dd class="mt-1 text-2xl font-bold text-emerald-600">{{ formatCurrency(form.amount) }}</dd>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Type</dt>
                                        <dd class="mt-1">
                                            <span :class="typeOptions.find(t => t.value === form.type)?.color" class="px-3 py-1 rounded-full text-sm font-semibold">
                                                {{ typeOptions.find(t => t.value === form.type)?.label }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Méthode</dt>
                                        <dd class="mt-1 text-sm font-semibold text-gray-900">
                                            {{ methodOptions.find(m => m.value === form.method)?.label }}
                                        </dd>
                                    </div>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <dt class="text-xs font-medium text-gray-500 uppercase">Statut</dt>
                                        <dd class="mt-1">
                                            <span :class="statusOptions.find(s => s.value === form.status)?.color" class="px-3 py-1 rounded-full text-sm font-semibold">
                                                {{ statusOptions.find(s => s.value === form.status)?.label }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
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
                                :href="route('tenant.payments.index')"
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
                                class="px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg"
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
                                <span v-else>Mettre à jour le paiement</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
