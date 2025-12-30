<template>
    <MobileLayout title="Mes Box">
        <!-- Search Bar with Enhanced Design -->
        <div class="mb-5">
            <div class="relative">
                <div class="absolute left-4 top-1/2 transform -translate-y-1/2 w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                    <MagnifyingGlassIcon class="w-5 h-5 text-primary-600" />
                </div>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher un box..."
                    class="w-full pl-16 pr-4 py-4 bg-white border-0 rounded-2xl shadow-sm focus:ring-2 focus:ring-primary-500 text-gray-900 placeholder-gray-400 transition-all duration-300 focus:shadow-lg"
                />
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-50"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-50"
                >
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute right-4 top-1/2 transform -translate-y-1/2 w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center hover:bg-gray-200 transition-colors"
                    >
                        <XMarkIcon class="w-4 h-4 text-gray-500" />
                    </button>
                </Transition>
            </div>
        </div>

        <!-- Filter Tabs with Icons -->
        <div class="flex space-x-2 mb-6 overflow-x-auto pb-2 scrollbar-hide">
            <button
                @click="activeFilter = 'all'"
                :class="[
                    'flex items-center space-x-2 px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-300 transform',
                    activeFilter === 'all'
                        ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105'
                        : 'bg-white text-gray-600 shadow-sm hover:shadow-md hover:scale-[1.02]'
                ]"
            >
                <Squares2X2Icon class="w-4 h-4" />
                <span>Tous ({{ contracts.length }})</span>
            </button>
            <button
                @click="activeFilter = 'active'"
                :class="[
                    'flex items-center space-x-2 px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-300 transform',
                    activeFilter === 'active'
                        ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-lg shadow-green-500/30 scale-105'
                        : 'bg-white text-gray-600 shadow-sm hover:shadow-md hover:scale-[1.02]'
                ]"
            >
                <CheckCircleIcon class="w-4 h-4" />
                <span>Actifs ({{ activeCount }})</span>
            </button>
            <button
                @click="activeFilter = 'expiring'"
                :class="[
                    'flex items-center space-x-2 px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-300 transform',
                    activeFilter === 'expiring'
                        ? 'bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/30 scale-105'
                        : 'bg-white text-gray-600 shadow-sm hover:shadow-md hover:scale-[1.02]'
                ]"
            >
                <ClockIcon class="w-4 h-4" />
                <span>Expirent bientot</span>
            </button>
        </div>

        <!-- Empty State with Animation -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="filteredContracts.length === 0" class="text-center py-16">
                <div class="w-28 h-28 mx-auto mb-6 bg-gradient-to-br from-primary-100 to-primary-200 rounded-3xl flex items-center justify-center transform rotate-3">
                    <CubeIcon class="w-14 h-14 text-primary-500" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun box trouve</h3>
                <p class="text-gray-500 mb-6 max-w-xs mx-auto">
                    {{ searchQuery ? 'Aucun resultat pour votre recherche' : 'Vous n\'avez pas encore de box loue' }}
                </p>
                <Link
                    :href="route('mobile.reserve')"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/30 transform transition-all duration-300 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Reserver un box
                </Link>
            </div>
        </Transition>

        <!-- Box List with Stagger Animation -->
        <TransitionGroup
            v-if="filteredContracts.length > 0"
            tag="div"
            class="space-y-5"
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-x-full"
        >
            <div
                v-for="(contract, index) in filteredContracts"
                :key="contract.id"
                :style="{ transitionDelay: `${index * 75}ms` }"
                class="bg-white rounded-3xl shadow-sm overflow-hidden transform transition-all duration-300 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99]"
            >
                <!-- Box Visual Header -->
                <div class="relative">
                    <!-- Box Image/Illustration Area -->
                    <div
                        class="h-36 relative overflow-hidden"
                        :class="getBoxGradientClass(contract.status)"
                    >
                        <!-- Decorative Elements -->
                        <div class="absolute inset-0">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/10 rounded-full"></div>
                            <div class="absolute -bottom-20 -left-10 w-48 h-48 bg-white/5 rounded-full"></div>
                            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                                <div class="w-20 h-20 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                                    <CubeIcon class="w-10 h-10 text-white" />
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4">
                            <span
                                class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide backdrop-blur-sm"
                                :class="getStatusBadgeClass(contract.status)"
                            >
                                {{ getStatusLabel(contract.status) }}
                            </span>
                        </div>

                        <!-- Box Name Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/50 to-transparent">
                            <h3 class="font-bold text-white text-2xl">{{ contract.box?.name }}</h3>
                            <p class="text-white/80 flex items-center text-sm mt-1">
                                <MapPinIcon class="w-4 h-4 mr-1.5" />
                                {{ contract.box?.site?.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Box Stats Grid -->
                <div class="p-4 bg-gradient-to-r from-gray-50 to-white">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-white rounded-2xl shadow-sm">
                            <div class="w-8 h-8 mx-auto mb-2 bg-blue-100 rounded-lg flex items-center justify-center">
                                <Square3Stack3DIcon class="w-4 h-4 text-blue-600" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500 font-medium">Surface</p>
                            <p class="font-bold text-gray-900 mt-0.5">{{ contract.box?.area || 'N/A' }} m<sup>2</sup></p>
                        </div>
                        <div class="text-center p-3 bg-gradient-to-br from-primary-50 to-primary-100 rounded-2xl shadow-sm">
                            <div class="w-8 h-8 mx-auto mb-2 bg-primary-500 rounded-lg flex items-center justify-center">
                                <CurrencyEuroIcon class="w-4 h-4 text-white" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-primary-600 font-medium">Prix/mois</p>
                            <p class="font-bold text-primary-600 mt-0.5">{{ contract.monthly_price }}EUR</p>
                        </div>
                        <div class="text-center p-3 bg-white rounded-2xl shadow-sm">
                            <div class="w-8 h-8 mx-auto mb-2 bg-green-100 rounded-lg flex items-center justify-center">
                                <CalendarIcon class="w-4 h-4 text-green-600" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500 font-medium">Depuis</p>
                            <p class="font-bold text-gray-900 mt-0.5 text-xs">{{ formatDate(contract.start_date) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contract Info Section -->
                <div class="px-4 py-3 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center mr-3">
                                <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Contrat</p>
                                <p class="font-semibold text-gray-900">{{ contract.contract_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Fin du contrat</p>
                            <p class="font-semibold text-gray-900">{{ formatDate(contract.end_date) }}</p>
                        </div>
                    </div>

                    <!-- Progress bar with Animation -->
                    <div v-if="contract.status === 'active'" class="mb-4">
                        <div class="flex justify-between text-xs mb-2">
                            <span class="text-gray-500 font-medium">Progression du contrat</span>
                            <span class="font-bold text-primary-600">{{ getProgressPercent(contract) }}%</span>
                        </div>
                        <div class="relative w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div
                                class="absolute left-0 top-0 h-full bg-gradient-to-r from-primary-400 to-primary-600 rounded-full transition-all duration-1000 ease-out"
                                :style="{ width: getProgressPercent(contract) + '%' }"
                            >
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent animate-shimmer"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Expiring Soon Warning -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div
                            v-if="isExpiringSoon(contract)"
                            class="mb-4 p-3 bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl flex items-center border border-amber-100"
                        >
                            <div class="w-8 h-8 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center mr-3 animate-pulse">
                                <ExclamationTriangleIcon class="w-4 h-4 text-white" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-amber-800">Expire bientot</p>
                                <p class="text-xs text-amber-600">Pensez a renouveler</p>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-amber-400" />
                        </div>
                    </Transition>

                    <!-- Actions -->
                    <div class="grid grid-cols-3 gap-2">
                        <Link
                            :href="route('mobile.contracts.show', contract.id)"
                            class="flex flex-col items-center justify-center py-3 bg-gradient-to-br from-primary-50 to-primary-100 text-primary-600 rounded-xl font-semibold text-xs transition-all duration-300 hover:from-primary-100 hover:to-primary-200 active:scale-[0.98]"
                        >
                            <DocumentTextIcon class="w-5 h-5 mb-1" />
                            Details
                        </Link>
                        <Link
                            :href="route('mobile.access', { contract_id: contract.id })"
                            class="flex flex-col items-center justify-center py-3 bg-gradient-to-br from-purple-50 to-purple-100 text-purple-600 rounded-xl font-semibold text-xs transition-all duration-300 hover:from-purple-100 hover:to-purple-200 active:scale-[0.98]"
                        >
                            <KeyIcon class="w-5 h-5 mb-1" />
                            Acces
                        </Link>
                        <button
                            @click="openInvoices(contract.id)"
                            class="flex flex-col items-center justify-center py-3 bg-gradient-to-br from-green-50 to-emerald-100 text-green-600 rounded-xl font-semibold text-xs transition-all duration-300 hover:from-green-100 hover:to-emerald-200 active:scale-[0.98]"
                        >
                            <CurrencyEuroIcon class="w-5 h-5 mb-1" />
                            Factures
                        </button>
                    </div>
                </div>
            </div>
        </TransitionGroup>

        <!-- Floating Add Button -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-0"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-0"
        >
            <div class="fixed bottom-24 right-4 z-40">
                <Link
                    :href="route('mobile.reserve')"
                    class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 text-white rounded-2xl flex items-center justify-center shadow-xl shadow-primary-500/40 transform transition-all duration-300 hover:shadow-2xl hover:scale-110 active:scale-95"
                >
                    <PlusIcon class="w-8 h-8" />
                </Link>
                <!-- Pulse ring effect -->
                <div class="absolute inset-0 bg-primary-500 rounded-2xl animate-ping opacity-25"></div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CubeIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    MapPinIcon,
    DocumentTextIcon,
    KeyIcon,
    CurrencyEuroIcon,
    Squares2X2Icon,
    CheckCircleIcon,
    ClockIcon,
    XMarkIcon,
    Square3Stack3DIcon,
    CalendarIcon,
    ExclamationTriangleIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contracts: Array,
})

const searchQuery = ref('')
const activeFilter = ref('all')

const activeCount = computed(() => {
    return props.contracts?.filter(c => c.status === 'active').length || 0
})

const filteredContracts = computed(() => {
    let result = props.contracts || []

    // Filter by status
    if (activeFilter.value === 'active') {
        result = result.filter(c => c.status === 'active')
    } else if (activeFilter.value === 'expiring') {
        const thirtyDaysFromNow = new Date()
        thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)
        result = result.filter(c => {
            if (c.status !== 'active') return false
            const endDate = new Date(c.end_date)
            return endDate <= thirtyDaysFromNow
        })
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(c =>
            c.box?.name?.toLowerCase().includes(query) ||
            c.contract_number?.toLowerCase().includes(query) ||
            c.box?.site?.name?.toLowerCase().includes(query)
        )
    }

    return result
})

const isExpiringSoon = (contract) => {
    if (contract.status !== 'active') return false
    const thirtyDaysFromNow = new Date()
    thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30)
    const endDate = new Date(contract.end_date)
    return endDate <= thirtyDaysFromNow
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        expiring: 'Expire bientot',
        terminated: 'Termine',
        pending: 'En attente',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-green-500/80 text-white',
        expiring: 'bg-amber-500/80 text-white',
        terminated: 'bg-gray-500/80 text-white',
        pending: 'bg-yellow-500/80 text-white',
    }
    return classes[status] || 'bg-gray-500/80 text-white'
}

const getBoxGradientClass = (status) => {
    const classes = {
        active: 'bg-gradient-to-br from-green-400 via-emerald-500 to-teal-600',
        expiring: 'bg-gradient-to-br from-amber-400 via-orange-500 to-red-500',
        terminated: 'bg-gradient-to-br from-gray-400 via-gray-500 to-gray-600',
        pending: 'bg-gradient-to-br from-yellow-400 via-amber-500 to-orange-500',
    }
    return classes[status] || 'bg-gradient-to-br from-primary-400 via-primary-500 to-primary-600'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const getProgressPercent = (contract) => {
    if (!contract.start_date || !contract.end_date) return 0
    const start = new Date(contract.start_date).getTime()
    const end = new Date(contract.end_date).getTime()
    const now = Date.now()

    if (now >= end) return 100
    if (now <= start) return 0

    return Math.round(((now - start) / (end - start)) * 100)
}

const openInvoices = (contractId) => {
    router.visit(route('mobile.invoices', { contract_id: contractId }))
}
</script>

<style scoped>
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
