<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    MagnifyingGlassIcon,
    EyeIcon,
    TrashIcon,
    FunnelIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    logs: Object,
    stats: Object,
    actions: Array,
    tenants: Array,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const tenantId = ref(props.filters?.tenant_id || '')
const action = ref(props.filters?.action || '')
const severity = ref(props.filters?.severity || '')
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const applyFilters = () => {
    router.get(route('superadmin.activity-logs.index'), {
        search: search.value,
        tenant_id: tenantId.value,
        action: action.value,
        severity: severity.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    tenantId.value = ''
    action.value = ''
    severity.value = ''
    dateFrom.value = ''
    dateTo.value = ''
    applyFilters()
}

const getSeverityColor = (sev) => {
    const colors = {
        info: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        warning: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        error: 'bg-red-500/10 text-red-400 border-red-500/20',
        critical: 'bg-red-600/20 text-red-500 border-red-600/30',
    }
    return colors[sev] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getSeverityIcon = (sev) => {
    if (sev === 'error' || sev === 'critical') return XCircleIcon
    if (sev === 'warning') return ExclamationTriangleIcon
    return InformationCircleIcon
}

const formatDate = (date) => {
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const clearOldLogs = () => {
    if (confirm('Supprimer les logs de plus de 90 jours ?')) {
        router.post(route('superadmin.activity-logs.clear-old'), { days: 90 })
    }
}
</script>

<template>
    <Head title="Journal d'Activité" />

    <SuperAdminLayout title="Journal d'Activité">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Journal d'Activité</h1>
                    <p class="mt-1 text-sm text-gray-400">Suivez toutes les actions du système</p>
                </div>
                <button
                    @click="clearOldLogs"
                    class="px-4 py-2 text-sm bg-red-600/20 text-red-400 hover:bg-red-600/30 rounded-lg transition-colors"
                >
                    Nettoyer les anciens logs
                </button>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total.toLocaleString() }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Aujourd'hui</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.today }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Erreurs (7j)</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.errors }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Warnings (7j)</div>
                    <div class="mt-1 text-2xl font-semibold text-yellow-400">{{ stats.warnings }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                    <div class="sm:col-span-2">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                    <select v-model="tenantId" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Tous les tenants</option>
                        <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                    </select>
                    <select v-model="action" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Toutes les actions</option>
                        <option v-for="act in actions" :key="act" :value="act">{{ act }}</option>
                    </select>
                    <select v-model="severity" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Toutes sévérités</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="error">Error</option>
                        <option value="critical">Critical</option>
                    </select>
                    <button @click="clearFilters" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Sévérité</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Tenant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getSeverityColor(log.severity), 'px-2 py-1 text-xs rounded-full border inline-flex items-center gap-1']">
                                    <component :is="getSeverityIcon(log.severity)" class="h-3 w-3" />
                                    {{ log.severity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ log.action }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-300 max-w-md truncate">
                                {{ log.description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ log.user?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ log.tenant?.name || '-' }}
                            </td>
                        </tr>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                                Aucun log trouvé
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            {{ logs.from }} à {{ logs.to }} sur {{ logs.total }}
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in logs.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    link.active ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                                    'px-3 py-1 text-sm rounded',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
