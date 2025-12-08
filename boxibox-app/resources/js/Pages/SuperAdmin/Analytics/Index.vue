<script setup>
import { Head } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    CurrencyEuroIcon,
    BuildingOffice2Icon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UserGroupIcon,
    CubeIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    kpis: Object,
    revenueByMonth: Array,
    tenantsByPlan: Array,
    newTenantsPerMonth: Array,
    topTenants: Array,
    geoDistribution: Array,
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatNumber = (value) => {
    return new Intl.NumberFormat('fr-FR').format(value || 0)
}

const getPlanColor = (plan) => {
    const colors = {
        'Basic': 'bg-gray-500',
        'Pro': 'bg-purple-500',
        'Enterprise': 'bg-amber-500',
    }
    return colors[plan] || 'bg-gray-500'
}

const maxRevenue = Math.max(...(props.revenueByMonth?.map(r => r.revenue) || [1]))
const maxNewTenants = Math.max(...(props.newTenantsPerMonth?.map(t => t.count) || [1]))
</script>

<template>
    <Head title="Analytics Plateforme" />

    <SuperAdminLayout title="Analytics">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-white">Analytics Plateforme</h1>
                <p class="mt-1 text-sm text-gray-400">Vue d'ensemble des métriques clés de la plateforme</p>
            </div>

            <!-- Main KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- MRR -->
                <div class="bg-gradient-to-br from-green-600/20 to-green-600/5 rounded-xl p-6 border border-green-600/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">MRR</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ formatCurrency(kpis.mrr) }}</p>
                        </div>
                        <CurrencyEuroIcon class="h-10 w-10 text-green-400/50" />
                    </div>
                </div>

                <!-- ARR -->
                <div class="bg-gradient-to-br from-blue-600/20 to-blue-600/5 rounded-xl p-6 border border-blue-600/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">ARR</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ formatCurrency(kpis.arr) }}</p>
                        </div>
                        <ArrowTrendingUpIcon class="h-10 w-10 text-blue-400/50" />
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-gradient-to-br from-purple-600/20 to-purple-600/5 rounded-xl p-6 border border-purple-600/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Revenus totaux</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ formatCurrency(kpis.total_revenue) }}</p>
                        </div>
                        <ChartBarIcon class="h-10 w-10 text-purple-400/50" />
                    </div>
                </div>

                <!-- Churn Rate -->
                <div class="bg-gradient-to-br from-red-600/20 to-red-600/5 rounded-xl p-6 border border-red-600/20">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-400">Taux de churn</p>
                            <p class="text-2xl font-bold text-white mt-1">{{ kpis.churn_rate }}%</p>
                        </div>
                        <ArrowTrendingDownIcon class="h-10 w-10 text-red-400/50" />
                    </div>
                </div>
            </div>

            <!-- Second Row KPIs -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Tenants -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <BuildingOffice2Icon class="h-8 w-8 text-purple-400" />
                        <div class="ml-4">
                            <p class="text-sm text-gray-400">Tenants actifs</p>
                            <p class="text-2xl font-bold text-white">{{ kpis.active_tenants }} / {{ kpis.total_tenants }}</p>
                        </div>
                    </div>
                </div>

                <!-- LTV -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <UserGroupIcon class="h-8 w-8 text-cyan-400" />
                        <div class="ml-4">
                            <p class="text-sm text-gray-400">LTV moyen</p>
                            <p class="text-2xl font-bold text-white">{{ formatCurrency(kpis.ltv) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Total Boxes -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <CubeIcon class="h-8 w-8 text-amber-400" />
                        <div class="ml-4">
                            <p class="text-sm text-gray-400">Boxes total</p>
                            <p class="text-2xl font-bold text-white">{{ formatNumber(kpis.total_boxes) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Occupancy Rate -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <ChartBarIcon class="h-8 w-8 text-green-400" />
                        <div class="ml-4">
                            <p class="text-sm text-gray-400">Taux d'occupation</p>
                            <p class="text-2xl font-bold text-white">{{ kpis.occupancy_rate }}%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Revenue Chart -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Évolution des revenus (12 mois)</h3>
                    <div class="h-64 flex items-end justify-around gap-1">
                        <div
                            v-for="(item, index) in revenueByMonth"
                            :key="index"
                            class="flex flex-col items-center flex-1"
                        >
                            <div
                                class="w-full bg-gradient-to-t from-green-600 to-green-400 rounded-t transition-all"
                                :style="{ height: `${Math.max(8, (item.revenue / maxRevenue) * 200)}px` }"
                            ></div>
                            <span class="text-[10px] text-gray-500 mt-2 transform -rotate-45 origin-left whitespace-nowrap">
                                {{ item.month }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- New Tenants Chart -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Nouveaux tenants (12 mois)</h3>
                    <div class="h-64 flex items-end justify-around gap-1">
                        <div
                            v-for="(item, index) in newTenantsPerMonth"
                            :key="index"
                            class="flex flex-col items-center flex-1"
                        >
                            <div
                                class="w-full bg-gradient-to-t from-purple-600 to-purple-400 rounded-t transition-all"
                                :style="{ height: `${Math.max(8, (item.count / maxNewTenants) * 200)}px` }"
                            ></div>
                            <span class="text-[10px] text-gray-500 mt-2 transform -rotate-45 origin-left whitespace-nowrap">
                                {{ item.month }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Charts -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Tenants by Plan -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Répartition par plan</h3>
                    <div class="space-y-4">
                        <div v-for="item in tenantsByPlan" :key="item.plan" class="flex items-center">
                            <span class="w-24 text-sm text-gray-400">{{ item.plan }}</span>
                            <div class="flex-1 h-8 bg-gray-700 rounded-full overflow-hidden mx-4">
                                <div
                                    :class="getPlanColor(item.plan)"
                                    class="h-full rounded-full transition-all flex items-center justify-end pr-2"
                                    :style="{ width: `${Math.max(10, (item.count / kpis.total_tenants) * 100)}%` }"
                                >
                                    <span class="text-xs text-white font-medium">{{ item.count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Geographic Distribution -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Répartition géographique</h3>
                    <div class="space-y-3">
                        <div v-for="item in geoDistribution" :key="item.country" class="flex items-center justify-between">
                            <span class="text-sm text-gray-300">{{ item.country }}</span>
                            <div class="flex items-center gap-2">
                                <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-blue-500 rounded-full"
                                        :style="{ width: `${(item.count / kpis.total_tenants) * 100}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm text-gray-400 w-8 text-right">{{ item.count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Tenants -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Top 10 Tenants par revenus</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Tenant</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Plan</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-400 uppercase">Clients</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-400 uppercase">Contrats</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-400 uppercase">Revenus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="(tenant, index) in topTenants" :key="tenant.id">
                                <td class="px-4 py-3 text-sm text-gray-400">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-sm text-white font-medium">{{ tenant.name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-purple-500/10 text-purple-400 uppercase">
                                        {{ tenant.plan }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-300 text-center">{{ tenant.customers }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300 text-center">{{ tenant.contracts }}</td>
                                <td class="px-4 py-3 text-sm text-green-400 text-right font-medium">{{ formatCurrency(tenant.revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
