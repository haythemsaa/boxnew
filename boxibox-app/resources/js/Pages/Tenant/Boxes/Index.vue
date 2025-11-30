<template>
    <TenantLayout title="Boxes">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Gestion des Boxes</h2>
                <p class="text-gray-500 mt-1">Gerez vos espaces de stockage et leur disponibilite</p>
            </div>
            <div class="flex items-center space-x-3 mt-4 md:mt-0">
                <Link
                    :href="route('tenant.boxes.plan')"
                    class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white rounded-xl font-medium text-sm hover:from-cyan-600 hover:to-cyan-700 transition-all shadow-lg shadow-cyan-500/25"
                >
                    <MapIcon class="w-5 h-5 mr-2" />
                    Plan visuel
                </Link>
                <Link
                    :href="route('tenant.boxes.create')"
                    class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-medium text-sm hover:from-primary-600 hover:to-primary-700 transition-all shadow-lg shadow-primary-500/25"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Nouveau box
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Boxes</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center">
                        <ArchiveBoxIcon class="w-6 h-6 text-purple-600" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Disponibles</p>
                        <p class="text-3xl font-bold text-emerald-600 mt-1">{{ stats.available }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center">
                        <CheckCircleIcon class="w-6 h-6 text-emerald-600" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-emerald-500 rounded-full transition-all duration-500"
                            :style="{ width: `${(stats.available / stats.total) * 100}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Occupes</p>
                        <p class="text-3xl font-bold text-amber-600 mt-1">{{ stats.occupied }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                        <LockClosedIcon class="w-6 h-6 text-amber-600" />
                    </div>
                </div>
                <div class="mt-3">
                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-amber-500 rounded-full transition-all duration-500"
                            :style="{ width: `${(stats.occupied / stats.total) * 100}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Revenus/mois</p>
                        <p class="text-3xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.total_revenue) }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center">
                        <CurrencyEuroIcon class="w-6 h-6 text-blue-600" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex flex-col md:flex-row md:items-center gap-4">
                <!-- Search -->
                <div class="flex-1 relative">
                    <MagnifyingGlassIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher par nom, code..."
                        class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-primary-500 focus:bg-white transition-all"
                        @input="handleSearch"
                    />
                </div>

                <!-- Status Filter -->
                <div class="relative">
                    <select
                        v-model="statusFilter"
                        class="appearance-none pl-4 pr-10 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all cursor-pointer"
                        @change="handleFilterChange"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="available">Disponible</option>
                        <option value="occupied">Occupe</option>
                        <option value="reserved">Reserve</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                    <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                </div>

                <!-- Site Filter -->
                <div class="relative">
                    <select
                        v-model="siteFilter"
                        class="appearance-none pl-4 pr-10 py-2.5 bg-gray-50 border-0 rounded-xl focus:ring-2 focus:ring-primary-500 transition-all cursor-pointer"
                        @change="handleFilterChange"
                    >
                        <option value="">Tous les sites</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
                    </select>
                    <ChevronDownIcon class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                </div>

                <!-- View Toggle -->
                <div class="flex items-center bg-gray-100 rounded-xl p-1">
                    <button
                        @click="viewMode = 'table'"
                        :class="[
                            'p-2 rounded-lg transition-all',
                            viewMode === 'table' ? 'bg-white shadow text-primary-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <Bars3Icon class="w-5 h-5" />
                    </button>
                    <button
                        @click="viewMode = 'grid'"
                        :class="[
                            'p-2 rounded-lg transition-all',
                            viewMode === 'grid' ? 'bg-white shadow text-primary-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                    >
                        <Squares2X2Icon class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Grid View -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 mb-6">
            <div
                v-for="box in boxes.data"
                :key="box.id"
                class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:border-primary-200 transition-all overflow-hidden"
            >
                <!-- Box Header -->
                <div class="relative p-4 pb-0">
                    <div :class="[
                        'absolute top-4 right-4 px-2.5 py-1 rounded-full text-xs font-semibold',
                        getStatusClass(box.status)
                    ]">
                        {{ getStatusLabel(box.status) }}
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-bold text-lg mb-3">
                        {{ box.code?.substring(0, 2) }}
                    </div>
                    <h3 class="font-semibold text-gray-900 text-lg">{{ box.name }}</h3>
                    <p class="text-sm text-gray-500 font-mono">{{ box.code }}</p>
                </div>

                <!-- Box Info -->
                <div class="p-4">
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Site</span>
                            <span class="font-medium text-gray-900">{{ box.site?.name || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Dimensions</span>
                            <span class="font-medium text-gray-900">{{ box.length }}x{{ box.width }}x{{ box.height }}m</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500">Surface</span>
                            <span class="font-medium text-gray-900">{{ (box.length * box.width).toFixed(1) }} m²</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-2xl font-bold text-gray-900">{{ formatCurrency(box.current_price) }}</span>
                            <span class="text-sm text-gray-500">/mois</span>
                        </div>
                    </div>
                </div>

                <!-- Box Actions -->
                <div class="px-4 py-3 bg-gray-50 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                    <Link
                        :href="route('tenant.boxes.show', box.id)"
                        class="text-sm font-medium text-primary-600 hover:text-primary-700"
                    >
                        Voir details
                    </Link>
                    <div class="flex items-center space-x-2">
                        <Link
                            :href="route('tenant.boxes.edit', box.id)"
                            class="p-1.5 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                        >
                            <PencilIcon class="w-4 h-4" />
                        </Link>
                        <button
                            @click="confirmDelete(box)"
                            class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                            <TrashIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="boxes.data.length === 0" class="col-span-full">
                <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center">
                    <ArchiveBoxIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun box trouve</h3>
                    <p class="text-gray-500 mb-6">
                        {{ filters.search || filters.status || filters.site_id ? 'Essayez de modifier vos filtres' : 'Commencez par creer votre premier box' }}
                    </p>
                    <Link
                        v-if="!filters.search && !filters.status && !filters.site_id"
                        :href="route('tenant.boxes.create')"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-xl font-medium text-sm hover:bg-primary-700 transition-colors"
                    >
                        <PlusIcon class="w-5 h-5 mr-2" />
                        Ajouter un box
                    </Link>
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Box
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Site
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Dimensions
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Prix
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Statut
                            </th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-if="boxes.data.length === 0">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <ArchiveBoxIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun box trouve</h3>
                                <p class="text-sm text-gray-500 mb-4">
                                    {{ filters.search || filters.status || filters.site_id ? 'Essayez de modifier vos filtres' : 'Commencez par creer votre premier box' }}
                                </p>
                                <Link
                                    v-if="!filters.search && !filters.status && !filters.site_id"
                                    :href="route('tenant.boxes.create')"
                                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg font-medium text-sm hover:bg-primary-700 transition-colors"
                                >
                                    <PlusIcon class="w-4 h-4 mr-2" />
                                    Ajouter un box
                                </Link>
                            </td>
                        </tr>
                        <tr
                            v-else
                            v-for="box in boxes.data"
                            :key="box.id"
                            class="hover:bg-gray-50/50 transition-colors"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ box.code?.substring(0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ box.name }}</p>
                                        <p class="text-sm text-gray-500 font-mono">{{ box.code }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900">{{ box.site?.name || '-' }}</p>
                                <p v-if="box.building" class="text-sm text-gray-500">{{ box.building.name }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-900">{{ box.length }}m × {{ box.width }}m × {{ box.height }}m</p>
                                <p class="text-sm text-gray-500">{{ box.volume || (box.length * box.width * box.height).toFixed(1) }} m³</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-900">{{ formatCurrency(box.current_price) }}</p>
                                <p v-if="box.current_price !== box.base_price" class="text-xs text-gray-500">
                                    Base: {{ formatCurrency(box.base_price) }}
                                </p>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold',
                                    getStatusClass(box.status)
                                ]">
                                    <span :class="[
                                        'w-1.5 h-1.5 rounded-full mr-1.5',
                                        getStatusDotClass(box.status)
                                    ]"></span>
                                    {{ getStatusLabel(box.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <Link
                                        :href="route('tenant.boxes.show', box.id)"
                                        class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors"
                                        title="Voir"
                                    >
                                        <EyeIcon class="w-4 h-4" />
                                    </Link>
                                    <Link
                                        :href="route('tenant.boxes.edit', box.id)"
                                        class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                        title="Modifier"
                                    >
                                        <PencilIcon class="w-4 h-4" />
                                    </Link>
                                    <button
                                        @click="confirmDelete(box)"
                                        class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="boxes.data.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-sm text-gray-600">
                        Affichage <span class="font-semibold">{{ boxes.from }}</span> a <span class="font-semibold">{{ boxes.to }}</span> sur <span class="font-semibold">{{ boxes.total }}</span> resultats
                    </p>
                    <div class="flex items-center space-x-1">
                        <template v-for="link in boxes.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'px-3 py-1.5 text-sm rounded-lg transition-colors',
                                    link.active
                                        ? 'bg-primary-600 text-white font-semibold'
                                        : 'text-gray-600 hover:bg-gray-100'
                                ]"
                                :preserve-scroll="true"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="px-3 py-1.5 text-sm text-gray-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Teleport to="body">
            <transition name="modal">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeDeleteModal"></div>

                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
                            <div class="flex items-center justify-center w-14 h-14 mx-auto bg-red-100 rounded-full mb-4">
                                <ExclamationTriangleIcon class="w-7 h-7 text-red-600" />
                            </div>

                            <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Supprimer ce box ?</h3>
                            <p class="text-gray-500 text-center mb-6">
                                Etes-vous sur de vouloir supprimer <strong class="text-gray-900">{{ boxToDelete?.name }}</strong> ?
                                Cette action est irreversible.
                            </p>

                            <div class="flex items-center space-x-3">
                                <button
                                    @click="closeDeleteModal"
                                    :disabled="deleteForm.processing"
                                    class="flex-1 px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl font-medium transition-colors disabled:opacity-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="deleteBox"
                                    :disabled="deleteForm.processing"
                                    class="flex-1 px-4 py-2.5 text-white bg-red-600 hover:bg-red-700 rounded-xl font-medium transition-colors disabled:opacity-50 flex items-center justify-center"
                                >
                                    <span v-if="deleteForm.processing" class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
                                    {{ deleteForm.processing ? 'Suppression...' : 'Supprimer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PlusIcon,
    MagnifyingGlassIcon,
    ChevronDownIcon,
    ArchiveBoxIcon,
    CheckCircleIcon,
    LockClosedIcon,
    CurrencyEuroIcon,
    MapIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    Bars3Icon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    boxes: Object,
    stats: Object,
    sites: Array,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const siteFilter = ref(props.filters.site_id || '')
const viewMode = ref('table')

const showDeleteModal = ref(false)
const boxToDelete = ref(null)
const deleteForm = useForm({})

let searchTimeout = null

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(value || 0)
}

const getStatusClass = (status) => {
    const classes = {
        available: 'bg-emerald-100 text-emerald-700',
        occupied: 'bg-amber-100 text-amber-700',
        reserved: 'bg-blue-100 text-blue-700',
        maintenance: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getStatusDotClass = (status) => {
    const classes = {
        available: 'bg-emerald-500',
        occupied: 'bg-amber-500',
        reserved: 'bg-blue-500',
        maintenance: 'bg-gray-500',
    }
    return classes[status] || 'bg-gray-500'
}

const getStatusLabel = (status) => {
    const labels = {
        available: 'Disponible',
        occupied: 'Occupe',
        reserved: 'Reserve',
        maintenance: 'Maintenance',
    }
    return labels[status] || status
}

const handleSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.boxes.index'), {
            search: search.value,
            status: statusFilter.value,
            site_id: siteFilter.value,
        }, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const handleFilterChange = () => {
    router.get(route('tenant.boxes.index'), {
        search: search.value,
        status: statusFilter.value,
        site_id: siteFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const confirmDelete = (box) => {
    boxToDelete.value = box
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    showDeleteModal.value = false
    boxToDelete.value = null
}

const deleteBox = () => {
    deleteForm.delete(route('tenant.boxes.destroy', boxToDelete.value.id), {
        onSuccess: () => {
            closeDeleteModal()
        },
    })
}
</script>

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
