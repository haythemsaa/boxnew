<template>
    <TenantLayout :title="`Logs - ${webhook.name}`">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <Link
                        href="/tenant/integrations/webhooks"
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux webhooks
                    </Link>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <span class="text-3xl">📋</span>
                        Logs du webhook
                    </h1>
                    <p class="text-gray-600 mt-1">{{ webhook.name }} - {{ webhook.url }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="refresh"
                        :disabled="loading"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Actualiser
                    </button>
                    <Link
                        :href="`/tenant/integrations/webhooks/${webhook.id}/edit`"
                        class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        Modifier le webhook
                    </Link>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-sm text-gray-500">Total appels</p>
                    <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-sm text-gray-500">Succes</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.success }}</p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-sm text-gray-500">Echecs</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-sm text-gray-500">Taux de succes</p>
                    <p class="text-2xl font-bold" :class="stats.success_rate >= 95 ? 'text-green-600' : stats.success_rate >= 80 ? 'text-amber-600' : 'text-red-600'">
                        {{ stats.success_rate }}%
                    </p>
                </div>
                <div class="bg-white rounded-xl border p-4">
                    <p class="text-sm text-gray-500">Temps moyen</p>
                    <p class="text-2xl font-bold text-gray-900">{{ stats.avg_response_time }}ms</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Rechercher par event ou payload..."
                            class="w-full px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                        />
                    </div>
                    <select
                        v-model="filters.status"
                        class="px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="success">Succes</option>
                        <option value="failed">Echec</option>
                        <option value="pending">En attente</option>
                    </select>
                    <select
                        v-model="filters.event"
                        class="px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="">Tous les evenements</option>
                        <option v-for="event in availableEvents" :key="event" :value="event">
                            {{ formatEventName(event) }}
                        </option>
                    </select>
                    <select
                        v-model="filters.period"
                        class="px-4 py-2 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="24h">Dernieres 24h</option>
                        <option value="7d">7 derniers jours</option>
                        <option value="30d">30 derniers jours</option>
                        <option value="all">Tout</option>
                    </select>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Evenement
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code HTTP
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Temps
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="log in filteredLogs"
                                :key="log.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="getStatusClass(log.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="getStatusDotClass(log.status)"></span>
                                        {{ getStatusLabel(log.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs font-mono">
                                        {{ log.event }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="font-mono text-sm"
                                        :class="log.http_code >= 200 && log.http_code < 300 ? 'text-green-600' : 'text-red-600'"
                                    >
                                        {{ log.http_code || '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ log.response_time_ms }}ms
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ formatDate(log.created_at) }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewDetails(log)"
                                            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                                        >
                                            Details
                                        </button>
                                        <button
                                            v-if="log.status === 'failed'"
                                            @click="retryDelivery(log)"
                                            :disabled="retrying === log.id"
                                            class="text-sm text-amber-600 hover:text-amber-700 font-medium disabled:opacity-50"
                                        >
                                            {{ retrying === log.id ? 'Retry...' : 'Retry' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <span class="text-3xl">📭</span>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Aucun log</h3>
                                    <p class="text-gray-500">Aucune livraison de webhook trouvee</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="pagination.total > pagination.per_page" class="px-4 py-3 border-t bg-gray-50 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Affichage {{ pagination.from }} - {{ pagination.to }} sur {{ pagination.total }}
                    </p>
                    <div class="flex items-center gap-2">
                        <button
                            @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1.5 border rounded-lg hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Precedent
                        </button>
                        <button
                            @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1.5 border rounded-lg hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Suivant
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details Modal -->
            <Teleport to="body">
                <div v-if="selectedLog" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="selectedLog = null"></div>
                    <div class="relative bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <!-- Modal Header -->
                        <div class="p-6 border-b flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Details de la livraison</h3>
                                <p class="text-sm text-gray-500">{{ formatDate(selectedLog.created_at) }}</p>
                            </div>
                            <button
                                @click="selectedLog = null"
                                class="p-2 rounded-lg hover:bg-gray-100"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                            <!-- Status Banner -->
                            <div
                                class="p-4 rounded-lg flex items-center gap-3"
                                :class="selectedLog.status === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'"
                            >
                                <span class="text-2xl">{{ selectedLog.status === 'success' ? '✅' : '❌' }}</span>
                                <div>
                                    <p class="font-medium" :class="selectedLog.status === 'success' ? 'text-green-800' : 'text-red-800'">
                                        {{ selectedLog.status === 'success' ? 'Livraison reussie' : 'Livraison echouee' }}
                                    </p>
                                    <p class="text-sm" :class="selectedLog.status === 'success' ? 'text-green-600' : 'text-red-600'">
                                        HTTP {{ selectedLog.http_code }} - {{ selectedLog.response_time_ms }}ms
                                    </p>
                                </div>
                            </div>

                            <!-- Info Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-500 mb-1">Evenement</p>
                                    <p class="font-mono text-sm">{{ selectedLog.event }}</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-500 mb-1">Tentative</p>
                                    <p class="font-medium">{{ selectedLog.attempt }} / {{ webhook.retry_count + 1 }}</p>
                                </div>
                            </div>

                            <!-- Request -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Requete envoyee</h4>
                                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                    <pre class="text-sm text-green-400 font-mono">{{ formatJson(selectedLog.payload) }}</pre>
                                </div>
                            </div>

                            <!-- Response -->
                            <div>
                                <h4 class="font-medium text-gray-900 mb-2">Reponse recue</h4>
                                <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                                    <pre class="text-sm text-blue-400 font-mono">{{ selectedLog.response_body || 'Aucune reponse' }}</pre>
                                </div>
                            </div>

                            <!-- Error -->
                            <div v-if="selectedLog.error">
                                <h4 class="font-medium text-gray-900 mb-2">Erreur</h4>
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <p class="text-sm text-red-700 font-mono">{{ selectedLog.error }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="p-6 border-t bg-gray-50 flex justify-end gap-3">
                            <button
                                v-if="selectedLog.status === 'failed'"
                                @click="retryDelivery(selectedLog); selectedLog = null;"
                                class="px-4 py-2 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 font-medium"
                            >
                                Reessayer
                            </button>
                            <button
                                @click="selectedLog = null"
                                class="px-4 py-2 bg-gray-100 rounded-lg hover:bg-gray-200 font-medium"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    webhook: {
        type: Object,
        required: true,
    },
    logs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({
            total: 0,
            success: 0,
            failed: 0,
            success_rate: 100,
            avg_response_time: 0,
        }),
    },
    pagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
});

const loading = ref(false);
const retrying = ref(null);
const selectedLog = ref(null);

const filters = reactive({
    search: '',
    status: '',
    event: '',
    period: '7d',
});

const availableEvents = computed(() => {
    return [...new Set(props.logs.map(l => l.event))];
});

const filteredLogs = computed(() => {
    return props.logs.filter(log => {
        if (filters.status && log.status !== filters.status) return false;
        if (filters.event && log.event !== filters.event) return false;
        if (filters.search) {
            const term = filters.search.toLowerCase();
            const matchEvent = log.event.toLowerCase().includes(term);
            const matchPayload = JSON.stringify(log.payload).toLowerCase().includes(term);
            if (!matchEvent && !matchPayload) return false;
        }
        return true;
    });
});

function formatEventName(event) {
    return event.split('.').pop().replace(/_/g, ' ');
}

function formatDate(date) {
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function formatJson(obj) {
    try {
        return JSON.stringify(obj, null, 2);
    } catch {
        return obj;
    }
}

function getStatusClass(status) {
    const classes = {
        success: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
        pending: 'bg-amber-100 text-amber-700',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
}

function getStatusDotClass(status) {
    const classes = {
        success: 'bg-green-500',
        failed: 'bg-red-500',
        pending: 'bg-amber-500',
    };
    return classes[status] || 'bg-gray-500';
}

function getStatusLabel(status) {
    const labels = {
        success: 'Succes',
        failed: 'Echec',
        pending: 'En attente',
    };
    return labels[status] || status;
}

function viewDetails(log) {
    selectedLog.value = log;
}

function refresh() {
    loading.value = true;
    router.reload({
        only: ['logs', 'stats'],
        onFinish: () => {
            loading.value = false;
        },
    });
}

async function retryDelivery(log) {
    retrying.value = log.id;
    try {
        const response = await fetch(`/tenant/integrations/webhooks/${props.webhook.id}/deliveries/${log.id}/retry`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const result = await response.json();
        if (result.success) {
            alert('Webhook renvoye avec succes');
            refresh();
        } else {
            alert('Echec: ' + (result.error || 'Erreur inconnue'));
        }
    } catch (e) {
        alert('Erreur lors du renvoi');
    } finally {
        retrying.value = null;
    }
}

function goToPage(page) {
    router.get(window.location.pathname, { page }, {
        preserveState: true,
        only: ['logs', 'pagination'],
    });
}
</script>

<style scoped>
.bg-primary-600 {
    background-color: #8FBD56;
}
</style>
