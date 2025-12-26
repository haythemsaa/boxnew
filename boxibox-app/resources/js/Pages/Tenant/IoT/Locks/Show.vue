<script setup>
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, LockClosedIcon, LockOpenIcon, PencilIcon, Battery50Icon, WifiIcon, ClockIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    lock: Object,
    accessLogs: Array,
    providers: Object,
});

const unlockLock = () => {
    if (confirm('Deverrouiller cette serrure ?')) {
        router.post(route('tenant.iot.locks.unlock', props.lock.id));
    }
};

const lockLock = () => {
    router.post(route('tenant.iot.locks.lock', props.lock.id));
};

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        offline: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        inactive: 'Inactif',
        offline: 'Hors ligne',
    };
    return labels[status] || status;
};

const getBatteryColor = (level) => {
    if (level > 50) return 'text-green-600';
    if (level > 20) return 'text-yellow-600';
    return 'text-red-600';
};

const getProviderLabel = (provider) => {
    return props.providers[provider] || provider;
};

const getActionLabel = (action) => {
    const labels = {
        unlock: 'Deverrouillage',
        lock: 'Verrouillage',
        access_denied: 'Acces refuse',
        battery_low: 'Batterie faible',
    };
    return labels[action] || action;
};

const getLogStatusColor = (status) => {
    const colors = {
        success: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        pending: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
    <TenantLayout :title="`Serrure ${lock.device_name}`">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('tenant.iot.locks.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                             :class="lock.status === 'active' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-100 dark:bg-gray-700'">
                            <LockClosedIcon class="w-6 h-6" :class="lock.status === 'active' ? 'text-green-600' : 'text-gray-500'" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ lock.device_name }}</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusColor(lock.status)]">
                                    {{ getStatusLabel(lock.status) }}
                                </span>
                                <span class="text-sm text-gray-500">{{ lock.box?.box_number }} - {{ lock.box?.site?.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="unlockLock" class="btn-success" :disabled="lock.status !== 'active'">
                        <LockOpenIcon class="w-5 h-5 mr-2" />
                        Deverrouiller
                    </button>
                    <Link :href="route('tenant.iot.locks.edit', lock.id)" class="btn-secondary">
                        <PencilIcon class="w-5 h-5 mr-2" />
                        Modifier
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <LockClosedIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Fournisseur</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ getProviderLabel(lock.provider) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <Battery50Icon class="w-5 h-5" :class="getBatteryColor(lock.battery_level)" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Batterie</p>
                            <p class="font-semibold" :class="getBatteryColor(lock.battery_level)">{{ lock.battery_level }}%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <WifiIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">ID Appareil</p>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm truncate" :title="lock.device_id">
                                {{ lock.device_id }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                            <ClockIcon class="w-5 h-5 text-orange-600" />
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Derniere activite</p>
                            <p class="font-semibold text-gray-900 dark:text-white text-sm">
                                {{ lock.last_seen_at ? new Date(lock.last_seen_at).toLocaleString('fr-FR') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lock Details -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-900 dark:text-white mb-4">Informations detaillees</h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500">Box associe</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ lock.box?.display_name || lock.box?.box_number || 'Non associe' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Site</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ lock.box?.site?.name || 'N/A' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Type de serrure</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ getProviderLabel(lock.provider) }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500">Identifiant unique</p>
                            <p class="font-medium text-gray-900 dark:text-white font-mono text-sm">{{ lock.device_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Statut</p>
                            <span :class="['px-3 py-1 rounded-full text-sm font-medium', getStatusColor(lock.status)]">
                                {{ getStatusLabel(lock.status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Cree le</p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ new Date(lock.created_at).toLocaleDateString('fr-FR') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Logs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Historique des acces</h2>
                    <p class="text-sm text-gray-500 mt-1">Les 50 derniers evenements</p>
                </div>

                <div v-if="accessLogs?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="log in accessLogs" :key="log.id" class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                 :class="log.status === 'success' ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'">
                                <LockOpenIcon v-if="log.action === 'unlock'" class="w-5 h-5"
                                              :class="log.status === 'success' ? 'text-green-600' : 'text-red-600'" />
                                <LockClosedIcon v-else class="w-5 h-5"
                                                :class="log.status === 'success' ? 'text-green-600' : 'text-red-600'" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ getActionLabel(log.action) }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ log.customer?.name || 'Systeme' }} -
                                    {{ log.accessed_at ? new Date(log.accessed_at).toLocaleString('fr-FR') : 'N/A' }}
                                </p>
                            </div>
                        </div>
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', getLogStatusColor(log.status)]">
                            {{ log.status === 'success' ? 'Succes' : log.status === 'failed' ? 'Echec' : log.status }}
                        </span>
                    </div>
                </div>

                <div v-else class="p-12 text-center">
                    <ClockIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun historique d'acces</p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.btn-success { @apply px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 font-medium inline-flex items-center; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium inline-flex items-center; }
</style>
