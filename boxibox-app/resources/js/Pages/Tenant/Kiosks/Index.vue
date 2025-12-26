<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ComputerDesktopIcon, UserGroupIcon, CurrencyEuroIcon, ChartBarIcon, ExclamationTriangleIcon, CheckCircleIcon, PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    kiosks: Array,
    statistics: Object,
    funnel: Object,
    openIssues: Array,
    dateRange: Object,
});

const getStatusColor = (status) => {
    const colors = {
        online: 'bg-green-100 text-green-800',
        offline: 'bg-red-100 text-red-800',
        maintenance: 'bg-yellow-100 text-yellow-800',
        idle: 'bg-blue-100 text-blue-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        online: 'En ligne',
        offline: 'Hors ligne',
        maintenance: 'Maintenance',
        idle: 'Inactif',
    };
    return labels[status] || status;
};

const resolveIssue = (issueId) => {
    router.post(route('tenant.kiosks.issues.resolve', issueId));
};
</script>

<template>
    <TenantLayout title="Kiosques">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kiosques Self-Service</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gérez vos bornes interactives en libre-service</p>
                </div>
                <div class="flex gap-3">
                    <Link :href="route('tenant.kiosks.issues')" class="btn-secondary">
                        <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                        Problèmes
                    </Link>
                    <Link :href="route('tenant.kiosks.analytics')" class="btn-secondary">
                        <ChartBarIcon class="w-5 h-5 mr-2" />
                        Analytics
                    </Link>
                    <Link :href="route('tenant.kiosks.create')" class="btn-primary">
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Nouveau kiosque
                    </Link>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <ComputerDesktopIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ kiosks?.length || 0 }}</p>
                            <p class="text-sm text-gray-500">Kiosques</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <CheckCircleIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ kiosks?.filter(k => k.status === 'online').length || 0 }}</p>
                            <p class="text-sm text-gray-500">En ligne</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <UserGroupIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.total_sessions || 0 }}</p>
                            <p class="text-sm text-gray-500">Sessions</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                            <ChartBarIcon class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.completed_rentals || 0 }}</p>
                            <p class="text-sm text-gray-500">Locations</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <CurrencyEuroIcon class="w-5 h-5 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics?.total_revenue || 0).toLocaleString() }}€</p>
                            <p class="text-sm text-gray-500">Revenus</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                            <ExclamationTriangleIcon class="w-5 h-5 text-orange-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ openIssues?.length || 0 }}</p>
                            <p class="text-sm text-gray-500">Problèmes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Conversion Funnel -->
            <div v-if="funnel" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Entonnoir de conversion</h2>
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1 text-center">
                        <div class="h-24 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mb-2" :style="{ opacity: 1 }">
                            <span class="text-2xl font-bold text-blue-600">{{ funnel.total_sessions || 0 }}</span>
                        </div>
                        <p class="text-sm text-gray-500">Sessions</p>
                    </div>
                    <div class="text-gray-300">→</div>
                    <div class="flex-1 text-center">
                        <div class="h-24 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mb-2" :style="{ opacity: funnel.total_sessions ? funnel.browsed / funnel.total_sessions : 0.5 }">
                            <span class="text-2xl font-bold text-purple-600">{{ funnel.browsed || 0 }}</span>
                        </div>
                        <p class="text-sm text-gray-500">Navigation</p>
                    </div>
                    <div class="text-gray-300">→</div>
                    <div class="flex-1 text-center">
                        <div class="h-24 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mb-2" :style="{ opacity: funnel.total_sessions ? funnel.started_rental / funnel.total_sessions : 0.3 }">
                            <span class="text-2xl font-bold text-indigo-600">{{ funnel.started_rental || 0 }}</span>
                        </div>
                        <p class="text-sm text-gray-500">Début location</p>
                    </div>
                    <div class="text-gray-300">→</div>
                    <div class="flex-1 text-center">
                        <div class="h-24 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mb-2" :style="{ opacity: funnel.total_sessions ? funnel.completed / funnel.total_sessions : 0.2 }">
                            <span class="text-2xl font-bold text-green-600">{{ funnel.completed || 0 }}</span>
                        </div>
                        <p class="text-sm text-gray-500">Complété</p>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <span class="text-sm text-gray-500">Taux de conversion: </span>
                    <span class="font-semibold text-green-600">{{ funnel.total_sessions ? Math.round((funnel.completed / funnel.total_sessions) * 100) : 0 }}%</span>
                </div>
            </div>

            <!-- Kiosks Grid -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Vos kiosques</h2>
                </div>
                <div v-if="kiosks?.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                    <div v-for="kiosk in kiosks" :key="kiosk.id" class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <ComputerDesktopIcon class="w-6 h-6 text-gray-500" />
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ kiosk.name }}</p>
                                    <p class="text-sm text-gray-500">{{ kiosk.site?.name }}</p>
                                </div>
                            </div>
                            <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(kiosk.status)]">
                                {{ getStatusLabel(kiosk.status) }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-sm mb-4">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ kiosk.sessions_today || 0 }}</p>
                                <p class="text-xs text-gray-500">Sessions</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ kiosk.rentals_today || 0 }}</p>
                                <p class="text-xs text-gray-500">Locations</p>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ (kiosk.revenue_today || 0).toLocaleString() }}€</p>
                                <p class="text-xs text-gray-500">Revenus</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Link :href="route('tenant.kiosks.show', kiosk.id)" class="btn-sm btn-secondary flex-1 justify-center">
                                Détails
                            </Link>
                            <Link :href="route('tenant.kiosks.edit', kiosk.id)" class="btn-sm btn-primary flex-1 justify-center">
                                Configurer
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <ComputerDesktopIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 mb-4">Aucun kiosque configuré</p>
                    <Link :href="route('tenant.kiosks.create')" class="btn-primary inline-flex items-center">
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Ajouter un kiosque
                    </Link>
                </div>
            </div>

            <!-- Open Issues -->
            <div v-if="openIssues?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <ExclamationTriangleIcon class="w-5 h-5 text-orange-500" />
                        Problèmes ouverts
                    </h2>
                    <Link :href="route('tenant.kiosks.issues')" class="text-blue-600 hover:underline text-sm">
                        Voir tout
                    </Link>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="issue in openIssues.slice(0, 5)" :key="issue.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div :class="[
                                'w-2 h-2 rounded-full',
                                issue.severity === 'critical' ? 'bg-red-500' :
                                issue.severity === 'high' ? 'bg-orange-500' :
                                issue.severity === 'medium' ? 'bg-yellow-500' : 'bg-blue-500'
                            ]"></div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ issue.title }}</p>
                                <p class="text-sm text-gray-500">{{ issue.kiosk?.name }} · {{ new Date(issue.created_at).toLocaleDateString('fr-FR') }}</p>
                            </div>
                        </div>
                        <button @click="resolveIssue(issue.id)" class="btn-sm btn-success">
                            Résoudre
                        </button>
                    </div>
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
.btn-success { @apply bg-green-600 text-white hover:bg-green-700; }
</style>
