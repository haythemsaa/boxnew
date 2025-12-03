<script setup>
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    EnvelopeIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    campaign: Object,
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

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        scheduled: 'bg-yellow-100 text-yellow-800',
        sending: 'bg-blue-100 text-blue-800',
        sent: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        scheduled: 'Planifiée',
        sending: 'En cours',
        sent: 'Envoyée',
        failed: 'Échouée',
    }
    return labels[status] || status
}

const getSegmentLabel = (segment) => {
    const labels = {
        all: 'Tous les clients',
        vip: 'Clients VIP',
        at_risk: 'Clients à risque',
        new: 'Nouveaux clients',
        inactive: 'Clients inactifs',
    }
    return labels[segment] || segment
}

const successRate = () => {
    if (!props.campaign.sent_count) return 0
    const failed = props.campaign.failed_count || 0
    return Math.round(((props.campaign.sent_count - failed) / props.campaign.sent_count) * 100)
}

const sendCampaign = () => {
    if (confirm('Voulez-vous vraiment envoyer cette campagne maintenant ?')) {
        router.post(route('tenant.crm.campaigns.send', props.campaign.id))
    }
}

const deleteCampaign = () => {
    if (confirm('Voulez-vous vraiment supprimer cette campagne ?')) {
        router.delete(route('tenant.crm.campaigns.destroy', props.campaign.id))
    }
}
</script>

<template>
    <TenantLayout :title="`Campagne - ${campaign.name}`">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-cyan-600 via-teal-600 to-cyan-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-5xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.crm.campaigns.index')"
                    class="inline-flex items-center text-cyan-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux campagnes
                </Link>

                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center space-x-3">
                            <div>
                                <div class="flex items-center space-x-3">
                                    <h1 class="text-4xl font-bold text-white">{{ campaign.name }}</h1>
                                    <span :class="getStatusColor(campaign.status)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold">
                                        {{ getStatusLabel(campaign.status) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-cyan-100 flex items-center space-x-1">
                                    <UsersIcon class="h-4 w-4" />
                                    <span>{{ getSegmentLabel(campaign.segment) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button
                            v-if="campaign.status === 'draft'"
                            @click="sendCampaign"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-xl shadow-lg text-sm font-medium text-white bg-green-500 hover:bg-green-600 transition-colors"
                        >
                            <EnvelopeIcon class="h-4 w-4 mr-2" />
                            Envoyer maintenant
                        </button>
                        <button
                            v-if="campaign.status !== 'sending'"
                            @click="deleteCampaign"
                            class="inline-flex items-center px-4 py-2 border border-white/30 rounded-xl shadow-lg text-sm font-medium text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm transition-colors"
                        >
                            Supprimer
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Message Content -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Contenu du message</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="bg-gray-100 rounded-lg p-6">
                                <div class="max-w-sm mx-auto">
                                    <div class="bg-green-500 text-white rounded-2xl rounded-br-none p-4 text-sm shadow-lg">
                                        {{ campaign.message }}
                                    </div>
                                    <div class="text-xs text-gray-500 mt-2 text-right">
                                        {{ campaign.message.length }} caractères • {{ Math.ceil(campaign.message.length / 160) }} SMS
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div v-if="campaign.status === 'sent' || campaign.status === 'sending'" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Statistiques</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="text-center p-4 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ campaign.sent_count || 0 }}</div>
                                    <div class="text-sm text-gray-500">SMS envoyés</div>
                                </div>
                                <div class="text-center p-4 bg-green-50 rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">{{ (campaign.sent_count || 0) - (campaign.failed_count || 0) }}</div>
                                    <div class="text-sm text-gray-500">Délivrés</div>
                                </div>
                                <div class="text-center p-4 bg-red-50 rounded-lg">
                                    <div class="text-2xl font-bold text-red-600">{{ campaign.failed_count || 0 }}</div>
                                    <div class="text-sm text-gray-500">Échoués</div>
                                </div>
                                <div class="text-center p-4 bg-blue-50 rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">{{ successRate() }}%</div>
                                    <div class="text-sm text-gray-500">Taux de succès</div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="mt-6">
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                    <span>Progression</span>
                                    <span>{{ successRate() }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div
                                        class="bg-green-500 h-3 rounded-full transition-all duration-300"
                                        :style="{ width: `${successRate()}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs -->
                    <div v-if="campaign.logs && campaign.logs.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Historique d'envoi</h2>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Destinataire</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="log in campaign.logs" :key="log.id">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ log.phone }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span
                                                :class="log.status === 'sent' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 py-1 text-xs rounded-full"
                                            >
                                                {{ log.status === 'sent' ? 'Envoyé' : 'Échoué' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(log.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Cost Card -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Coût</h2>
                        </div>
                        <div class="p-6 text-center">
                            <div class="text-3xl font-bold text-gray-900">{{ formatCurrency(campaign.cost) }}</div>
                            <p class="mt-1 text-sm text-gray-500">Coût total de la campagne</p>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Détails</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-3 text-sm">
                                <div>
                                    <dt class="text-gray-500">Segment</dt>
                                    <dd class="mt-1 text-gray-900 font-medium">{{ getSegmentLabel(campaign.segment) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Statut</dt>
                                    <dd class="mt-1">
                                        <span :class="getStatusColor(campaign.status)" class="px-2 py-1 text-xs rounded-full">
                                            {{ getStatusLabel(campaign.status) }}
                                        </span>
                                    </dd>
                                </div>
                                <div v-if="campaign.scheduled_at">
                                    <dt class="text-gray-500">Planifiée pour</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(campaign.scheduled_at) }}</dd>
                                </div>
                                <div v-if="campaign.sent_at">
                                    <dt class="text-gray-500">Envoyée le</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(campaign.sent_at) }}</dd>
                                </div>
                            </dl>
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
                                    <dt class="text-gray-500">Créée le</dt>
                                    <dd class="text-gray-900">{{ formatDate(campaign.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Mise à jour</dt>
                                    <dd class="text-gray-900">{{ formatDate(campaign.updated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
