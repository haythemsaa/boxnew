<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { GlobeAltIcon, UserPlusIcon, CurrencyEuroIcon, ChartBarIcon, ArrowTrendingUpIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    integrations: Array,
    statistics: Object,
    statsByPlatform: Array,
    platforms: Object,
    dateRange: Object,
});

const getPlatformIcon = (platform) => {
    const icons = {
        sparefoot: '/images/platforms/sparefoot.png',
        selfstorage: '/images/platforms/selfstorage.png',
        google_business: '/images/platforms/google.png',
        jestocke: '/images/platforms/jestocke.png',
        costockage: '/images/platforms/costockage.png',
    };
    return icons[platform] || null;
};

const syncIntegration = (id) => {
    router.post(route('tenant.marketplaces.integrations.sync', id));
};
</script>

<template>
    <TenantLayout title="Marketplaces">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketplaces</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gérez vos intégrations et leads des plateformes</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('tenant.marketplaces.leads')" class="btn-secondary">
                        <UserPlusIcon class="w-5 h-5 mr-2" />
                        Leads
                    </Link>
                    <Link :href="route('tenant.marketplaces.settings')" class="btn-primary">
                        <Cog6ToothIcon class="w-5 h-5 mr-2" />
                        Paramètres
                    </Link>
                </div>
            </div>

            <!-- Statistics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <UserPlusIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.total_leads || 0 }}</p>
                            <p class="text-sm text-gray-500">Total leads</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                            <UserPlusIcon class="w-5 h-5 text-yellow-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.new_leads || 0 }}</p>
                            <p class="text-sm text-gray-500">Nouveaux</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <ChartBarIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.converted_leads || 0 }}</p>
                            <p class="text-sm text-gray-500">Convertis</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <ArrowTrendingUpIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.conversion_rate || 0 }}%</p>
                            <p class="text-sm text-gray-500">Conversion</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <CurrencyEuroIcon class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics?.total_cost || 0).toLocaleString() }}€</p>
                            <p class="text-sm text-gray-500">Coût</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                            <CurrencyEuroIcon class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics?.total_revenue || 0).toLocaleString() }}€</p>
                            <p class="text-sm text-gray-500">Revenus</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Integrations -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Intégrations actives</h2>
                </div>
                <div v-if="integrations?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="integration in integrations" :key="integration.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <GlobeAltIcon class="w-6 h-6 text-gray-400" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ platforms[integration.platform] || integration.platform }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ integration.listings_count }} annonces · {{ integration.leads_count }} leads
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <span :class="[
                                'px-2 py-1 rounded-full text-xs font-medium',
                                integration.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">
                                {{ integration.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <span v-if="integration.last_sync_at" class="text-sm text-gray-500">
                                Sync: {{ new Date(integration.last_sync_at).toLocaleString('fr-FR') }}
                            </span>
                            <button @click="syncIntegration(integration.id)" class="btn-sm btn-secondary">
                                Synchroniser
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <GlobeAltIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucune intégration configurée</p>
                    <Link :href="route('tenant.marketplaces.settings')" class="text-blue-600 hover:underline mt-2 inline-block">
                        Ajouter une intégration
                    </Link>
                </div>
            </div>

            <!-- Stats by Platform -->
            <div v-if="statsByPlatform?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Performance par plateforme</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Plateforme</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Leads</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Convertis</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Coût</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Revenus</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ROI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="stat in statsByPlatform" :key="stat.platform">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ platforms[stat.platform] || stat.platform }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ stat.total_leads }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ stat.converted }}</td>
                                <td class="px-6 py-4 text-right text-gray-600 dark:text-gray-400">{{ (stat.total_cost || 0).toLocaleString() }}€</td>
                                <td class="px-6 py-4 text-right text-green-600">{{ (stat.total_revenue || 0).toLocaleString() }}€</td>
                                <td class="px-6 py-4 text-right">
                                    <span :class="stat.total_revenue > stat.total_cost ? 'text-green-600' : 'text-red-600'">
                                        {{ stat.total_cost > 0 ? Math.round(((stat.total_revenue - stat.total_cost) / stat.total_cost) * 100) : 0 }}%
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

<style scoped>
@reference "tailwindcss";
.btn-sm { @apply px-3 py-1.5 text-sm rounded-lg font-medium transition inline-flex items-center; }
.btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700; }
.btn-secondary { @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300; }
</style>
