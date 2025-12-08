<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    sites: Array,
    customers: Array,
    boxes: Array,
    selectedBoxId: Number,
    selectedSiteId: Number,
    selectedBox: Object,
})

const currentStep = ref(1)
const totalSteps = 5

const form = useForm({
    site_id: props.selectedSiteId || '',
    customer_id: '',
    box_id: props.selectedBoxId || '',
    contract_number: '',
    status: 'draft',
    type: 'standard',
    start_date: '',
    end_date: '',
    notice_period_days: 30,
    auto_renew: true,
    renewal_period: 'monthly',
    monthly_price: props.selectedBox?.base_price || '',
    deposit_amount: props.selectedBox ? props.selectedBox.base_price * 2 : 0,
    deposit_paid: false,
    discount_percentage: 0,
    discount_amount: 0,
    billing_frequency: 'monthly',
    billing_day: 1,
    payment_method: 'card',
    auto_pay: false,
    access_code: '',
    key_given: false,
    key_returned: false,
    signed_by_customer: false,
    customer_signed_at: '',
    signed_by_staff: false,
    staff_user_id: '',
    termination_reason: '',
    termination_notes: '',
})

const steps = [
    { number: 1, title: 'Client & Box', description: 'Sélection du client et box' },
    { number: 2, title: 'Durée', description: 'Période du contrat' },
    { number: 3, title: 'Tarification', description: 'Prix et paiement' },
    { number: 4, title: 'Accès', description: 'Codes et clés' },
    { number: 5, title: 'Validation', description: 'Vérification finale' },
]

const filteredBoxes = computed(() => {
    if (!form.site_id) return props.boxes
    return props.boxes.filter((box) => box.site_id == form.site_id && box.status === 'available')
})

const selectedBox = computed(() => {
    if (!form.box_id) return null
    return props.boxes.find((box) => box.id == form.box_id)
})

const selectedCustomer = computed(() => {
    if (!form.customer_id) return null
    return props.customers.find((c) => c.id == form.customer_id)
})

