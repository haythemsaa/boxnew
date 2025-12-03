<script setup>
import { ref, watch, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    UserPlusIcon,
    PhoneIcon,
    EnvelopeIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    TrashIcon,
    CheckCircleIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
    FireIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    ChartBarIcon,
    EyeIcon,
    UserIcon,
    BuildingOfficeIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    prospects: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const sourceFilter = ref(props.filters.source || '')

const showDeleteModal = ref(false)
const prospectToDelete = ref(null)
const showContactModal = ref(false)
const prospectToContact = ref(null)
const deleteForm = useForm({})
const contactForm = useForm({
    notes: '',
    status: '',
})

let searchTimeout = null

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.prospects.index'), {
            search: search.value,
            status: statusFilter.value,
            source: sourceFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.prospects.index'), {
        search: search.value,
        status: statusFilter.value,
        source: sourceFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    statusFilter.value = ''
    sourceFilter.value = ''
    router.get(route('tenant.prospects.index'))
}

const hasActiveFilters = computed(() => {
    return search.value || statusFilter.value || sourceFilter.value
})

const confirmDelete = (prospect) => {
    prospectToDelete.value = prospect
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    prospectToDelete.value = null
}

const deleteProspect = () => {
    deleteForm.delete(route('tenant.prospects.destroy', prospectToDelete.value.id), {
        onSuccess: () => closeDeleteModal(),
    })
}

const openContactModal = (prospect) => {
    prospectToContact.value = prospect
    contactForm.notes = ''
    contactForm.status = prospect.status
    showContactModal.value = true
}

const closeContactModal = () => {
    showContactModal.value = false
    prospectToContact.value = null
}

const recordContact = () => {
    contactForm.post(route('tenant.prospects.record-contact', prospectToContact.value.id), {
        onSuccess: () => closeContactModal(),
    })
}

const convertToCustomer = (prospect) => {
    if (confirm('Voulez-vous convertir ce prospect en client ?')) {
        router.post(route('tenant.prospects.convert', prospect.id))
    }
}

const markAsLost = (prospect) => {
    if (confirm('Voulez-vous marquer ce prospect comme perdu ?')) {
        router.post(route('tenant.prospects.mark-lost', prospect.id))
    }
}

const statusOptions = [
    { value: 'new', label: 'Nouveau', icon: '🆕', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 'contacted', label: 'Contacté', icon: '📞', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 'qualified', label: 'Qualifié', icon: '⭐', color: 'bg-purple-100 text-purple-700 border-purple-200' },
    { value: 'quoted', label: 'Devis envoyé', icon: '📄', color: 'bg-indigo-100 text-indigo-700 border-indigo-200' },
    { value: 'converted', label: 'Converti', icon: '✅', color: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'lost', label: 'Perdu', icon: '❌', color: 'bg-gray-100 text-gray-700 border-gray-200' },
]

const sourceOptions = [
    { value: 'website', label: 'Site web', icon: '🌐' },
    { value: 'phone', label: 'Téléphone', icon: '📞' },
    { value: 'email', label: 'Email', icon: '📧' },
    { value: 'referral', label: 'Recommandation', icon: '👥' },
    { value: 'walk_in', label: 'Visite', icon: '🚶' },
    { value: 'social_media', label: 'Réseaux sociaux', icon: '📱' },
    { value: 'other', label: 'Autre', icon: '📌' },
]

const getStatusConfig = (status) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
}

const getSourceConfig = (source) => {
    return sourceOptions.find(s => s.value === source) || { value: source, label: source, icon: '📌' }
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    if (!amount) return '-'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}

const totalStats = computed(() => {
    return (props.stats?.new || 0) + (props.stats?.contacted || 0) +
           (props.stats?.qualified || 0) + (props.stats?.quoted || 0) +
           (props.stats?.converted || 0) + (props.stats?.lost || 0)
})

const conversionRate = computed(() => {
    if (totalStats.value === 0) return 0
    return ((props.stats?.converted || 0) / totalStats.value * 100).toFixed(1)
})
</script>

