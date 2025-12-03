<template>
    <TenantLayout title="Nouvelle Signature">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-violet-50 to-purple-50 py-8">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="route('tenant.signatures.index')"
                        class="inline-flex items-center text-sm text-violet-600 hover:text-violet-800 transition-colors mb-4"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour aux signatures
                    </Link>
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg shadow-violet-500/25">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Demande de Signature</h1>
                            <p class="text-gray-500 mt-1">Envoyez un document à signer électroniquement</p>
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
                                        ? 'bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-500/25'
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
                                    currentStep >= step.number ? 'text-violet-600' : 'text-gray-400'
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
                        <!-- Step 1: Sélection du contrat -->
                        <div v-show="currentStep === 1" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Contrat à signer</h3>
                                    <p class="text-sm text-gray-500">Sélectionnez le contrat pour lequel envoyer une demande de signature</p>
                                </div>
                            </div>

                            <div v-if="contracts.length === 0" class="text-center py-12 bg-gray-50 rounded-xl">
                                <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto mb-4 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 mb-4">Aucun contrat en attente de signature</p>
                                <Link :href="route('tenant.contracts.create')" class="text-violet-600 hover:text-violet-700 font-medium">
                                    Créer un nouveau contrat
                                </Link>
                            </div>

                            <div v-else class="space-y-3">
                                <label
                                    v-for="contract in contracts"
                                    :key="contract.id"
                                    class="relative cursor-pointer block"
                                >
                                    <input
                                        type="radio"
                                        :value="contract.id"
                                        @change="selectContract(contract.id)"
                                        :checked="form.contract_id === contract.id"
                                        class="peer sr-only"
                                    />
                                    <div :class="[
                                        'p-4 rounded-xl border-2 transition-all',
                                        'peer-checked:border-violet-500 peer-checked:bg-violet-50',
                                        'hover:border-violet-300 hover:bg-violet-50/50',
                                        form.contract_id !== contract.id ? 'border-gray-200' : ''
                                    ]">
                                        <div class="flex justify-between items-start">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 bg-violet-100 rounded-lg flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ contract.contract_number }}</p>
                                                    <p class="text-sm text-gray-600">{{ getCustomerName(contract.customer) }}</p>
                                                    <p class="text-xs text-gray-500">Box: {{ contract.box?.code || 'N/A' }}</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-violet-600">{{ contract.monthly_price?.toFixed(2) }} €/mois</p>
                                                <p class="text-xs text-gray-500">{{ contract.customer?.email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.contract_id" class="mt-2 text-sm text-red-600">{{ form.errors.contract_id }}</p>
                        </div>

                        <!-- Step 2: Type de document -->
                        <div v-show="currentStep === 2" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Type de Document</h3>
                                    <p class="text-sm text-gray-500">Quel document souhaitez-vous faire signer ?</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <label
                                    v-for="type in documentTypes"
                                    :key="type.value"
                                    class="relative cursor-pointer"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.type"
                                        :value="type.value"
                                        class="peer sr-only"
                                    />
                                    <div :class="[
                                        'p-6 rounded-xl border-2 transition-all',
                                        'peer-checked:border-purple-500 peer-checked:bg-purple-50',
                                        'hover:border-purple-300 hover:bg-purple-50/50',
                                        form.type !== type.value ? 'border-gray-200' : ''
                                    ]">
                                        <div class="flex items-start space-x-4">
                                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center', type.bgColor]">
                                                <svg class="w-6 h-6" :class="type.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="type.icon" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ type.label }}</p>
                                                <p class="text-sm text-gray-500 mt-1">{{ type.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Step 3: Paramètres -->
                        <div v-show="currentStep === 3" class="p-8">
                            <div class="flex items-center space-x-3 mb-6">
                                <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-semibold text-gray-900">Paramètres d'envoi</h3>
                                    <p class="text-sm text-gray-500">Configurez les détails de la demande</p>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Email du signataire <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all"
                                            :class="{ 'border-red-300': form.errors.email }"
                                            placeholder="client@email.com"
                                        />
                                    </div>
                                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                                </div>

                                <!-- Validité -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                                        Durée de validité
                                    </label>
                                    <div class="grid grid-cols-5 gap-3">
                                        <label
                                            v-for="validity in validityOptions"
                                            :key="validity.value"
                                            class="relative cursor-pointer"
                                        >
                                            <input
                                                type="radio"
                                                v-model="form.expires_in_days"
                                                :value="validity.value"
                                                class="peer sr-only"
                                            />
                                            <div :class="[
                                                'p-3 rounded-xl border-2 transition-all text-center',
                                                'peer-checked:border-indigo-500 peer-checked:bg-indigo-50',
                                                'hover:border-indigo-300 hover:bg-indigo-50/50',
                                                form.expires_in_days !== validity.value ? 'border-gray-200' : ''
                                            ]">
                                                <span class="font-bold text-gray-900 block">{{ validity.label }}</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <!-- Notes -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Notes internes
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 transition-all resize-none"
                                        placeholder="Notes visibles uniquement par l'équipe..."
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
                                    <p class="text-sm text-gray-500">Vérifiez les informations avant l'envoi</p>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Contrat -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Contrat</span>
                                        </div>
                                        <div class="space-y-1 text-sm">
                                            <p class="font-semibold text-gray-900">{{ selectedContract?.contract_number || '-' }}</p>
                                            <p class="text-gray-600">{{ getCustomerName(selectedContract?.customer) }}</p>
                                        </div>
                                    </div>

                                    <!-- Document -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Document</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">{{ selectedTypeLabel }}</p>
                                    </div>

                                    <!-- Destinataire -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Destinataire</span>
                                        </div>
                                        <p class="text-sm text-gray-900">{{ form.email || '-' }}</p>
                                    </div>

                                    <!-- Validité -->
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <div class="flex items-center space-x-2 mb-3">
                                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-700">Validité</span>
                                        </div>
                                        <p class="font-semibold text-gray-900">{{ form.expires_in_days }} jours</p>
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
                                    :href="route('tenant.signatures.index')"
                                    class="px-5 py-2.5 text-gray-600 hover:text-gray-800 font-medium transition-colors"
                                >
                                    Annuler
                                </Link>
                                <button
                                    v-if="currentStep < 4"
                                    type="button"
                                    @click="nextStep"
                                    :disabled="!canProceed"
                                    class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-violet-500/25 hover:shadow-xl hover:shadow-violet-500/30 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    {{ form.processing ? 'Envoi...' : 'Envoyer la demande' }}
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
    contracts: Array,
})

