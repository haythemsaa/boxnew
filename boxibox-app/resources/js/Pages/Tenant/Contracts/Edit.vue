<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    DocumentTextIcon,
    UserIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    KeyIcon,
    PencilSquareIcon,
    ClockIcon,
    BuildingOfficeIcon,
    CubeIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
    sites: Array,
    customers: Array,
    boxes: Array,
})

const currentStep = ref(1)
const steps = [
    { number: 1, title: 'Informations', icon: DocumentTextIcon },
    { number: 2, title: 'Période', icon: CalendarDaysIcon },
    { number: 3, title: 'Tarification', icon: CurrencyEuroIcon },
    { number: 4, title: 'Accès & Signature', icon: KeyIcon },
]

const form = useForm({
    site_id: props.contract.site_id,
    customer_id: props.contract.customer_id,
    box_id: props.contract.box_id,
    contract_number: props.contract.contract_number,
    status: props.contract.status,
    type: props.contract.type,
    start_date: props.contract.start_date,
    end_date: props.contract.end_date,
    actual_end_date: props.contract.actual_end_date,
    notice_period_days: props.contract.notice_period_days,
    auto_renew: props.contract.auto_renew,
    renewal_period: props.contract.renewal_period,
    monthly_price: props.contract.monthly_price,
    deposit_amount: props.contract.deposit_amount,
    deposit_paid: props.contract.deposit_paid,
    discount_percentage: props.contract.discount_percentage,
    discount_amount: props.contract.discount_amount,
    billing_frequency: props.contract.billing_frequency,
    billing_day: props.contract.billing_day,
    payment_method: props.contract.payment_method,
    auto_pay: props.contract.auto_pay,
    access_code: props.contract.access_code,
    key_given: props.contract.key_given,
    key_returned: props.contract.key_returned,
    signed_by_customer: props.contract.signed_by_customer,
    customer_signed_at: props.contract.customer_signed_at,
    signed_by_staff: props.contract.signed_by_staff,
    staff_user_id: props.contract.staff_user_id,
    termination_reason: props.contract.termination_reason,
    termination_notes: props.contract.termination_notes,
})

