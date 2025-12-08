<script setup>
import { Head } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    BuildingOffice2Icon,
    UsersIcon,
    CubeIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CurrencyEuroIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: Object,
    revenueTrend: Array,
    tenantsGrowth: Array,
    topTenants: Array,
    recentTenants: Array,
    recentUsers: Array,
    systemHealth: Object,
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

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-500/10 text-green-400',
        trial: 'bg-blue-500/10 text-blue-400',
        suspended: 'bg-red-500/10 text-red-400',
        cancelled: 'bg-gray-500/10 text-gray-400',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400'
}
</script>

<template>
    <Head title="SuperAdmin Dashboard" />

    <SuperAdminLayout title="Dashboard">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-white">Dashboard SuperAdmin</h1>
                <p class="mt-1 text-sm text-gray-400">Vue d'ensemble de la plateforme Boxibox</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Total Tenants -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <BuildingOffice2Icon class="h-8 w-8 text-purple-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Total Tenants</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-white">{{ formatNumber(stats.total_tenants) }}</div>
                                    <span class="ml-2 text-sm text-green-400">{{ stats.active_tenants }} actifs</span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Total Users -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UsersIcon class="h-8 w-8 text-blue-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Utilisateurs</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatNumber(stats.total_users) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Total Boxes -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CubeIcon class="h-8 w-8 text-amber-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Boxes</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatNumber(stats.total_boxes) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Monthly Revenue -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CurrencyEuroIcon class="h-8 w-8 text-green-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Revenus (mois)</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatCurrency(stats.monthly_revenue) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Row Stats -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Customers -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <UserGroupIcon class="h-8 w-8 text-cyan-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Clients</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatNumber(stats.total_customers) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Active Contracts -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <DocumentTextIcon class="h-8 w-8 text-indigo-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Contrats actifs</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatNumber(stats.total_contracts) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 sm:col-span-2">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <CurrencyEuroIcon class="h-8 w-8 text-emerald-400" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-400 truncate">Revenus totaux</dt>
                                <dd class="text-2xl font-semibold text-white">{{ formatCurrency(stats.total_revenue) }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables Row -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Top Tenants -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Top Tenants par Revenus</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase">Tenant</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-400 uppercase">Status</th>
                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-400 uppercase">Revenus</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <tr v-for="tenant in topTenants" :key="tenant.id">
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <div class="text-sm font-medium text-white">{{ tenant.name }}</div>
                                        <div class="text-xs text-gray-400">{{ tenant.customers_count }} clients</div>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap">
                                        <span :class="[getStatusColor(tenant.status), 'px-2 py-1 text-xs rounded-full']">
                                            {{ tenant.status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3 whitespace-nowrap text-right text-sm text-white">
                                        {{ formatCurrency(tenant.revenue) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Santé du Système</h3>
                    <div class="space-y-4">
                        <div v-for="(value, key) in systemHealth" :key="key" class="flex items-center justify-between">
                            <div class="flex items-center">
                                <CheckCircleIcon v-if="value" class="h-5 w-5 text-green-400 mr-2" />
                                <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-400 mr-2" />
                                <span class="text-sm text-gray-300 capitalize">{{ key }}</span>
                            </div>
                            <span :class="value ? 'text-green-400' : 'text-red-400'" class="text-sm">
                                {{ value ? 'OK' : 'Erreur' }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-700">
                        <h4 class="text-sm font-medium text-gray-400 mb-3">Derniers tenants</h4>
                        <div class="space-y-2">
                            <div v-for="tenant in recentTenants" :key="tenant.id" class="flex items-center justify-between">
                                <span class="text-sm text-white">{{ tenant.name }}</span>
                                <span :class="[getStatusColor(tenant.status), 'px-2 py-0.5 text-xs rounded-full']">
                                    {{ tenant.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Trend Chart (placeholder) -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Évolution des Revenus (12 derniers mois)</h3>
                <div class="h-64 flex items-end justify-around gap-2">
                    <div
                        v-for="(item, index) in revenueTrend"
                        :key="index"
                        class="flex flex-col items-center flex-1"
                    >
                        <div
                            class="w-full bg-purple-600 rounded-t"
                            :style="{ height: `${Math.max(10, (item.revenue / Math.max(...revenueTrend.map(r => r.revenue || 1))) * 200)}px` }"
                        ></div>
                        <span class="text-xs text-gray-400 mt-2 rotate-45 origin-left">{{ item.month }}</span>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
