<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PencilSquareIcon,
    PlusIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EnvelopeIcon,
    BellAlertIcon,
    ArrowDownTrayIcon,
    XCircleIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ClockIcon,
    EyeIcon,
    XMarkIcon,
    NoSymbolIcon,
} from '@heroicons/vue/24/outline'

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

const statusConfig = {
    pending: { label: 'En attente', color: 'bg-gray-100 text-gray-700 border-gray-200', icon: ClockIcon },
    sent: { label: 'Envoyée', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: EnvelopeIcon },
    viewed: { label: 'Consultée', color: 'bg-amber-100 text-amber-700 border-amber-200', icon: EyeIcon },
    signed: { label: 'Signée', color: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCircleIcon },
    refused: { label: 'Refusée', color: 'bg-red-100 text-red-700 border-red-200', icon: XMarkIcon },
    expired: { label: 'Expirée', color: 'bg-gray-100 text-gray-500 border-gray-200', icon: NoSymbolIcon },
}

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

const getTypeLabel = (type) => {
    return type === 'mandate' ? 'Mandat SEPA' : 'Contrat'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <TenantLayout title="Signatures">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-violet-600 via-purple-600 to-violet-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="w-16 h-16 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <PencilSquareIcon class="w-8 h-8 text-white" />
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Signatures</h1>
                            <p class="text-violet-100 mt-1">Gérez les signatures électroniques de vos documents</p>
                        </div>
                    </div>
                    <div class="mt-6 lg:mt-0">
                        <Link
                            :href="route('tenant.signatures.create')"
                            class="inline-flex items-center px-5 py-3 bg-white text-violet-600 rounded-xl font-semibold shadow-lg hover:bg-violet-50 transition-all"
                        >
                            <PlusIcon class="w-5 h-5 mr-2" />
                            Nouvelle demande
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-white">{{ stats.total }}</p>
                        <p class="text-violet-200 text-sm">Total</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-gray-200">{{ stats.pending }}</p>
                        <p class="text-violet-200 text-sm">En attente</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-blue-300">{{ stats.sent }}</p>
                        <p class="text-violet-200 text-sm">Envoyées</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-amber-300">{{ stats.viewed }}</p>
                        <p class="text-violet-200 text-sm">Consultées</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-emerald-300">{{ stats.signed }}</p>
                        <p class="text-violet-200 text-sm">Signées</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-red-300">{{ stats.refused }}</p>
                        <p class="text-violet-200 text-sm">Refusées</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20 text-center">
                        <p class="text-3xl font-bold text-gray-400">{{ stats.expired }}</p>
                        <p class="text-violet-200 text-sm">Expirées</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <!-- Flash Messages -->
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl shadow-sm">
                <p class="text-sm text-emerald-600 flex items-center">
                    <CheckCircleIcon class="w-5 h-5 mr-2" />
                    {{ $page.props.flash.success }}
                </p>
            </div>
            <div v-if="$page.props.flash?.error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl shadow-sm">
                <p class="text-sm text-red-600 flex items-center">
                    <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                    {{ $page.props.flash.error }}
                </p>
            </div>

            <!-- Filters Card -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 p-6 mb-6">
                <div class="flex items-center space-x-2 mb-4">
                    <FunnelIcon class="w-5 h-5 text-violet-600" />
                    <h3 class="font-semibold text-gray-900">Filtres</h3>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher par client ou contrat..."
                            class="w-full pl-10 pr-4 py-3 rounded-xl border-2 border-gray-200 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 transition-all"
                            @input="handleSearch"
                        />
                    </div>
                    <select
                        v-model="statusFilter"
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 transition-all"
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
                        class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 transition-all"
                        @change="handleFilterChange"
                    >
                        <option value="">Tous les types</option>
                        <option value="contract">Contrat</option>
                        <option value="mandate">Mandat SEPA</option>
                    </select>
                </div>
            </div>

            <!-- Signatures Table -->
            <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contrat</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Expire le</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <tr v-if="signatures.data.length === 0">
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="w-20 h-20 bg-violet-100 rounded-2xl flex items-center justify-center mb-4">
                                            <PencilSquareIcon class="w-10 h-10 text-violet-400" />
                                        </div>
                                        <p class="text-lg font-semibold text-gray-900 mb-2">Aucune signature trouvée</p>
                                        <p class="text-gray-500 mb-6">Envoyez votre première demande de signature électronique</p>
                                        <Link
                                            :href="route('tenant.signatures.create')"
                                            class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-violet-500 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-violet-500/25 hover:shadow-xl transition-all"
                                        >
                                            <PlusIcon class="w-5 h-5 mr-2" />
                                            Nouvelle demande
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-else
                                v-for="signature in signatures.data"
                                :key="signature.id"
                                class="hover:bg-gray-50 transition-colors"
                            >
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(signature.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-violet-600">{{ signature.contract?.contract_number || '-' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                            <span class="text-violet-600 font-semibold text-xs">
                                                {{ (signature.customer?.first_name?.[0] || signature.customer?.company_name?.[0] || '?').toUpperCase() }}
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-900">{{ getCustomerName(signature.customer) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ signature.email_sent_to || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium',
                                        signature.type === 'mandate' ? 'bg-blue-100 text-blue-700' : 'bg-violet-100 text-violet-700'
                                    ]">
                                        <DocumentTextIcon class="w-3.5 h-3.5 mr-1" />
                                        {{ getTypeLabel(signature.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold border',
                                        statusConfig[signature.status]?.color || 'bg-gray-100 text-gray-600'
                                    ]">
                                        <component :is="statusConfig[signature.status]?.icon" class="w-3.5 h-3.5 mr-1" />
                                        {{ statusConfig[signature.status]?.label || signature.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(signature.expires_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <button
                                            v-if="signature.status === 'pending'"
                                            @click="sendSignature(signature)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Envoyer"
                                        >
                                            <EnvelopeIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            v-if="['sent', 'viewed'].includes(signature.status)"
                                            @click="sendReminder(signature)"
                                            class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                            title="Relancer"
                                        >
                                            <BellAlertIcon class="w-5 h-5" />
                                        </button>
                                        <a
                                            v-if="signature.status === 'signed'"
                                            :href="route('tenant.signatures.download-signed', signature.id)"
                                            class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Télécharger document signé"
                                        >
                                            <ArrowDownTrayIcon class="w-5 h-5" />
                                        </a>
                                        <button
                                            v-if="!['signed', 'refused', 'expired'].includes(signature.status)"
                                            @click="cancelSignature(signature)"
                                            class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Annuler"
                                        >
                                            <XCircleIcon class="w-5 h-5" />
                                        </button>
                                        <button
                                            @click="confirmDelete(signature)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="signatures.data.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Affichage de <span class="font-semibold">{{ signatures.from }}</span> à <span class="font-semibold">{{ signatures.to }}</span> sur <span class="font-semibold">{{ signatures.total }}</span> résultats
                        </div>
                        <div class="flex space-x-2">
                            <template v-for="link in signatures.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                        link.active
                                            ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/25'
                                            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50'
                                    ]"
                                    preserve-scroll
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-4 py-2 rounded-xl text-sm font-medium bg-gray-100 text-gray-400 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <Teleport to="body">
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeDeleteModal"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Supprimer la signature</h3>
                                <p class="text-sm text-gray-500">Cette action est irréversible</p>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-6">
                            Êtes-vous sûr de vouloir supprimer cette demande de signature ?
                        </p>
                        <div class="flex justify-end space-x-3">
                            <button
                                @click="closeDeleteModal"
                                class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="deleteSignature"
                                :disabled="deleteForm.processing"
                                class="px-4 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors disabled:opacity-50"
                            >
                                {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>