<template>
    <TenantLayout title="Prospects">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 via-orange-500 to-red-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <UserPlusIcon class="h-8 w-8 text-white" />
                                </div>
                                Prospects
                            </h1>
                            <p class="mt-2 text-amber-100">
                                Gérez vos prospects et convertissez-les en clients
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.prospects.create')"
                            class="inline-flex items-center px-6 py-3 bg-white text-orange-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <UserPlusIcon class="h-5 w-5 mr-2" />
                            Nouveau Prospect
                        </Link>
                    </div>

                    <!-- Stats Pipeline -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-8 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.new || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Nouveaux</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.contacted || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Contactés</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.qualified || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Qualifiés</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.quoted || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Devis</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.converted || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Convertis</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.lost || 0 }}</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Perdus</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ stats?.hot || 0 }}
                                <FireIconSolid class="h-5 w-5 ml-1 text-yellow-300" />
                            </p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Chauds</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ conversionRate }}%</p>
                            <p class="text-xs text-amber-100 font-medium mt-1">Conversion</p>
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
                            placeholder="Rechercher un prospect (nom, email, téléphone)..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all"
                            @input="handleSearch"
                        />
                    </div>

                    <!-- Filtre Statut -->
                    <div class="w-full lg:w-48">
                        <select
                            v-model="statusFilter"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all bg-white"
                            @change="handleFilterChange"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.icon }} {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtre Source -->
                    <div class="w-full lg:w-48">
                        <select
                            v-model="sourceFilter"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 transition-all bg-white"
                            @change="handleFilterChange"
                        >
                            <option value="">Toutes les sources</option>
                            <option v-for="source in sourceOptions" :key="source.value" :value="source.value">
                                {{ source.icon }} {{ source.label }}
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

            <!-- Liste des prospects -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- État vide -->
                <div v-if="prospects.data.length === 0" class="py-16 px-4 text-center">
                    <div class="mx-auto w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mb-4">
                        <UserPlusIcon class="h-8 w-8 text-orange-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun prospect trouvé</h3>
                    <p class="text-gray-500 mb-6">Commencez par créer votre premier prospect</p>
                    <Link
                        :href="route('tenant.prospects.create')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                        <UserPlusIcon class="h-5 w-5 mr-2" />
                        Nouveau Prospect
                    </Link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Prospect
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Intérêt
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Source
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Relances
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr
                                v-for="(prospect, index) in prospects.data"
                                :key="prospect.id"
                                class="hover:bg-orange-50/50 transition-colors duration-150"
                                :style="{ animationDelay: `${index * 50}ms` }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-400 to-red-500 flex items-center justify-center text-white font-semibold shadow-sm">
                                                <template v-if="prospect.type === 'company'">
                                                    <BuildingOfficeIcon class="h-5 w-5" />
                                                </template>
                                                <template v-else>
                                                    {{ (prospect.first_name?.charAt(0) || '') + (prospect.last_name?.charAt(0) || '') }}
                                                </template>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ prospect.type === 'company' ? prospect.company_name : `${prospect.first_name} ${prospect.last_name}` }}
                                            </div>
                                            <div v-if="prospect.type === 'company'" class="text-sm text-gray-500">
                                                {{ prospect.first_name }} {{ prospect.last_name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <a :href="`mailto:${prospect.email}`" class="text-sm text-gray-900 hover:text-orange-600 flex items-center gap-1">
                                            <EnvelopeIcon class="h-4 w-4 text-gray-400" />
                                            {{ prospect.email }}
                                        </a>
                                        <a v-if="prospect.phone" :href="`tel:${prospect.phone}`" class="text-sm text-gray-500 hover:text-orange-600 flex items-center gap-1">
                                            <PhoneIcon class="h-4 w-4 text-gray-400" />
                                            {{ prospect.phone }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="space-y-1">
                                        <div v-if="prospect.box_size_interested" class="flex items-center gap-1 text-sm text-gray-900">
                                            <ChartBarIcon class="h-4 w-4 text-gray-400" />
                                            {{ prospect.box_size_interested }}
                                        </div>
                                        <div v-if="prospect.move_in_date" class="flex items-center gap-1 text-xs text-gray-500">
                                            <CalendarDaysIcon class="h-3.5 w-3.5" />
                                            {{ formatDate(prospect.move_in_date) }}
                                        </div>
                                        <div v-if="prospect.budget" class="flex items-center gap-1 text-xs text-gray-500">
                                            <CurrencyEuroIcon class="h-3.5 w-3.5" />
                                            {{ formatCurrency(prospect.budget) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                                        {{ getSourceConfig(prospect.source).icon }}
                                        {{ getSourceConfig(prospect.source).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusConfig(prospect.status).color"
                                    >
                                        {{ getStatusConfig(prospect.status).icon }}
                                        {{ getStatusConfig(prospect.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm">
                                        <span class="font-medium text-gray-900">{{ prospect.follow_up_count || 0 }}</span>
                                        <span class="text-gray-500"> relances</span>
                                    </div>
                                    <div v-if="prospect.last_contact_at" class="text-xs text-gray-400 mt-0.5">
                                        Dernier: {{ formatDate(prospect.last_contact_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Enregistrer contact -->
                                        <button
                                            v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                            @click="openContactModal(prospect)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Enregistrer un contact"
                                        >
                                            <PhoneIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Convertir -->
                                        <button
                                            v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                            @click="convertToCustomer(prospect)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Convertir en client"
                                        >
                                            <CheckCircleIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Modifier -->
                                        <Link
                                            :href="route('tenant.prospects.edit', prospect.id)"
                                            class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                            title="Modifier"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </Link>
                                        <!-- Marquer perdu -->
                                        <button
                                            v-if="prospect.status !== 'converted' && prospect.status !== 'lost'"
                                            @click="markAsLost(prospect)"
                                            class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors"
                                            title="Marquer comme perdu"
                                        >
                                            <XMarkIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Supprimer -->
                                        <button
                                            @click="confirmDelete(prospect)"
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
                <div v-if="prospects.data.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-600">
                            Affichage de <span class="font-semibold">{{ prospects.from }}</span> à
                            <span class="font-semibold">{{ prospects.to }}</span> sur
                            <span class="font-semibold">{{ prospects.total }}</span> résultats
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in prospects.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-lg transition-all',
                                        link.active
                                            ? 'bg-gradient-to-r from-orange-500 to-red-500 text-white shadow-sm'
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
                                    <h3 class="text-lg font-semibold text-gray-900">Supprimer le prospect</h3>
                                    <p class="text-sm text-gray-500">Cette action est irréversible</p>
                                </div>
                            </div>

                            <p class="text-gray-600 mb-6">
                                Êtes-vous sûr de vouloir supprimer ce prospect ? Toutes les données associées seront perdues.
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
                                    @click="deleteProspect"
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

        <!-- Modal Contact -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showContactModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeContactModal"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
                            <div class="flex items-center gap-4 mb-6">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <PhoneIcon class="h-6 w-6 text-blue-600" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Enregistrer un contact</h3>
                                    <p class="text-sm text-gray-500">{{ prospectToContact?.first_name }} {{ prospectToContact?.last_name }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau statut</label>
                                    <select
                                        v-model="contactForm.status"
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                    >
                                        <option v-for="status in statusOptions.slice(0, 4)" :key="status.value" :value="status.value">
                                            {{ status.icon }} {{ status.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                    <textarea
                                        v-model="contactForm.notes"
                                        rows="4"
                                        placeholder="Résumé de l'échange..."
                                        class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 resize-none"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 mt-6">
                                <button
                                    @click="closeContactModal"
                                    :disabled="contactForm.processing"
                                    class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="recordContact"
                                    :disabled="contactForm.processing"
                                    class="px-4 py-2 bg-gradient-to-r from-orange-500 to-red-500 text-white rounded-lg hover:shadow-lg transition-all disabled:opacity-50"
                                >
                                    {{ contactForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
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
