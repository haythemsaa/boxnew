<template>
    <AuthenticatedLayout title="Plan - État des boxes">
        <!-- Success Message -->
        <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>

        <!-- Header with statistics -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <svg class="h-8 w-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-700">Plans - État des boxes</h2>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('tenant.boxes.plan.edit')"
                        class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Éditer le plan
                    </Link>
                    <Link
                        :href="route('tenant.boxes.index')"
                        class="text-gray-600 hover:text-gray-900 font-medium px-4 py-2 rounded-lg border border-gray-300 transition-colors"
                    >
                        ← Retour à la liste
                    </Link>
                </div>
            </div>

            <!-- Statistics banner -->
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="flex items-center gap-2 text-cyan-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="font-semibold">
                        PLAN - NB BOX : {{ stats.total }} - OCCUPÉ : {{ stats.occupied }} - LIBRE : {{ stats.available }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="mb-6 bg-white rounded-lg shadow-sm p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Légende :</h3>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-cyan-500 border border-gray-300 rounded"></div>
                    <span class="text-sm text-gray-700">Occupé ({{ stats.occupied }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-green-500 border border-gray-300 rounded"></div>
                    <span class="text-sm text-gray-700">Libre ({{ stats.available }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-yellow-400 border border-gray-300 rounded"></div>
                    <span class="text-sm text-gray-700">Réservé ({{ stats.reserved }})</span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-orange-400 border border-gray-300 rounded"></div>
                    <span class="text-sm text-gray-700">Maintenance ({{ stats.maintenance }})</span>
                </div>
            </div>
        </div>

        <!-- Plan view -->
        <div class="bg-white rounded-lg shadow-lg p-6 overflow-auto">
            <div class="relative min-h-[800px] bg-gray-50 border-2 border-gray-200 rounded-lg p-4">
                <!-- Boxes positioned on the plan -->
                <div
                    v-for="box in boxes"
                    :key="box.id"
                    :style="getBoxStyle(box)"
                    @click="selectBox(box)"
                    class="absolute cursor-pointer border-2 border-gray-700 rounded flex flex-col items-center justify-center text-xs font-semibold transition-all hover:shadow-lg hover:scale-105 hover:z-10"
                    :class="getBoxColorClass(box.status)"
                    :title="`${box.number} - ${box.volume}m³ - ${box.status}`"
                >
                    <div class="text-center">
                        <div class="font-bold">{{ box.number }}</div>
                        <div class="text-[10px]">{{ box.volume }}m³</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box Details Modal -->
        <div v-if="selectedBox" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75" @click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header -->
                    <div :class="selectedBox.status === 'available' ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gradient-to-r from-cyan-600 to-blue-600'" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                <h3 class="text-xl font-bold text-white">Box {{ selectedBox.number }}</h3>
                            </div>
                            <button @click="closeModal" class="text-white hover:text-gray-200 transition-colors">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="bg-white px-6 py-6">
                        <!-- Available Box - Create Contract -->
                        <div v-if="selectedBox.status === 'available'" class="text-center py-8">
                            <svg class="mx-auto h-16 w-16 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h4 class="text-2xl font-bold text-gray-900 mb-2">Box Disponible</h4>
                            <p class="text-gray-600 mb-6">Cette box est libre et prête à être louée</p>

                            <div class="bg-gray-50 rounded-lg p-6 mb-6 space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Numéro :</span>
                                    <span class="text-base font-semibold text-gray-900">{{ selectedBox.number }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Volume :</span>
                                    <span class="text-base font-semibold text-gray-900">{{ selectedBox.volume }} m³</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500">Prix mensuel :</span>
                                    <span class="text-lg font-bold text-primary-600">{{ selectedBox.current_price }} €</span>
                                </div>
                            </div>

                            <Link
                                :href="route('tenant.contracts.create', { box_id: selectedBox.id })"
                                class="inline-flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-3 rounded-lg font-medium transition-colors shadow-lg"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Créer un nouveau contrat
                            </Link>
                        </div>

                        <!-- Occupied Box - Show Details -->
                        <div v-else class="space-y-6">
                            <!-- Box Information -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Informations Box
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Numéro</label>
                                        <p class="text-sm font-semibold text-gray-900">{{ selectedBox.number }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Volume</label>
                                        <p class="text-sm font-semibold text-gray-900">{{ selectedBox.volume }} m³</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">État</label>
                                        <p>
                                            <span :class="getStatusBadgeClass(selectedBox.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                                {{ getStatusLabel(selectedBox.status) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Prix mensuel</label>
                                        <p class="text-sm font-bold text-primary-600">{{ selectedBox.current_price }} €</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Contract Information -->
                            <div v-if="selectedBox.contract" class="bg-blue-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Contrat
                                </h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Numéro contrat</label>
                                        <p class="text-sm font-semibold text-gray-900">{{ selectedBox.contract.contract_number }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs font-medium text-gray-500">Date début</label>
                                        <p class="text-sm font-semibold text-gray-900">{{ formatDate(selectedBox.contract.start_date) }}</p>
                                    </div>
                                </div>
                                <Link
                                    :href="route('tenant.contracts.show', selectedBox.contract.id)"
                                    class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800 transition-colors"
                                >
                                    Voir le contrat
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>

                            <!-- Customer Information -->
                            <div v-if="selectedBox.contract && selectedBox.contract.customer" class="bg-green-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    Client
                                </h4>
                                <div>
                                    <label class="text-xs font-medium text-gray-500">Nom</label>
                                    <p class="text-sm font-semibold text-gray-900">{{ selectedBox.contract.customer.name }}</p>
                                </div>
                                <Link
                                    :href="route('tenant.customers.show', selectedBox.contract.customer.id)"
                                    class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-green-600 hover:text-green-800 transition-colors"
                                >
                                    Voir le client
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                        <Link
                            :href="route('tenant.boxes.show', selectedBox.id)"
                            class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium transition-colors"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Détails complets
                        </Link>
                        <button
                            @click="closeModal"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg font-medium transition-colors"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    boxes: Array,
    stats: Object,
})

const selectedBox = ref(null)

// Get box color class based on status
const getBoxColorClass = (status) => {
    const colors = {
        occupied: 'bg-cyan-500 text-white',
        available: 'bg-green-500 text-white',
        reserved: 'bg-yellow-400 text-gray-900',
        maintenance: 'bg-orange-400 text-white',
        unavailable: 'bg-gray-400 text-white',
    }
    return colors[status] || 'bg-gray-300 text-gray-900'
}

// Get status badge class
const getStatusBadgeClass = (status) => {
    const colors = {
        occupied: 'bg-cyan-100 text-cyan-800',
        available: 'bg-green-100 text-green-800',
        reserved: 'bg-yellow-100 text-yellow-800',
        maintenance: 'bg-orange-100 text-orange-800',
        unavailable: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

// Get status label in French
const getStatusLabel = (status) => {
    const labels = {
        occupied: 'Occupé',
        available: 'Libre',
        reserved: 'Réservé',
        maintenance: 'Maintenance',
        unavailable: 'Indisponible',
    }
    return labels[status] || status
}

// Calculate box position and size
const getBoxStyle = (box) => {
    // Default position if not set
    if (!box.position || !box.position.x || !box.position.y) {
        // Auto-arrange boxes in a grid if no position is set
        const index = props.boxes.findIndex(b => b.id === box.id)
        const cols = 15
        const col = index % cols
        const row = Math.floor(index / cols)

        return {
            left: `${col * 80 + 10}px`,
            top: `${row * 70 + 10}px`,
            width: '70px',
            height: '60px',
        }
    }

    // Use position from database
    return {
        left: `${box.position.x}px`,
        top: `${box.position.y}px`,
        width: `${box.position.width || 70}px`,
        height: `${box.position.height || 60}px`,
    }
}

// Select a box to show details
const selectBox = (box) => {
    selectedBox.value = box
}

// Close modal
const closeModal = () => {
    selectedBox.value = null
}

// Format date
const formatDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}
</script>
