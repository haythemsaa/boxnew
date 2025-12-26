<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { LockClosedIcon, LockOpenIcon, PlusIcon, PencilIcon, TrashIcon, BoltIcon, Battery50Icon } from '@heroicons/vue/24/outline';

const props = defineProps({
    locks: Object,
    sites: Array,
    filters: Object,
});

const selectedSite = ref(props.filters?.site_id || '');

const filterBySite = () => {
    router.get(route('tenant.iot.locks.index'), {
        site_id: selectedSite.value || undefined,
    }, { preserveState: true });
};

const unlockLock = (lock) => {
    if (confirm('Deverrouiller cette serrure ?')) {
        router.post(route('tenant.iot.locks.unlock', lock.id));
    }
};

const lockLock = (lock) => {
    router.post(route('tenant.iot.locks.lock', lock.id));
};

const deleteLock = (lock) => {
    if (confirm('Supprimer cette serrure ? Cette action est irreversible.')) {
        router.delete(route('tenant.iot.locks.destroy', lock.id));
    }
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
    const labels = {
        noke: 'Noke',
        salto: 'Salto KS',
        kisi: 'Kisi',
        pti: 'PTI',
        generic: 'Generique',
    };
    return labels[provider] || provider;
};
</script>

<template>
    <TenantLayout title="Serrures Connectees">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Serrures Connectees</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gerez vos serrures intelligentes</p>
                </div>
                <Link :href="route('tenant.iot.locks.create')" class="btn-primary">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Ajouter une serrure
                </Link>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4">
                <div class="flex items-center gap-4">
                    <div class="flex-1 max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                        <select v-model="selectedSite" @change="filterBySite" class="input">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                    </div>
                    <div class="text-sm text-gray-500 mt-6">
                        {{ locks.total }} serrure(s)
                    </div>
                </div>
            </div>

            <!-- Locks Grid -->
            <div v-if="locks.data?.length" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="lock in locks.data" :key="lock.id"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                                 :class="lock.status === 'active' ? 'bg-green-100 dark:bg-green-900' : 'bg-gray-100 dark:bg-gray-700'">
                                <LockClosedIcon class="w-6 h-6" :class="lock.status === 'active' ? 'text-green-600' : 'text-gray-500'" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ lock.device_name }}</p>
                                <p class="text-sm text-gray-500">{{ lock.box?.box_number }} - {{ lock.box?.site?.name }}</p>
                            </div>
                        </div>
                        <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(lock.status)]">
                            {{ getStatusLabel(lock.status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-4 text-sm">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                            <p class="text-gray-500 text-xs">Fournisseur</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ getProviderLabel(lock.provider) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                            <p class="text-gray-500 text-xs">ID Appareil</p>
                            <p class="font-medium text-gray-900 dark:text-white truncate" :title="lock.device_id">{{ lock.device_id }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                            <p class="text-gray-500 text-xs">Batterie</p>
                            <p class="font-medium flex items-center gap-1" :class="getBatteryColor(lock.battery_level)">
                                <Battery50Icon class="w-4 h-4" />
                                {{ lock.battery_level }}%
                            </p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2">
                            <p class="text-gray-500 text-xs">Derniere activite</p>
                            <p class="font-medium text-gray-900 dark:text-white text-xs">
                                {{ lock.last_seen_at ? new Date(lock.last_seen_at).toLocaleString('fr-FR') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <button @click="unlockLock(lock)"
                                :disabled="lock.status !== 'active'"
                                class="btn-sm btn-success flex-1 justify-center"
                                :class="{ 'opacity-50 cursor-not-allowed': lock.status !== 'active' }">
                            <LockOpenIcon class="w-4 h-4 mr-1" />
                            Deverrouiller
                        </button>
                        <Link :href="route('tenant.iot.locks.edit', lock.id)" class="btn-sm btn-secondary">
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button @click="deleteLock(lock)" class="btn-sm btn-danger">
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-12 text-center">
                <LockClosedIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                <p class="text-gray-500 mb-4">Aucune serrure configuree</p>
                <Link :href="route('tenant.iot.locks.create')" class="btn-primary inline-flex items-center">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Ajouter une serrure
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="locks.last_page > 1" class="flex justify-center gap-2">
                <Link v-for="page in locks.last_page" :key="page"
                      :href="route('tenant.iot.locks.index', { page, site_id: selectedSite })"
                      :class="['px-3 py-1 rounded', page === locks.current_page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200']">
                    {{ page }}
                </Link>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.btn-sm { @apply px-3 py-1.5 text-sm rounded-lg font-medium transition inline-flex items-center; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium inline-flex items-center; }
.btn-secondary { @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300; }
.btn-success { @apply bg-green-600 text-white hover:bg-green-700; }
.btn-danger { @apply bg-red-600 text-white hover:bg-red-700; }
</style>
