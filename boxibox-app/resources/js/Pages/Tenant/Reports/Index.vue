<template>
    <TenantLayout title="Rapports" :breadcrumbs="[{ label: 'Rapports' }]">
        <div class="space-y-6">
            <!-- Quick Reports -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Link :href="route('tenant.reports.rent-roll')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
                            <TableCellsIcon class="w-6 h-6 text-blue-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-blue-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Rent Roll</h3>
                    <p class="text-sm text-gray-500 mt-1">Liste des contrats actifs et revenus</p>
                </Link>

                <Link :href="route('tenant.reports.occupancy')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
                            <ChartPieIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-green-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Occupation</h3>
                    <p class="text-sm text-gray-500 mt-1">Taux d'occupation par site</p>
                </Link>

                <Link :href="route('tenant.reports.revenue')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
                            <CurrencyEuroIcon class="w-6 h-6 text-purple-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-purple-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Revenus</h3>
                    <p class="text-sm text-gray-500 mt-1">Analyse des revenus mensuels</p>
                </Link>

                <Link :href="route('tenant.reports.cash-flow')" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-100 rounded-xl group-hover:bg-orange-200 transition-colors">
                            <ArrowTrendingUpIcon class="w-6 h-6 text-orange-600" />
                        </div>
                        <ArrowRightIcon class="w-5 h-5 text-gray-400 group-hover:text-orange-600 transition-colors" />
                    </div>
                    <h3 class="font-semibold text-gray-900">Trésorerie</h3>
                    <p class="text-sm text-gray-500 mt-1">Prévisions de cash-flow</p>
                </Link>
            </div>

            <!-- Custom Reports -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Mes rapports personnalisés</h3>
                    <Link :href="route('tenant.reports.create')" class="btn-primary">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouveau rapport
                    </Link>
                </div>

                <div v-if="reports.length === 0" class="px-6 py-12 text-center">
                    <DocumentChartBarIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun rapport personnalisé</p>
                    <Link :href="route('tenant.reports.create')" class="text-primary-600 hover:text-primary-800 text-sm mt-2 inline-block">
                        Créer mon premier rapport
                    </Link>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="report in reports" :key="report.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <DocumentChartBarIcon class="w-5 h-5 text-gray-600" />
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">{{ report.name }}</h4>
                                <p class="text-sm text-gray-500">{{ report.type_label }} - {{ report.description || 'Pas de description' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="report.is_favorite" class="text-yellow-500">
                                <StarIcon class="w-5 h-5 fill-current" />
                            </span>
                            <Link :href="route('tenant.reports.show', report.id)" class="btn-secondary text-sm">
                                Voir
                            </Link>
                            <Link :href="route('tenant.reports.export', report.id) + '?format=csv'" class="text-gray-500 hover:text-gray-700">
                                <ArrowDownTrayIcon class="w-5 h-5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scheduled Reports -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Rapports programmés</h3>
                </div>

                <div v-if="scheduledReports.length === 0" class="px-6 py-8 text-center">
                    <ClockIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucun rapport programmé</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="scheduled in scheduledReports" :key="scheduled.id" class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <h4 class="font-medium text-gray-900">{{ scheduled.name }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ scheduled.frequency_label }} - Prochaine exécution: {{ formatDate(scheduled.next_run_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="scheduled.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ scheduled.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    TableCellsIcon,
    ChartPieIcon,
    CurrencyEuroIcon,
    ArrowTrendingUpIcon,
    ArrowRightIcon,
    PlusIcon,
    DocumentChartBarIcon,
    StarIcon,
    ArrowDownTrayIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'

defineProps({
    reports: Array,
    scheduledReports: Array,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>
