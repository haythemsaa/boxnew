<template>
    <AuthenticatedLayout>
        <Head title="Campagnes SMS" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Campagnes SMS</h2>
                        <p class="mt-1 text-sm text-gray-600">Gérez vos campagnes de marketing par SMS</p>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow transition"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle campagne
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Total envoyés</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_sent }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Taux de succès</p>
                                <p class="text-2xl font-bold text-green-600 mt-1">{{ stats.success_rate }}%</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Coût total</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatCurrency(stats.total_cost) }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-full">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">Campagnes actives</p>
                                <p class="text-2xl font-bold text-orange-600 mt-1">{{ stats.active_campaigns }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-full">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaigns Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Toutes les campagnes</h3>
                            <div class="flex space-x-2">
                                <select v-model="filters.status" class="rounded-lg border-gray-300 text-sm">
                                    <option value="">Tous les statuts</option>
                                    <option value="draft">Brouillon</option>
                                    <option value="scheduled">Planifiées</option>
                                    <option value="sending">En cours</option>
                                    <option value="sent">Envoyées</option>
                                    <option value="failed">Échouées</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Campagne
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Segment
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Envoyés
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Taux succès
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Coût
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="campaign in filteredCampaigns" :key="campaign.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ campaign.name }}</div>
                                        <div class="text-sm text-gray-500 truncate max-w-xs">{{ campaign.message }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                            {{ campaign.segment }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusClass(campaign.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                            {{ getStatusLabel(campaign.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ campaign.sent_count || 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ calculateSuccessRate(campaign) }}%
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                            <div
                                                class="bg-green-600 h-1.5 rounded-full"
                                                :style="{ width: calculateSuccessRate(campaign) + '%' }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatCurrency(campaign.cost || 0) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(campaign.sent_at || campaign.scheduled_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="viewCampaign(campaign)"
                                            class="text-blue-600 hover:text-blue-900 mr-3"
                                        >
                                            Voir
                                        </button>
                                        <button
                                            v-if="campaign.status === 'draft'"
                                            @click="sendCampaign(campaign)"
                                            class="text-green-600 hover:text-green-900 mr-3"
                                        >
                                            Envoyer
                                        </button>
                                        <button
                                            @click="deleteCampaign(campaign)"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Supprimer
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div v-if="campaigns.length === 0" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune campagne</h3>
                        <p class="mt-1 text-sm text-gray-500">Créez votre première campagne SMS pour commencer.</p>
                        <div class="mt-6">
                            <button
                                @click="openCreateModal"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                            >
                                Créer une campagne
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

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
});

const filters = ref({
    status: '',
});

const filteredCampaigns = computed(() => {
    if (!filters.value.status) {
        return props.campaigns;
    }
    return props.campaigns.filter(c => c.status === filters.value.status);
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-800',
        scheduled: 'bg-yellow-100 text-yellow-800',
        sending: 'bg-blue-100 text-blue-800',
        sent: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        scheduled: 'Planifiée',
        sending: 'En cours',
        sent: 'Envoyée',
        failed: 'Échouée',
    };
    return labels[status] || status;
};

const calculateSuccessRate = (campaign) => {
    if (!campaign.sent_count) return 0;
    const failed = campaign.failed_count || 0;
    return Math.round(((campaign.sent_count - failed) / campaign.sent_count) * 100);
};

const openCreateModal = () => {
    router.visit(route('tenant.crm.campaigns.create'));
};

const viewCampaign = (campaign) => {
    router.visit(route('tenant.crm.campaigns.show', campaign.id));
};

const sendCampaign = (campaign) => {
    if (confirm(`Êtes-vous sûr de vouloir envoyer la campagne "${campaign.name}" ?`)) {
        router.post(route('tenant.crm.campaigns.send', campaign.id));
    }
};

const deleteCampaign = (campaign) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer la campagne "${campaign.name}" ?`)) {
        router.delete(route('tenant.crm.campaigns.destroy', campaign.id));
    }
};
</script>
