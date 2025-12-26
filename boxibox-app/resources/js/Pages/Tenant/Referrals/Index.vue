<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    UserGroupIcon,
    GiftIcon,
    ChartBarIcon,
    CurrencyEuroIcon,
    Cog6ToothIcon,
    ClipboardDocumentIcon,
    CheckIcon,
    ArrowTrendingUpIcon,
    LinkIcon,
    UserPlusIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
    stats: Object,
    referrals: Array,
    topReferrers: Array,
})

const showSettingsModal = ref(false)
const copied = ref(false)

const form = useForm({
    is_active: props.settings?.is_active ?? true,
    referrer_reward_type: props.settings?.referrer_reward_type ?? 'percentage',
    referrer_reward_value: props.settings?.referrer_reward_value ?? 10,
    referrer_reward_description: props.settings?.referrer_reward_description ?? '',
    referee_reward_type: props.settings?.referee_reward_type ?? 'percentage',
    referee_reward_value: props.settings?.referee_reward_value ?? 10,
    referee_reward_description: props.settings?.referee_reward_description ?? '',
    min_rental_months: props.settings?.min_rental_months ?? 1,
    max_referrals_per_customer: props.settings?.max_referrals_per_customer ?? null,
    reward_delay_days: props.settings?.reward_delay_days ?? 30,
    require_active_contract: props.settings?.require_active_contract ?? true,
})

const saveSettings = () => {
    form.post(route('tenant.referrals.settings.update'), {
        onSuccess: () => {
            showSettingsModal.value = false
        },
    })
}

const rewardTypes = [
    { value: 'percentage', label: 'Pourcentage', suffix: '%' },
    { value: 'fixed', label: 'Montant fixe', suffix: '€' },
    { value: 'free_month', label: 'Mois gratuit', suffix: 'mois' },
]

const statusColors = {
    pending: 'bg-yellow-100 text-yellow-800',
    qualified: 'bg-blue-100 text-blue-800',
    rewarded: 'bg-green-100 text-green-800',
    expired: 'bg-gray-100 text-gray-600',
    cancelled: 'bg-red-100 text-red-800',
}

