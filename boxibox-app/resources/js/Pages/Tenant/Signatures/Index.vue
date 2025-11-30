<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    signatures: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const typeFilter = ref(props.filters.type || '')

const showDeleteModal = ref(false)
const signatureToDelete = ref(null)
const deleteForm = useForm({})

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.signatures.index'), {
            search: search.value,
            status: statusFilter.value,
            type: typeFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.signatures.index'), {
        search: search.value,
        status: statusFilter.value,
        type: typeFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmDelete = (signature) => {
    signatureToDelete.value = signature
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    signatureToDelete.value = null
}

const deleteSignature = () => {
    deleteForm.delete(route('tenant.signatures.destroy', signatureToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    })
}

const sendSignature = (signature) => {
    router.post(route('tenant.signatures.send', signature.id))
}

const sendReminder = (signature) => {
    router.post(route('tenant.signatures.remind', signature.id))
}

const cancelSignature = (signature) => {
    if (confirm('Voulez-vous annuler cette demande de signature ?')) {
        router.post(route('tenant.signatures.cancel', signature.id))
    }
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        sent: 'Envoyée',
        viewed: 'Consultée',
        signed: 'Signée',
        refused: 'Refusée',
        expired: 'Expirée',
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        sent: 'bg-blue-100 text-blue-800',
        viewed: 'bg-yellow-100 text-yellow-800',
        signed: 'bg-green-100 text-green-800',
        refused: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-600',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getTypeLabel = (type) => {
    return type === 'mandate' ? 'Mandat SEPA' : 'Contrat'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR')
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <AuthenticatedLayout title="Signatures">
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
                <h2 class="text-2xl font-bold text-gray-900">Signatures</h2>
                <p class="mt-1 text-sm text-gray-500">Gérez les signatures électroniques de vos contrats et mandats</p>
            </div>
            <Link
                :href="route('tenant.signatures.create')"
                class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors inline-flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nouvelle demande
            </Link>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 md:grid-cols-7 mb-6">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
                <p class="text-sm text-gray-500">Total</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-gray-300">
                <p class="text-2xl font-bold text-gray-600">{{ stats.pending }}</p>
                <p class="text-sm text-gray-500">En attente</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-blue-500">
                <p class="text-2xl font-bold text-blue-600">{{ stats.sent }}</p>
                <p class="text-sm text-gray-500">Envoyées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-yellow-500">
                <p class="text-2xl font-bold text-yellow-600">{{ stats.viewed }}</p>
                <p class="text-sm text-gray-500">Consultées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-green-500">
                <p class="text-2xl font-bold text-green-600">{{ stats.signed }}</p>
                <p class="text-sm text-gray-500">Signées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-red-500">
                <p class="text-2xl font-bold text-red-600">{{ stats.refused }}</p>
                <p class="text-sm text-gray-500">Refusées</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center border-l-4 border-gray-400">
                <p class="text-2xl font-bold text-gray-500">{{ stats.expired }}</p>
                <p class="text-sm text-gray-500">Expirées</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid gap-4 md:grid-cols-3">
                <input
                    v-model="search"
                    type="text"
                    placeholder="Rechercher par client ou contrat..."
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
                    <option value="sent">Envoyée</option>
                    <option value="viewed">Consultée</option>
                    <option value="signed">Signée</option>
                    <option value="refused">Refusée</option>
                    <option value="expired">Expirée</option>
                </select>
                <select
                    v-model="typeFilter"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    @change="handleFilterChange"
                >
                    <option value="">Tous les types</option>
                    <option value="contract">Contrat</option>
                    <option value="mandate">Mandat SEPA</option>
                </select>
            </div>
        </div>

        <!-- Signatures Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contrat
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Client
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Expire le
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-if="signatures.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <p class="text-lg font-medium mb-2">Aucune signature trouvée</p>
                                    <Link
                                        :href="route('tenant.signatures.create')"
                                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
                                    >
                                        + Nouvelle demande
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-else v-for="signature in signatures.data" :key="signature.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(signature.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ signature.contract?.contract_number || '-' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ getCustomerName(signature.customer) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ signature.email_sent_to || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">{{ getTypeLabel(signature.type) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getStatusColor(signature.status)"
                                >
                                    {{ getStatusLabel(signature.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(signature.expires_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        v-if="signature.status === 'pending'"
                                        @click="sendSignature(signature)"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="Envoyer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button
                                        v-if="['sent', 'viewed'].includes(signature.status)"
                                        @click="sendReminder(signature)"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Relancer"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        </svg>
                                    </button>
                                    <Link
                                        v-if="signature.status === 'signed'"
                                        :href="route('tenant.signatures.download-signed', signature.id)"
                                        class="text-green-600 hover:text-green-900"
                                        title="Télécharger document signé"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </Link>
                                    <button
                                        v-if="!['signed', 'refused', 'expired'].includes(signature.status)"
                                        @click="cancelSignature(signature)"
                                        class="text-gray-600 hover:text-gray-900"
                                        title="Annuler"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(signature)"
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
            <div v-if="signatures.data.length > 0" class="px-6 py-4 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Affichage de <span class="font-medium">{{ signatures.from }}</span> à <span class="font-medium">{{ signatures.to }}</span> sur <span class="font-medium">{{ signatures.total }}</span> résultats
                    </div>
                    <div class="flex space-x-2">
                        <Link
                            v-for="link in signatures.links"
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
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Supprimer la signature</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        Êtes-vous sûr de vouloir supprimer cette demande de signature ?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            @click="deleteSignature"
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
