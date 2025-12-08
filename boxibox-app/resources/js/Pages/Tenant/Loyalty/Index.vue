<template>
    <TenantLayout title="Programme de fidélité" :breadcrumbs="[{ label: 'Fidélité' }]">
        <div class="space-y-6">
            <!-- Program Status -->
            <div v-if="!program" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                <GiftIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Programme de fidélité non configuré</h3>
                <p class="text-gray-500 mb-6">Créez un programme de fidélité pour récompenser vos clients</p>
                <Link :href="route('tenant.loyalty.settings')" class="btn-primary">
                    Configurer le programme
                </Link>
            </div>

            <template v-else>
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Membres</p>
                                <p class="text-3xl font-bold text-gray-900">{{ stats.total_members }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-xl">
                                <UsersIcon class="w-6 h-6 text-blue-600" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Points émis</p>
                                <p class="text-3xl font-bold text-green-600">{{ formatNumber(stats.total_points_issued) }}</p>
                            </div>
                            <div class="p-3 bg-green-100 rounded-xl">
                                <SparklesIcon class="w-6 h-6 text-green-600" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Points échangés</p>
                                <p class="text-3xl font-bold text-purple-600">{{ formatNumber(stats.total_points_redeemed) }}</p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-xl">
                                <GiftIcon class="w-6 h-6 text-purple-600" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Récompenses actives</p>
                                <p class="text-3xl font-bold text-orange-600">{{ stats.active_rewards }}</p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-xl">
                                <TrophyIcon class="w-6 h-6 text-orange-600" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Program Info & Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Program Details -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900">{{ program.name }}</h3>
                            <span :class="program.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ program.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Points par euro</span>
                                <span class="font-medium">{{ program.points_per_euro }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Min. pour échange</span>
                                <span class="font-medium">{{ program.min_points_redeem }} pts</span>
                            </div>
                            <div v-if="program.welcome_bonus" class="flex justify-between">
                                <span class="text-gray-500">Bonus bienvenue</span>
                                <span class="font-medium">{{ program.welcome_bonus }} pts</span>
                            </div>
                            <div v-if="program.referral_bonus" class="flex justify-between">
                                <span class="text-gray-500">Bonus parrainage</span>
                                <span class="font-medium">{{ program.referral_bonus }} pts</span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <Link :href="route('tenant.loyalty.settings')" class="text-primary-600 hover:text-primary-800 text-sm">
                                Modifier les paramètres
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Actions rapides</h3>
                        <div class="space-y-3">
                            <Link :href="route('tenant.loyalty.members')" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <UsersIcon class="w-5 h-5 text-gray-500 mr-3" />
                                <span class="text-sm font-medium text-gray-700">Gérer les membres</span>
                            </Link>
                            <Link :href="route('tenant.loyalty.rewards')" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <GiftIcon class="w-5 h-5 text-gray-500 mr-3" />
                                <span class="text-sm font-medium text-gray-700">Gérer les récompenses</span>
                            </Link>
                            <Link :href="route('tenant.loyalty.redemptions')" class="flex items-center p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                <ReceiptRefundIcon class="w-5 h-5 text-gray-500 mr-3" />
                                <span class="text-sm font-medium text-gray-700">Voir les échanges</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Tier Breakdown -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Niveaux</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-amber-600"></div>
                                    <span class="text-sm text-gray-700">Bronze</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ program.tier_thresholds?.bronze || 0 }}+ pts</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                    <span class="text-sm text-gray-700">Argent</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ program.tier_thresholds?.silver || 500 }}+ pts</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <span class="text-sm text-gray-700">Or</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ program.tier_thresholds?.gold || 2000 }}+ pts</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-indigo-600"></div>
                                    <span class="text-sm text-gray-700">Platine</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900">{{ program.tier_thresholds?.platinum || 5000 }}+ pts</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Members -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Top membres</h3>
                        <Link :href="route('tenant.loyalty.members')" class="text-primary-600 hover:text-primary-800 text-sm">
                            Voir tous
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-if="!topMembers || topMembers.length === 0" class="px-6 py-8 text-center text-gray-500">
                            Aucun membre pour le moment
                        </div>
                        <div v-else v-for="(member, index) in topMembers" :key="member.id" class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="index < 3 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'">
                                    {{ index + 1 }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ member.customer?.first_name }} {{ member.customer?.last_name }}</p>
                                    <p class="text-sm text-gray-500">{{ member.tier }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">{{ formatNumber(member.total_points_earned) }} pts</p>
                                <p class="text-xs text-gray-500">{{ formatNumber(member.points_balance) }} disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    GiftIcon,
    UsersIcon,
    SparklesIcon,
    TrophyIcon,
    ReceiptRefundIcon,
} from '@heroicons/vue/24/outline'

defineProps({
    program: Object,
    stats: Object,
    topMembers: Array,
})

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num || 0)
}
</script>
