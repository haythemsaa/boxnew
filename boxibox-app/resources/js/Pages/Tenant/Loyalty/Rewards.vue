<template>
    <TenantLayout title="Récompenses fidélité" :breadcrumbs="[{ label: 'Fidélité', href: route('tenant.loyalty.index') }, { label: 'Récompenses' }]">
        <div class="space-y-6">
            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Gérer les récompenses</h3>
                        <p class="text-sm text-gray-500">Configurez les récompenses disponibles pour vos membres fidélité</p>
                    </div>
                    <button @click="showCreateModal = true" class="btn-primary">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouvelle récompense
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Récompenses actives</p>
                    <p class="text-3xl font-bold text-gray-900">{{ stats.active_rewards }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Échanges ce mois</p>
                    <p class="text-3xl font-bold text-green-600">{{ stats.redemptions_this_month }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Points utilisés</p>
                    <p class="text-3xl font-bold text-purple-600">{{ formatNumber(stats.points_redeemed) }}</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Plus populaire</p>
                    <p class="text-lg font-bold text-orange-600 truncate">{{ stats.most_popular || '-' }}</p>
                </div>
            </div>

            <!-- Rewards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="reward in rewards" :key="reward.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Reward Image/Icon -->
                    <div class="h-32 bg-gradient-to-r from-primary-500 to-primary-600 flex items-center justify-center">
                        <span class="text-6xl">{{ reward.icon || '🎁' }}</span>
                    </div>

                    <!-- Reward Info -->
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="font-semibold text-gray-900">{{ reward.name }}</h3>
                            <span :class="reward.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ reward.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">{{ reward.description }}</p>

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-1">
                                <SparklesIcon class="w-5 h-5 text-yellow-500" />
                                <span class="font-bold text-gray-900">{{ formatNumber(reward.points_required) }}</span>
                                <span class="text-sm text-gray-500">points</span>
                            </div>
                            <span :class="getTypeBadgeClass(reward.type)" class="px-2 py-1 rounded text-xs font-medium">
                                {{ getTypeLabel(reward.type) }}
                            </span>
                        </div>

                        <!-- Conditions -->
                        <div v-if="reward.min_tier || reward.valid_until || reward.max_redemptions" class="text-xs text-gray-500 space-y-1 mb-4">
                            <p v-if="reward.min_tier">Niveau minimum: {{ getTierLabel(reward.min_tier) }}</p>
                            <p v-if="reward.valid_until">Valide jusqu'au: {{ formatDate(reward.valid_until) }}</p>
                            <p v-if="reward.max_redemptions">{{ reward.redemption_count }}/{{ reward.max_redemptions }} utilisations</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button @click="editReward(reward)" class="flex-1 btn-secondary text-sm">
                                <PencilIcon class="w-4 h-4 mr-1" />
                                Modifier
                            </button>
                            <button @click="toggleRewardStatus(reward)" :class="reward.is_active ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'" class="p-2">
                                <PauseIcon v-if="reward.is_active" class="w-5 h-5" />
                                <PlayIcon v-else class="w-5 h-5" />
                            </button>
                            <button @click="deleteReward(reward)" class="p-2 text-red-600 hover:text-red-800">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="rewards.length === 0" class="col-span-full bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <GiftIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucune récompense créée</p>
                    <button @click="showCreateModal = true" class="text-primary-600 hover:text-primary-800 text-sm mt-2">
                        Créer ma première récompense
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ showEditModal ? 'Modifier la récompense' : 'Nouvelle récompense' }}
                </h3>
                <form @submit.prevent="submitReward" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                        <input v-model="rewardForm.name" type="text" class="w-full rounded-xl border-gray-200" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="rewardForm.description" rows="2" class="w-full rounded-xl border-gray-200"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                            <select v-model="rewardForm.type" class="w-full rounded-xl border-gray-200" required>
                                <option value="discount_percent">Remise %</option>
                                <option value="discount_fixed">Remise fixe €</option>
                                <option value="free_month">Mois gratuit</option>
                                <option value="upgrade">Upgrade box</option>
                                <option value="gift">Cadeau</option>
                                <option value="service">Service gratuit</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Icône (emoji)</label>
                            <input v-model="rewardForm.icon" type="text" class="w-full rounded-xl border-gray-200" placeholder="🎁" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Points requis *</label>
                            <input v-model="rewardForm.points_required" type="number" min="1" class="w-full rounded-xl border-gray-200" required />
                        </div>
                        <div v-if="rewardForm.type === 'discount_percent' || rewardForm.type === 'discount_fixed'">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ rewardForm.type === 'discount_percent' ? 'Pourcentage' : 'Montant €' }}
                            </label>
                            <input v-model="rewardForm.value" type="number" min="0" :step="rewardForm.type === 'discount_percent' ? '1' : '0.01'" class="w-full rounded-xl border-gray-200" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Niveau minimum</label>
                            <select v-model="rewardForm.min_tier" class="w-full rounded-xl border-gray-200">
                                <option value="">Tous les niveaux</option>
                                <option value="bronze">Bronze</option>
                                <option value="silver">Argent</option>
                                <option value="gold">Or</option>
                                <option value="platinum">Platine</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Utilisations max</label>
                            <input v-model="rewardForm.max_redemptions" type="number" min="0" class="w-full rounded-xl border-gray-200" placeholder="Illimité" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin de validité</label>
                        <input v-model="rewardForm.valid_until" type="date" class="w-full rounded-xl border-gray-200" />
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="rewardForm.is_active" class="sr-only peer" />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                        <span class="text-sm text-gray-700">Récompense active</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" :disabled="rewardForm.processing" class="btn-primary">
                            {{ showEditModal ? 'Mettre à jour' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
    SparklesIcon,
    GiftIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    rewards: Array,
    stats: Object,
})

