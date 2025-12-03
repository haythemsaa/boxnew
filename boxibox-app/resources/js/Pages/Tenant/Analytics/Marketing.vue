<template>
    <TenantLayout title="Analytics - Marketing">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-pink-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <MegaphoneIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Marketing & Ventes</h1>
                        <p class="text-pink-100 mt-1">Génération de leads, tunnel de conversion et acquisition clients</p>
                    </div>
                </div>

                <!-- Lead Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-pink-200 text-sm">Total Prospects</p>
                                <p class="text-3xl font-bold text-white mt-1">{{ analytics.leads.total }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <UsersIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-pink-200 text-sm">Convertis</p>
                                <p class="text-3xl font-bold text-emerald-300 mt-1">{{ analytics.leads.converted }}</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-400/30 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-pink-200 text-sm">Taux de conversion</p>
                                <p class="text-3xl font-bold text-blue-300 mt-1">{{ analytics.leads.conversion_rate.toFixed(1) }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-400/30 rounded-xl flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-blue-300" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-5 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-pink-200 text-sm">Nouveaux clients</p>
                                <p class="text-3xl font-bold text-purple-300 mt-1">{{ analytics.customers.new }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-400/30 rounded-xl flex items-center justify-center">
                                <UserPlusIcon class="w-6 h-6 text-purple-300" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Customer Value Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <CurrencyEuroIcon class="w-6 h-6" />
                        </div>
                        <span class="text-white/80 text-sm font-medium">Valeur Client (LTV)</span>
                    </div>
                    <p class="text-4xl font-bold">{{ formatCurrency(analytics.value.ltv) }}</p>
                    <p class="text-white/70 text-sm mt-2">Valeur vie client moyenne</p>
                </div>

                <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <BanknotesIcon class="w-6 h-6" />
                        </div>
                        <span class="text-white/80 text-sm font-medium">Coût Acquisition (CAC)</span>
                    </div>
                    <p class="text-4xl font-bold">{{ formatCurrency(analytics.value.cac) }}</p>
                    <p class="text-white/70 text-sm mt-2">Coût par nouveau client</p>
                </div>

                <div class="bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <ArrowTrendingUpIcon class="w-6 h-6" />
                        </div>
                        <span class="text-white/80 text-sm font-medium">Ratio LTV / CAC</span>
                    </div>
                    <p class="text-4xl font-bold">{{ analytics.value.ltv_cac_ratio.toFixed(1) }}x</p>
                    <p class="text-white/70 text-sm mt-2">Objectif : > 3x</p>
                </div>
            </div>

            <!-- Conversion Funnel -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <FunnelIcon class="w-5 h-5 text-pink-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Tunnel de Conversion</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        <div v-for="(step, index) in funnelSteps" :key="index">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center space-x-3">
                                    <div :class="[
                                        'w-8 h-8 rounded-lg flex items-center justify-center text-sm font-bold',
                                        index === 0 ? 'bg-pink-100 text-pink-600' :
                                        index === 1 ? 'bg-purple-100 text-purple-600' :
                                        index === 2 ? 'bg-blue-100 text-blue-600' :
                                        'bg-emerald-100 text-emerald-600'
                                    ]">
                                        {{ index + 1 }}
                                    </div>
                                    <span class="font-semibold text-gray-900">{{ step.label }}</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-lg font-bold text-gray-900">{{ step.count }}</span>
                                    <span class="px-2 py-1 bg-gray-100 rounded-lg text-xs font-medium text-gray-600">{{ step.rate.toFixed(1) }}%</span>
                                </div>
                            </div>
                            <div class="relative">
                                <div class="w-full bg-gray-100 rounded-full h-10">
                                    <div
                                        :class="[
                                            'h-10 rounded-full flex items-center justify-center text-white font-semibold text-sm transition-all duration-500',
                                            index === 0 ? 'bg-gradient-to-r from-pink-500 to-rose-500' :
                                            index === 1 ? 'bg-gradient-to-r from-purple-500 to-indigo-500' :
                                            index === 2 ? 'bg-gradient-to-r from-blue-500 to-cyan-500' :
                                            'bg-gradient-to-r from-emerald-500 to-teal-500'
                                        ]"
                                        :style="{width: step.width + '%', minWidth: step.width > 0 ? '60px' : '0'}"
                                    >
                                        {{ step.width.toFixed(0) }}%
                                    </div>
                                </div>
                            </div>
                            <div v-if="index < funnelSteps.length - 1" class="text-xs text-gray-500 mt-1 flex items-center justify-end">
                                <ArrowDownIcon class="w-3 h-3 mr-1" />
                                Perte : {{ ((step.count - funnelSteps[index + 1].count) / step.count * 100).toFixed(1) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Performance -->
            <div v-if="campaigns && campaigns.length > 0" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <RocketLaunchIcon class="w-5 h-5 text-pink-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Performance des Campagnes</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Campagne</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Envoyés</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Taux ouverture</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Taux clic</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Conversion</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-for="campaign in campaigns" :key="campaign.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900">{{ campaign.name }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ campaign.sent }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-semibold',
                                        campaign.open_rate >= 25 ? 'bg-emerald-100 text-emerald-700' :
                                        campaign.open_rate >= 15 ? 'bg-amber-100 text-amber-700' :
                                        'bg-red-100 text-red-700'
                                    ]">
                                        {{ campaign.open_rate.toFixed(1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-semibold',
                                        campaign.click_rate >= 5 ? 'bg-emerald-100 text-emerald-700' :
                                        campaign.click_rate >= 2 ? 'bg-amber-100 text-amber-700' :
                                        'bg-red-100 text-red-700'
                                    ]">
                                        {{ campaign.click_rate.toFixed(1) }}%
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-lg text-xs font-semibold',
                                        campaign.conversion_rate >= 3 ? 'bg-emerald-100 text-emerald-700' :
                                        campaign.conversion_rate >= 1 ? 'bg-amber-100 text-amber-700' :
                                        'bg-red-100 text-red-700'
                                    ]">
                                        {{ campaign.conversion_rate.toFixed(1) }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed } from 'vue'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    MegaphoneIcon,
    UsersIcon,
    CheckCircleIcon,
    ChartBarIcon,
    UserPlusIcon,
    CurrencyEuroIcon,
    BanknotesIcon,
    ArrowTrendingUpIcon,
    FunnelIcon,
    RocketLaunchIcon,
    ArrowDownIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    analytics: Object,
    funnel: Object,
    campaigns: Array,
})

const formatCurrency = (num) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(num)
}

const funnelSteps = computed(() => {
    if (!props.funnel) return []

    const visitors = props.funnel.visitors || 0
    const maxWidth = 100

    return [
        {
            label: 'Visiteurs',
            count: visitors,
            rate: 100,
            width: maxWidth
        },
        {
            label: 'Prospects',
            count: props.funnel.leads || 0,
            rate: props.funnel.visitor_to_lead || 0,
            width: props.funnel.visitor_to_lead || 0
        },
        {
            label: 'Qualifiés',
            count: props.funnel.qualified || 0,
            rate: props.funnel.lead_to_qualified || 0,
            width: (props.funnel.lead_to_qualified / 100) * (props.funnel.visitor_to_lead || 0)
        },
        {
            label: 'Clients',
            count: props.funnel.customers || 0,
            rate: props.funnel.overall_conversion || 0,
            width: props.funnel.overall_conversion || 0
        }
    ]
})
</script>
