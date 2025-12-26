<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    ClockIcon,
    UserGroupIcon,
    CheckCircleIcon,
    XCircleIcon,
    BellIcon,
    FunnelIcon,
    PlusIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    entries: Object,
    stats: Object,
    filters: Object,
})

const statusFilter = ref(props.filters?.status || '')
const siteFilter = ref(props.filters?.site_id || '')

const statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: 'active', label: 'En attente' },
    { value: 'notified', label: 'Notifié' },
    { value: 'converted', label: 'Converti' },
    { value: 'expired', label: 'Expiré' },
    { value: 'cancelled', label: 'Annulé' },
]

const applyFilters = () => {
    router.get(route('tenant.waitlist.index'), {
        status: statusFilter.value || undefined,
        site_id: siteFilter.value || undefined,
    }, { preserveState: true })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        notified: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        converted: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        expired: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }
    return classes[status] || classes.active
}
</script>

<template>
    <Head title="Liste d'attente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Liste d'attente
                </h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('tenant.waitlist.settings')"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                    >
                        Paramètres
                    </Link>
                    <button
                        @click="$inertia.visit(route('tenant.waitlist.index'), { data: { create: true } })"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                    >
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Ajouter
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                                <ClockIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">En attente</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.total_active }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <BellIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Notifiés</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.total_notified }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <CheckCircleIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Convertis</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.total_converted }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-indigo-100 dark:bg-indigo-900">
                                <UserGroupIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Taux conversion</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.conversion_rate }}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6 p-4">
                    <div class="flex items-center space-x-4">
                        <FunnelIcon class="w-5 h-5 text-gray-400" />
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Entries Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Client
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Site / Box
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Critères
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Position
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="entry in entries.data" :key="entry.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ entry.customer_first_name }} {{ entry.customer_last_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ entry.customer_email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ entry.site?.name }}
                                    </div>
                                    <div v-if="entry.box" class="text-sm text-gray-500 dark:text-gray-400">
                                        Box {{ entry.box.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div v-if="entry.min_size || entry.max_size">
                                        {{ entry.min_size || 0 }} - {{ entry.max_size || '∞' }} m²
                                    </div>
                                    <div v-if="entry.max_price">
                                        Max {{ entry.max_price }}€/mois
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                        #{{ entry.position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(entry.status)]">
                                        {{ entry.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(entry.created_at).toLocaleDateString('fr-FR') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('tenant.waitlist.show', entry.id)"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                    >
                                        Voir
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="entries.data.length === 0" class="text-center py-12">
                        <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucune entrée dans la liste d'attente
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Les clients pourront s'inscrire depuis votre page de réservation.
                        </p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="entries.links" class="mt-4">
                    <!-- Pagination component here -->
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
