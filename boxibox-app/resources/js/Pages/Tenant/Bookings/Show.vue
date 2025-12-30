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
    CreditCardIcon,
    BanknotesIcon,
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

const getPaymentMethodLabel = (method) => {
    const labels = {
        'card_now': 'Carte bancaire (payé en ligne)',
        'at_signing': 'Paiement à la signature',
        'bank_transfer': 'Virement bancaire',
        'sepa_debit': 'Prélèvement SEPA',
        'cash': 'Espèces',
        'check': 'Chèque',
    }
    return labels[method] || method || 'Non défini'
}

const getPaymentMethodClass = (method) => {
    const classes = {
        'card_now': 'bg-green-100 text-green-800',
        'at_signing': 'bg-blue-100 text-blue-800',
        'bank_transfer': 'bg-purple-100 text-purple-800',
        'sepa_debit': 'bg-indigo-100 text-indigo-800',
        'cash': 'bg-orange-100 text-orange-800',
        'check': 'bg-gray-100 text-gray-800',
    }
    return classes[method] || 'bg-gray-100 text-gray-600'
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
                        <!-- Secondary Contact -->
                        <div v-if="booking.customer.secondary_contact_name || booking.customer.secondary_contact_phone" class="mt-4 pt-4 border-t border-gray-100">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Contact secondaire</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-if="booking.customer.secondary_contact_name">
                                    <p class="text-sm text-gray-500">Nom</p>
                                    <p class="font-medium text-gray-900">{{ booking.customer.secondary_contact_name }}</p>
                                </div>
                                <div v-if="booking.customer.secondary_contact_phone">
                                    <p class="text-sm text-gray-500">Téléphone</p>
                                    <p class="font-medium text-gray-900 flex items-center">
                                        <PhoneIcon class="h-4 w-4 mr-1 text-gray-400" />
                                        {{ booking.customer.secondary_contact_phone }}
                                    </p>
                                </div>
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

                    <!-- Payment Method -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <CreditCardIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Mode de paiement
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Méthode choisie</span>
                                <span
                                    :class="getPaymentMethodClass(booking.payment_method)"
                                    class="px-3 py-1 rounded-full text-sm font-medium"
                                >
                                    {{ getPaymentMethodLabel(booking.payment_method) }}
                                </span>
                            </div>
                            <div v-if="booking.total_paid > 0" class="flex justify-between items-center pt-2 border-t">
                                <span class="text-gray-600">Montant payé</span>
                                <span class="font-bold text-green-600">{{ formatCurrency(booking.total_paid) }}</span>
                            </div>
                            <div v-else class="flex justify-between items-center pt-2 border-t">
                                <span class="text-gray-600">Statut paiement</span>
                                <span class="text-orange-600 font-medium">En attente</span>
                            </div>
                            <div v-if="booking.payment_notes" class="pt-2 border-t">
                                <span class="text-gray-600 text-sm">Notes:</span>
                                <p class="text-gray-800 mt-1">{{ booking.payment_notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Dates & Duration -->
                    <div class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <CalendarDaysIcon class="h-5 w-5 mr-2 text-teal-600" />
                            Durée de location
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
                                <span class="text-gray-600">Type de location</span>
                                <span class="font-medium">{{ booking.duration_type === 'fixed_term' ? 'Durée déterminée' : 'Mois par mois' }}</span>
                            </div>
                            <div v-if="booking.planned_duration_months" class="flex justify-between">
                                <span class="text-gray-600">Durée prévue</span>
                                <span class="font-medium">{{ booking.planned_duration_months }} mois</span>
                            </div>
                            <div v-if="booking.planned_end_date" class="flex justify-between">
                                <span class="text-gray-600">Fin prévue</span>
                                <span class="font-medium">{{ booking.planned_end_date }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Special Needs -->
                    <div v-if="booking.special_needs" class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Besoins spécifiques</h2>
                        <div class="flex flex-wrap gap-2">
                            <span v-if="booking.special_needs.needs_24h_access" class="px-3 py-1 bg-teal-100 text-teal-800 rounded-full text-sm">
                                Accès 24h/24
                            </span>
                            <span v-if="booking.special_needs.needs_climate_control" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                                Climatisation
                            </span>
                            <span v-if="booking.special_needs.needs_electricity" class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">
                                Électricité
                            </span>
                            <span v-if="booking.special_needs.needs_insurance" class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm">
                                Assurance
                            </span>
                            <span v-if="booking.special_needs.needs_moving_help" class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">
                                Aide déménagement
                            </span>
                            <span v-if="!booking.special_needs.needs_24h_access && !booking.special_needs.needs_climate_control && !booking.special_needs.needs_electricity && !booking.special_needs.needs_insurance && !booking.special_needs.needs_moving_help" class="text-gray-500 text-sm">
                                Aucun besoin spécifique
                            </span>
                        </div>
                        <div v-if="booking.special_needs.preferred_time_slot && booking.special_needs.preferred_time_slot !== 'flexible'" class="mt-3 pt-3 border-t">
                            <span class="text-gray-600 text-sm">Créneau préféré:</span>
                            <span class="ml-2 font-medium text-sm">
                                {{ booking.special_needs.preferred_time_slot === 'morning' ? 'Matin (8h-12h)' : booking.special_needs.preferred_time_slot === 'afternoon' ? 'Après-midi (14h-18h)' : 'Soir (18h-20h)' }}
                            </span>
                        </div>
                    </div>

                    <!-- Storage Info -->
                    <div v-if="booking.storage_contents || booking.estimated_value" class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations stockage</h2>
                        <div class="space-y-3">
                            <div v-if="booking.storage_contents">
                                <p class="text-sm text-gray-500">Contenu à stocker</p>
                                <p class="font-medium text-gray-900">{{ booking.storage_contents }}</p>
                            </div>
                            <div v-if="booking.estimated_value">
                                <p class="text-sm text-gray-500">Valeur estimée</p>
                                <p class="font-medium text-gray-900">
                                    {{ booking.estimated_value === 'under_1000' ? 'Moins de 1 000 €' : booking.estimated_value === '1000_5000' ? '1 000 € - 5 000 €' : booking.estimated_value === '5000_10000' ? '5 000 € - 10 000 €' : 'Plus de 10 000 €' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Special Requests -->
                    <div v-if="booking.special_requests" class="bg-white rounded-2xl shadow-xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Demandes spéciales</h2>
                        <p class="text-gray-700 whitespace-pre-line">{{ booking.special_requests }}</p>
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
