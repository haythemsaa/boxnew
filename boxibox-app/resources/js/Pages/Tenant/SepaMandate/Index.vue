<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    CreditCardIcon,
    MagnifyingGlassIcon,
    ArrowPathIcon,
    PlusIcon,
    CheckCircleIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    XCircleIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    BanknotesIcon,
    ClockIcon,
    UserIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mandates: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')

const showDeleteModal = ref(false)
const mandateToDelete = ref(null)
const deleteForm = useForm({})

let searchTimeout = null

const statusOptions = [
    { value: 'pending', label: 'En attente', icon: '⏳', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 'active', label: 'Actif', icon: '✅', color: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'suspended', label: 'Suspendu', icon: '⏸️', color: 'bg-orange-100 text-orange-700 border-orange-200' },
    { value: 'cancelled', label: 'Annulé', icon: '❌', color: 'bg-red-100 text-red-700 border-red-200' },
    { value: 'expired', label: 'Expiré', icon: '⌛', color: 'bg-gray-100 text-gray-600 border-gray-200' },
]

const getStatusConfig = (status) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
}

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

const clearFilters = () => {
    search.value = ''
    statusFilter.value = ''
    router.get(route('tenant.sepa-mandates.index'))
}

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value
})

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
    <TenantLayout title="Mandats SEPA">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 via-violet-500 to-purple-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <CreditCardIcon class="h-8 w-8 text-white" />
                                </div>
                                Mandats SEPA
                            </h1>
                            <p class="mt-2 text-indigo-100">
                                Gérez les autorisations de prélèvement automatique
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.sepa-mandates.create')"
                            class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <PlusIcon class="h-5 w-5 mr-2" />
                            Nouveau Mandat
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-6 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.total || 0 }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">Total</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.active || 0 }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">Actifs</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.pending || 0 }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">En attente</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.suspended || 0 }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">Suspendus</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.cancelled || 0 }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">Annulés</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ formatCurrency(stats?.total_collected || 0) }}</p>
                            <p class="text-xs text-indigo-100 font-medium mt-1">Prélevé</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Recherche -->
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Rechercher par RUM, client, titulaire..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Filtre Statut -->
                    <div class="w-full lg:w-48">
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-white"
                            @change="handleFilterChange"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.icon }} {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Bouton Reset -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors"
                    >
                        <ArrowPathIcon class="h-5 w-5 mr-2" />
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste des mandats -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- État vide -->
                <div v-if="!mandates?.data?.length" class="py-16 px-4 text-center">
                    <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <CreditCardIcon class="h-8 w-8 text-indigo-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun mandat SEPA</h3>
                    <p class="text-gray-500 mb-6">Créez votre premier mandat de prélèvement</p>
                    <Link
                        :href="route('tenant.sepa-mandates.create')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-500 to-violet-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Nouveau Mandat
                    </Link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    RUM
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Client
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Titulaire compte
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    IBAN
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Date signature
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Prélevé
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr
                                v-for="(mandate, index) in mandates.data"
                                :key="mandate.id"
                                class="hover:bg-indigo-50/50 transition-colors duration-150"
                                :style="{ animationDelay: `${index * 50}ms` }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <CreditCardIcon class="h-5 w-5 text-indigo-500" />
                                        <span class="text-sm font-mono font-medium text-gray-900">{{ mandate.rum }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <UserIcon class="h-4 w-4 text-gray-400" />
                                        <span class="text-sm text-gray-900">{{ getCustomerName(mandate.customer) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ mandate.account_holder }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        {{ mandate.masked_iban }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusConfig(mandate.status).color"
                                    >
                                        {{ getStatusConfig(mandate.status).icon }}
                                        {{ getStatusConfig(mandate.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1 text-sm text-gray-500">
                                        <ClockIcon class="h-4 w-4" />
                                        {{ formatDate(mandate.signature_date) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <BanknotesIcon class="h-4 w-4 text-green-500" />
                                        <span class="text-sm font-semibold text-gray-900">{{ formatCurrency(mandate.total_collected) }}</span>
                                        <span v-if="mandate.collection_count > 0" class="text-xs text-gray-500">
                                            ({{ mandate.collection_count }}x)
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Activer -->
                                        <button
                                            v-if="mandate.status === 'pending'"
                                            @click="activateMandate(mandate)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Activer"
                                        >
                                            <CheckCircleIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Suspendre -->
                                        <button
                                            v-if="mandate.status === 'active'"
                                            @click="suspendMandate(mandate)"
                                            class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                            title="Suspendre"
                                        >
                                            <PauseCircleIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Réactiver -->
                                        <button
                                            v-if="mandate.status === 'suspended'"
                                            @click="reactivateMandate(mandate)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Réactiver"
                                        >
                                            <PlayCircleIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Annuler -->
                                        <button
                                            v-if="!['cancelled', 'expired'].includes(mandate.status)"
                                            @click="cancelMandate(mandate)"
                                            class="p-2 text-gray-500 hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Annuler"
                                        >
                                            <XCircleIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Supprimer -->
                                        <button
                                            @click="confirmDelete(mandate)"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="mandates?.data?.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-600">
                            Affichage de <span class="font-semibold">{{ mandates.from }}</span> à
                            <span class="font-semibold">{{ mandates.to }}</span> sur
                            <span class="font-semibold">{{ mandates.total }}</span> résultats
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in mandates.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-lg transition-all',
                                        link.active
                                            ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-sm'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    ]"
                                    :preserve-scroll="true"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Suppression -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeDeleteModal"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                                    <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Supprimer le mandat SEPA</h3>
                                    <p class="text-sm text-gray-500">Cette action est irréversible</p>
                                </div>
                            </div>

                            <p class="text-gray-600 mb-6">
                                Êtes-vous sûr de vouloir supprimer ce mandat SEPA ? Toutes les données associées seront perdues.
                            </p>

                            <div class="flex justify-end gap-3">
                                <button
                                    @click="closeDeleteModal"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="deleteMandate"
                                    :disabled="deleteForm.processing"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                                >
                                    {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </TenantLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .animate-scale-in,
.modal-leave-to .animate-scale-in {
    transform: scale(0.95);
}
</style>