const selectedSite = computed(() => {
    if (!form.site_id) return null
    return props.sites.find((s) => s.id == form.site_id)
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

const getBoxLabel = (box) => {
    const parts = [box.number || box.name]
    const size = box.length && box.width ? (box.length * box.width).toFixed(1) : null
    if (size) parts.push(`${size}m²`)
    return parts.join(' - ')
}

watch(() => form.box_id, () => {
    if (selectedBox.value && !form.monthly_price) {
        form.monthly_price = selectedBox.value.base_price
        form.deposit_amount = selectedBox.value.base_price * 2
    }
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

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
            if (form.deposit_amount < 0) errors.deposit_amount = 'Le dépôt ne peut pas être négatif'
            break
        case 4:
            // Pas de champs obligatoires à l'étape 4
            break
        case 5:
            // Validation finale - vérifier tous les champs obligatoires
            if (!form.site_id) errors.site_id = 'Site manquant'
            if (!form.customer_id) errors.customer_id = 'Client manquant'
            if (!form.box_id) errors.box_id = 'Box manquant'
            if (!form.start_date) errors.start_date = 'Date de début manquante'
            if (!form.monthly_price || form.monthly_price <= 0) errors.monthly_price = 'Prix mensuel manquant'
            break
    }

    return errors
}

const canProceed = computed(() => {
    const errors = validateStep(currentStep.value)
    return Object.keys(errors).length === 0
})

const nextStep = () => {
    const errors = validateStep(currentStep.value)
    stepErrors.value = errors

    if (Object.keys(errors).length > 0) {
        // Scroll to first error
        const firstErrorField = document.querySelector('.field-error')
        if (firstErrorField) {
            firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
    }

    if (currentStep.value < totalSteps) {
        currentStep.value++
        stepErrors.value = {}
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
    form.post(route('tenant.contracts.store'))
}

const statusOptions = [
    { value: 'draft', label: 'Brouillon', color: 'gray' },
    { value: 'pending_signature', label: 'En attente de signature', color: 'amber' },
    { value: 'active', label: 'Actif', color: 'emerald' },
]

const typeOptions = [
    { value: 'standard', label: 'Standard', description: 'Contrat classique mensuel', icon: '📄' },
    { value: 'short_term', label: 'Court terme', description: 'Moins de 3 mois', icon: '⏱️' },
    { value: 'long_term', label: 'Long terme', description: 'Plus de 12 mois', icon: '📅' },
]

const renewalOptions = [
    { value: 'monthly', label: 'Mensuel' },
    { value: 'quarterly', label: 'Trimestriel' },
    { value: 'yearly', label: 'Annuel' },
]

const billingOptions = [
    { value: 'monthly', label: 'Mensuelle' },
    { value: 'quarterly', label: 'Trimestrielle' },
    { value: 'yearly', label: 'Annuelle' },
]

const paymentMethods = [
    { value: 'card', label: 'Carte bancaire', icon: '💳' },
    { value: 'sepa', label: 'Prélèvement SEPA', icon: '🏦' },
    { value: 'bank_transfer', label: 'Virement', icon: '🔄' },
    { value: 'cash', label: 'Espèces', icon: '💵' },
]
</script>

<template>
    <TenantLayout title="Nouveau Contrat">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50/30">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="mb-8 animate-fade-in-up">
                    <Link
                        :href="route('tenant.contracts.index')"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-purple-600 transition-colors mb-4"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux contrats
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Contrat</h1>
                            <p class="text-gray-500 mt-1">Créez un nouveau contrat de location</p>
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
                                                ? 'bg-gradient-to-br from-purple-500 to-purple-600 text-white shadow-lg shadow-purple-500/25'
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
                                            currentStep === step.number ? 'text-purple-600' : 'text-gray-500'
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
                    <!-- Step 1: Client & Box -->
                    <div v-if="currentStep === 1" class="space-y-6 animate-fade-in-up">
                        <!-- Site Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    </svg>
                                </span>
                                Sélection du site
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <button
                                    v-for="site in sites"
                                    :key="site.id"
                                    type="button"
                                    @click="form.site_id = site.id; form.box_id = ''"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-left transition-all duration-200',
                                        form.site_id == site.id
                                            ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-500/20'
                                            : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <p class="font-semibold text-gray-900">{{ site.name }}</p>
                                    <p class="text-sm text-gray-500">{{ site.city }}</p>
                                </button>
                            </div>
                            <div v-if="form.errors.site_id || stepErrors.site_id" class="mt-2 text-sm text-red-600 field-error flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ form.errors.site_id || stepErrors.site_id }}
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
                                Sélection du client
                            </h3>
                            <div class="relative mb-4">
                                <select
                                    v-model="form.customer_id"
                                    class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm bg-white"
                                >
                                    <option value="">Sélectionner un client...</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ getCustomerName(customer) }} - {{ customer.email }}
                                    </option>
                                </select>
                            </div>
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
                            <div v-if="form.errors.customer_id || stepErrors.customer_id" class="mt-2 text-sm text-red-600 field-error flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ form.errors.customer_id || stepErrors.customer_id }}
                            </div>
                        </div>

                        <!-- Box Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                                <span class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </span>
                                Sélection du box
                            </h3>
                            <div v-if="!form.site_id" class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                <p>Sélectionnez d'abord un site</p>
                            </div>
                            <div v-else-if="filteredBoxes.length === 0" class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p>Aucun box disponible sur ce site</p>
                            </div>
                            <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                                <button
                                    v-for="box in filteredBoxes"
                                    :key="box.id"
                                    type="button"
                                    @click="form.box_id = box.id"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-left transition-all duration-200',
                                        form.box_id == box.id
                                            ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-500/20'
                                            : 'border-gray-200 hover:border-emerald-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <p class="font-bold text-gray-900">{{ box.number || box.name }}</p>
                                    <p class="text-sm text-gray-500">{{ box.length && box.width ? (box.length * box.width).toFixed(1) : '-' }}m²</p>
                                    <p class="text-lg font-bold text-emerald-600 mt-2">{{ formatCurrency(box.base_price) }}/mois</p>
                                </button>
                            </div>
                            <div v-if="form.errors.box_id || stepErrors.box_id" class="mt-2 text-sm text-red-600 field-error flex items-center gap-1">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ form.errors.box_id || stepErrors.box_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Duration -->
                    <div v-if="currentStep === 2" class="space-y-6 animate-fade-in-up">
                        <!-- Contract Type -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Type de contrat</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <button
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    type="button"
                                    @click="form.type = option.value"
                                    :class="[
                                        'p-5 rounded-xl border-2 text-left transition-all duration-200',
                                        form.type === option.value
                                            ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-500/20'
                                            : 'border-gray-200 hover:border-purple-300 hover:bg-gray-50'
                                    ]"
                                >
                                    <span class="text-2xl mb-2 block">{{ option.icon }}</span>
                                    <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                    <p class="text-sm text-gray-500">{{ option.description }}</p>
                                </button>
                            </div>
                        </div>

                        <!-- Contract Dates -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Période du contrat</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de début <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.start_date"
                                        type="date"
                                        required
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                    />
                                    <div v-if="form.errors.start_date || stepErrors.start_date" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.start_date || stepErrors.start_date }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Date de fin (optionnel)
                                    </label>
                                    <input
                                        v-model="form.end_date"
                                        type="date"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                    />
                                    <div v-if="stepErrors.end_date" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ stepErrors.end_date }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Renewal Options -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Options de renouvellement</h3>
                            <div class="space-y-6">
                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input v-model="form.auto_renew" type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    </label>
                                    <div>
                                        <p class="font-medium text-gray-900">Renouvellement automatique</p>
                                        <p class="text-sm text-gray-500">Le contrat sera renouvelé automatiquement</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Période de renouvellement</label>
                                        <select
                                            v-model="form.renewal_period"
                                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-white"
                                        >
                                            <option v-for="option in renewalOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Préavis (jours)</label>
                                        <input
                                            v-model.number="form.notice_period_days"
                                            type="number"
                                            min="0"
                                            max="365"
                                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Pricing -->
                    <div v-if="currentStep === 3" class="space-y-6 animate-fade-in-up">
                        <!-- Pricing -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tarification</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Prix mensuel (€) <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                        <input
                                            v-model.number="form.monthly_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                        />
                                    </div>
                                    <div v-if="form.errors.monthly_price || stepErrors.monthly_price" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ form.errors.monthly_price || stepErrors.monthly_price }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Caution (€)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                        <input
                                            v-model.number="form.deposit_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                        />
                                    </div>
                                    <div v-if="stepErrors.deposit_amount" class="mt-1 text-sm text-red-600 field-error flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        {{ stepErrors.deposit_amount }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remise (%)</label>
                                    <input
                                        v-model.number="form.discount_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Remise fixe (€)</label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                        <input
                                            v-model.number="form.discount_amount"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            class="block w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Options -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Paiement</h3>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Méthode de paiement</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <button
                                            v-for="method in paymentMethods"
                                            :key="method.value"
                                            type="button"
                                            @click="form.payment_method = method.value"
                                            :class="[
                                                'p-4 rounded-xl border-2 text-center transition-all duration-200',
                                                form.payment_method === method.value
                                                    ? 'border-purple-500 bg-purple-50'
                                                    : 'border-gray-200 hover:border-purple-300'
                                            ]"
                                        >
                                            <span class="text-2xl block mb-1">{{ method.icon }}</span>
                                            <span class="text-sm font-medium text-gray-700">{{ method.label }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Fréquence de facturation</label>
                                        <select
                                            v-model="form.billing_frequency"
                                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-white"
                                        >
                                            <option v-for="option in billingOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Jour de facturation</label>
                                        <input
                                            v-model.number="form.billing_day"
                                            type="number"
                                            min="1"
                                            max="31"
                                            class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input v-model="form.auto_pay" type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                    </label>
                                    <div>
                                        <p class="font-medium text-gray-900">Paiement automatique</p>
                                        <p class="text-sm text-gray-500">Le client sera débité automatiquement</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 p-4 bg-emerald-50 rounded-xl">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input v-model="form.deposit_paid" type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                    <div>
                                        <p class="font-medium text-gray-900">Caution payée</p>
                                        <p class="text-sm text-gray-500">La caution a été encaissée</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Access -->
                    <div v-if="currentStep === 4" class="space-y-6 animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Accès au box</h3>
                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Code d'accès</label>
                                    <input
                                        v-model="form.access_code"
                                        type="text"
                                        maxlength="10"
                                        placeholder="Ex: 1234"
                                        class="block w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                    />
                                    <p class="mt-1 text-sm text-gray-500">Laissez vide pour générer automatiquement</p>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-4">
                                    <div class="flex-1 flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input v-model="form.key_given" type="checkbox" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                        </label>
                                        <div>
                                            <p class="font-medium text-gray-900">Clé remise</p>
                                            <p class="text-sm text-gray-500">La clé a été donnée au client</p>
                                        </div>
                                    </div>
                                    <div class="flex-1 flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input v-model="form.key_returned" type="checkbox" class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                        </label>
                                        <div>
                                            <p class="font-medium text-gray-900">Clé rendue</p>
                                            <p class="text-sm text-gray-500">La clé a été restituée</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Statut du contrat</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <button
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    type="button"
                                    @click="form.status = option.value"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-left transition-all duration-200',
                                        form.status === option.value
                                            ? `border-${option.color}-500 bg-${option.color}-50 ring-2 ring-${option.color}-500/20`
                                            : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <p class="font-semibold text-gray-900">{{ option.label }}</p>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Summary -->
                    <div v-if="currentStep === 5" class="space-y-6 animate-fade-in-up">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-6">Récapitulatif du contrat</h3>

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

                                <!-- Box Info -->
                                <div class="p-4 bg-emerald-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-emerald-700 mb-3 uppercase tracking-wider">Box</h4>
                                    <div v-if="selectedBox" class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-emerald-500 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-900">{{ selectedBox.number || selectedBox.name }}</p>
                                            <p class="text-sm text-gray-500">{{ selectedBox.length && selectedBox.width ? (selectedBox.length * selectedBox.width).toFixed(1) : '-' }}m² - {{ selectedSite?.name }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Duration -->
                                <div class="p-4 bg-purple-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-purple-700 mb-3 uppercase tracking-wider">Durée</h4>
                                    <p class="font-semibold text-gray-900">{{ typeOptions.find(t => t.value === form.type)?.label }}</p>
                                    <p class="text-sm text-gray-500">Début : {{ form.start_date || 'Non défini' }}</p>
                                    <p class="text-sm text-gray-500" v-if="form.end_date">Fin : {{ form.end_date }}</p>
                                    <p class="text-sm text-gray-500" v-else>Sans date de fin</p>
                                </div>

                                <!-- Pricing -->
                                <div class="p-4 bg-amber-50 rounded-xl">
                                    <h4 class="text-sm font-semibold text-amber-700 mb-3 uppercase tracking-wider">Tarification</h4>
                                    <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(form.monthly_price) }}<span class="text-sm font-normal text-gray-500">/mois</span></p>
                                    <p class="text-sm text-gray-500">Caution : {{ formatCurrency(form.deposit_amount) }}</p>
                                    <p class="text-sm text-gray-500" v-if="form.discount_percentage > 0">Remise : {{ form.discount_percentage }}%</p>
                                </div>
                            </div>

                            <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Paiement</h4>
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <p class="text-gray-500">Méthode</p>
                                        <p class="font-medium text-gray-900">{{ paymentMethods.find(m => m.value === form.payment_method)?.label }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Fréquence</p>
                                        <p class="font-medium text-gray-900">{{ billingOptions.find(b => b.value === form.billing_frequency)?.label }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Paiement auto</p>
                                        <p class="font-medium" :class="form.auto_pay ? 'text-emerald-600' : 'text-gray-900'">{{ form.auto_pay ? 'Oui' : 'Non' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Renouvellement auto</p>
                                        <p class="font-medium" :class="form.auto_renew ? 'text-emerald-600' : 'text-gray-900'">{{ form.auto_renew ? 'Oui' : 'Non' }}</p>
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
                                :href="route('tenant.contracts.index')"
                                class="px-6 py-3 border border-gray-300 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                            >
                                Annuler
                            </Link>
                            <button
                                v-if="currentStep < totalSteps"
                                type="button"
                                @click="nextStep"
                                :disabled="!canProceed"
                                class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 transition-all duration-200 shadow-lg shadow-purple-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                {{ form.processing ? 'Création...' : 'Créer le contrat' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
