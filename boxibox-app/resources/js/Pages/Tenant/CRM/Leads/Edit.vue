<template>
    <TenantLayout :title="`Modifier Lead - ${lead.first_name} ${lead.last_name}`">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-purple-600 via-violet-600 to-purple-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <Link
                    :href="route('tenant.crm.leads.show', lead.id)"
                    class="inline-flex items-center text-purple-100 hover:text-white mb-4 transition-colors duration-200"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour au lead
                </Link>

                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <SparklesIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Modifier Lead</h1>
                        <p class="text-purple-100 mt-1">{{ lead.first_name }} {{ lead.last_name }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Contact Information -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <UserIcon class="w-5 h-5 text-purple-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Informations de contact</h2>
                    </div>
                    <div class="px-6 py-6 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prénom <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    required
                                />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="text-red-500">*</span></label>
                                <input
                                    v-model="form.last_name"
                                    type="text"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    required
                                />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                <div class="relative">
                                    <PhoneIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Entreprise</label>
                            <div class="relative">
                                <BuildingOfficeIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input
                                    v-model="form.company"
                                    type="text"
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                />
                            </div>
                            <p v-if="form.errors.company" class="mt-1 text-sm text-red-600">{{ form.errors.company }}</p>
                        </div>
                    </div>
                </div>

                <!-- Lead Details -->
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                        <DocumentTextIcon class="w-5 h-5 text-purple-600 mr-2" />
                        <h2 class="text-lg font-semibold text-gray-900">Détails du lead</h2>
                    </div>
                    <div class="px-6 py-6 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                                <select
                                    v-model="form.status"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                >
                                    <option v-for="status in statuses" :key="status.value" :value="status.value">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">{{ form.errors.status }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Source</label>
                                <select
                                    v-model="form.source"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                >
                                    <option v-for="source in sources" :key="source.value" :value="source.value">
                                        {{ source.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.source" class="mt-1 text-sm text-red-600">{{ form.errors.source }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Site intéressé</label>
                                <select
                                    v-model="form.site_id"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                >
                                    <option value="">Tous les sites</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">{{ form.errors.site_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assigné à</label>
                                <select
                                    v-model="form.assigned_to"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                >
                                    <option value="">Non assigné</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.assigned_to" class="mt-1 text-sm text-red-600">{{ form.errors.assigned_to }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type de box souhaité</label>
                                <select
                                    v-model="form.box_type_interest"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                >
                                    <option value="">Non précisé</option>
                                    <option v-for="type in boxTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                                <p v-if="form.errors.box_type_interest" class="mt-1 text-sm text-red-600">{{ form.errors.box_type_interest }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date d'emménagement</label>
                                <div class="relative">
                                    <CalendarDaysIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        v-model="form.move_in_date"
                                        type="date"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    />
                                </div>
                                <p v-if="form.errors.move_in_date" class="mt-1 text-sm text-red-600">{{ form.errors.move_in_date }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Budget min (EUR/mois)</label>
                                <div class="relative">
                                    <CurrencyEuroIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        v-model="form.budget_min"
                                        type="number"
                                        min="0"
                                        step="10"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    />
                                </div>
                                <p v-if="form.errors.budget_min" class="mt-1 text-sm text-red-600">{{ form.errors.budget_min }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Budget max (EUR/mois)</label>
                                <div class="relative">
                                    <CurrencyEuroIcon class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        v-model="form.budget_max"
                                        type="number"
                                        min="0"
                                        step="10"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200"
                                    />
                                </div>
                                <p v-if="form.errors.budget_max" class="mt-1 text-sm text-red-600">{{ form.errors.budget_max }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes internes</label>
                            <textarea
                                v-model="form.notes"
                                rows="4"
                                placeholder="Notes internes sur ce lead..."
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-200 focus:border-purple-500 transition-all duration-200 resize-none"
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4">
                    <Link
                        :href="route('tenant.crm.leads.show', lead.id)"
                        class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 font-medium hover:bg-gray-100 transition-colors duration-200"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-violet-600 text-white rounded-xl font-semibold hover:from-purple-700 hover:to-violet-700 transition-all duration-200 shadow-lg shadow-purple-200 disabled:opacity-50 flex items-center space-x-2"
                    >
                        <CheckIcon v-if="!form.processing" class="w-5 h-5" />
                        <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    UserIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    CheckIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    lead: Object,
    sites: Array,
    users: Array,
})

const form = useForm({
    first_name: props.lead.first_name || '',
    last_name: props.lead.last_name || '',
    email: props.lead.email || '',
    phone: props.lead.phone || '',
    company: props.lead.company || '',
    source: props.lead.source || 'website',
    site_id: props.lead.site_id || '',
    assigned_to: props.lead.assigned_to || '',
    box_type_interest: props.lead.box_type_interest || '',
    budget_min: props.lead.budget_min || '',
    budget_max: props.lead.budget_max || '',
    move_in_date: props.lead.move_in_date || '',
    status: props.lead.status || 'new',
    notes: props.lead.notes || '',
})

const sources = [
    { value: 'website', label: 'Site web' },
    { value: 'phone', label: 'Téléphone' },
    { value: 'referral', label: 'Parrainage' },
    { value: 'walk-in', label: 'Visite' },
    { value: 'google_ads', label: 'Google Ads' },
    { value: 'facebook', label: 'Facebook' },
]

const statuses = [
    { value: 'new', label: 'Nouveau' },
    { value: 'contacted', label: 'Contacté' },
    { value: 'qualified', label: 'Qualifié' },
    { value: 'converted', label: 'Converti' },
    { value: 'lost', label: 'Perdu' },
]

const boxTypes = [
    { value: 'small', label: 'Petit (1-5 m²)' },
    { value: 'medium', label: 'Moyen (5-10 m²)' },
    { value: 'large', label: 'Grand (10-20 m²)' },
    { value: 'xl', label: 'Très grand (20+ m²)' },
]

const submit = () => {
    form.patch(route('tenant.crm.leads.update', props.lead.id))
}
</script>
