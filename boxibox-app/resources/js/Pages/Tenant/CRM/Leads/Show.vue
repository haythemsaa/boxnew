<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    lead: Object,
    activities: Array,
    users: Array,
})

const showConvertModal = ref(false)

const convertForm = useForm({
    address: '',
    city: '',
    postal_code: '',
    country: 'France',
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const getStatusColor = (status) => {
    const colors = {
        new: 'bg-blue-100 text-blue-800',
        contacted: 'bg-yellow-100 text-yellow-800',
        qualified: 'bg-purple-100 text-purple-800',
        converted: 'bg-green-100 text-green-800',
        lost: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        new: 'Nouveau',
        contacted: 'Contacté',
        qualified: 'Qualifié',
        converted: 'Converti',
        lost: 'Perdu',
    }
    return labels[status] || status
}

const getScoreColor = (score) => {
    if (score >= 70) return 'text-red-600'
    if (score >= 40) return 'text-yellow-600'
    return 'text-gray-600'
}

const getSourceLabel = (source) => {
    const labels = {
        website: 'Site web',
        phone: 'Téléphone',
        referral: 'Parrainage',
        'walk-in': 'Visite',
        google_ads: 'Google Ads',
        facebook: 'Facebook',
    }
    return labels[source] || source
}

const updateStatus = (status) => {
    router.patch(route('tenant.crm.leads.update', props.lead.id), { status }, {
        preserveScroll: true,
    })
}

const submitConvert = () => {
    convertForm.post(route('tenant.crm.leads.convert', props.lead.id))
}
</script>

<template>
    <TenantLayout :title="`Lead - ${lead.first_name} ${lead.last_name}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
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
                            <div class="flex items-center space-x-3">
                                <h1 class="text-3xl font-bold text-gray-900">{{ lead.first_name }} {{ lead.last_name }}</h1>
                                <span :class="getStatusColor(lead.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(lead.status) }}
                                </span>
                            </div>
                            <p class="mt-1 text-gray-500">{{ lead.company || 'Particulier' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('tenant.crm.leads.edit', lead.id)"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Modifier
                        </Link>
                        <button
                            v-if="lead.status !== 'converted'"
                            @click="showConvertModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                        >
                            Convertir en client
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Informations de contact</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a :href="`mailto:${lead.email}`" class="text-primary-600 hover:text-primary-700">
                                            {{ lead.email }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Téléphone</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-primary-600 hover:text-primary-700">
                                            {{ lead.phone }}
                                        </a>
                                        <span v-else class="text-gray-400">-</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Entreprise</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ lead.company || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Source</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ getSourceLabel(lead.source) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Interests -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Intérêts</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type de box souhaité</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ lead.box_type_interest || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date d'emménagement</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ lead.move_in_date ? new Date(lead.move_in_date).toLocaleDateString('fr-FR') : '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Budget</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span v-if="lead.budget_min || lead.budget_max">
                                            {{ lead.budget_min || 0 }} - {{ lead.budget_max || '∞' }} EUR/mois
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Site intéressé</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ lead.site?.name || 'Tous les sites' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="lead.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ lead.notes }}</p>
                        </div>
                    </div>

                    <!-- Activity Timeline -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Activité récente</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div v-if="activities && activities.length > 0" class="flow-root">
                                <ul role="list" class="-mb-8">
                                    <li v-for="(activity, index) in activities" :key="activity.id">
                                        <div class="relative pb-8">
                                            <span v-if="index !== activities.length - 1" class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-4 w-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                    <div>
                                                        <p class="text-sm text-gray-500">{{ activity.description }}</p>
                                                    </div>
                                                    <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                        {{ formatDate(activity.created_at) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <p v-else class="text-sm text-gray-500 text-center py-4">Aucune activité enregistrée</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Score Card -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Score</h2>
                        </div>
                        <div class="p-6 text-center">
                            <div :class="getScoreColor(lead.score)" class="text-5xl font-bold">
                                {{ lead.score || 0 }}
                            </div>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ lead.score >= 70 ? 'Lead chaud' : lead.score >= 40 ? 'Lead tiède' : 'Lead froid' }}
                            </p>
                            <div class="mt-4 w-full bg-gray-200 rounded-full h-2">
                                <div
                                    :class="lead.score >= 70 ? 'bg-red-500' : lead.score >= 40 ? 'bg-yellow-500' : 'bg-gray-400'"
                                    class="h-2 rounded-full transition-all duration-300"
                                    :style="{ width: `${lead.score}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Actions rapides</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <button
                                v-if="lead.status === 'new'"
                                @click="updateStatus('contacted')"
                                class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Marquer comme contacté
                            </button>
                            <button
                                v-if="lead.status === 'contacted'"
                                @click="updateStatus('qualified')"
                                class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700"
                            >
                                Marquer comme qualifié
                            </button>
                            <button
                                v-if="lead.status !== 'lost' && lead.status !== 'converted'"
                                @click="updateStatus('lost')"
                                class="w-full flex items-center justify-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                            >
                                Marquer comme perdu
                            </button>
                        </div>
                    </div>

                    <!-- Assigned To -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Assigné à</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div v-if="lead.assigned_to" class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-medium">{{ lead.assigned_to.name[0] }}</span>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-900">{{ lead.assigned_to.name }}</p>
                                    <p class="text-sm text-gray-500">{{ lead.assigned_to.email }}</p>
                                </div>
                            </div>
                            <p v-else class="text-sm text-gray-500">Non assigné</p>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Timeline</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Créé le</dt>
                                    <dd class="text-gray-900">{{ formatDate(lead.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Mis à jour le</dt>
                                    <dd class="text-gray-900">{{ formatDate(lead.updated_at) }}</dd>
                                </div>
                                <div v-if="lead.converted_at">
                                    <dt class="text-gray-500">Converti le</dt>
                                    <dd class="text-green-600">{{ formatDate(lead.converted_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Convert Modal -->
        <div v-if="showConvertModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Convertir en client</h3>
                </div>
                <form @submit.prevent="submitConvert">
                    <div class="px-6 py-4 space-y-4">
                        <p class="text-sm text-gray-500">Complétez les informations manquantes pour créer le client.</p>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse *</label>
                            <input
                                v-model="convertForm.address"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Code postal *</label>
                                <input
                                    v-model="convertForm.postal_code"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ville *</label>
                                <input
                                    v-model="convertForm.city"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Pays *</label>
                            <input
                                v-model="convertForm.country"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            />
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                        <button
                            type="button"
                            @click="showConvertModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="convertForm.processing"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 disabled:opacity-50"
                        >
                            Convertir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
