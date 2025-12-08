<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    sites: Array,
    users: Array,
})

const currentStep = ref(1)
const stepErrors = ref({})
const totalSteps = 4

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    company: '',
    type: 'individual',
    source: 'website',
    site_id: '',
    assigned_to: '',
    box_type_interest: '',
    budget_min: '',
    budget_max: '',
    move_in_date: '',
    priority: 'medium',
    notes: '',
})

const sources = [
    { value: 'website', label: 'Site web', icon: '🌐', color: 'bg-blue-100 text-blue-700' },
    { value: 'phone', label: 'Téléphone', icon: '📞', color: 'bg-green-100 text-green-700' },
    { value: 'referral', label: 'Parrainage', icon: '🤝', color: 'bg-purple-100 text-purple-700' },
    { value: 'walk-in', label: 'Visite', icon: '🚶', color: 'bg-orange-100 text-orange-700' },
    { value: 'google_ads', label: 'Google Ads', icon: '📢', color: 'bg-red-100 text-red-700' },
    { value: 'facebook', label: 'Facebook', icon: '📘', color: 'bg-indigo-100 text-indigo-700' },
]

const boxTypes = [
    { value: 'small', label: 'Petit', size: '1-5 m²', icon: '📦' },
    { value: 'medium', label: 'Moyen', size: '5-10 m²', icon: '📦📦' },
    { value: 'large', label: 'Grand', size: '10-20 m²', icon: '🏠' },
    { value: 'xl', label: 'Très grand', size: '20+ m²', icon: '🏢' },
]

const priorities = [
    { value: 'low', label: 'Basse', color: 'bg-gray-100 text-gray-700 border-gray-300' },
    { value: 'medium', label: 'Moyenne', color: 'bg-yellow-100 text-yellow-700 border-yellow-300' },
    { value: 'high', label: 'Haute', color: 'bg-orange-100 text-orange-700 border-orange-300' },
    { value: 'urgent', label: 'Urgente', color: 'bg-red-100 text-red-700 border-red-300' },
]

const steps = [
    { number: 1, title: 'Identité', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
    { number: 2, title: 'Contact', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { number: 3, title: 'Projet', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { number: 4, title: 'Récapitulatif', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
]

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.first_name && form.last_name && form.type
        case 2:
            return form.email
        case 3:
            return true
        case 4:
            return true
        default:
            return false
    }
})

const validateStep = (step) => {
    const errors = {}

    switch (step) {
        case 1:
            if (!form.first_name) errors.first_name = 'Le prénom est requis'
            if (!form.last_name) errors.last_name = 'Le nom est requis'
            if (!form.type) errors.type = 'Le type de prospect est requis'
            break
        case 2:
            if (!form.email) errors.email = 'L\'email est requis'
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errors.email = 'L\'email n\'est pas valide'
            break
        case 3:
            // Step 3 has no required fields
            break
        case 4:
            // Step 4 is summary, no validation needed
            break
    }

    stepErrors.value = errors
    return Object.keys(errors).length === 0
}

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        if (validateStep(currentStep.value)) {
            currentStep.value++
            window.scrollTo({ top: 0, behavior: 'smooth' })
        } else {
            const firstError = document.querySelector('.border-red-300, .text-red-600')
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
            }
        }
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        stepErrors.value = {}
        currentStep.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const getSourceLabel = (value) => {
    const source = sources.find(s => s.value === value)
    return source ? source.label : value
}

const getBoxTypeLabel = (value) => {
    const type = boxTypes.find(t => t.value === value)
    return type ? `${type.label} (${type.size})` : value
}

const getPriorityLabel = (value) => {
    const priority = priorities.find(p => p.value === value)
    return priority ? priority.label : value
}

const getAssignedUserName = (userId) => {
    if (!userId) return 'Non assigné'
    const user = props.users?.find(u => u.id === parseInt(userId))
    return user ? user.name : 'Non assigné'
}

const getSiteName = (siteId) => {
    if (!siteId) return 'Tous les sites'
    const site = props.sites?.find(s => s.id === parseInt(siteId))
    return site ? site.name : 'Tous les sites'
}

const submit = () => {
    form.post(route('tenant.crm.leads.store'))
}
</script>

