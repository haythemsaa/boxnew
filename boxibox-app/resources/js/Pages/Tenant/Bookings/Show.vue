<script setup>
import { ref } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    CalendarDaysIcon,
    UserIcon,
    CubeIcon,
    MapPinIcon,
    CurrencyEuroIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon,
    DocumentTextIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    GlobeAltIcon,
    TagIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    booking: Object,
})

const showRejectModal = ref(false)
const rejectReason = ref('')
const processing = ref(false)

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
        deposit_paid: 'bg-purple-100 text-purple-800 border-purple-200',
        active: 'bg-green-100 text-green-800 border-green-200',
        completed: 'bg-gray-100 text-gray-800 border-gray-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200',
        rejected: 'bg-red-100 text-red-800 border-red-200',
    }
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200'
}

const confirmBooking = () => {
    if (confirm('Confirmer cette réservation ?')) {
        processing.value = true
        router.post(route('tenant.bookings.confirm', props.booking.id), {}, {
            onFinish: () => processing.value = false,
        })
    }
}

const rejectBooking = () => {
    processing.value = true
    router.post(route('tenant.bookings.reject', props.booking.id), {
        reason: rejectReason.value,
    }, {
        onFinish: () => {
            processing.value = false
            showRejectModal.value = false
        },
    })
}

const cancelBooking = () => {
    if (confirm('Annuler cette réservation ?')) {
        processing.value = true
        router.post(route('tenant.bookings.cancel', props.booking.id), {}, {
            onFinish: () => processing.value = false,
        })
    }
}

const convertToContract = () => {
    if (confirm('Convertir cette réservation en contrat ?')) {
        processing.value = true
        router.post(route('tenant.bookings.convert', props.booking.id), {}, {
            onFinish: () => processing.value = false,
        })
    }
}
</script>

