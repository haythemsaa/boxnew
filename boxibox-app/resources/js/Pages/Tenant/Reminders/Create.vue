<template>
    <TenantLayout title="Nouveau Rappel">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-orange-50 to-red-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.reminders.index')"
                        class="inline-flex items-center text-sm text-orange-600 hover:text-orange-800 transition-colors mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux rappels
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center shadow-lg shadow-orange-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Nouveau Rappel de Paiement</h1>
                            <p class="text-gray-500 mt-1">Créez un rappel pour une facture impayée</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Steps -->
                <div class="mb-8">
                    <div class="flex items-center justify-between">
                        <div v-for="step in steps" :key="step.number" class="flex items-center" :class="step.number < steps.length ? 'flex-1' : ''">
                            <div class="flex flex-col items-center">
                                <div :class="[
                                    'w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg transition-all duration-300',
                                    currentStep === step.number
                                        ? 'bg-gradient-to-br from-orange-500 to-red-600 text-white shadow-lg shadow-orange-500/25'
                                        : currentStep > step.number
                                            ? 'bg-emerald-500 text-white'
                                            : 'bg-gray-100 text-gray-400'
                                ]">
                                    <svg v-if="currentStep > step.number" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span v-else>{{ step.number }}</span>
                                </div>
                                <span :class="[
                                    'mt-2 text-sm font-medium transition-colors',
                                    currentStep >= step.number ? 'text-orange-600' : 'text-gray-400'
                                ]">{{ step.title }}</span>
                            </div>
                            <div v-if="step.number < steps.length" :class="[
                                'flex-1 h-1 mx-4 rounded-full transition-colors',
                                currentStep > step.number ? 'bg-emerald-500' : 'bg-gray-200'
                            ]"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Facture -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Facture Impayée</h3>
                                    <p class="text-sm text-gray-500">Sélectionnez la facture concernée</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <select
                                    v-model="form.invoice_id"
                                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 transition-all"
                                    :class="{ 'border-red-300': form.errors.invoice_id }"
                                >
                                    <option value="">Sélectionner une facture...</option>
                                    <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">
                                        {{ invoice.invoice_number }} - {{ getCustomerName(invoice.customer) }} - {{ formatCurrency(invoice.total - invoice.paid_amount) }} ({{ getDaysOverdue(invoice.due_date) }} jours de retard)
                                    </option>
                                </select>
                                <p v-if="form.errors.invoice_id" class="text-sm text-red-600">{{ form.errors.invoice_id }}</p>

                                <!-- Preview -->
                                <div v-if="selectedInvoice" class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-4 border border-orange-100">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500">Facture</p>
                                            <p class="font-semibold text-gray-900">{{ selectedInvoice.invoice_number }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Client</p>
                                            <p class="font-semibold text-gray-900">{{ getCustomerName(selectedInvoice.customer) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Montant dû</p>
                                            <p class="font-bold text-red-600">{{ formatCurrency(selectedInvoice.total - selectedInvoice.paid_amount) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Retard</p>
                                            <p class="font-bold text-red-600">{{ getDaysOverdue(selectedInvoice.due_date) }} jours</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Niveau de relance -->
                        <div v-show="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Niveau de Relance</h3>
                                    <p class="text-sm text-gray-500">Choisissez l'intensité du rappel</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-4 gap-4">
                                <label
                                    v-for="level in reminderLevels"
                                    :key="level.value"
                                    class="relative cursor-pointer"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.level"
                                        :value="level.value"
                                        class="peer sr-only"
                                    />
                                    <div :class="[
                                        'p-4 rounded-xl border-2 transition-all text-center',
                                        'peer-checked:border-orange-500 peer-checked:bg-orange-50',
                                        'hover:border-orange-300 hover:bg-orange-50/50',
                                        form.level !== level.value ? 'border-gray-200' : ''
                                    ]">
                                        <div :class="['w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center', level.bgColor]">
                                            <span :class="['font-bold text-lg', level.textColor]">{{ level.value }}</span>
                                        </div>
                                        <span class="font-medium text-gray-900 block text-sm">{{ level.label }}</span>
                                    </div>
                                </label>
                            </div>

                            <div class="mt-4 p-4 bg-gray-50 rounded-xl">
                                <p class="text-sm text-gray-600">{{ levelDescriptions[form.level] }}</p>
                            </div>
                        </div>

                        <!-- Step 3: Paramètres d'envoi -->
                        <div v-show="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Paramètres d'Envoi</h3>
                                    <p class="text-sm text-gray-500">Configurez la méthode et la date</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Méthode d'envoi -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Méthode d'envoi
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <label
                                            v-for="method in deliveryMethods"
                                            :key="method.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.type"
                                                :value="method.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-4 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-amber-500 peer-checked:bg-amber-50',
                                                'hover:border-amber-300 hover:bg-amber-50/50',
                                                form.type !== method.value ? 'border-gray-200' : ''
                                            ]">
                                                <div :class="['w-10 h-10 rounded-lg mx-auto mb-2 flex items-center justify-center', method.bgColor]">
                                                    <svg class="w-5 h-5" :class="method.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="method.icon" />
                                                    </svg>
                                                </div>
                                                <span class="font-medium text-gray-900 text-sm">{{ method.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Date d'envoi -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Date d'envoi programmé
                                    </label>
                                    <input
                                        v-model="form.scheduled_at"
                                        type="date"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">Laissez la date d'aujourd'hui pour un envoi immédiat</p>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Notes internes
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 transition-all resize-none"
                                        placeholder="Notes internes sur ce rappel..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Récapitulatif -->
                        <div v-show="currentStep === 4" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Récapitulatif</h3>
                                    <p class="text-sm text-gray-500">Vérifiez avant d'envoyer le rappel</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-xl p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Facture</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p class="font-semibold text-gray-900">{{ selectedInvoice?.invoice_number || '-' }}</p>
                                            <p class="text-gray-600">{{ getCustomerName(selectedInvoice?.customer) }}</p>
                                            <p class="font-bold text-red-600">{{ formatCurrency(selectedInvoice?.total - selectedInvoice?.paid_amount) }}</p>
                                        </div>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Niveau</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">Niveau {{ form.level }}</p>
                                        <p class="text-xs text-gray-500">{{ reminderLevels.find(l => l.value === form.level)?.label }}</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Méthode</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">{{ deliveryMethods.find(m => m.value === form.type)?.label }}</p>
                                    </div>

                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Date</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">{{ form.scheduled_at || "Aujourd'hui" }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="px-8 py-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                            <button
                                v-if="currentStep > 1"
                                type="button"
                                @click="prevStep"
                                class="inline-flex items-center px-5 py-2.5 border-2 border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-100 transition-all"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Précédent
                            </button>
                            <div v-else></div>

                            <div class="flex items-center space-x-3">
                                <Link
                                    :href="route('tenant.reminders.index')"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 4"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-xl font-medium shadow-lg shadow-orange-500/25 hover:shadow-xl hover:shadow-orange-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Suivant
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                                <button
                                    v-else
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl hover:shadow-emerald-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="form.processing" class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                    </svg>
                                    {{ form.processing ? 'Création...' : 'Créer le rappel' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    invoices: Array,
})

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Facture' },
    { number: 2, title: 'Niveau' },
    { number: 3, title: 'Paramètres' },
    { number: 4, title: 'Récapitulatif' }
]

const reminderLevels = [
    { value: 1, label: 'Amical', bgColor: 'bg-blue-100', textColor: 'text-blue-600' },
    { value: 2, label: 'Ferme', bgColor: 'bg-yellow-100', textColor: 'text-yellow-600' },
    { value: 3, label: 'Formel', bgColor: 'bg-orange-100', textColor: 'text-orange-600' },
    { value: 4, label: 'Final', bgColor: 'bg-red-100', textColor: 'text-red-600' }
]

const levelDescriptions = {
    1: 'Rappel amical - Premier rappel courtois pour informer le client du retard de paiement.',
    2: 'Relance ferme - Deuxième rappel avec un ton plus direct concernant le paiement en retard.',
    3: 'Mise en demeure - Avertissement formel avant toute action contentieuse.',
    4: 'Dernier avis - Ultime rappel avant procédure de recouvrement.'
}

const deliveryMethods = [
    { value: 'email', label: 'Email', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' },
    { value: 'sms', label: 'SMS', icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', bgColor: 'bg-green-100', iconColor: 'text-green-600' },
    { value: 'letter', label: 'Courrier', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', bgColor: 'bg-amber-100', iconColor: 'text-amber-600' }
]

const form = useForm({
    invoice_id: '',
    level: 1,
    type: 'email',
    scheduled_at: new Date().toISOString().split('T')[0],
    notes: '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) return form.invoice_id
    if (currentStep.value === 2) return form.level
    if (currentStep.value === 3) return form.type
    return true
})

const selectedInvoice = computed(() => {
    if (!form.invoice_id || !props.invoices) return null
    return props.invoices.find(i => i.id == form.invoice_id)
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}

const getDaysOverdue = (dueDate) => {
    const due = new Date(dueDate)
    const today = new Date()
    const diffTime = today - due
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays > 0 ? diffDays : 0
}

const nextStep = () => {
    if (currentStep.value < 4 && canProceed.value) currentStep.value++
}

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--
}

const submit = () => {
    form.post(route('tenant.reminders.store'))
}
</script>
