<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    PlusIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    CloudArrowUpIcon,
    CheckCircleIcon,
    XCircleIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    backups: Object,
    stats: Object,
    diskInfo: Object,
})

const selectedType = ref('database')

const createBackup = () => {
    router.post(route('superadmin.backups.create'), {
        type: selectedType.value,
    })
}

const downloadBackup = (backup) => {
    window.location.href = route('superadmin.backups.download', backup.id)
}

const deleteBackup = (backup) => {
    if (confirm('Supprimer ce backup ?')) {
        router.delete(route('superadmin.backups.destroy', backup.id))
    }
}

const cleanOldBackups = () => {
    if (confirm('Supprimer les backups de plus de 30 jours ?')) {
        router.post(route('superadmin.backups.clean-old'), { days: 30 })
    }
}

const getStatusIcon = (status) => {
    if (status === 'completed') return CheckCircleIcon
    if (status === 'failed') return XCircleIcon
    return ClockIcon
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'text-yellow-400',
        running: 'text-blue-400',
        completed: 'text-green-400',
        failed: 'text-red-400',
    }
    return colors[status] || 'text-gray-400'
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        running: 'En cours',
        completed: 'Terminé',
        failed: 'Échoué',
    }
    return labels[status] || status
}

const formatSize = (bytes) => {
    if (!bytes) return '-'
    const units = ['B', 'KB', 'MB', 'GB', 'TB']
    let i = 0
    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024
        i++
    }
    return `${bytes.toFixed(2)} ${units[i]}`
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

const diskUsagePercent = () => {
    if (!props.diskInfo?.total) return 0
    return Math.round((props.diskInfo.used / props.diskInfo.total) * 100)
}
</script>

<template>
    <Head title="Backups" />

    <SuperAdminLayout title="Backups">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Backups</h1>
                    <p class="mt-1 text-sm text-gray-400">Gérez les sauvegardes du système</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="cleanOldBackups"
                        class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                    >
                        Nettoyer anciens backups
                    </button>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="selectedType"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="database">Base de données</option>
                            <option value="files">Fichiers</option>
                            <option value="full">Complet</option>
                        </select>
                        <button
                            @click="createBackup"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Nouveau Backup
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total Backups</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Réussis</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.completed }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Échoués</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.failed }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Taille totale</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-400">{{ formatSize(stats.total_size) }}</div>
                </div>
            </div>

            <!-- Disk Info -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Espace disque</h2>
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="h-4 bg-gray-700 rounded-full overflow-hidden">
                            <div
                                :style="{ width: diskUsagePercent() + '%' }"
                                :class="[
                                    diskUsagePercent() > 90 ? 'bg-red-500' : diskUsagePercent() > 70 ? 'bg-yellow-500' : 'bg-green-500',
                                    'h-full transition-all'
                                ]"
                            ></div>
                        </div>
                    </div>
                    <div class="text-sm text-gray-400 whitespace-nowrap">
                        {{ formatSize(diskInfo?.used) }} / {{ formatSize(diskInfo?.total) }} ({{ diskUsagePercent() }}%)
                    </div>
                </div>
                <div class="mt-2 text-sm text-gray-500">
                    Espace libre: {{ formatSize(diskInfo?.free) }}
                </div>
            </div>

            <!-- Backups Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Taille</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Créé par</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Date</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="backup in backups.data" :key="backup.id" class="hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <CloudArrowUpIcon class="h-5 w-5 text-gray-400" />
                                    <span class="text-sm font-medium text-white">{{ backup.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs bg-gray-700 text-gray-300 rounded capitalize">
                                    {{ backup.type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ formatSize(backup.size) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <component :is="getStatusIcon(backup.status)" :class="['h-5 w-5', getStatusColor(backup.status)]" />
                                    <span :class="['text-sm', getStatusColor(backup.status)]">
                                        {{ getStatusLabel(backup.status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ backup.creator?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ formatDate(backup.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        v-if="backup.status === 'completed'"
                                        @click="downloadBackup(backup)"
                                        class="p-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                                        title="Télécharger"
                                    >
                                        <ArrowDownTrayIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteBackup(backup)"
                                        class="p-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="backups.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-500 mb-2" />
                                Aucun backup trouvé
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="backups.links && backups.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            {{ backups.from }} à {{ backups.to }} sur {{ backups.total }}
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in backups.links"
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