<template>
    <TenantLayout :title="`Réservation ${booking.booking_number}`">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-6xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.bookings.index')"
                    class="inline-flex items-center text-teal-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux réservations
                </Link>

                <div class="flex items-start justify-between">
                    <div class="flex items-center space-x-4">
                        <CalendarDaysIcon class="h-10 w-10 text-white" />
                        <div>
                            <h1 class="text-3xl font-bold text-white">{{ booking.booking_number }}</h1>
                            <p class="mt-1 text-teal-100">Créée le {{ booking.created_at }}</p>
                        </div>
                    </div>
                    <span
                        :class="getStatusClass(booking.status)"
                        class="px-4 py-2 rounded-xl text-sm font-medium border"
                    >
                        {{ booking.status_label }}
                    </span>
                </div>
            </div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <UserIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Informations client
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Nom complet</p>
                                <p class="font-medium text-gray-900">{{ booking.customer.full_name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium text-gray-900 flex items-center">
                                    <EnvelopeIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    {{ booking.customer.email }}
                                </p>
                            </div>
                            <div v-if="booking.customer.phone">
                                <p class="text-sm text-gray-500">Téléphone</p>
                                <p class="font-medium text-gray-900 flex items-center">
                                    <PhoneIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    {{ booking.customer.phone }}
                                </p>
                            </div>
                            <div v-if="booking.customer.company">
                                <p class="text-sm text-gray-500">Entreprise</p>
                                <p class="font-medium text-gray-900 flex items-center">
                                    <BuildingOfficeIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    {{ booking.customer.company }}
                                </p>
                            </div>
                            <div v-if="booking.customer.address" class="md:col-span-2">
                                <p class="text-sm text-gray-500">Adresse</p>
                                <p class="font-medium text-gray-900">
                                    {{ booking.customer.address }}<br />
                                    {{ booking.customer.postal_code }} {{ booking.customer.city }}, {{ booking.customer.country }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Box Info -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <CubeIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Box réservé
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Box</p>
                                <p class="font-medium text-gray-900">{{ booking.box.name }} ({{ booking.box.code }})</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Volume</p>
                                <p class="font-medium text-gray-900">{{ booking.box.volume }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Dimensions</p>
                                <p class="font-medium text-gray-900">{{ booking.box.dimensions }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Site</p>
                                <p class="font-medium text-gray-900 flex items-center">
                                    <MapPinIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    {{ booking.site.name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Status History -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <ClockIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Historique
                        </h2>
                        <div class="space-y-4">
                            <div
                                v-for="(history, index) in booking.history"
                                :key="index"
                                class="flex items-start space-x-3"
                            >
                                <div class="flex-shrink-0 w-2 h-2 mt-2 rounded-full bg-teal-500"></div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <p class="font-medium text-gray-900">
                                            {{ history.from_status || 'Création' }} → {{ history.to_status }}
                                        </p>
                                        <span class="text-sm text-gray-500">{{ history.created_at }}</span>
                                    </div>
                                    <p v-if="history.notes" class="text-sm text-gray-600">{{ history.notes }}</p>
                                    <p v-if="history.user" class="text-xs text-gray-400">par {{ history.user }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Pricing -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <CurrencyEuroIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Tarification
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Prix mensuel</span>
                                <span class="font-medium">{{ formatCurrency(booking.monthly_price) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Acompte</span>
                                <span class="font-medium">{{ formatCurrency(booking.deposit_amount) }}</span>
                            </div>
                            <div v-if="booking.discount_amount > 0" class="flex justify-between text-green-600">
                                <span>Réduction</span>
                                <span class="font-medium">-{{ formatCurrency(booking.discount_amount) }}</span>
                            </div>
                            <div v-if="booking.promo_code" class="flex justify-between items-center pt-2 border-t">
                                <span class="text-gray-600 flex items-center">
                                    <TagIcon class="h-4 w-4 mr-1" />
                                    Code promo
                                </span>
                                <span class="font-mono bg-gray-100 px-2 py-0.5 rounded">{{ booking.promo_code }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dates -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <CalendarDaysIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Dates
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date de début</span>
                                <span class="font-medium">{{ booking.start_date }}</span>
                            </div>
                            <div v-if="booking.end_date" class="flex justify-between">
                                <span class="text-gray-600">Date de fin</span>
                                <span class="font-medium">{{ booking.end_date }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Type</span>
                                <span class="font-medium">{{ booking.rental_type === 'month_to_month' ? 'Mois par mois' : 'Durée fixe' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Source -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <GlobeAltIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Source
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Origine</span>
                                <span class="font-medium">{{ booking.source_label }}</span>
                            </div>
                            <div v-if="booking.utm.source" class="flex justify-between">
                                <span class="text-gray-600">UTM Source</span>
                                <span class="text-sm">{{ booking.utm.source }}</span>
                            </div>
                            <div v-if="booking.utm.medium" class="flex justify-between">
                                <span class="text-gray-600">UTM Medium</span>
                                <span class="text-sm">{{ booking.utm.medium }}</span>
                            </div>
                            <div v-if="booking.utm.campaign" class="flex justify-between">
                                <span class="text-gray-600">UTM Campaign</span>
                                <span class="text-sm">{{ booking.utm.campaign }}</span>
                            </div>
                            <div v-if="booking.ip_address" class="flex justify-between pt-2 border-t">
                                <span class="text-gray-600">Adresse IP</span>
                                <span class="text-sm font-mono">{{ booking.ip_address }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
                        <div class="space-y-3">
                            <button
                                v-if="booking.status === 'pending'"
                                @click="confirmBooking"
                                :disabled="processing"
                                class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors disabled:opacity-50"
                            >
                                <CheckCircleIcon class="h-5 w-5 mr-2" />
                                Confirmer
                            </button>
                            <button
                                v-if="booking.status === 'pending'"
                                @click="showRejectModal = true"
                                :disabled="processing"
                                class="w-full flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50"
                            >
                                <XCircleIcon class="h-5 w-5 mr-2" />
                                Refuser
                            </button>
                            <button
                                v-if="['confirmed', 'deposit_paid'].includes(booking.status) && !booking.contract"
                                @click="convertToContract"
                                :disabled="processing"
                                class="w-full flex items-center justify-center px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors disabled:opacity-50"
                            >
                                <DocumentTextIcon class="h-5 w-5 mr-2" />
                                Convertir en contrat
                            </button>
                            <Link
                                v-if="booking.contract"
                                :href="route('tenant.contracts.show', booking.contract.id)"
                                class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors"
                            >
                                <DocumentTextIcon class="h-5 w-5 mr-2" />
                                Voir le contrat
                            </Link>
                            <button
                                v-if="['pending', 'confirmed'].includes(booking.status)"
                                @click="cancelBooking"
                                :disabled="processing"
                                class="w-full flex items-center justify-center px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-colors disabled:opacity-50"
                            >
                                <XCircleIcon class="h-5 w-5 mr-2" />
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Refuser la réservation</h3>
                <textarea
                    v-model="rejectReason"
                    rows="4"
                    class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    placeholder="Raison du refus..."
                ></textarea>
                <div class="flex justify-end space-x-3 mt-4">
                    <button
                        @click="showRejectModal = false"
                        class="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-xl transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="rejectBooking"
                        :disabled="!rejectReason || processing"
                        class="px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors disabled:opacity-50"
                    >
                        Confirmer le refus
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
