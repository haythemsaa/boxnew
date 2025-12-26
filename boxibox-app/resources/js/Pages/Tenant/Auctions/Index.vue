<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    CurrencyEuroIcon,
    ClockIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    BanknotesIcon,
    FunnelIcon,
    Cog6ToothIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auctions: Object,
    stats: Object,
    filters: Object,
})

const statusFilter = ref(props.filters?.status || '')

const statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: 'pending', label: 'En attente' },
    { value: 'notice_sent', label: 'Avis envoyé' },
    { value: 'scheduled', label: 'Programmée' },
    { value: 'active', label: 'En cours' },
    { value: 'sold', label: 'Vendu' },
    { value: 'unsold', label: 'Non vendu' },
    { value: 'redeemed', label: 'Dette remboursée' },
    { value: 'cancelled', label: 'Annulée' },
]

const applyFilters = () => {
    router.get(route('tenant.auctions.index'), {
        status: statusFilter.value || undefined,
    }, { preserveState: true })
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        notice_sent: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        sold: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        unsold: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        redeemed: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }
    return classes[status] || classes.pending
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount)
}
</script>

<template>
    <Head title="Enchères (Lien Sales)" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Enchères (Lien Sales)
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Gestion des ventes aux enchères pour impayés
                    </p>
                </div>
                <Link
                    :href="route('tenant.auctions.settings')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                >
                    <Cog6ToothIcon class="w-5 h-5 mr-2" />
                    Paramètres
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900">
                                <ExclamationTriangleIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">En cours</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.total_pending + stats.total_active }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                                <CurrencyEuroIcon class="w-6 h-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Dette totale</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(stats.total_debt_pending) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                                <BanknotesIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Récupéré</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ formatCurrency(stats.total_recovered) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                                <CheckCircleIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Taux remboursement</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ stats.redemption_rate }}%
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

                <!-- Auctions Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    N° Enchère
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Client / Box
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Dette
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Enchère actuelle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Fin
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="auction in auctions.data" :key="auction.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ auction.auction_number }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ auction.days_overdue }} jours de retard
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">
                                        {{ auction.customer?.full_name || 'N/A' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Box {{ auction.box?.name }} - {{ auction.site?.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-red-600 dark:text-red-400">
                                        {{ formatCurrency(auction.total_debt) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="auction.current_bid > 0" class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(auction.current_bid) }}
                                    </div>
                                    <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                        Min: {{ formatCurrency(auction.starting_bid) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(auction.status)]">
                                        {{ auction.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div v-if="auction.auction_end_date">
                                        {{ new Date(auction.auction_end_date).toLocaleDateString('fr-FR') }}
                                    </div>
                                    <div v-else>-</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link
                                        :href="route('tenant.auctions.show', auction.id)"
                                        class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400"
                                    >
                                        Gérer
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div v-if="auctions.data.length === 0" class="text-center py-12">
                        <CurrencyEuroIcon class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                            Aucune enchère en cours
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Les enchères seront créées automatiquement pour les contrats avec impayés.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
