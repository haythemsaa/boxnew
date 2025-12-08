<template>
    <TenantLayout title="Demandes d'avis" :breadcrumbs="[{ label: 'Avis', href: route('tenant.reviews.index') }, { label: 'Demandes' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Demandes envoyées</p>
                            <p class="text-3xl font-bold text-blue-600">{{ stats?.total_sent ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <PaperAirplaneIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Avis reçus</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats?.total_completed ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <CheckCircleIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Taux de réponse</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats?.response_rate ?? 0 }}%</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <ChartBarIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Envoyer des demandes d'avis</h3>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Individual Request -->
                    <div class="border border-gray-200 rounded-xl p-5">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <UserIcon class="w-5 h-5 text-gray-500" />
                            Demande individuelle
                        </h4>
                        <form @submit.prevent="sendIndividualRequest" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Client</label>
                                <select v-model="individualForm.customer_id" class="w-full rounded-xl border-gray-200" required>
                                    <option value="">Sélectionner un client</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.first_name }} {{ customer.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select v-model="individualForm.type" class="w-full rounded-xl border-gray-200">
                                        <option value="satisfaction">Satisfaction</option>
                                        <option value="nps">NPS</option>
                                        <option value="google">Google Review</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Canal</label>
                                    <select v-model="individualForm.channel" class="w-full rounded-xl border-gray-200">
                                        <option value="email">Email</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-full" :disabled="individualForm.processing">
                                <PaperAirplaneIcon class="w-4 h-4" />
                                Envoyer la demande
                            </button>
                        </form>
                    </div>

                    <!-- Bulk Request -->
                    <div class="border border-gray-200 rounded-xl p-5">
                        <h4 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
                            <UsersIcon class="w-5 h-5 text-gray-500" />
                            Envoi groupé
                        </h4>
                        <form @submit.prevent="sendBulkRequest" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cibler</label>
                                <select v-model="bulkForm.filter" class="w-full rounded-xl border-gray-200">
                                    <option value="all_active">Tous les clients actifs</option>
                                    <option value="recent_contracts">Nouveaux contrats (3 derniers mois)</option>
                                    <option value="never_reviewed">Clients sans avis</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select v-model="bulkForm.type" class="w-full rounded-xl border-gray-200">
                                        <option value="satisfaction">Satisfaction</option>
                                        <option value="nps">NPS</option>
                                        <option value="google">Google Review</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Canal</label>
                                    <select v-model="bulkForm.channel" class="w-full rounded-xl border-gray-200">
                                        <option value="email">Email</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-full" :disabled="bulkForm.processing">
                                <UsersIcon class="w-4 h-4" />
                                Envoyer à tous
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Requests List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Historique des demandes</h3>
                    <select v-model="statusFilter" @change="applyFilter" class="rounded-xl border-gray-200 text-sm">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="completed">Complétées</option>
                    </select>
                </div>

                <div v-if="!requests?.data || requests.data.length === 0" class="px-6 py-12 text-center">
                    <InboxIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucune demande d'avis</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="request in requests.data" :key="request.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-semibold text-sm">
                                        {{ getInitials(request.customer) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ request.customer?.first_name }} {{ request.customer?.last_name }}
                                    </p>
                                    <div class="flex items-center gap-3 text-sm text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <EnvelopeIcon v-if="request.channel === 'email'" class="w-4 h-4" />
                                            <DevicePhoneMobileIcon v-else class="w-4 h-4" />
                                            {{ request.channel === 'email' ? 'Email' : 'SMS' }}
                                        </span>
                                        <span>{{ getTypeLabel(request.type) }}</span>
                                        <span v-if="request.sent_at">Envoyé le {{ formatDate(request.sent_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="getStatusBadgeClass(request)" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(request) }}
                                </span>
                                <button
                                    v-if="!request.completed_at"
                                    @click="resendRequest(request)"
                                    class="text-gray-500 hover:text-primary-600 p-2 hover:bg-gray-100 rounded-lg transition-colors"
                                    title="Relancer"
                                >
                                    <ArrowPathIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="requests?.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="requests.links" />
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    PaperAirplaneIcon,
    CheckCircleIcon,
    ChartBarIcon,
    UserIcon,
    UsersIcon,
    InboxIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    requests: Object,
    stats: Object,
    filters: Object,
    customers: {
        type: Array,
        default: () => []
    },
})

const statusFilter = ref(props.filters?.status || '')

const individualForm = useForm({
    customer_id: '',
    type: 'satisfaction',
    channel: 'email',
})

const bulkForm = useForm({
    filter: 'all_active',
    type: 'satisfaction',
    channel: 'email',
})

const applyFilter = () => {
    router.get(route('tenant.reviews.request'), {
        status: statusFilter.value,
    }, { preserveState: true, replace: true })
}

const sendIndividualRequest = () => {
    individualForm.post(route('tenant.reviews.send-request'), {
        onSuccess: () => individualForm.reset('customer_id'),
    })
}

const sendBulkRequest = () => {
    if (confirm('Envoyer des demandes d\'avis à tous les clients ciblés ?')) {
        bulkForm.post(route('tenant.reviews.bulk-send'))
    }
}

const resendRequest = (request) => {
    if (confirm('Relancer cette demande d\'avis ?')) {
        router.post(route('tenant.reviews.resend', request.id))
    }
}

const getInitials = (customer) => {
    if (!customer) return '?'
    const first = customer.first_name?.[0] || ''
    const last = customer.last_name?.[0] || ''
    return (first + last).toUpperCase() || '?'
}

const getTypeLabel = (type) => {
    const labels = {
        satisfaction: 'Satisfaction',
        nps: 'NPS',
        google: 'Google',
    }
    return labels[type] || type
}

const getStatusLabel = (request) => {
    if (request.completed_at) return 'Complétée'
    if (request.opened_at) return 'Ouverte'
    if (request.sent_at) return 'Envoyée'
    return 'En attente'
}

const getStatusBadgeClass = (request) => {
    if (request.completed_at) return 'bg-green-100 text-green-700'
    if (request.opened_at) return 'bg-yellow-100 text-yellow-700'
    if (request.sent_at) return 'bg-blue-100 text-blue-700'
    return 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}
</script>
