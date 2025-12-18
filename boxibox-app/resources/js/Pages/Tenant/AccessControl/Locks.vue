<template>
    <TenantLayout title="Gestion des Smart Locks">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Smart Locks</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Gestion et surveillance des serrures connectées
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('tenant.access-control.index')"
                              class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ← Retour Dashboard
                        </Link>
                        <button @click="showAddLockModal = true"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajouter une serrure
                        </button>
                    </div>
                </div>

                <!-- Status Overview -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.total }}</div>
                        <div class="text-sm text-gray-500">Total</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.online }}</div>
                        <div class="text-sm text-gray-500">En ligne</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ stats.offline }}</div>
                        <div class="text-sm text-gray-500">Hors ligne</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.low_battery }}</div>
                        <div class="text-sm text-gray-500">Batterie faible</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ stats.needs_attention }}</div>
                        <div class="text-sm text-gray-500">À vérifier</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <input v-model="filters.search"
                               type="text"
                               placeholder="Rechercher..."
                               class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <select v-model="filters.site"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                        </select>
                        <select v-model="filters.status"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les statuts</option>
                            <option value="online">En ligne</option>
                            <option value="offline">Hors ligne</option>
                            <option value="low_battery">Batterie faible</option>
                        </select>
                        <div class="flex gap-2 ml-auto">
                            <button @click="viewMode = 'grid'"
                                    :class="['p-2 rounded', viewMode === 'grid' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600']">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                                </svg>
                            </button>
                            <button @click="viewMode = 'list'"
                                    :class="['p-2 rounded', viewMode === 'list' ? 'bg-blue-100 text-blue-600' : 'text-gray-400 hover:text-gray-600']">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="lock in filteredLocks"
                         :key="lock.id"
                         class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition">
                        <!-- Lock Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Box {{ lock.box_number }}</h3>
                                <p class="text-sm text-gray-500">{{ lock.site_name }}</p>
                            </div>
                            <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(lock)]">
                                {{ getStatusLabel(lock) }}
                            </span>
                        </div>

                        <!-- Lock Info -->
                        <div class="p-4 space-y-3">
                            <!-- Battery -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    Batterie
                                </span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div :class="['h-full', getBatteryColor(lock.battery_level)]"
                                             :style="{width: lock.battery_level + '%'}"></div>
                                    </div>
                                    <span :class="['text-sm font-medium', lock.battery_level <= 20 ? 'text-red-600' : 'text-gray-700 dark:text-gray-300']">
                                        {{ lock.battery_level }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Signal -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                                    </svg>
                                    Signal
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ lock.signal_strength || '-' }} dBm</span>
                            </div>

                            <!-- Last Activity -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Dernière activité
                                </span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ lock.last_activity || 'Jamais' }}</span>
                            </div>

                            <!-- Firmware -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Firmware</span>
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ lock.firmware_version }}
                                    <span v-if="lock.update_available" class="ml-1 text-blue-600 text-xs">(MAJ dispo)</span>
                                </span>
                            </div>
                        </div>

                        <!-- Lock Actions -->
                        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 flex justify-between items-center">
                            <div class="flex gap-2">
                                <button @click="lockAction(lock, 'unlock')"
                                        :disabled="lock.status === 'offline'"
                                        class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                    </svg>
                                    Ouvrir
                                </button>
                                <button @click="lockAction(lock, 'lock')"
                                        :disabled="lock.status === 'offline'"
                                        class="px-3 py-1.5 bg-gray-600 text-white rounded text-sm hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    Verrouiller
                                </button>
                            </div>
                            <button @click="showLockDetails(lock)"
                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- List View -->
                <div v-else class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Box</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Site</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Batterie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Dernière activité</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="lock in filteredLocks" :key="lock.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Box {{ lock.box_number }}</div>
                                    <div class="text-xs text-gray-500">{{ lock.serial_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ lock.site_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs rounded-full', getStatusClass(lock)]">
                                        {{ getStatusLabel(lock) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-2 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                                            <div :class="['h-full', getBatteryColor(lock.battery_level)]"
                                                 :style="{width: lock.battery_level + '%'}"></div>
                                        </div>
                                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ lock.battery_level }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ lock.last_activity || 'Jamais' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="lockAction(lock, 'unlock')"
                                                :disabled="lock.status === 'offline'"
                                                class="px-3 py-1 bg-green-600 text-white rounded text-xs hover:bg-green-700 disabled:opacity-50">
                                            Ouvrir
                                        </button>
                                        <button @click="showLockDetails(lock)"
                                                class="px-3 py-1 bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded text-xs hover:bg-gray-300 dark:hover:bg-gray-500">
                                            Détails
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="filteredLocks.length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucune serrure trouvée</h3>
                    <p class="mt-1 text-sm text-gray-500">Commencez par ajouter une serrure connectée.</p>
                </div>
            </div>
        </div>

        <!-- Lock Details Modal -->
        <div v-if="selectedLock" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="selectedLock = null">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Box {{ selectedLock.box_number }}</h3>
                    <button @click="selectedLock = null" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Numéro de série</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ selectedLock.serial_number }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Modèle</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ selectedLock.model || 'Standard' }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Firmware</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ selectedLock.firmware_version }}</p>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Installation</span>
                            <p class="font-medium text-gray-900 dark:text-white">{{ selectedLock.installed_at || 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="font-medium mb-3">Actions de maintenance</h4>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                Mettre à jour le firmware
                            </button>
                            <button class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm hover:bg-yellow-700">
                                Redémarrer
                            </button>
                            <button class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                                Désactiver
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    locks: Array,
    sites: Array,
    stats: Object,
});

const viewMode = ref('grid');
const showAddLockModal = ref(false);
const selectedLock = ref(null);

const filters = ref({
    search: '',
    site: '',
    status: '',
});

const filteredLocks = computed(() => {
    let result = props.locks || [];

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        result = result.filter(lock =>
            lock.box_number?.toLowerCase().includes(search) ||
            lock.serial_number?.toLowerCase().includes(search) ||
            lock.site_name?.toLowerCase().includes(search)
        );
    }

    if (filters.value.site) {
        result = result.filter(lock => lock.site_id == filters.value.site);
    }

    if (filters.value.status) {
        if (filters.value.status === 'low_battery') {
            result = result.filter(lock => lock.battery_level <= 20);
        } else {
            result = result.filter(lock => lock.status === filters.value.status);
        }
    }

    return result;
});

const getStatusClass = (lock) => {
    if (lock.status === 'offline') return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    if (lock.battery_level <= 20) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400';
    return 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400';
};

const getStatusLabel = (lock) => {
    if (lock.status === 'offline') return 'Hors ligne';
    if (lock.battery_level <= 20) return 'Batterie faible';
    return 'En ligne';
};

const getBatteryColor = (level) => {
    if (level <= 20) return 'bg-red-500';
    if (level <= 50) return 'bg-yellow-500';
    return 'bg-green-500';
};

const lockAction = (lock, action) => {
    router.post(route('tenant.smart-locks.action', lock.id), {
        action: action,
    }, {
        preserveScroll: true,
    });
};

const showLockDetails = (lock) => {
    selectedLock.value = lock;
};
</script>