const showCreateModal = ref(false)
const showEditModal = ref(false)

const rewardForm = useForm({
    id: null,
    name: '',
    description: '',
    type: 'discount_percent',
    icon: '🎁',
    points_required: 100,
    value: 10,
    min_tier: '',
    max_redemptions: null,
    valid_until: '',
    is_active: true,
})

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getTypeLabel = (type) => {
    const labels = {
        discount_percent: 'Remise %',
        discount_fixed: 'Remise €',
        free_month: 'Mois gratuit',
        upgrade: 'Upgrade',
        gift: 'Cadeau',
        service: 'Service',
    }
    return labels[type] || type
}

const getTypeBadgeClass = (type) => {
    const classes = {
        discount_percent: 'bg-green-100 text-green-700',
        discount_fixed: 'bg-green-100 text-green-700',
        free_month: 'bg-blue-100 text-blue-700',
        upgrade: 'bg-purple-100 text-purple-700',
        gift: 'bg-pink-100 text-pink-700',
        service: 'bg-orange-100 text-orange-700',
    }
    return classes[type] || 'bg-gray-100 text-gray-700'
}

const getTierLabel = (tier) => {
    const labels = {
        bronze: 'Bronze',
        silver: 'Argent',
        gold: 'Or',
        platinum: 'Platine',
    }
    return labels[tier] || tier
}

const editReward = (reward) => {
    rewardForm.id = reward.id
    rewardForm.name = reward.name
    rewardForm.description = reward.description
    rewardForm.type = reward.type
    rewardForm.icon = reward.icon
    rewardForm.points_required = reward.points_required
    rewardForm.value = reward.value
    rewardForm.min_tier = reward.min_tier || ''
    rewardForm.max_redemptions = reward.max_redemptions
    rewardForm.valid_until = reward.valid_until?.split('T')[0] || ''
    rewardForm.is_active = reward.is_active
    showEditModal.value = true
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    rewardForm.reset()
}

const submitReward = () => {
    if (showEditModal.value) {
        rewardForm.put(route('tenant.loyalty.rewards.update', rewardForm.id), {
            onSuccess: () => closeModal()
        })
    } else {
        rewardForm.post(route('tenant.loyalty.rewards.store'), {
            onSuccess: () => closeModal()
        })
    }
}

const toggleRewardStatus = (reward) => {
    router.post(route('tenant.loyalty.rewards.toggle', reward.id))
}

const deleteReward = (reward) => {
    if (confirm(`Supprimer la récompense "${reward.name}" ?`)) {
        router.delete(route('tenant.loyalty.rewards.destroy', reward.id))
    }
}
</script>
