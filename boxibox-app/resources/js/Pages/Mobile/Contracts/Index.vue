<template>
    <MobileLayout title="Mes Contrats">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-white rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-sm">Actifs</span>
                    <CheckCircleIcon class="w-5 h-5 text-green-500" />
                </div>
                <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-500 text-sm">Expirent bientot</span>
                    <ClockIcon class="w-5 h-5 text-yellow-500" />
                </div>
                <p class="text-2xl font-bold text-yellow-600">{{ stats.expiring_soon }}</p>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="activeFilter = filter.value"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeFilter === filter.value
                        ? `${filter.activeClass} text-white`
                        : 'bg-white text-gray-700'
                ]"
            >
                {{ filter.label }}
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="filteredContracts.length === 0" class="text-center py-12">
            <DocumentTextIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun contrat</h3>
            <p class="text-gray-500 mb-4">Vous n'avez pas encore de contrat</p>
            <Link
                :href="route('mobile.reserve')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Reserver un box
            </Link>
        </div>

        <!-- Contract List -->
        <div v-else class="space-y-4">
            <div
                v-for="contract in filteredContracts"
                :key="contract.id"
                class="bg-white rounded-2xl shadow-sm overflow-hidden"
            >
                <!-- Contract Header -->
                <div class="p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center mr-3"
                                :class="getStatusBgClass(contract.status)"
                            >
                                <DocumentTextIcon
                                    class="w-6 h-6"
                                    :class="getStatusTextClass(contract.status)"
                                />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ contract.contract_number }}</h3>
                                <p class="text-sm text-gray-500">{{ contract.box?.name }}</p>
                            </div>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium"
                            :class="getStatusBadgeClass(contract.status)"
                        >
                            {{ getStatusLabel(contract.status) }}
                        </span>
                    </div>
                </div>

                <!-- Contract Details -->
                <div class="px-4 py-3 bg-gray-50 grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Debut</p>
                        <p class="font-medium text-gray-900 text-sm">{{ formatDate(contract.start_date) }}</p>
                    </div>
                    <div class="text-center border-x border-gray-200">
                        <p class="text-xs text-gray-500">Fin</p>
                        <p class="font-medium text-gray-900 text-sm">{{ formatDate(contract.end_date) }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Mensuel</p>
                        <p class="font-bold text-primary-600 text-sm">{{ contract.monthly_price }}€</p>
                    </div>
                </div>

                <!-- Expiring Warning -->
                <div
                    v-if="contract.days_until_expiry && contract.days_until_expiry <= 30"
                    class="px-4 py-2 bg-yellow-50 flex items-center"
                >
                    <ExclamationTriangleIcon class="w-4 h-4 text-yellow-500 mr-2" />
                    <span class="text-sm text-yellow-700">
                        Expire dans {{ contract.days_until_expiry }} jours
                    </span>
                </div>

                <!-- Actions -->
                <div class="p-4 pt-3 border-t border-gray-100">
                    <div class="flex space-x-2">
                        <Link
                            :href="route('mobile.contracts.show', contract.id)"
                            class="flex-1 flex items-center justify-center py-2.5 bg-primary-50 text-primary-600 rounded-lg font-medium text-sm"
                        >
                            <EyeIcon class="w-4 h-4 mr-1.5" />
                            Details
                        </Link>
                        <a
                            :href="route('mobile.contracts.pdf', contract.id)"
                            target="_blank"
                            class="flex-1 flex items-center justify-center py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm"
                        >
                            <ArrowDownTrayIcon class="w-4 h-4 mr-1.5" />
                            PDF
                        </a>
                        <button
                            v-if="contract.status === 'active'"
                            @click="showRenewalModal(contract)"
                            class="flex-1 flex items-center justify-center py-2.5 bg-green-50 text-green-600 rounded-lg font-medium text-sm"
                        >
                            <ArrowPathIcon class="w-4 h-4 mr-1.5" />
                            Renouveler
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Renewal Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="renewalContract" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="renewalContract = null"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">Renouveler le contrat</h3>
                        <div class="bg-gray-50 rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-500">Box</span>
                                <span class="font-medium">{{ renewalContract.box?.name }}</span>
                            </div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-gray-500">Prix mensuel</span>
                                <span class="font-bold text-primary-600">{{ renewalContract.monthly_price }}€</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Fin actuelle</span>
                                <span class="font-medium">{{ formatDate(renewalContract.end_date) }}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Duree du renouvellement</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    v-for="duration in renewalDurations"
                                    :key="duration.months"
                                    @click="selectedDuration = duration.months"
                                    :class="[
                                        'py-3 rounded-xl text-center font-medium transition',
                                        selectedDuration === duration.months
                                            ? 'bg-primary-600 text-white'
                                            : 'bg-gray-100 text-gray-700'
                                    ]"
                                >
                                    {{ duration.label }}
                                </button>
                            </div>
                        </div>

                        <div class="bg-primary-50 rounded-xl p-4 mb-6">
                            <div class="flex items-center justify-between">
                                <span class="text-primary-700">Nouvelle date de fin</span>
                                <span class="font-bold text-primary-700">{{ getNewEndDate() }}</span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <button
                                @click="renewalContract = null"
                                class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                            >
                                Annuler
                            </button>
                            <button
                                @click="confirmRenewal"
                                class="flex-1 py-3 bg-primary-600 text-white font-semibold rounded-xl"
                            >
                                Confirmer
                            </button>
                        </div>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
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
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contracts: Array,
    stats: Object,
})

const activeFilter = ref('all')
const renewalContract = ref(null)
const selectedDuration = ref(12)

const filters = [
    { value: 'all', label: 'Tous', activeClass: 'bg-primary-600' },
    { value: 'active', label: 'Actifs', activeClass: 'bg-green-600' },
    { value: 'expiring', label: 'Expirent bientot', activeClass: 'bg-yellow-600' },
    { value: 'terminated', label: 'Termines', activeClass: 'bg-gray-600' },
]

const renewalDurations = [
    { months: 1, label: '1 mois' },
    { months: 6, label: '6 mois' },
    { months: 12, label: '12 mois' },
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
        active: 'bg-green-100',
        terminated: 'bg-gray-100',
        pending: 'bg-yellow-100',
        cancelled: 'bg-red-100',
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
        active: 'bg-green-100 text-green-700',
        terminated: 'bg-gray-100 text-gray-700',
        pending: 'bg-yellow-100 text-yellow-700',
        cancelled: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
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