const filteredBoxes = computed(() => {
    if (!form.site_id) return props.boxes
    return props.boxes.filter((box) => box.site_id == form.site_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getBoxLabel = (box) => {
    const parts = [box.code]
    if (box.site) parts.push(box.site.name)
    if (box.building) parts.push(box.building.name)
    if (box.floor) parts.push(`Étage ${box.floor.floor_number}`)
    if (box.status === 'occupied' && box.id !== props.contract.box_id) {
        parts.push('(Occupé)')
    }
    return parts.join(' - ')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const statusOptions = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'pending_signature', label: 'En attente de signature' },
    { value: 'active', label: 'Actif' },
    { value: 'expired', label: 'Expiré' },
    { value: 'terminated', label: 'Résilié' },
    { value: 'cancelled', label: 'Annulé' },
]

const typeOptions = [
    { value: 'standard', label: 'Standard', description: 'Contrat classique' },
    { value: 'short_term', label: 'Court terme', description: 'Moins de 3 mois' },
    { value: 'long_term', label: 'Long terme', description: 'Plus de 12 mois' },
]

const renewalOptions = [
    { value: 'monthly', label: 'Mensuel' },
    { value: 'quarterly', label: 'Trimestriel' },
    { value: 'yearly', label: 'Annuel' },
]

const billingOptions = [
    { value: 'monthly', label: 'Mensuel' },
    { value: 'quarterly', label: 'Trimestriel' },
    { value: 'yearly', label: 'Annuel' },
]

const paymentOptions = [
    { value: 'card', label: 'Carte bancaire' },
    { value: 'bank_transfer', label: 'Virement bancaire' },
    { value: 'cash', label: 'Espèces' },
    { value: 'sepa', label: 'Prélèvement SEPA' },
]

const terminationReasons = [
    { value: '', label: 'Aucune résiliation' },
    { value: 'customer_request', label: 'Demande du client' },
    { value: 'non_payment', label: 'Non-paiement' },
    { value: 'breach', label: 'Rupture de contrat' },
    { value: 'end_of_term', label: 'Fin de terme' },
    { value: 'other', label: 'Autre' },
]

// Validation errors for each step
const stepErrors = ref({})

const validateStep = (step) => {
    const errors = {}

    switch (step) {
        case 1:
            if (!form.site_id) errors.site_id = 'Veuillez sélectionner un site'
            if (!form.customer_id) errors.customer_id = 'Veuillez sélectionner un client'
            if (!form.box_id) errors.box_id = 'Veuillez sélectionner un box'
            break
        case 2:
            if (!form.start_date) errors.start_date = 'La date de début est obligatoire'
            if (form.end_date && form.start_date && new Date(form.end_date) <= new Date(form.start_date)) {
                errors.end_date = 'La date de fin doit être après la date de début'
            }
            break
        case 3:
            if (!form.monthly_price || form.monthly_price <= 0) errors.monthly_price = 'Le prix mensuel est obligatoire'
            break
        case 4:
            // Pas de champs obligatoires à l'étape 4
            break
    }

    return errors
}

const nextStep = () => {
    const errors = validateStep(currentStep.value)
    stepErrors.value = errors

    if (Object.keys(errors).length > 0) {
        const firstErrorField = document.querySelector('.field-error')
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
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

const submit = () => {
    // Final validation before submit
    const allErrors = {}
    for (let i = 1; i <= 4; i++) {
        Object.assign(allErrors, validateStep(i))
    }

    if (Object.keys(allErrors).length > 0) {
        stepErrors.value = allErrors
        return
    }

    form.put(route('tenant.contracts.update', props.contract.id))
}
</script>

<template>
    <TenantLayout title="Modifier le contrat">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50 to-purple-50 py-8">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.contracts.index')"
                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 mb-4 transition-colors"
                    >
                        <ArrowLeftIcon class="w-4 h-4" />
                        Retour aux contrats
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                            <DocumentTextIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">Modifier le contrat</h1>
                            <p class="mt-1 text-gray-600">{{ contract.contract_number }}</p>
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
                                            ? 'bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg'
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
                                    currentStep > step.number ? 'bg-gradient-to-r from-indigo-500 to-purple-600' : 'bg-gray-200'
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
                                <DocumentTextIcon class="w-5 h-5 text-indigo-600" />
                                Informations générales
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        N° de contrat
                                    </label>
                                    <input
                                        v-model="form.contract_number"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Site <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.site_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un site</option>
                                        <option v-for="site in sites" :key="site.id" :value="site.id">
                                            {{ site.name }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Client <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.customer_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un client</option>
                                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                            {{ getCustomerName(customer) }}
                                        </option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Box <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.box_id"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option value="">Sélectionner un box</option>
                                        <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                            {{ getBoxLabel(box) }} - {{ formatCurrency(box.base_price) }}/mois
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Statut <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.status"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Type de contrat -->
                            <div class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Type de contrat <span class="text-red-500">*</span>
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
                                        <div class="p-4 rounded-xl border-2 border-gray-200 bg-white hover:border-indigo-300 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition-all">
                                            <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                            <p class="text-sm text-gray-500">{{ option.description }}</p>
                                        </div>
                                        <div class="absolute top-3 right-3 hidden peer-checked:block">
                                            <CheckIcon class="w-5 h-5 text-indigo-600" />
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Période du contrat -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CalendarDaysIcon class="w-5 h-5 text-indigo-600" />
                                Période du contrat
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de début <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de fin
                                    </label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de fin réelle
                                    </label>
                                    <input
                                        v-model="form.actual_end_date"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Préavis (jours) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.notice_period_days"
                                        type="number"
                                        min="0"
                                        max="365"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Période de renouvellement <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.renewal_period"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option v-for="option in renewalOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex items-center">
                                    <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors w-full">
                                        <input
                                            v-model="form.auto_renew"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">Renouvellement automatique</span>
                                            <p class="text-xs text-gray-500">Le contrat sera renouvelé automatiquement</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Tarification & Paiement -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <CurrencyEuroIcon class="w-5 h-5 text-indigo-600" />
                                Tarification & Paiement
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Loyer mensuel (€) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 font-medium">€</span>
                                        </div>
                                        <input
                                            v-model.number="form.monthly_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="block w-full pl-10 rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xl font-bold transition-colors"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Dépôt de garantie (€) <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.deposit_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Remise (%)
                                    </label>
                                    <input
                                        v-model.number="form.discount_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Montant remise (€)
                                    </label>
                                    <input
                                        v-model.number="form.discount_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Fréquence de facturation <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.billing_frequency"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option v-for="option in billingOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Jour de facturation <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.billing_day"
                                        type="number"
                                        min="1"
                                        max="31"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Mode de paiement <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.payment_method"
                                        required
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option v-for="option in paymentOptions" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex flex-col gap-3">
                                    <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input
                                            v-model="form.deposit_paid"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm font-medium text-gray-900">Dépôt payé</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input
                                            v-model="form.auto_pay"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm font-medium text-gray-900">Paiement automatique</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Accès, Signature & Résiliation -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-fade-in">
                        <!-- Accès & Clés -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <KeyIcon class="w-5 h-5 text-indigo-600" />
                                Accès & Clés
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Code d'accès
                                    </label>
                                    <input
                                        v-model="form.access_code"
                                        type="text"
                                        maxlength="10"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors font-mono"
                                    />
                                </div>

                                <div class="flex items-center gap-6">
                                    <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input
                                            v-model="form.key_given"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm font-medium text-gray-900">Clé remise</span>
                                    </label>
                                    <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                        <input
                                            v-model="form.key_returned"
                                            type="checkbox"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <span class="text-sm font-medium text-gray-900">Clé rendue</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Signature -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <PencilSquareIcon class="w-5 h-5 text-indigo-600" />
                                Signature
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.signed_by_customer"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm font-medium text-gray-900">Signé par le client</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100 transition-colors">
                                    <input
                                        v-model="form.signed_by_staff"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <span class="text-sm font-medium text-gray-900">Signé par le personnel</span>
                                </label>

                                <div v-if="form.signed_by_customer">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de signature client
                                    </label>
                                    <input
                                        v-model="form.customer_signed_at"
                                        type="date"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Résiliation -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                                <ClockIcon class="w-5 h-5 text-red-600" />
                                Résiliation
                            </h3>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Motif de résiliation
                                    </label>
                                    <select
                                        v-model="form.termination_reason"
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    >
                                        <option v-for="option in terminationReasons" :key="option.value" :value="option.value">
                                            {{ option.label }}
                                        </option>
                                    </select>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Notes de résiliation
                                    </label>
                                    <textarea
                                        v-model="form.termination_notes"
                                        rows="3"
                                        maxlength="2000"
                                        placeholder="Informations complémentaires..."
                                        class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors"
                                    ></textarea>
                                </div>
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
                                :href="route('tenant.contracts.index')"
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
                                class="px-6 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-medium hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg"
                            >
                                Suivant
                            </button>
                            <button
                                v-else
                                type="submit"
                                :disabled="form.processing"
                                class="px-8 py-2.5 bg-gradient-to-r from-indigo-500 to-purple-600 text-white rounded-xl font-medium hover:from-indigo-600 hover:to-purple-700 transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Mise à jour...
                                </span>
                                <span v-else>Mettre à jour le contrat</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