<template>
    <TenantLayout title="Nouveau Lead">
        <div class="min-h-screen bg-gradient-to-br from-cyan-50 via-white to-teal-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.crm.leads.index')"
                        class="inline-flex items-center text-cyan-600 hover:text-cyan-700 mb-4 group"
                    >
                        <svg class="h-5 w-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux leads
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-cyan-500 to-teal-600 flex items-center justify-center shadow-lg shadow-cyan-500/25">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Lead</h1>
                            <p class="text-gray-500 mt-1">Ajoutez un nouveau prospect au CRM</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <template v-for="(step, index) in steps" :key="step.number">
                            <div class="flex items-center">
                                <div
                                    class="flex items-center justify-center w-12 h-12 rounded-xl transition-all duration-300"
                                    :class="currentStep >= step.number
                                        ? 'bg-gradient-to-br from-cyan-500 to-teal-600 text-white shadow-lg shadow-cyan-500/25'
                                        : 'bg-gray-100 text-gray-400'"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon" />
                                    </svg>
                                </div>
                                <span
                                    class="ml-3 text-sm font-medium hidden sm:block"
                                    :class="currentStep >= step.number ? 'text-cyan-600' : 'text-gray-400'"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                class="flex-1 mx-4 h-1 rounded-full transition-all duration-300"
                                :class="currentStep > step.number ? 'bg-gradient-to-r from-cyan-500 to-teal-500' : 'bg-gray-200'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Form Container -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Identité -->
                        <div v-if="currentStep === 1" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Informations d'identité</h2>

                            <!-- Type de lead -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Type de prospect</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="form.type" value="individual" class="peer sr-only" />
                                        <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 transition-all hover:border-cyan-300">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-12 w-12 rounded-xl bg-cyan-100 flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">Particulier</p>
                                                    <p class="text-sm text-gray-500">Personne physique</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="form.type" value="company" class="peer sr-only" />
                                        <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 transition-all hover:border-cyan-300">
                                            <div class="flex items-center space-x-3">
                                                <div class="h-12 w-12 rounded-xl bg-teal-100 flex items-center justify-center">
                                                    <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900">Entreprise</p>
                                                    <p class="text-sm text-gray-500">Société ou professionnel</p>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                                    <input
                                        v-model="form.first_name"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                        placeholder="Prénom du prospect"
                                        required
                                    />
                                    <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                                    <input
                                        v-model="form.last_name"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                        placeholder="Nom de famille"
                                        required
                                    />
                                    <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                                </div>
                            </div>

                            <div v-if="form.type === 'company'" class="mt-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Entreprise</label>
                                <input
                                    v-model="form.company"
                                    type="text"
                                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                    placeholder="Nom de l'entreprise"
                                />
                                <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                            </div>
                        </div>

                        <!-- Step 2: Contact -->
                        <div v-if="currentStep === 2" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Coordonnées</h2>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                            placeholder="email@exemple.com"
                                            required
                                        />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                            placeholder="+33 6 12 34 56 78"
                                        />
                                    </div>
                                    <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                                </div>

                                <!-- Source -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Source du lead</label>
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        <label v-for="source in sources" :key="source.value" class="relative cursor-pointer">
                                            <input type="radio" v-model="form.source" :value="source.value" class="peer sr-only" />
                                            <div class="p-3 rounded-xl border-2 border-gray-200 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 transition-all hover:border-cyan-300 text-center">
                                                <span class="text-2xl block mb-1">{{ source.icon }}</span>
                                                <span class="text-sm font-medium text-gray-700">{{ source.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Projet -->
                        <div v-if="currentStep === 3" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Détails du projet</h2>

                            <div class="space-y-6">
                                <!-- Type de box -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Type de box souhaité</label>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <label v-for="type in boxTypes" :key="type.value" class="relative cursor-pointer">
                                            <input type="radio" v-model="form.box_type_interest" :value="type.value" class="peer sr-only" />
                                            <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-cyan-500 peer-checked:bg-cyan-50 transition-all hover:border-cyan-300 text-center">
                                                <span class="text-2xl block mb-1">{{ type.icon }}</span>
                                                <span class="text-sm font-medium text-gray-900 block">{{ type.label }}</span>
                                                <span class="text-xs text-gray-500">{{ type.size }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Budget -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Budget min (€/mois)</label>
                                        <input
                                            v-model="form.budget_min"
                                            type="number"
                                            min="0"
                                            step="10"
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                            placeholder="50"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Budget max (€/mois)</label>
                                        <input
                                            v-model="form.budget_max"
                                            type="number"
                                            min="0"
                                            step="10"
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                            placeholder="200"
                                        />
                                    </div>
                                </div>

                                <!-- Site et date -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Site intéressé</label>
                                        <select
                                            v-model="form.site_id"
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                        >
                                            <option value="">Tous les sites</option>
                                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                                {{ site.name }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'emménagement souhaitée</label>
                                        <input
                                            v-model="form.move_in_date"
                                            type="date"
                                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                        />
                                    </div>
                                </div>

                                <!-- Priorité -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Priorité</label>
                                    <div class="flex flex-wrap gap-3">
                                        <label v-for="priority in priorities" :key="priority.value" class="relative cursor-pointer">
                                            <input type="radio" v-model="form.priority" :value="priority.value" class="peer sr-only" />
                                            <div
                                                class="px-4 py-2 rounded-full border-2 peer-checked:ring-2 peer-checked:ring-cyan-200 transition-all"
                                                :class="priority.color"
                                            >
                                                {{ priority.label }}
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Assignation -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Assigné à</label>
                                    <select
                                        v-model="form.assigned_to"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                    >
                                        <option value="">Non assigné</option>
                                        <option v-for="user in users" :key="user.id" :value="user.id">
                                            {{ user.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="4"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-all"
                                        placeholder="Notes internes sur ce prospect..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Récapitulatif -->
                        <div v-if="currentStep === 4" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Récapitulatif du lead</h2>

                            <div class="space-y-6">
                                <!-- Identité -->
                                <div class="bg-gradient-to-r from-cyan-50 to-teal-50 rounded-xl p-6">
                                    <h3 class="font-medium text-gray-900 mb-4 flex items-center">
                                        <svg class="h-5 w-5 text-cyan-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Identité
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Type :</span>
                                            <span class="ml-2 font-medium">{{ form.type === 'individual' ? 'Particulier' : 'Entreprise' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Nom complet :</span>
                                            <span class="ml-2 font-medium">{{ form.first_name }} {{ form.last_name }}</span>
                                        </div>
                                        <div v-if="form.company">
                                            <span class="text-gray-500">Entreprise :</span>
                                            <span class="ml-2 font-medium">{{ form.company }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact -->
                                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6">
                                    <h3 class="font-medium text-gray-900 mb-4 flex items-center">
                                        <svg class="h-5 w-5 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Contact
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Email :</span>
                                            <span class="ml-2 font-medium">{{ form.email }}</span>
                                        </div>
                                        <div v-if="form.phone">
                                            <span class="text-gray-500">Téléphone :</span>
                                            <span class="ml-2 font-medium">{{ form.phone }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Source :</span>
                                            <span class="ml-2 font-medium">{{ getSourceLabel(form.source) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Projet -->
                                <div class="bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-6">
                                    <h3 class="font-medium text-gray-900 mb-4 flex items-center">
                                        <svg class="h-5 w-5 text-teal-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        Projet
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div v-if="form.box_type_interest">
                                            <span class="text-gray-500">Type de box :</span>
                                            <span class="ml-2 font-medium">{{ getBoxTypeLabel(form.box_type_interest) }}</span>
                                        </div>
                                        <div v-if="form.budget_min || form.budget_max">
                                            <span class="text-gray-500">Budget :</span>
                                            <span class="ml-2 font-medium">
                                                {{ form.budget_min || '?' }} - {{ form.budget_max || '?' }} €/mois
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Site :</span>
                                            <span class="ml-2 font-medium">{{ getSiteName(form.site_id) }}</span>
                                        </div>
                                        <div v-if="form.move_in_date">
                                            <span class="text-gray-500">Emménagement :</span>
                                            <span class="ml-2 font-medium">{{ form.move_in_date }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Priorité :</span>
                                            <span class="ml-2 font-medium">{{ getPriorityLabel(form.priority) }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Assigné à :</span>
                                            <span class="ml-2 font-medium">{{ getAssignedUserName(form.assigned_to) }}</span>
                                        </div>
                                    </div>
                                    <div v-if="form.notes" class="mt-4 pt-4 border-t border-gray-200">
                                        <span class="text-gray-500 text-sm">Notes :</span>
                                        <p class="mt-1 text-sm text-gray-700">{{ form.notes }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-between">
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-all font-medium"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Précédent
                            </button>
                            <div v-else></div>

                            <div class="flex space-x-3">
                                <Link
                                    :href="route('tenant.crm.leads.index')"
                                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-all font-medium"
                                >
                                    Annuler
                                </Link>

                                <button
                                    v-if="currentStep < totalSteps"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-cyan-500 to-teal-600 text-white rounded-xl hover:from-cyan-600 hover:to-teal-700 transition-all font-medium shadow-lg shadow-cyan-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Suivant
                                    <svg class="h-5 w-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>

                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-cyan-500 to-teal-600 text-white rounded-xl hover:from-cyan-600 hover:to-teal-700 transition-all font-medium shadow-lg shadow-cyan-500/25 disabled:opacity-50"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                    </svg>
                                    Créer le lead
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