const currentStep = ref(1)

const steps = [
    { number: 1, title: 'Contrat' },
    { number: 2, title: 'Document' },
    { number: 3, title: 'Paramètres' },
    { number: 4, title: 'Récapitulatif' }
]

const documentTypes = [
    { value: 'contract', label: 'Contrat de location', description: 'Contrat de location de box de stockage', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', bgColor: 'bg-violet-100', iconColor: 'text-violet-600' },
    { value: 'mandate', label: 'Mandat SEPA', description: 'Autorisation de prélèvement automatique', icon: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', bgColor: 'bg-blue-100', iconColor: 'text-blue-600' }
]

const validityOptions = [
    { value: 7, label: '7 jours' },
    { value: 14, label: '14 jours' },
    { value: 30, label: '30 jours' },
    { value: 60, label: '60 jours' },
    { value: 90, label: '90 jours' }
]

const form = useForm({
    contract_id: '',
    type: 'contract',
    email: '',
    expires_in_days: 30,
    notes: '',
})

const canProceed = computed(() => {
    if (currentStep.value === 1) {
        return form.contract_id
    }
    if (currentStep.value === 2) {
        return form.type
    }
    if (currentStep.value === 3) {
        return form.email
    }
    return true
})

const selectedContract = computed(() => {
    if (!form.contract_id || !props.contracts) return null
    return props.contracts.find(c => c.id == form.contract_id)
})

const selectedTypeLabel = computed(() => {
    const type = documentTypes.find(t => t.value === form.type)
    return type ? type.label : form.type
})

const selectContract = (contractId) => {
    form.contract_id = contractId
    const contract = props.contracts.find(c => c.id === contractId)
    if (contract && contract.customer) {
        form.email = contract.customer.email
    }
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}

const nextStep = () => {
    if (currentStep.value < 4 && canProceed.value) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const submit = () => {
    form.post(route('tenant.signatures.store'))
}
</script>