const statusLabels = {
    pending: 'En attente',
    qualified: 'Qualifié',
    rewarded: 'Récompensé',
    expired: 'Expiré',
    cancelled: 'Annulé',
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getRewardLabel = (type, value) => {
    switch (type) {
        case 'percentage': return `${value}%`
        case 'fixed': return `${value}€`
        case 'free_month': return `${value} mois`
        default: return value
    }
}
</script>

<template>
    <TenantLayout title="Parrainage">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <UserGroupIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">Parrainage</h1>
                            <p class="mt-1 text-purple-100">Programme de parrainage clients</p>
                        </div>
                    </div>
                    <button
                        @click="showSettingsModal = true"
                        class="inline-flex items-center px-4 py-2 bg-white text-purple-700 rounded-xl hover:bg-purple-50 transition-colors font-medium"
                    >
                        <Cog6ToothIcon class="h-5 w-5 mr-2" />
                        Paramètres
                    </button>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-xl">
                                <UserPlusIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-purple-100 text-sm">Total parrainages</p>
                                <p class="text-2xl font-bold text-white">{{ stats?.total_referrals || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-xl">
                                <CheckIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-purple-100 text-sm">Réussis</p>
                                <p class="text-2xl font-bold text-white">{{ stats?.successful_referrals || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-xl">
                                <LinkIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-purple-100 text-sm">Codes actifs</p>
                                <p class="text-2xl font-bold text-white">{{ stats?.active_codes || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-4">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-white/20 rounded-xl">
                                <GiftIcon class="h-6 w-6 text-white" />
                            </div>
                            <div>
                                <p class="text-purple-100 text-sm">Récompenses</p>
                                <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.total_rewards_amount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Program Status -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <Cog6ToothIcon class="h-5 w-5 mr-2 text-purple-500" />
                        Programme actuel
                    </h3>

                    <div v-if="!settings" class="text-center py-8">
                        <GiftIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                        <p class="text-gray-500 mb-4">Aucun programme configuré</p>
                        <button
                            @click="showSettingsModal = true"
                            class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700"
                        >
                            Configurer
                        </button>
                    </div>

                    <div v-else class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Statut</span>
                            <span :class="settings.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ settings.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Récompense Parrain</p>
                            <p class="text-lg font-bold text-purple-600">
                                {{ getRewardLabel(settings.referrer_reward_type, settings.referrer_reward_value) }}
                            </p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Récompense Filleul</p>
                            <p class="text-lg font-bold text-purple-600">
                                {{ getRewardLabel(settings.referee_reward_type, settings.referee_reward_value) }}
                            </p>
                        </div>

                        <div class="border-t pt-4 text-sm text-gray-500">
                            <p>Délai validation: {{ settings.reward_delay_days }} jours</p>
                            <p>Min. location: {{ settings.min_rental_months }} mois</p>
                        </div>
                    </div>
                </div>

                <!-- Top Referrers -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <ArrowTrendingUpIcon class="h-5 w-5 mr-2 text-purple-500" />
                        Meilleurs parrains
                    </h3>

                    <div v-if="topReferrers.length === 0" class="text-center py-8 text-gray-500">
                        <ChartBarIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                        <p>Aucun parrain pour le moment</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="(referrer, index) in topReferrers"
                            :key="index"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                        >
                            <div class="flex items-center space-x-3">
                                <span class="w-6 h-6 flex items-center justify-center bg-purple-100 text-purple-700 rounded-full text-sm font-bold">
                                    {{ index + 1 }}
                                </span>
                                <div>
                                    <p class="font-medium text-gray-900">{{ referrer.customer?.name || 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ referrer.code }}</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-purple-600">
                                {{ referrer.successful_referrals }} filleul(s)
                            </span>
                        </div>
                    </div>

                    <Link
                        :href="route('tenant.referrals.codes')"
                        class="mt-4 block text-center text-sm text-purple-600 hover:text-purple-700"
                    >
                        Voir tous les codes &rarr;
                    </Link>
                </div>

                <!-- Recent Referrals -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center">
                        <UserGroupIcon class="h-5 w-5 mr-2 text-purple-500" />
                        Parrainages récents
                    </h3>

                    <div v-if="referrals.length === 0" class="text-center py-8 text-gray-500">
                        <UserGroupIcon class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                        <p>Aucun parrainage</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="referral in referrals.slice(0, 5)"
                            :key="referral.id"
                            class="flex items-center justify-between p-3 border border-gray-100 rounded-xl"
                        >
                            <div>
                                <p class="font-medium text-gray-900">{{ referral.referee?.name }}</p>
                                <p class="text-xs text-gray-500">
                                    par {{ referral.referrer?.name || 'Code promo' }}
                                </p>
                            </div>
                            <span :class="statusColors[referral.status]" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ statusLabels[referral.status] }}
                            </span>
                        </div>
                    </div>

                    <Link
                        :href="route('tenant.referrals.rewards')"
                        class="mt-4 block text-center text-sm text-purple-600 hover:text-purple-700"
                    >
                        Voir les récompenses &rarr;
                    </Link>
                </div>
            </div>
        </div>

        <!-- Settings Modal -->
        <div v-if="showSettingsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto p-4">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-2xl my-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Paramètres du programme</h3>

                <form @submit.prevent="saveSettings" class="space-y-6">
                    <!-- Active Toggle -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-medium text-gray-900">Programme actif</p>
                            <p class="text-sm text-gray-500">Activer le programme de parrainage</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                        </label>
                    </div>

                    <!-- Referrer Reward -->
                    <div class="border rounded-xl p-4">
                        <h4 class="font-medium text-gray-900 mb-4">Récompense Parrain</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select v-model="form.referrer_reward_type" class="w-full border border-gray-300 rounded-xl px-4 py-2">
                                    <option v-for="type in rewardTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valeur</label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        v-model="form.referrer_reward_value"
                                        min="0"
                                        step="0.01"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2 pr-10"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                                        {{ rewardTypes.find(t => t.value === form.referrer_reward_type)?.suffix }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referee Reward -->
                    <div class="border rounded-xl p-4">
                        <h4 class="font-medium text-gray-900 mb-4">Récompense Filleul</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select v-model="form.referee_reward_type" class="w-full border border-gray-300 rounded-xl px-4 py-2">
                                    <option v-for="type in rewardTypes" :key="type.value" :value="type.value">
                                        {{ type.label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Valeur</label>
                                <div class="relative">
                                    <input
                                        type="number"
                                        v-model="form.referee_reward_value"
                                        min="0"
                                        step="0.01"
                                        class="w-full border border-gray-300 rounded-xl px-4 py-2 pr-10"
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">
                                        {{ rewardTypes.find(t => t.value === form.referee_reward_type)?.suffix }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rules -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Délai validation (jours)</label>
                            <input
                                type="number"
                                v-model="form.reward_delay_days"
                                min="0"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Min. mois location</label>
                            <input
                                type="number"
                                v-model="form.min_rental_months"
                                min="0"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2"
                            />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            id="require_active"
                            v-model="form.require_active_contract"
                            class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                        />
                        <label for="require_active" class="ml-2 text-sm text-gray-700">
                            Le parrain doit avoir un contrat actif
                        </label>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button
                            type="button"
                            @click="showSettingsModal = false"
                            class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 disabled:opacity-50"
                        >
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
