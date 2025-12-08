<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    segments: {
        type: Array,
        default: () => [
            { value: 'all', label: 'Tous les clients', description: 'Envoyer à tous les clients ayant accepté les SMS', icon: '👥', count: '~500' },
            { value: 'vip', label: 'Clients VIP', description: 'Clients avec un contrat actif (top 100)', icon: '⭐', count: '~100' },
            { value: 'at_risk', label: 'Clients à risque', description: 'Contrats expirant dans 30 jours', icon: '⚠️', count: '~25' },
            { value: 'new', label: 'Nouveaux clients', description: 'Clients créés dans les 30 derniers jours', icon: '🆕', count: '~50' },
            { value: 'inactive', label: 'Clients inactifs', description: 'Clients sans contrat actif', icon: '💤', count: '~75' },
        ],
    },
})

const currentStep = ref(1)
const stepErrors = ref({})
const totalSteps = 4

const form = useForm({
    name: '',
    type: 'sms',
    message: '',
    subject: '',
    segment: 'all',
    scheduled_at: '',
    send_now: true,
})

const charCount = computed(() => form.message.length)
const smsCount = computed(() => Math.ceil(form.message.length / 160) || 1)
const maxChars = computed(() => form.type === 'sms' ? 1600 : 5000)

const steps = [
    { number: 1, title: 'Campagne', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { number: 2, title: 'Audience', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
    { number: 3, title: 'Message', icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z' },
    { number: 4, title: 'Planification', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
]

const campaignTypes = [
    { value: 'sms', label: 'SMS', description: 'Envoi de SMS court', icon: '📱', color: 'from-green-500 to-emerald-600' },
    { value: 'email', label: 'Email', description: 'Envoi d\'email marketing', icon: '📧', color: 'from-blue-500 to-indigo-600' },
]

const placeholders = [
    { value: '{prenom}', label: 'Prénom' },
    { value: '{nom}', label: 'Nom' },
    { value: '{box}', label: 'N° Box' },
    { value: '{site}', label: 'Site' },
]

const canProceed = computed(() => {
    switch (currentStep.value) {
        case 1:
            return form.name && form.type
        case 2:
            return form.segment
        case 3:
            return form.message && (form.type === 'sms' || form.subject)
        case 4:
            return form.send_now || form.scheduled_at
        default:
            return false
    }
})

const validateStep = (step) => {
    const errors = {}

    switch (step) {
        case 1:
            if (!form.name) errors.name = 'Le nom de la campagne est requis'
            if (!form.type) errors.type = 'Le type de campagne est requis'
            break
        case 2:
            if (!form.segment) errors.segment = 'Veuillez sélectionner une audience'
            break
        case 3:
            if (!form.message) errors.message = 'Le contenu du message est requis'
            if (form.type === 'email' && !form.subject) errors.subject = 'Le sujet de l\'email est requis'
            break
        case 4:
            if (!form.send_now && !form.scheduled_at) errors.scheduled_at = 'Veuillez définir une date de planification'
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

const insertPlaceholder = (placeholder) => {
    form.message += placeholder
}

const getSegmentInfo = (value) => {
    const segment = props.segments.find(s => s.value === value)
    return segment || { label: value, description: '', icon: '', count: '?' }
}

const estimatedCost = computed(() => {
    const segment = getSegmentInfo(form.segment)
    const count = parseInt(segment.count?.replace(/[^0-9]/g, '') || '0')
    if (form.type === 'sms') {
        return (count * smsCount.value * 0.07).toFixed(2)
    }
    return '0.00'
})

const submit = () => {
    if (form.send_now) {
        form.scheduled_at = ''
    }
    form.post(route('tenant.crm.campaigns.store'))
}
</script>

<template>
    <TenantLayout title="Nouvelle Campagne">
        <div class="min-h-screen bg-gradient-to-br from-fuchsia-50 via-white to-pink-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.crm.campaigns.index')"
                        class="inline-flex items-center text-fuchsia-600 hover:text-fuchsia-700 mb-4 group"
                    >
                        <svg class="h-5 w-5 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux campagnes
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-fuchsia-500 to-pink-600 flex items-center justify-center shadow-lg shadow-fuchsia-500/25">
                            <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouvelle Campagne</h1>
                            <p class="text-gray-500 mt-1">Créez et envoyez une campagne marketing</p>
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
                                        ? 'bg-gradient-to-br from-fuchsia-500 to-pink-600 text-white shadow-lg shadow-fuchsia-500/25'
                                        : 'bg-gray-100 text-gray-400'"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="step.icon" />
                                    </svg>
                                </div>
                                <span
                                    class="ml-3 text-sm font-medium hidden sm:block"
                                    :class="currentStep >= step.number ? 'text-fuchsia-600' : 'text-gray-400'"
                                >
                                    {{ step.title }}
                                </span>
                            </div>
                            <div
                                v-if="index < steps.length - 1"
                                class="flex-1 mx-4 h-1 rounded-full transition-all duration-300"
                                :class="currentStep > step.number ? 'bg-gradient-to-r from-fuchsia-500 to-pink-500' : 'bg-gray-200'"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Form Container -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Campagne -->
                        <div v-if="currentStep === 1" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Détails de la campagne</h2>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom de la campagne *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-200 transition-all"
                                        placeholder="Ex: Promotion Black Friday"
                                        required
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <!-- Type de campagne -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Type de campagne *</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label v-for="type in campaignTypes" :key="type.value" class="relative cursor-pointer">
                                            <input type="radio" v-model="form.type" :value="type.value" class="peer sr-only" />
                                            <div class="p-6 rounded-xl border-2 border-gray-200 peer-checked:border-fuchsia-500 peer-checked:bg-fuchsia-50 transition-all hover:border-fuchsia-300">
                                                <div class="flex flex-col items-center text-center">
                                                    <div
                                                        class="h-16 w-16 rounded-xl bg-gradient-to-br flex items-center justify-center text-3xl mb-3"
                                                        :class="type.color"
                                                    >
                                                        {{ type.icon }}
                                                    </div>
                                                    <p class="font-semibold text-gray-900">{{ type.label }}</p>
                                                    <p class="text-sm text-gray-500 mt-1">{{ type.description }}</p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Audience -->
                        <div v-if="currentStep === 2" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Sélectionner l'audience</h2>

                            <div class="space-y-3">
                                <label
                                    v-for="segment in segments"
                                    :key="segment.value"
                                    class="relative cursor-pointer block"
                                >
                                    <input type="radio" v-model="form.segment" :value="segment.value" class="peer sr-only" />
                                    <div class="p-5 rounded-xl border-2 border-gray-200 peer-checked:border-fuchsia-500 peer-checked:bg-fuchsia-50 transition-all hover:border-fuchsia-300">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <span class="text-3xl">{{ segment.icon }}</span>
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ segment.label }}</p>
                                                    <p class="text-sm text-gray-500">{{ segment.description }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-700">
                                                    {{ segment.count }} contacts
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Step 3: Message -->
                        <div v-if="currentStep === 3" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Rédiger le message</h2>

                            <div class="space-y-6">
                                <!-- Sujet (email uniquement) -->
                                <div v-if="form.type === 'email'">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sujet de l'email *</label>
                                    <input
                                        v-model="form.subject"
                                        type="text"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-200 transition-all"
                                        placeholder="Objet de votre email"
                                        required
                                    />
                                    <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                                </div>

                                <!-- Placeholders -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Variables personnalisées</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="placeholder in placeholders"
                                            :key="placeholder.value"
                                            type="button"
                                            @click="insertPlaceholder(placeholder.value)"
                                            class="px-3 py-1.5 text-sm bg-fuchsia-100 text-fuchsia-700 rounded-lg hover:bg-fuchsia-200 transition-colors"
                                        >
                                            + {{ placeholder.label }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Contenu du message -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Contenu du message *
                                    </label>
                                    <textarea
                                        v-model="form.message"
                                        :rows="form.type === 'sms' ? 6 : 10"
                                        :maxlength="maxChars"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-200 transition-all"
                                        placeholder="Rédigez votre message ici..."
                                        required
                                    ></textarea>
                                    <div class="mt-2 flex items-center justify-between text-sm">
                                        <div class="flex items-center space-x-4 text-gray-500">
                                            <span>{{ charCount }} / {{ maxChars }} caractères</span>
                                            <span v-if="form.type === 'sms'">{{ smsCount }} SMS</span>
                                        </div>
                                        <div v-if="form.type === 'sms' && charCount > 160" class="text-orange-600">
                                            Message long ({{ smsCount }} SMS)
                                        </div>
                                    </div>
                                    <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                                </div>

                                <!-- Aperçu SMS -->
                                <div v-if="form.type === 'sms'" class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Aperçu</label>
                                    <div class="bg-gray-100 rounded-2xl p-6">
                                        <div class="max-w-xs mx-auto">
                                            <!-- Phone mockup -->
                                            <div class="bg-gray-800 rounded-3xl p-3 shadow-xl">
                                                <div class="bg-white rounded-2xl p-4 min-h-[200px]">
                                                    <div class="flex justify-center mb-4">
                                                        <span class="text-xs text-gray-400">Aperçu SMS</span>
                                                    </div>
                                                    <div class="bg-gradient-to-br from-fuchsia-500 to-pink-500 text-white rounded-2xl rounded-br-none p-4 text-sm shadow-lg">
                                                        {{ form.message || 'Votre message apparaîtra ici...' }}
                                                    </div>
                                                    <div class="text-xs text-gray-400 mt-2 text-right">Maintenant</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Planification -->
                        <div v-if="currentStep === 4" class="p-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-6">Planification de l'envoi</h2>

                            <div class="space-y-6">
                                <!-- Options d'envoi -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">Quand envoyer ?</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="relative cursor-pointer">
                                            <input type="radio" v-model="form.send_now" :value="true" class="peer sr-only" />
                                            <div class="p-5 rounded-xl border-2 border-gray-200 peer-checked:border-fuchsia-500 peer-checked:bg-fuchsia-50 transition-all hover:border-fuchsia-300">
                                                <div class="flex items-center space-x-3">
                                                    <div class="h-12 w-12 rounded-xl bg-gray-100 flex items-center justify-center">
                                                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">Brouillon</p>
                                                        <p class="text-sm text-gray-500">Enregistrer sans envoyer</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                        <label class="relative cursor-pointer">
                                            <input type="radio" v-model="form.send_now" :value="false" class="peer sr-only" />
                                            <div class="p-5 rounded-xl border-2 border-gray-200 peer-checked:border-fuchsia-500 peer-checked:bg-fuchsia-50 transition-all hover:border-fuchsia-300">
                                                <div class="flex items-center space-x-3">
                                                    <div class="h-12 w-12 rounded-xl bg-fuchsia-100 flex items-center justify-center">
                                                        <svg class="h-6 w-6 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="font-semibold text-gray-900">Planifier</p>
                                                        <p class="text-sm text-gray-500">Définir date et heure</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Date de planification -->
                                <div v-if="!form.send_now" class="animate-fade-in">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date et heure d'envoi *</label>
                                    <input
                                        v-model="form.scheduled_at"
                                        type="datetime-local"
                                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-fuchsia-500 focus:ring-2 focus:ring-fuchsia-200 transition-all"
                                        :min="new Date().toISOString().slice(0, 16)"
                                    />
                                    <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                                </div>

                                <!-- Récapitulatif -->
                                <div class="bg-gradient-to-r from-fuchsia-50 to-pink-50 rounded-xl p-6 mt-8">
                                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                                        <svg class="h-5 w-5 text-fuchsia-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Récapitulatif de la campagne
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Nom :</span>
                                            <span class="ml-2 font-medium">{{ form.name || '-' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Type :</span>
                                            <span class="ml-2 font-medium">{{ form.type === 'sms' ? 'SMS' : 'Email' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Audience :</span>
                                            <span class="ml-2 font-medium">{{ getSegmentInfo(form.segment).label }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Destinataires :</span>
                                            <span class="ml-2 font-medium">{{ getSegmentInfo(form.segment).count }}</span>
                                        </div>
                                        <div v-if="form.type === 'sms'">
                                            <span class="text-gray-500">SMS par message :</span>
                                            <span class="ml-2 font-medium">{{ smsCount }}</span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Envoi :</span>
                                            <span class="ml-2 font-medium">{{ form.send_now ? 'Brouillon' : form.scheduled_at || 'Planifié' }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Info coût -->
                                <div v-if="form.type === 'sms'" class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                    <div class="flex">
                                        <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-blue-800">Estimation des coûts</h4>
                                            <p class="mt-1 text-sm text-blue-700">
                                                Coût estimé : <strong>~{{ estimatedCost }} €</strong> (0.07 € par SMS)
                                            </p>
                                            <p class="text-xs text-blue-600 mt-1">
                                                Les SMS seront envoyés uniquement aux clients ayant accepté de recevoir des communications.
                                            </p>
                                        </div>
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
                                    :href="route('tenant.crm.campaigns.index')"
                                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-100 transition-all font-medium"
                                >
                                    Annuler
                                </Link>

                                <button
                                    v-if="currentStep < totalSteps"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-fuchsia-500 to-pink-600 text-white rounded-xl hover:from-fuchsia-600 hover:to-pink-700 transition-all font-medium shadow-lg shadow-fuchsia-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                    class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-fuchsia-500 to-pink-600 text-white rounded-xl hover:from-fuchsia-600 hover:to-pink-700 transition-all font-medium shadow-lg shadow-fuchsia-500/25 disabled:opacity-50"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                    </svg>
                                    {{ form.send_now ? 'Créer le brouillon' : 'Planifier l\'envoi' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
