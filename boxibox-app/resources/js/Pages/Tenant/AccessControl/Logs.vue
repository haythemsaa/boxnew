<template>
    <TenantLayout title="Logs d'Accès">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Logs d'Accès</h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Historique complet des tentatives d'accès
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <Link :href="route('tenant.access-control.index')"
                              class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            ← Retour Dashboard
                        </Link>
                        <button @click="exportLogs"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Exporter
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recherche</label>
                            <input v-model="filters.search"
                                   type="text"
                                   placeholder="Client, Box, Code..."
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                            <select v-model="filters.site"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut</label>
                            <select v-model="filters.status"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous</option>
                                <option value="granted">Autorisé</option>
                                <option value="denied">Refusé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Méthode</label>
                            <select v-model="filters.method"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="">Toutes</option>
                                <option value="code">Code PIN</option>
                                <option value="qr">QR Code</option>
                                <option value="badge">Badge RFID</option>
                                <option value="app">Application</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Période</label>
                            <select v-model="filters.period"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                                <option value="today">Aujourd'hui</option>
                                <option value="week">7 derniers jours</option>
                                <option value="month">30 derniers jours</option>
                                <option value="all">Tout</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-between items-center mt-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="filters.suspicious_only" type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Activité suspecte uniquement</span>
                        </label>
                        <button @click="resetFilters" class="text-sm text-blue-600 hover:text-blue-700">
                            Réinitialiser les filtres
                        </button>
                    </div>
                </div>

                <!-- Stats Summary -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                        <div class="text-sm text-gray-500">Total accès</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.granted }}</div>
                        <div class="text-sm text-gray-500">Autorisés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-red-600">{{ stats.denied }}</div>
                        <div class="text-sm text-gray-500">Refusés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 text-center">
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.suspicious }}</div>
                        <div class="text-sm text-gray-500">Suspects</div>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date/Heure</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Box</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Méthode</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Détails</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="log in logs.data" :key="log.id" :class="log.is_suspicious ? 'bg-red-50 dark:bg-red-900/20' : ''">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    <div>{{ formatDate(log.created_at) }}</div>
                                    <div class="text-xs text-gray-500">{{ formatTime(log.created_at) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ log.customer_name || 'Inconnu' }}</div>
                                    <div class="text-xs text-gray-500">{{ log.customer_email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ log.box_number }}</div>
                                    <div class="text-xs text-gray-500">{{ log.site_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full" :class="getMethodClass(log.method)">
                                        {{ getMethodLabel(log.method) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full" :class="log.status === 'granted' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'">
                                        {{ log.status === 'granted' ? 'Autorisé' : 'Refusé' }}
                                    </span>
                                    <span v-if="log.is_suspicious" class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400 rounded-full">
                                        ⚠️ Suspect
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ log.reason || '-' }}
                                </td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    Aucun log d'accès trouvé
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                Affichage de {{ logs.from }} à {{ logs.to }} sur {{ logs.total }} résultats
                            </div>
                            <div class="flex gap-2">
                                <Link v-if="logs.prev_page_url"
                                      :href="logs.prev_page_url"
                                      class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                                    Précédent
                                </Link>
                                <Link v-if="logs.next_page_url"
                                      :href="logs.next_page_url"
                                      class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 text-sm">
                                    Suivant
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    logs: Object,
    sites: Array,
    stats: Object,
    filters: Object,
});

const filters = ref({
    search: props.filters?.search || '',
    site: props.filters?.site || '',
    status: props.filters?.status || '',
    method: props.filters?.method || '',
    period: props.filters?.period || 'week',
    suspicious_only: props.filters?.suspicious_only || false,
});

const applyFilters = () => {
    router.get(route('tenant.access-control.logs.index'), {
        ...filters.value,
    }, {
        preserveState: true,
        replace: true,
    });
};

watch(filters, () => {
    applyFilters();
}, { deep: true });

const resetFilters = () => {
    filters.value = {
        search: '',
        site: '',
        status: '',
        method: '',
        period: 'week',
        suspicious_only: false,
    };
};

const exportLogs = () => {
    window.location.href = route('tenant.access-control.logs.export', filters.value);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getMethodClass = (method) => {
    const classes = {
        code: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400',
        qr: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-400',
        badge: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-400',
        app: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-400',
    };
    return classes[method] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getMethodLabel = (method) => {
    const labels = {
        code: 'Code PIN',
        qr: 'QR Code',
        badge: 'Badge RFID',
        app: 'Application',
    };
    return labels[method] || method;
};
</script>
