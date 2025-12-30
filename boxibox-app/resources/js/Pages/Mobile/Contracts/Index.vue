<template>
    <MobileLayout title="Mes Contrats">
        <!-- Stats Cards with Gradient -->
        <div class="grid grid-cols-2 gap-3 mb-6">
            <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl p-4 shadow-lg transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
                <div class="absolute -right-2 -bottom-6 w-16 h-16 bg-white/5 rounded-full"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3 backdrop-blur-sm">
                        <CheckCircleIcon class="w-5 h-5 text-white" />
                    </div>
                    <p class="text-3xl font-bold text-white">{{ stats.active }}</p>
                    <p class="text-white/80 text-sm mt-1">Contrats actifs</p>
                </div>
            </div>
            <div class="relative overflow-hidden bg-gradient-to-br from-amber-400 to-orange-500 rounded-2xl p-4 shadow-lg transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                <div class="absolute -right-4 -top-4 w-20 h-20 bg-white/10 rounded-full"></div>
                <div class="absolute -right-2 -bottom-6 w-16 h-16 bg-white/5 rounded-full"></div>
                <div class="relative z-10">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mb-3 backdrop-blur-sm">
                        <ClockIcon class="w-5 h-5 text-white" />
                    </div>
                    <p class="text-3xl font-bold text-white">{{ stats.expiring_soon }}</p>
                    <p class="text-white/80 text-sm mt-1">Expirent bientot</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs with Animation -->
        <div class="flex space-x-2 mb-5 overflow-x-auto pb-2 scrollbar-hide">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                :class="[
                    'px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-300 transform',
                    activeFilter === filter.value
                        ? `${filter.activeClass} text-white shadow-lg scale-105`
                        : 'bg-white text-gray-600 shadow-sm hover:shadow-md hover:scale-[1.02]'
                ]"
            >
                <span class="flex items-center space-x-1.5">
                    <component :is="filter.icon" class="w-4 h-4" />
                    <span>{{ filter.label }}</span>
                </span>
            </button>
        </div>

        <!-- Empty State with Animation -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="filteredContracts.length === 0" class="text-center py-16">
                <div class="w-24 h-24 mx-auto mb-6 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center">
                    <DocumentTextIcon class="w-12 h-12 text-gray-400" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun contrat</h3>
                <p class="text-gray-500 mb-6 max-w-xs mx-auto">Vous n'avez pas encore de contrat actif</p>
                <Link
                    :href="route('mobile.reserve')"
                    class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-500 to-primary-600 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/30 transform transition-all duration-300 hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Reserver un box
                </Link>
            </div>
        </Transition>

        <!-- Contract List with Stagger Animation -->
        <TransitionGroup
            v-if="filteredContracts.length > 0"
            tag="div"
            class="space-y-4"
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
                :style="{ transitionDelay: `${index * 50}ms` }"
                class="bg-white rounded-3xl shadow-sm overflow-hidden transform transition-all duration-300 hover:shadow-lg hover:scale-[1.01] active:scale-[0.99]"
            >
                <!-- Contract Header with Visual Element -->
                <div class="relative">
                    <!-- Status Color Bar -->
                    <div
                        class="absolute left-0 top-0 bottom-0 w-1.5 rounded-r-full"
                        :class="{
                            'bg-gradient-to-b from-green-400 to-green-600': contract.status === 'active',
                            'bg-gradient-to-b from-gray-400 to-gray-600': contract.status === 'terminated',
                            'bg-gradient-to-b from-yellow-400 to-yellow-600': contract.status === 'pending',
                            'bg-gradient-to-b from-red-400 to-red-600': contract.status === 'cancelled'
                        }"
                    ></div>

                    <div class="p-5 pl-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <!-- Animated Icon Container -->
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center mr-4 shadow-inner transition-transform duration-300 hover:rotate-3"
                                    :class="getStatusBgClass(contract.status)"
                                >
                                    <component
                                        :is="getContractIcon(contract)"
                                        class="w-7 h-7 transition-all duration-300"
                                        :class="getStatusTextClass(contract.status)"
                                    />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">{{ contract.contract_number }}</h3>
                                    <p class="text-sm text-gray-500 flex items-center mt-0.5">
                                        <CubeIcon class="w-4 h-4 mr-1" />
                                        {{ contract.box?.name }}
                                    </p>
                                </div>
                            </div>
                            <!-- Animated Status Badge -->
                            <span
                                class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide transition-all duration-300"
                                :class="getStatusBadgeClass(contract.status)"
                            >
                                {{ getStatusLabel(contract.status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contract Details Grid -->
                <div class="px-5 py-4 bg-gradient-to-r from-gray-50 to-gray-100/50">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-2 rounded-xl bg-white/60 backdrop-blur-sm">
                            <div class="flex items-center justify-center mb-1">
                                <CalendarIcon class="w-4 h-4 text-gray-400" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500 font-medium">Debut</p>
                            <p class="font-bold text-gray-900 text-sm mt-0.5">{{ formatDate(contract.start_date) }}</p>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-white/60 backdrop-blur-sm border-x border-gray-100">
                            <div class="flex items-center justify-center mb-1">
                                <CalendarDaysIcon class="w-4 h-4 text-gray-400" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-gray-500 font-medium">Fin</p>
                            <p class="font-bold text-gray-900 text-sm mt-0.5">{{ formatDate(contract.end_date) }}</p>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-gradient-to-br from-primary-50 to-primary-100">
                            <div class="flex items-center justify-center mb-1">
                                <CurrencyEuroIcon class="w-4 h-4 text-primary-500" />
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-primary-600 font-medium">Mensuel</p>
                            <p class="font-bold text-primary-600 text-sm mt-0.5">{{ contract.monthly_price }}EUR</p>
                        </div>
                    </div>
                </div>

                <!-- Expiring Warning with Animation -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                >
                    <div
                        v-if="contract.days_until_expiry && contract.days_until_expiry <= 30"
                        class="px-5 py-3 bg-gradient-to-r from-amber-50 to-orange-50 flex items-center border-t border-amber-100"
                    >
                        <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center mr-3 animate-pulse">
                            <ExclamationTriangleIcon class="w-4 h-4 text-amber-600" />
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-amber-800">
                                Expire dans {{ contract.days_until_expiry }} jours
                            </p>
                            <p class="text-xs text-amber-600">Pensez a renouveler votre contrat</p>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-amber-400" />
                    </div>
                </Transition>

                <!-- Actions with Hover Effects -->
                <div class="p-4 pt-3 border-t border-gray-100 bg-white">
                    <div class="flex space-x-2">
                        <Link
                            :href="route('mobile.contracts.show', contract.id)"
                            class="flex-1 flex items-center justify-center py-3 bg-gradient-to-r from-primary-50 to-primary-100 text-primary-600 rounded-xl font-semibold text-sm transition-all duration-300 hover:from-primary-100 hover:to-primary-200 active:scale-[0.98]"
                        >
                            <EyeIcon class="w-4 h-4 mr-2" />
                            Details
                        </Link>
                        <a
                            :href="route('mobile.contracts.pdf', contract.id)"
                            target="_blank"
                            class="flex-1 flex items-center justify-center py-3 bg-gray-100 text-gray-700 rounded-xl font-semibold text-sm transition-all duration-300 hover:bg-gray-200 active:scale-[0.98]"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                            PDF
                        </a>
                        <button
                            v-if="contract.status === 'active'"
                            @click="showRenewalModal(contract)"
                            class="flex-1 flex items-center justify-center py-3 bg-gradient-to-r from-green-50 to-emerald-100 text-green-600 rounded-xl font-semibold text-sm transition-all duration-300 hover:from-green-100 hover:to-emerald-200 active:scale-[0.98]"
                        >
                            <ArrowPathIcon class="w-4 h-4 mr-2" />
                            Renouveler
                        </button>
                    </div>
                </div>
            </div>
        </TransitionGroup>

        <!-- Renewal Modal with Enhanced Design -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="renewalContract" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="renewalContract = null"></div>
                <Transition
                    enter-active-class="transition-all duration-300 delay-100"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div v-if="renewalContract" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[2rem] shadow-2xl">
                        <!-- Handle Bar -->
                        <div class="flex justify-center pt-4 pb-2">
                            <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                        </div>

                        <div class="px-6 pb-6">
                            <!-- Header with Icon -->
                            <div class="text-center mb-6">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-green-400 to-emerald-500 rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/30">
                                    <ArrowPathIcon class="w-8 h-8 text-white" />
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Renouveler le contrat</h3>
                                <p class="text-gray-500 mt-1">Prolongez votre location en quelques clics</p>
                            </div>

                            <!-- Contract Summary Card -->
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5 mb-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-sm mr-4">
                                        <CubeIcon class="w-6 h-6 text-primary-600" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ renewalContract.box?.name }}</p>
                                        <p class="text-sm text-gray-500">{{ renewalContract.contract_number }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="bg-white rounded-xl p-3 text-center">
                                        <p class="text-xs text-gray-500 mb-1">Prix mensuel</p>
                                        <p class="font-bold text-primary-600 text-lg">{{ renewalContract.monthly_price }}EUR</p>
                                    </div>
                                    <div class="bg-white rounded-xl p-3 text-center">
                                        <p class="text-xs text-gray-500 mb-1">Fin actuelle</p>
                                        <p class="font-bold text-gray-900">{{ formatDate(renewalContract.end_date) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Duration Selection -->
                            <div class="mb-6">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Duree du renouvellement</label>
                                <div class="grid grid-cols-3 gap-3">
                                    <button
                                        v-for="duration in renewalDurations"
                                        :key="duration.months"
                                        @click="selectedDuration = duration.months"
                                        :class="[
                                            'relative py-4 rounded-2xl text-center font-bold transition-all duration-300 overflow-hidden',
                                            selectedDuration === duration.months
                                                ? 'bg-gradient-to-br from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105'
                                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                        ]"
                                    >
                                        <span class="text-2xl">{{ duration.months }}</span>
                                        <span class="block text-xs opacity-80 mt-0.5">{{ duration.label }}</span>
                                        <div v-if="selectedDuration === duration.months" class="absolute top-2 right-2">
                                            <CheckCircleIcon class="w-5 h-5 text-white/80" />
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- New End Date Preview -->
                            <div class="bg-gradient-to-r from-primary-50 to-primary-100 rounded-2xl p-4 mb-6 flex items-center justify-between">
                                <div class="flex items-center">
                                    <CalendarDaysIcon class="w-6 h-6 text-primary-600 mr-3" />
                                    <span class="text-primary-700 font-medium">Nouvelle date de fin</span>
                                </div>
                                <span class="font-bold text-primary-700 text-lg">{{ getNewEndDate() }}</span>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-3">
                                <button
                                    @click="renewalContract = null"
                                    class="flex-1 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl transition-all duration-300 hover:bg-gray-200 active:scale-[0.98]"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="confirmRenewal"
                                    class="flex-1 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold rounded-2xl shadow-lg shadow-green-500/30 transition-all duration-300 hover:shadow-xl active:scale-[0.98]"
                                >
                                    Confirmer
                                </button>
                            </div>
                        </div>
                        <!-- Safe Area -->
                        <div class="h-8 bg-white"></div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentTextIcon,
    CheckCircleIcon,
    ClockIcon,
    PlusIcon,
    EyeIcon,
    ArrowDownTrayIcon,
    ArrowPathIcon,
    ExclamationTriangleIcon,
    CubeIcon,
    CalendarIcon,
    CalendarDaysIcon,
    CurrencyEuroIcon,
    ChevronRightIcon,
    Squares2X2Icon,
    FunnelIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contracts: Array,
    stats: Object,
})

const activeFilter = ref('all')
const renewalContract = ref(null)
const selectedDuration = ref(12)

const filters = [
    { value: 'all', label: 'Tous', activeClass: 'bg-gradient-to-r from-primary-500 to-primary-600', icon: Squares2X2Icon },
    { value: 'active', label: 'Actifs', activeClass: 'bg-gradient-to-r from-green-500 to-emerald-600', icon: CheckCircleIcon },
    { value: 'expiring', label: 'Expirent bientot', activeClass: 'bg-gradient-to-r from-amber-500 to-orange-500', icon: ClockIcon },
    { value: 'terminated', label: 'Termines', activeClass: 'bg-gradient-to-r from-gray-500 to-gray-600', icon: DocumentTextIcon },
]

const renewalDurations = [
    { months: 1, label: 'mois' },
    { months: 6, label: 'mois' },
    { months: 12, label: 'mois' },
]

const filteredContracts = computed(() => {
    let result = props.contracts || []

    if (activeFilter.value === 'active') {
        result = result.filter(c => c.status === 'active')
    } else if (activeFilter.value === 'expiring') {
        result = result.filter(c => c.status === 'active' && c.days_until_expiry && c.days_until_expiry <= 30)
    } else if (activeFilter.value === 'terminated') {
        result = result.filter(c => c.status === 'terminated')
    }

    return result
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        terminated: 'Termine',
        pending: 'En attente',
        cancelled: 'Annule',
    }
    return labels[status] || status
}

const getStatusBgClass = (status) => {
    const classes = {
        active: 'bg-gradient-to-br from-green-100 to-emerald-100',
        terminated: 'bg-gradient-to-br from-gray-100 to-gray-200',
        pending: 'bg-gradient-to-br from-yellow-100 to-amber-100',
        cancelled: 'bg-gradient-to-br from-red-100 to-rose-100',
    }
    return classes[status] || 'bg-gray-100'
}

const getStatusTextClass = (status) => {
    const classes = {
        active: 'text-green-600',
        terminated: 'text-gray-600',
        pending: 'text-yellow-600',
        cancelled: 'text-red-600',
    }
    return classes[status] || 'text-gray-600'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-700',
        terminated: 'bg-gray-100 text-gray-700',
        pending: 'bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-700',
        cancelled: 'bg-gradient-to-r from-red-100 to-rose-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getContractIcon = (contract) => {
    if (contract.status === 'active') return CheckCircleIcon
    if (contract.status === 'terminated') return DocumentTextIcon
    return DocumentTextIcon
}

const showRenewalModal = (contract) => {
    renewalContract.value = contract
    selectedDuration.value = 12
}

const getNewEndDate = () => {
    if (!renewalContract.value) return '-'
    const endDate = new Date(renewalContract.value.end_date)
    endDate.setMonth(endDate.getMonth() + selectedDuration.value)
    return formatDate(endDate)
}

const confirmRenewal = () => {
    router.post(route('mobile.contracts.renew', renewalContract.value.id), {
        duration_months: selectedDuration.value,
    }, {
        onSuccess: () => {
            renewalContract.value = null
        },
    })
}
</script>
