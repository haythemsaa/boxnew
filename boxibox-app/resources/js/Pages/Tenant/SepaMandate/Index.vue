<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    mandates: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')

const showDeleteModal = ref(false)
const mandateToDelete = ref(null)
const deleteForm = useForm({})

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.sepa-mandates.index'), {
            search: search.value,
            status: statusFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.sepa-mandates.index'), {
        search: search.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmDelete = (mandate) => {
    mandateToDelete.value = mandate
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    mandateToDelete.value = null
}

const deleteMandate = () => {
    deleteForm.delete(route('tenant.sepa-mandates.destroy', mandateToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    })
}

const activateMandate = (mandate) => {
    router.post(route('tenant.sepa-mandates.activate', mandate.id))
}

const suspendMandate = (mandate) => {
    router.post(route('tenant.sepa-mandates.suspend', mandate.id))
}

const reactivateMandate = (mandate) => {
    router.post(route('tenant.sepa-mandates.reactivate', mandate.id))
}

const cancelMandate = (mandate) => {
    if (confirm('Voulez-vous vraiment annuler ce mandat SEPA ?')) {
        router.post(route('tenant.sepa-mandates.cancel', mandate.id))
    }
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        active: 'Actif',
        suspended: 'Suspendu',
        cancelled: 'Annulé',
        expired: 'Expiré',
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        suspended: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-600',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <AuthenticatedLayout title="Mandats SEPA">
        <!-- Success/Error Messages -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>
        <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ $page.props.flash.error }}</p>
        </div>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Mandats SEPA</h2>
                <p class="mt-1 text-sm text-gray-500">Gérez les autorisations de prélèvement automatique</p>
            </div>
            <Link
                :href="route('tenant.sepa-mandates.create')"
                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouveau Mandat
            </Link>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 md:grid-cols-6 mb-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-green-500">
                <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
                <p class="text-sm text-gray-500">Actifs</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-500">
                <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                <p class="text-sm text-gray-500">En attente</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-orange-500">
                <p class="text-2xl font-bold text-orange-600">{{ stats.suspended }}</p>
                <p class="text-sm text-gray-500">Suspendus</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
                <p class="text-2xl font-bold text-red-600">{{ stats.cancelled }}</p>
                <p class="text-sm text-gray-500">Annulés</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-primary-500">
                <p class="text-lg font-bold text-primary-600">{{ formatCurrency(stats.total_collected) }}</p>
                <p class="text-sm text-gray-500">Prélevé</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid gap-4 md:grid-cols-2">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par RUM, client, titulaire..."
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @input="handleSearch"
                />
                <select
                    v-model="statusFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="active">Actif</option>
                    <option value="suspended">Suspendu</option>
                    <option value="cancelled">Annulé</option>
                </select>
            </div>
        </div>

        <!-- Mandates Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                RUM
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Titulaire compte
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                IBAN
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date signature
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prélevé
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="mandates.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Aucun mandat SEPA</p>
                                    <Link
                                        :href="route('tenant.sepa-mandates.create')"
                                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        + Nouveau Mandat
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="mandate in mandates.data" :key="mandate.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 font-mono">
                                    {{ mandate.rum }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ getCustomerName(mandate.customer) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ mandate.account_holder }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                {{ mandate.masked_iban }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getStatusColor(mandate.status)"
                                >
                                    {{ getStatusLabel(mandate.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(mandate.signature_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ formatCurrency(mandate.total_collected) }}
                                <span v-if="mandate.collection_count > 0" class="text-xs text-gray-500">
                                    ({{ mandate.collection_count }}x)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        v-if="mandate.status === 'pending'"
                                        @click="activateMandate(mandate)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Activer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="mandate.status === 'active'"
                                        @click="suspendMandate(mandate)"
                                        class="text-orange-600 hover:text-orange-900"
                                        title="Suspendre"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="mandate.status === 'suspended'"
                                        @click="reactivateMandate(mandate)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Réactiver"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="!['cancelled', 'expired'].includes(mandate.status)"
                                        @click="cancelMandate(mandate)"
                                        class="text-gray-600 hover:text-gray-900"
                                        title="Annuler"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(mandate)"
                                        class="text-red-600 hover:text-red-900"
                                        title="Supprimer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="mandates.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Affichage de <span class="font-medium">{{ mandates.from }}</span> à <span class="font-medium">{{ mandates.to }}</span> sur <span class="font-medium">{{ mandates.total }}</span> résultats
                    </div>
                    <div class="flex space-x-2">
                        <Link
                            v-for="link in mandates.links"
                            :key="link.label"
                            :href="link.url"
                            :class="{
                                'px-4 py-2 border rounded-lg transition-colors': true,
                                'bg-primary-600 text-white border-primary-600': link.active,
                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50': !link.active && link.url,
                                'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed': !link.url
                            }"
                            :preserve-scroll="true"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="closeDeleteModal"></div>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Supprimer le mandat SEPA</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Êtes-vous sûr de vouloir supprimer ce mandat SEPA ?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteMandate"
                            :disabled="deleteForm.processing"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                        >
                            Supprimer
                        </button>
                        <button
                            @click="closeDeleteModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
