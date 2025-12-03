<template>
    <TenantLayout title="Analytics - Opérations">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-orange-600 via-amber-600 to-orange-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                        <CogIcon class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-white">Analyse Opérationnelle</h1>
                        <p class="text-orange-100 mt-1">Efficacité opérationnelle et métriques de rentabilité</p>
                    </div>
                </div>

                <!-- Cost Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-200 text-sm font-medium">Dépenses Mensuelles</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(analytics.costs.total_expenses) }}</p>
                                <p class="text-orange-200 text-sm mt-1">{{ formatCurrency(analytics.costs.cost_per_unit) }} par unité</p>
                            </div>
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center">
                                <BanknotesIcon class="w-7 h-7 text-white" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-200 text-sm font-medium">Ratio de Dépenses</p>
                                <p class="text-3xl font-bold mt-2" :class="getExpenseRatioColor(analytics.costs.expense_ratio)">
                                    {{ analytics.costs.expense_ratio.toFixed(1) }}%
                                </p>
                                <p class="text-orange-200 text-sm mt-1">Objectif : 25-40%</p>
                            </div>
                            <div class="w-14 h-14 bg-amber-400/30 rounded-xl flex items-center justify-center">
                                <ChartPieIcon class="w-7 h-7 text-amber-200" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-6 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-200 text-sm font-medium">Coût par Unité</p>
                                <p class="text-3xl font-bold text-white mt-2">{{ formatCurrency(analytics.costs.cost_per_unit) }}</p>
                                <p class="text-orange-200 text-sm mt-1">Par box occupé</p>
                            </div>
                            <div class="w-14 h-14 bg-purple-400/30 rounded-xl flex items-center justify-center">
                                <CubeIcon class="w-7 h-7 text-purple-200" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Profitability Card -->
            <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <ArrowTrendingUpIcon class="w-6 h-6 text-white" />
                    </div>
                    <h2 class="text-2xl font-bold">Rentabilité</h2>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <p class="text-emerald-100 text-sm font-medium">Résultat Net d'Exploitation (NOI)</p>
                        <p class="text-5xl font-bold mt-2">{{ formatCurrency(analytics.profitability.noi) }}</p>
                        <p class="text-emerald-200 text-sm mt-2">Marge : {{ analytics.profitability.noi_margin.toFixed(1) }}%</p>
                    </div>
                    <div class="flex items-center justify-center">
                        <div class="text-center bg-white/10 rounded-2xl p-6 backdrop-blur-sm">
                            <div class="w-16 h-16 mx-auto rounded-xl flex items-center justify-center mb-3"
                                 :class="analytics.profitability.noi >= 0 ? 'bg-emerald-400/30' : 'bg-red-400/30'">
                                <ArrowTrendingUpIcon v-if="analytics.profitability.noi >= 0" class="w-8 h-8 text-emerald-200" />
                                <ArrowTrendingDownIcon v-else class="w-8 h-8 text-red-200" />
                            </div>
                            <p class="text-lg font-semibold">
                                {{ analytics.profitability.noi >= 0 ? 'Rentable' : 'À améliorer' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Efficiency -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <UsersIcon class="w-5 h-5 text-orange-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Efficacité du Personnel</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 text-center">
                            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <UserGroupIcon class="w-7 h-7 text-blue-600" />
                            </div>
                            <p class="text-4xl font-bold text-blue-600">{{ analytics.efficiency.staff_count }}</p>
                            <p class="text-sm font-medium text-gray-600 mt-2">Employés</p>
                        </div>

                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-6 text-center">
                            <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <CurrencyEuroIcon class="w-7 h-7 text-emerald-600" />
                            </div>
                            <p class="text-4xl font-bold text-emerald-600">{{ formatCurrency(analytics.efficiency.revenue_per_staff) }}</p>
                            <p class="text-sm font-medium text-gray-600 mt-2">Revenu par employé</p>
                        </div>

                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6 text-center">
                            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <Square3Stack3DIcon class="w-7 h-7 text-purple-600" />
                            </div>
                            <p class="text-4xl font-bold text-purple-600">{{ analytics.efficiency.units_per_staff.toFixed(1) }}</p>
                            <p class="text-sm font-medium text-gray-600 mt-2">Unités par employé</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Summary -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                    <ChartBarIcon class="w-5 h-5 text-orange-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Résumé des KPIs Opérationnels</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-semibold text-gray-900">Efficacité Opérationnelle</p>
                            <p class="text-sm text-gray-500">Plus bas = meilleur</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-32 bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="h-2.5 rounded-full transition-all duration-500"
                                    :class="getExpenseBarColor(analytics.costs.expense_ratio)"
                                    :style="{width: Math.min(analytics.costs.expense_ratio, 100) + '%'}"
                                ></div>
                            </div>
                            <span class="text-2xl font-bold" :class="getExpenseRatioTextColor(analytics.costs.expense_ratio)">
                                {{ analytics.costs.expense_ratio.toFixed(0) }}%
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-semibold text-gray-900">Marge Bénéficiaire</p>
                            <p class="text-sm text-gray-500">Plus haut = meilleur</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="w-32 bg-gray-200 rounded-full h-2.5">
                                <div
                                    class="bg-gradient-to-r from-emerald-500 to-teal-500 h-2.5 rounded-full transition-all duration-500"
                                    :style="{width: Math.min(analytics.profitability.noi_margin, 100) + '%'}"
                                ></div>
                            </div>
                            <span class="text-2xl font-bold text-emerald-600">
                                {{ analytics.profitability.noi_margin.toFixed(1) }}%
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-semibold text-gray-900">Productivité du Personnel</p>
                            <p class="text-sm text-gray-500">Génération de revenus par employé</p>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">
                            {{ formatCurrency(analytics.efficiency.revenue_per_staff) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden border border-amber-100">
                <div class="px-6 py-4 border-b border-amber-100 flex items-center">
                    <LightBulbIcon class="w-5 h-5 text-amber-600 mr-2" />
                    <h2 class="text-lg font-semibold text-gray-900">Recommandations d'Optimisation</h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div v-if="analytics.costs.expense_ratio > 40" class="flex items-start p-4 bg-red-50 rounded-xl border border-red-100">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-red-900">Ratio de dépenses élevé</p>
                                <p class="text-sm text-red-700 mt-1">Le ratio de dépenses dépasse l'objectif (40%). Examinez les coûts opérationnels pour améliorer la rentabilité.</p>
                            </div>
                        </div>

                        <div v-if="analytics.efficiency.units_per_staff > 30" class="flex items-start p-4 bg-amber-50 rounded-xl border border-amber-100">
                            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ExclamationTriangleIcon class="w-5 h-5 text-amber-600" />
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-amber-900">Charge de travail élevée</p>
                                <p class="text-sm text-amber-700 mt-1">Plus de 30 unités par employé. Envisagez de recruter pour maintenir la qualité du service client.</p>
                            </div>
                        </div>

                        <div v-if="analytics.profitability.noi_margin < 20" class="flex items-start p-4 bg-orange-50 rounded-xl border border-orange-100">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <ExclamationTriangleIcon class="w-5 h-5 text-orange-600" />
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-orange-900">Marge bénéficiaire faible</p>
                                <p class="text-sm text-orange-700 mt-1">Marge inférieure à 20%. Concentrez-vous sur l'optimisation des revenus et la réduction des coûts.</p>
                            </div>
                        </div>

                        <div v-if="analytics.costs.expense_ratio <= 40 && analytics.profitability.noi_margin >= 20" class="flex items-start p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <CheckCircleIcon class="w-5 h-5 text-emerald-600" />
                            </div>
                            <div class="ml-4">
                                <p class="font-semibold text-emerald-900">Excellente performance</p>
                                <p class="text-sm text-emerald-700 mt-1">Performance opérationnelle excellente ! Maintenez les niveaux d'efficacité actuels.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CogIcon,
    BanknotesIcon,
    ChartPieIcon,
    CubeIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    UsersIcon,
    UserGroupIcon,
    CurrencyEuroIcon,
    Square3Stack3DIcon,
    ChartBarIcon,
    LightBulbIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    analytics: Object,
})

const formatCurrency = (num) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0,
    }).format(num)
}

const getExpenseRatioColor = (ratio) => {
    if (ratio <= 25) return 'text-emerald-300'
    if (ratio <= 40) return 'text-amber-300'
    return 'text-red-300'
}

const getExpenseRatioTextColor = (ratio) => {
    if (ratio <= 25) return 'text-emerald-600'
    if (ratio <= 40) return 'text-amber-600'
    return 'text-red-600'
}

const getExpenseBarColor = (ratio) => {
    if (ratio <= 25) return 'bg-gradient-to-r from-emerald-500 to-teal-500'
    if (ratio <= 40) return 'bg-gradient-to-r from-amber-500 to-orange-500'
    return 'bg-gradient-to-r from-red-500 to-rose-500'
}
</script>
