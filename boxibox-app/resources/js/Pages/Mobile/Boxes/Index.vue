<template>
    <MobileLayout title="Mes Box">
        <!-- Search and Filter -->
        <div class="mb-4">
            <div class="relative">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Rechercher un box..."
                    class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
                />
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                @click="activeFilter = 'all'"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeFilter === 'all' ? 'bg-primary-600 text-white' : 'bg-white text-gray-700'
                ]"
            >
                Tous ({{ contracts.length }})
            </button>
            <button
                @click="activeFilter = 'active'"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeFilter === 'active' ? 'bg-green-600 text-white' : 'bg-white text-gray-700'
                ]"
            >
                Actifs ({{ activeCount }})
            </button>
            <button
                @click="activeFilter = 'expiring'"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    activeFilter === 'expiring' ? 'bg-yellow-600 text-white' : 'bg-white text-gray-700'
                ]"
            >
                Expirent bientot
            </button>
        </div>

        <!-- Empty State -->
        <div v-if="filteredContracts.length === 0" class="text-center py-12">
            <CubeIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun box trouve</h3>
            <p class="text-gray-500 mb-4">
                {{ searchQuery ? 'Aucun resultat pour votre recherche' : 'Vous n\'avez pas encore de box loue' }}
            </p>
            <Link
                :href="route('mobile.reserve')"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg"
            >
                <PlusIcon class="w-5 h-5 mr-2" />
                Reserver un box
            </Link>
        </div>

        <!-- Box List -->
        <div v-else class="space-y-4">
            <div
                v-for="contract in filteredContracts"
                :key="contract.id"
                class="bg-white rounded-2xl shadow-sm overflow-hidden"
            >
                <!-- Box Header -->
                <div class="p-4 pb-3">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div
                                class="w-14 h-14 rounded-xl flex items-center justify-center mr-3"
                                :class="{
                                    'bg-green-100': contract.status === 'active',
                                    'bg-yellow-100': contract.status === 'expiring',
                                    'bg-gray-100': contract.status === 'terminated'
                                }"
                            >
                                <CubeIcon
                                    class="w-7 h-7"
                                    :class="{
                                        'text-green-600': contract.status === 'active',
                                        'text-yellow-600': contract.status === 'expiring',
                                        'text-gray-600': contract.status === 'terminated'
                                    }"
                                />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 text-lg">{{ contract.box?.name }}</h3>
                                <p class="text-sm text-gray-500 flex items-center mt-0.5">
                                    <MapPinIcon class="w-4 h-4 mr-1" />
                                    {{ contract.box?.site?.name }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium"
                            :class="{
                                'bg-green-100 text-green-700': contract.status === 'active',
                                'bg-yellow-100 text-yellow-700': contract.status === 'expiring',
                                'bg-gray-100 text-gray-700': contract.status === 'terminated'
                            }"
                        >
                            {{ getStatusLabel(contract.status) }}
                        </span>
                    </div>
                </div>

                <!-- Box Details -->
                <div class="px-4 py-3 bg-gray-50 grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Surface</p>
                        <p class="font-semibold text-gray-900">{{ contract.box?.area || 'N/A' }} m²</p>
                    </div>
                    <div class="text-center border-x border-gray-200">
                        <p class="text-xs text-gray-500">Prix/mois</p>
                        <p class="font-semibold text-primary-600">{{ contract.monthly_price }}€</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs text-gray-500">Depuis</p>
                        <p class="font-semibold text-gray-900">{{ formatDate(contract.start_date) }}</p>
                    </div>
                </div>

                <!-- Contract Info -->
                <div class="p-4 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-xs text-gray-500">Contrat</p>
                            <p class="font-medium text-gray-900">{{ contract.contract_number }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Fin du contrat</p>
                            <p class="font-medium text-gray-900">{{ formatDate(contract.end_date) }}</p>
                        </div>
                    </div>

                    <!-- Progress bar for contract duration -->
                    <div class="mb-4" v-if="contract.status === 'active'">
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Progression</span>
                            <span>{{ getProgressPercent(contract) }}%</span>
                        </div>
                        <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="h-full bg-primary-500 rounded-full transition-all duration-300"
                                :style="{ width: getProgressPercent(contract) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <Link
                            :href="route('mobile.contracts.show', contract.id)"
                            class="flex-1 flex items-center justify-center py-2.5 bg-primary-50 text-primary-600 rounded-lg font-medium text-sm"
                        >
                            <DocumentTextIcon class="w-4 h-4 mr-1.5" />
                            Details
                        </Link>
                        <Link
                            :href="route('mobile.access', { contract_id: contract.id })"
                            class="flex-1 flex items-center justify-center py-2.5 bg-purple-50 text-purple-600 rounded-lg font-medium text-sm"
                        >
                            <KeyIcon class="w-4 h-4 mr-1.5" />
                            Acces
                        </Link>
                        <button
                            @click="openInvoices(contract.id)"
                            class="flex-1 flex items-center justify-center py-2.5 bg-green-50 text-green-600 rounded-lg font-medium text-sm"
                        >
                            <CurrencyEuroIcon class="w-4 h-4 mr-1.5" />
                            Factures
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New Box Button -->
        <div class="fixed bottom-24 right-4">
            <Link
                :href="route('mobile.reserve')"
                class="w-14 h-14 bg-primary-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-primary-700 transition"
            >
                <PlusIcon class="w-7 h-7" />
            </Link>
        </div>
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

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        expiring: 'Expire bientot',
        terminated: 'Termine',
        pending: 'En attente',
    }
    return labels[status] || status
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
