<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    segments: {
        type: Array,
        default: () => [
            { value: 'all', label: 'Tous les clients', description: 'Envoyer à tous les clients ayant accepté les SMS' },
            { value: 'vip', label: 'Clients VIP', description: 'Clients avec un contrat actif (top 100)' },
            { value: 'at_risk', label: 'Clients à risque', description: 'Contrats expirant dans 30 jours' },
            { value: 'new', label: 'Nouveaux clients', description: 'Clients créés dans les 30 derniers jours' },
            { value: 'inactive', label: 'Clients inactifs', description: 'Clients sans contrat actif' },
        ],
    },
})

const form = useForm({
    name: '',
    message: '',
    segment: 'all',
    scheduled_at: '',
    send_now: true,
})

const charCount = computed(() => form.message.length)
const smsCount = computed(() => Math.ceil(form.message.length / 160) || 1)

const maxChars = 1600 // Max 10 SMS

const submit = () => {
    // Clear scheduled_at if sending now
    if (form.send_now) {
        form.scheduled_at = ''
    }
    form.post(route('tenant.crm.campaigns.store'))
}

const insertPlaceholder = (placeholder) => {
    form.message += placeholder
}

const placeholders = [
    { value: '{prenom}', label: 'Prénom' },
    { value: '{nom}', label: 'Nom' },
    { value: '{box}', label: 'N° Box' },
    { value: '{site}', label: 'Site' },
]
</script>

<template>
    <TenantLayout title="Nouvelle campagne SMS">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.crm.campaigns.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Nouvelle campagne SMS</h1>
                        <p class="mt-1 text-gray-500">Créez et envoyez une campagne SMS à vos clients</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Campaign Details -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Détails de la campagne</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom de la campagne *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Ex: Promotion Black Friday"
                                required
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Segment cible *</label>
                            <div class="mt-2 space-y-2">
                                <label
                                    v-for="segment in segments"
                                    :key="segment.value"
                                    class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:border-primary-500"
                                    :class="form.segment === segment.value ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.segment"
                                        :value="segment.value"
                                        class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                    />
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">{{ segment.label }}</span>
                                        <span class="block text-sm text-gray-500">{{ segment.description }}</span>
                                    </div>
                                </label>
                            </div>
                            <p v-if="form.errors.segment" class="mt-1 text-sm text-red-600">{{ form.errors.segment }}</p>
                        </div>
                    </div>
                </div>

                <!-- Message -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Message</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Contenu du message *</label>
                                <div class="flex items-center space-x-2">
                                    <button
                                        v-for="placeholder in placeholders"
                                        :key="placeholder.value"
                                        type="button"
                                        @click="insertPlaceholder(placeholder.value)"
                                        class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
                                    >
                                        {{ placeholder.label }}
                                    </button>
                                </div>
                            </div>
                            <textarea
                                v-model="form.message"
                                rows="6"
                                :maxlength="maxChars"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Rédigez votre message ici..."
                                required
                            ></textarea>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <div class="flex items-center space-x-4 text-gray-500">
                                    <span>{{ charCount }} / {{ maxChars }} caractères</span>
                                    <span>{{ smsCount }} SMS</span>
                                </div>
                                <div v-if="charCount > 160" class="text-orange-600">
                                    Message long ({{ smsCount }} SMS)
                                </div>
                            </div>
                            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                        </div>

                        <!-- Preview -->
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Aperçu</label>
                            <div class="bg-gray-100 rounded-lg p-4">
                                <div class="max-w-xs mx-auto">
                                    <div class="bg-green-500 text-white rounded-2xl rounded-br-none p-3 text-sm shadow">
                                        {{ form.message || 'Votre message apparaîtra ici...' }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-2 text-right">Aperçu SMS</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Planification</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="form.send_now"
                                    :value="true"
                                    class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Enregistrer comme brouillon</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    type="radio"
                                    v-model="form.send_now"
                                    :value="false"
                                    class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Planifier l'envoi</span>
                            </label>
                        </div>

                        <div v-if="!form.send_now">
                            <label class="block text-sm font-medium text-gray-700">Date et heure d'envoi</label>
                            <input
                                v-model="form.scheduled_at"
                                type="datetime-local"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                :min="new Date().toISOString().slice(0, 16)"
                            />
                            <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">Information</h3>
                            <p class="mt-1 text-sm text-blue-700">
                                Les SMS seront envoyés uniquement aux clients ayant accepté de recevoir des communications SMS.
                                Coût estimé : ~0.07 EUR par SMS.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('tenant.crm.campaigns.index')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.send_now ? 'Créer le brouillon' : 'Planifier l\'envoi' }}
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
