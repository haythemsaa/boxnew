<template>
    <TenantLayout title="Échanges de points" :breadcrumbs="[{ label: 'Fidélité', href: route('tenant.loyalty.index') }, { label: 'Échanges' }]">
        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">En attente</p>
                    <p class="text-3xl font-bold text-orange-600">{{ stats.pending }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Approuvés</p>
                    <p class="text-3xl font-bold text-green-600">{{ stats.approved }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Utilisés</p>
                    <p class="text-3xl font-bold text-blue-600">{{ stats.used }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Points échangés</p>
                    <p class="text-3xl font-bold text-purple-600">{{ formatNumber(stats.total_points) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-wrap gap-2">
                    <button v-for="status in statusOptions" :key="status.value"
                            @click="filterByStatus(status.value)"
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-medium transition',
                                selectedStatus === status.value
                                    ? status.activeClass
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]">
                        {{ status.label }}
                    </button>
                    <button v-if="selectedStatus" @click="clearFilter" class="px-3 py-2 text-gray-500 hover:text-gray-700">
                        <XMarkIcon class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Redemptions List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="redemptions.data.length === 0" class="p-12 text-center">
                    <GiftIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun échange trouvé</p>
                </div>

                <table v-else class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Membre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Récompense
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Points
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="redemption in redemptions.data" :key="redemption.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-medium">
                                        {{ getInitials(redemption.loyalty_points?.customer) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ redemption.loyalty_points?.customer?.first_name }}
                                            {{ redemption.loyalty_points?.customer?.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ redemption.loyalty_points?.customer?.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ redemption.reward?.icon || '🎁' }}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ redemption.reward?.name }}</p>
                                        <p class="text-xs text-gray-500">{{ getRewardTypeLabel(redemption.reward?.type) }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-purple-600">
                                    {{ formatNumber(redemption.points_spent) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span :class="getStatusBadgeClass(redemption.status)">
                                    {{ getStatusLabel(redemption.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm text-gray-900">{{ formatDate(redemption.created_at) }}</p>
                                <p v-if="redemption.used_at" class="text-xs text-gray-500">
                                    Utilisé le {{ formatDate(redemption.used_at) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Mark as used -->
                                    <button v-if="redemption.status === 'approved' || redemption.status === 'pending'"
                                            @click="processRedemption(redemption, 'mark_used')"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition"
                                            title="Marquer comme utilisé">
                                        <CheckIcon class="w-5 h-5" />
                                    </button>

                                    <!-- Cancel/Reject -->
                                    <button v-if="redemption.status === 'pending' || redemption.status === 'approved'"
                                            @click="processRedemption(redemption, 'reject')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Annuler et rembourser">
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>

                                    <!-- View details -->
                                    <button @click="showDetails(redemption)"
                                            class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition"
                                            title="Voir les détails">
                                        <EyeIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="redemptions.last_page > 1" class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        Affichage {{ redemptions.from }} à {{ redemptions.to }} sur {{ redemptions.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link v-for="link in redemptions.links" :key="link.label"
                              :href="link.url || '#'"
                              :class="[
                                  'px-3 py-1 rounded text-sm',
                                  link.active ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                  !link.url && 'opacity-50 cursor-not-allowed'
                              ]"
                              v-html="link.label">
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Modal -->
        <div v-if="showDetailsModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showDetailsModal = false">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Détails de l'échange</h3>
                    <button @click="showDetailsModal = false" class="p-2 hover:bg-gray-100 rounded-lg">
                        <XMarkIcon class="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                <div v-if="selectedRedemption" class="space-y-4">
                    <!-- Member Info -->
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <p class="text-sm text-gray-500 mb-1">Membre</p>
                        <p class="font-medium text-gray-900">
                            {{ selectedRedemption.loyalty_points?.customer?.first_name }}
                            {{ selectedRedemption.loyalty_points?.customer?.last_name }}
                        </p>
                        <p class="text-sm text-gray-500">{{ selectedRedemption.loyalty_points?.customer?.email }}</p>
                    </div>

                    <!-- Reward Info -->
                    <div class="p-4 bg-purple-50 rounded-xl">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-3xl">{{ selectedRedemption.reward?.icon || '🎁' }}</span>
                            <div>
                                <p class="font-semibold text-purple-900">{{ selectedRedemption.reward?.name }}</p>
                                <p class="text-sm text-purple-700">{{ getRewardTypeLabel(selectedRedemption.reward?.type) }}</p>
                            </div>
                        </div>
                        <p class="text-sm text-purple-800 mt-2">{{ selectedRedemption.reward?.description }}</p>
                    </div>

                    <!-- Points & Status -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500">Points dépensés</p>
                            <p class="text-2xl font-bold text-purple-600">{{ formatNumber(selectedRedemption.points_spent) }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-500">Statut</p>
                            <span :class="getStatusBadgeClass(selectedRedemption.status)" class="mt-1 inline-block">
                                {{ getStatusLabel(selectedRedemption.status) }}
                            </span>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Date de demande:</span>
                            <span class="text-gray-900">{{ formatDateTime(selectedRedemption.created_at) }}</span>
                        </div>
                        <div v-if="selectedRedemption.used_at" class="flex justify-between">
                            <span class="text-gray-500">Date d'utilisation:</span>
                            <span class="text-gray-900">{{ formatDateTime(selectedRedemption.used_at) }}</span>
                        </div>
                        <div v-if="selectedRedemption.code" class="flex justify-between">
                            <span class="text-gray-500">Code:</span>
                            <span class="font-mono text-gray-900 bg-gray-100 px-2 py-0.5 rounded">{{ selectedRedemption.code }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="selectedRedemption.status === 'pending' || selectedRedemption.status === 'approved'" class="flex gap-3 pt-4 border-t border-gray-100">
                        <button @click="processRedemption(selectedRedemption, 'mark_used'); showDetailsModal = false"
                                class="flex-1 btn-primary">
                            <CheckIcon class="w-5 h-5 mr-2" />
                            Marquer utilisé
                        </button>
                        <button @click="processRedemption(selectedRedemption, 'reject'); showDetailsModal = false"
                                class="flex-1 btn-secondary text-red-600 border-red-200 hover:bg-red-50">
                            <XMarkIcon class="w-5 h-5 mr-2" />
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    GiftIcon,
    CheckIcon,
    XMarkIcon,
    EyeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    redemptions: Object,
    filters: Object,
})

const selectedStatus = ref(props.filters?.status || '')
const showDetailsModal = ref(false)
const selectedRedemption = ref(null)

const stats = computed(() => {
    const data = props.redemptions.data || []
    return {
        pending: data.filter(r => r.status === 'pending').length,
        approved: data.filter(r => r.status === 'approved').length,
        used: data.filter(r => r.status === 'used').length,
        total_points: data.reduce((sum, r) => sum + (r.points_spent || 0), 0),
    }
})

const statusOptions = [
    { value: 'pending', label: 'En attente', activeClass: 'bg-orange-100 text-orange-800' },
    { value: 'approved', label: 'Approuvés', activeClass: 'bg-green-100 text-green-800' },
    { value: 'used', label: 'Utilisés', activeClass: 'bg-blue-100 text-blue-800' },
    { value: 'cancelled', label: 'Annulés', activeClass: 'bg-red-100 text-red-800' },
]

const filterByStatus = (status) => {
    selectedStatus.value = selectedStatus.value === status ? '' : status
    router.get(route('tenant.loyalty.redemptions'), {
        status: selectedStatus.value || undefined,
    }, { preserveState: true })
}

const clearFilter = () => {
    selectedStatus.value = ''
    router.get(route('tenant.loyalty.redemptions'), {}, { preserveState: true })
}

const showDetails = (redemption) => {
    selectedRedemption.value = redemption
    showDetailsModal.value = true
}

const processRedemption = (redemption, action) => {
    if (action === 'reject' && !confirm('Annuler cet échange et rembourser les points ?')) {
        return
    }

    router.post(route('tenant.loyalty.redemptions.process', redemption.id), {
        action: action
    })
}

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getInitials = (customer) => {
    if (!customer) return '?'
    return `${customer.first_name?.[0] || ''}${customer.last_name?.[0] || ''}`.toUpperCase()
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        approved: 'Approuvé',
        used: 'Utilisé',
        cancelled: 'Annulé',
        expired: 'Expiré',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800',
        approved: 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800',
        used: 'px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        cancelled: 'px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800',
        expired: 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
    }
    return classes[status] || 'px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
}

const getRewardTypeLabel = (type) => {
    const labels = {
        discount_percent: 'Remise %',
        discount_fixed: 'Remise €',
        free_month: 'Mois gratuit',
        upgrade: 'Upgrade box',
        gift: 'Cadeau',
        service: 'Service gratuit',
    }
    return labels[type] || type
}
</script>
