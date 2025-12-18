<template>
    <TenantLayout title="Détails membre fidélité" :breadcrumbs="[{ label: 'Fidélité', href: route('tenant.loyalty.index') }, { label: 'Membres', href: route('tenant.loyalty.members') }, { label: memberName }]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center text-white text-xl font-bold">
                            {{ getInitials(member.customer) }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                {{ member.customer?.first_name }} {{ member.customer?.last_name }}
                            </h1>
                            <p class="text-gray-500">{{ member.customer?.email }}</p>
                            <div class="flex items-center gap-2 mt-2">
                                <span :class="getTierBadgeClass(member.current_tier)">
                                    {{ getTierIcon(member.current_tier) }} {{ getTierLabel(member.current_tier) }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    Membre depuis {{ formatDate(member.created_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button @click="showAdjustModal = true" class="btn-secondary">
                            <PlusCircleIcon class="w-5 h-5 mr-2" />
                            Ajuster les points
                        </button>
                        <Link :href="route('tenant.customers.show', member.customer_id)" class="btn-primary">
                            Voir le client
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Solde actuel</p>
                    <p class="text-3xl font-bold text-primary-600">{{ formatNumber(member.current_balance) }}</p>
                    <p class="text-xs text-gray-400 mt-1">points disponibles</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Total gagné</p>
                    <p class="text-3xl font-bold text-green-600">+{{ formatNumber(member.total_points_earned) }}</p>
                    <p class="text-xs text-gray-400 mt-1">depuis l'inscription</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Total utilisé</p>
                    <p class="text-3xl font-bold text-purple-600">-{{ formatNumber(member.total_points_redeemed) }}</p>
                    <p class="text-xs text-gray-400 mt-1">en récompenses</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <p class="text-sm text-gray-500">Prochain niveau</p>
                    <div v-if="nextTier">
                        <p class="text-lg font-bold text-gray-900">{{ nextTier.label }}</p>
                        <div class="mt-2">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>{{ formatNumber(member.total_points_earned) }} pts</span>
                                <span>{{ formatNumber(nextTier.threshold) }} pts</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-2 rounded-full transition-all duration-500"
                                     :style="{ width: nextTier.progress + '%' }"></div>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-lg font-bold text-gray-900">Niveau max atteint 🏆</p>
                </div>
            </div>

            <!-- Tier Progress -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Progression des niveaux</h3>
                <div class="flex items-center justify-between">
                    <div v-for="(tier, index) in tiers" :key="tier.name" class="flex-1 relative">
                        <div class="flex flex-col items-center">
                            <div :class="[
                                'w-12 h-12 rounded-full flex items-center justify-center text-2xl border-4 transition-all',
                                isUnlocked(tier.name)
                                    ? 'border-' + tier.color + '-500 bg-' + tier.color + '-100'
                                    : 'border-gray-200 bg-gray-50'
                            ]">
                                {{ tier.icon }}
                            </div>
                            <p :class="[
                                'mt-2 text-sm font-medium',
                                isUnlocked(tier.name) ? 'text-gray-900' : 'text-gray-400'
                            ]">{{ tier.label }}</p>
                            <p class="text-xs text-gray-400">{{ formatNumber(tier.threshold) }}+ pts</p>
                        </div>
                        <div v-if="index < tiers.length - 1"
                             :class="[
                                'absolute top-6 left-1/2 w-full h-1 -z-10',
                                isUnlocked(tiers[index + 1].name) ? 'bg-primary-500' : 'bg-gray-200'
                             ]"></div>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Historique des transactions</h3>
                    <span class="text-sm text-gray-500">{{ member.transactions?.length || 0 }} transactions</span>
                </div>

                <div v-if="!member.transactions || member.transactions.length === 0" class="p-8 text-center text-gray-500">
                    <ClockIcon class="w-12 h-12 mx-auto text-gray-300 mb-3" />
                    <p>Aucune transaction pour le moment</p>
                </div>

                <div v-else class="divide-y divide-gray-100 max-h-[500px] overflow-y-auto">
                    <div v-for="transaction in member.transactions" :key="transaction.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center',
                                getTransactionTypeClass(transaction.type)
                            ]">
                                <component :is="getTransactionIcon(transaction.type)" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ transaction.description }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ formatDateTime(transaction.created_at) }}
                                    <span v-if="transaction.type" class="ml-2 px-1.5 py-0.5 bg-gray-100 rounded text-gray-600">
                                        {{ getTransactionTypeLabel(transaction.type) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p :class="[
                                'font-bold',
                                transaction.points >= 0 ? 'text-green-600' : 'text-red-600'
                            ]">
                                {{ transaction.points >= 0 ? '+' : '' }}{{ formatNumber(transaction.points) }} pts
                            </p>
                            <p class="text-xs text-gray-500">
                                Solde: {{ formatNumber(transaction.balance_after) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <div v-if="showAdjustModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showAdjustModal = false">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajuster les points</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Solde actuel: <strong>{{ formatNumber(member.current_balance) }}</strong> points
                </p>

                <form @submit.prevent="submitAdjustment" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Points à ajouter/retirer *</label>
                        <input v-model="adjustForm.points" type="number" class="w-full rounded-xl border-gray-200" placeholder="+100 ou -50" required />
                        <p class="text-xs text-gray-500 mt-1">Utilisez un nombre négatif pour retirer des points</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Raison *</label>
                        <input v-model="adjustForm.reason" type="text" class="w-full rounded-xl border-gray-200" placeholder="Bonus fidélité, Correction, etc." required />
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAdjustModal = false" class="btn-secondary">Annuler</button>
                        <button type="submit" :disabled="adjustForm.processing" class="btn-primary">
                            Appliquer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PlusCircleIcon,
    ClockIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    GiftIcon,
    SparklesIcon,
    UserPlusIcon,
    CakeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    member: Object,
})

const showAdjustModal = ref(false)

const adjustForm = useForm({
    points: null,
    reason: '',
})

const memberName = computed(() => {
    return `${props.member.customer?.first_name || ''} ${props.member.customer?.last_name || ''}`.trim() || 'Membre'
})

const tiers = [
    { name: 'bronze', label: 'Bronze', icon: '🥉', threshold: 0, color: 'amber' },
    { name: 'silver', label: 'Argent', icon: '🥈', threshold: 500, color: 'gray' },
    { name: 'gold', label: 'Or', icon: '🥇', threshold: 2000, color: 'yellow' },
    { name: 'platinum', label: 'Platine', icon: '💎', threshold: 5000, color: 'indigo' },
]

const nextTier = computed(() => {
    const currentTierIndex = tiers.findIndex(t => t.name === props.member.current_tier)
    if (currentTierIndex === -1 || currentTierIndex >= tiers.length - 1) return null

    const next = tiers[currentTierIndex + 1]
    const current = tiers[currentTierIndex]
    const progress = Math.min(100, ((props.member.total_points_earned - current.threshold) / (next.threshold - current.threshold)) * 100)

    return {
        ...next,
        progress: Math.max(0, progress)
    }
})

const isUnlocked = (tierName) => {
    const tierOrder = ['bronze', 'silver', 'gold', 'platinum']
    const currentIndex = tierOrder.indexOf(props.member.current_tier)
    const checkIndex = tierOrder.indexOf(tierName)
    return checkIndex <= currentIndex
}

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getInitials = (customer) => {
    if (!customer) return '?'
    return `${customer.first_name?.[0] || ''}${customer.last_name?.[0] || ''}`.toUpperCase()
}

const getTierLabel = (tier) => {
    const labels = { bronze: 'Bronze', silver: 'Argent', gold: 'Or', platinum: 'Platine' }
    return labels[tier] || tier
}

const getTierIcon = (tier) => {
    const icons = { bronze: '🥉', silver: '🥈', gold: '🥇', platinum: '💎' }
    return icons[tier] || '⭐'
}

const getTierBadgeClass = (tier) => {
    const classes = {
        bronze: 'px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800',
        silver: 'px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800',
        gold: 'px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800',
        platinum: 'px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800',
    }
    return classes[tier] || classes.bronze
}

const getTransactionTypeLabel = (type) => {
    const labels = {
        earned: 'Gagné',
        redeemed: 'Échangé',
        bonus: 'Bonus',
        adjustment: 'Ajustement',
        welcome: 'Bienvenue',
        referral: 'Parrainage',
        birthday: 'Anniversaire',
        expired: 'Expiré',
    }
    return labels[type] || type
}

const getTransactionTypeClass = (type) => {
    if (['earned', 'bonus', 'welcome', 'referral', 'birthday'].includes(type)) {
        return 'bg-green-100 text-green-600'
    }
    if (type === 'redeemed') {
        return 'bg-purple-100 text-purple-600'
    }
    if (type === 'expired') {
        return 'bg-red-100 text-red-600'
    }
    return 'bg-gray-100 text-gray-600'
}

const getTransactionIcon = (type) => {
    const icons = {
        earned: ArrowUpIcon,
        redeemed: GiftIcon,
        bonus: SparklesIcon,
        adjustment: ArrowDownIcon,
        welcome: UserPlusIcon,
        referral: UserPlusIcon,
        birthday: CakeIcon,
        expired: ClockIcon,
    }
    return icons[type] || ArrowUpIcon
}

const submitAdjustment = () => {
    adjustForm.post(route('tenant.loyalty.members.adjust', props.member.id), {
        onSuccess: () => {
            showAdjustModal.value = false
            adjustForm.reset()
        }
    })
}
</script>
