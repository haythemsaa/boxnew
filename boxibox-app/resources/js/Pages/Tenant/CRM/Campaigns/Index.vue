<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    MegaphoneIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    TrashIcon,
    PaperAirplaneIcon,
    EyeIcon,
    ChatBubbleLeftRightIcon,
    CurrencyEuroIcon,
    CheckCircleIcon,
    ClockIcon,
    BoltIcon,
    ExclamationTriangleIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    campaigns: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total_sent: 0,
            success_rate: 0,
            total_cost: 0,
            active_campaigns: 0,
        }),
    },
})

const filters = ref({
    status: '',
})

const statusOptions = [
    { value: 'draft', label: 'Brouillon', icon: '📝', color: 'bg-gray-100 text-gray-700 border-gray-200' },
    { value: 'scheduled', label: 'Planifiée', icon: '📅', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 'sending', label: 'En cours', icon: '📤', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 'sent', label: 'Envoyée', icon: '✅', color: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'failed', label: 'Échouée', icon: '❌', color: 'bg-red-100 text-red-700 border-red-200' },
]

const getStatusConfig = (status) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
}

const filteredCampaigns = computed(() => {
    if (!filters.value.status) {
        return props.campaigns
    }
    return props.campaigns.filter(c => c.status === filters.value.status)
})

const hasActiveFilters = computed(() => filters.value.status !== '')

const clearFilters = () => {
    filters.value.status = ''
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const calculateSuccessRate = (campaign) => {
    if (!campaign.sent_count) return 0
    const failed = campaign.failed_count || 0
    return Math.round(((campaign.sent_count - failed) / campaign.sent_count) * 100)
}

const openCreateModal = () => {
    router.visit(route('tenant.crm.campaigns.create'))
}

const viewCampaign = (campaign) => {
    router.visit(route('tenant.crm.campaigns.show', campaign.id))
}

const sendCampaign = (campaign) => {
    if (confirm(`Êtes-vous sûr de vouloir envoyer la campagne "${campaign.name}" ?`)) {
        router.post(route('tenant.crm.campaigns.send', campaign.id))
    }
}

const deleteCampaign = (campaign) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la campagne "${campaign.name}" ?`)) {
        router.delete(route('tenant.crm.campaigns.destroy', campaign.id))
    }
}
</script>

<template>
    <TenantLayout title="Campagnes Marketing">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-fuchsia-500 via-purple-500 to-indigo-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <MegaphoneIcon class="h-8 w-8 text-white" />
                                </div>
                                Campagnes Marketing
                            </h1>
                            <p class="mt-2 text-purple-100">
                                Gérez vos campagnes SMS et Email marketing
                            </p>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="inline-flex items-center px-6 py-3 bg-white text-purple-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <PlusIcon class="h-5 w-5 mr-2" />
                            Nouvelle Campagne
                        </button>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <div class="flex items-center justify-center gap-2">
                                <ChatBubbleLeftRightIcon class="h-5 w-5 text-purple-200" />
                            </div>
                            <p class="text-3xl font-bold text-white mt-2">{{ stats.total_sent || 0 }}</p>
                            <p class="text-xs text-purple-100 font-medium mt-1">Total envoyés</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <div class="flex items-center justify-center gap-2">
                                <CheckCircleIcon class="h-5 w-5 text-emerald-300" />
                            </div>
                            <p class="text-3xl font-bold text-white mt-2">{{ stats.success_rate || 0 }}%</p>
                            <p class="text-xs text-purple-100 font-medium mt-1">Taux de succès</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <div class="flex items-center justify-center gap-2">
                                <CurrencyEuroIcon class="h-5 w-5 text-yellow-300" />
                            </div>
                            <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(stats.total_cost || 0) }}</p>
                            <p class="text-xs text-purple-100 font-medium mt-1">Coût total</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <div class="flex items-center justify-center gap-2">
                                <BoltIcon class="h-5 w-5 text-orange-300" />
                            </div>
                            <p class="text-3xl font-bold text-white mt-2">{{ stats.active_campaigns || 0 }}</p>
                            <p class="text-xs text-purple-100 font-medium mt-1">Campagnes actives</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row gap-4 items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Toutes les campagnes</h3>
                    <div class="flex gap-3">
                        <select
                            v-model="filters.status"
                            class="px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all bg-white"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.icon }} {{ status.label }}
                            </option>
                        </select>

                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors"
                        >
                            <ArrowPathIcon class="h-5 w-5 mr-2" />
                            Réinitialiser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste des campagnes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- État vide -->
                <div v-if="campaigns.length === 0" class="py-16 px-4 text-center">
                    <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <MegaphoneIcon class="h-8 w-8 text-purple-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune campagne</h3>
                    <p class="text-gray-500 mb-6">Créez votre première campagne marketing pour commencer</p>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-fuchsia-500 to-purple-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Créer une campagne
                    </button>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Campagne
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Segment
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Envoyés
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Taux succès
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Coût
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr
                                v-for="(campaign, index) in filteredCampaigns"
                                :key="campaign.id"
                                class="hover:bg-purple-50/50 transition-colors duration-150"
                                :style="{ animationDelay: `${index * 50}ms` }"
                            >
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-fuchsia-400 to-purple-500 flex items-center justify-center text-white font-semibold shadow-sm">
                                                <MegaphoneIcon class="h-5 w-5" />
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ campaign.name }}</div>
                                            <div class="text-sm text-gray-500 truncate max-w-xs">{{ campaign.message }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700 border border-purple-200">
                                        {{ campaign.segment || 'Tous' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusConfig(campaign.status).color"
                                    >
                                        {{ getStatusConfig(campaign.status).icon }}
                                        {{ getStatusConfig(campaign.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">{{ campaign.sent_count || 0 }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium" :class="calculateSuccessRate(campaign) >= 80 ? 'text-green-600' : calculateSuccessRate(campaign) >= 50 ? 'text-yellow-600' : 'text-red-600'">
                                            {{ calculateSuccessRate(campaign) }}%
                                        </span>
                                        <div class="w-16 h-1.5 bg-gray-200 rounded-full">
                                            <div
                                                class="h-1.5 rounded-full transition-all"
                                                :class="calculateSuccessRate(campaign) >= 80 ? 'bg-green-500' : calculateSuccessRate(campaign) >= 50 ? 'bg-yellow-500' : 'bg-red-500'"
                                                :style="{ width: calculateSuccessRate(campaign) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ formatCurrency(campaign.cost || 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1 text-sm text-gray-500">
                                        <ClockIcon class="h-4 w-4" />
                                        {{ formatDate(campaign.sent_at || campaign.scheduled_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Voir -->
                                        <button
                                            @click="viewCampaign(campaign)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Voir les détails"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Envoyer -->
                                        <button
                                            v-if="campaign.status === 'draft'"
                                            @click="sendCampaign(campaign)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Envoyer"
                                        >
                                            <PaperAirplaneIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Supprimer -->
                                        <button
                                            @click="deleteCampaign(campaign)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
