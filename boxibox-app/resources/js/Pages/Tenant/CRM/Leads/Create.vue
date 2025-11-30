<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    sites: Array,
    users: Array,
})

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    company: '',
    source: 'website',
    site_id: '',
    assigned_to: '',
    box_type_interest: '',
    budget_min: '',
    budget_max: '',
    move_in_date: '',
    notes: '',
})

const sources = [
    { value: 'website', label: 'Site web' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'referral', label: 'Parrainage' },
    { value: 'walk-in', label: 'Visite' },
    { value: 'google_ads', label: 'Google Ads' },
    { value: 'facebook', label: 'Facebook' },
]

const boxTypes = [
    { value: 'small', label: 'Petit (1-5 m²)' },
    { value: 'medium', label: 'Moyen (5-10 m²)' },
    { value: 'large', label: 'Grand (10-20 m²)' },
    { value: 'xl', label: 'Très grand (20+ m²)' },
]

const submit = () => {
    form.post(route('tenant.crm.leads.store'))
}
</script>

<template>
    <TenantLayout title="Nouveau Lead">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.crm.leads.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Nouveau Lead</h1>
                        <p class="mt-1 text-gray-500">Ajoutez un nouveau prospect au CRM</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Contact Information -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Informations de contact</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Prénom *</label>
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom *</label>
                                <input
                                    v-model="form.last_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email *</label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Entreprise</label>
                            <input
                                v-model="form.company"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                            <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lead Details -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Détails du lead</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Source</label>
                                <select
                                    v-model="form.source"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option v-for="source in sources" :key="source.value" :value="source.value">
                                        {{ source.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.source" class="mt-1 text-sm text-red-600">{{ form.errors.source }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Site intéressé</label>
                                <select
                                    v-model="form.site_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">Tous les sites</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">{{ form.errors.site_id }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type de box souhaité</label>
                                <select
                                    v-model="form.box_type_interest"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">Non précisé</option>
                                    <option v-for="type in boxTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.box_type_interest" class="mt-1 text-sm text-red-600">{{ form.errors.box_type_interest }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date d'emménagement souhaitée</label>
                                <input
                                    v-model="form.move_in_date"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.move_in_date" class="mt-1 text-sm text-red-600">{{ form.errors.move_in_date }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Budget min (EUR/mois)</label>
                                <input
                                    v-model="form.budget_min"
                                    type="number"
                                    min="0"
                                    step="10"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.budget_min" class="mt-1 text-sm text-red-600">{{ form.errors.budget_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Budget max (EUR/mois)</label>
                                <input
                                    v-model="form.budget_max"
                                    type="number"
                                    min="0"
                                    step="10"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.budget_max" class="mt-1 text-sm text-red-600">{{ form.errors.budget_max }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assigné à</label>
                            <select
                                v-model="form.assigned_to"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">Non assigné</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.assigned_to" class="mt-1 text-sm text-red-600">{{ form.errors.assigned_to }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Notes internes sur ce lead..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('tenant.crm.leads.index')"
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
                        Créer le lead
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
