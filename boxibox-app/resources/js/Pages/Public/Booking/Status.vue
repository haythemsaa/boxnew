<script setup>
import { Head } from '@inertiajs/vue3'
import {
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    CubeIcon,
    MapPinIcon,
    CalendarIcon,
    CurrencyEuroIcon,
    UserIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    booking: Object,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getStatusIcon = (status) => {
    const icons = {
        pending: ClockIcon,
        confirmed: CheckCircleIcon,
        deposit_paid: CheckCircleIcon,
        active: CheckCircleIcon,
        completed: CheckCircleIcon,
        cancelled: XCircleIcon,
        rejected: XCircleIcon,
        expired: ExclamationTriangleIcon,
    }
    return icons[status] || ClockIcon
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'text-yellow-600 bg-yellow-100',
        confirmed: 'text-blue-600 bg-blue-100',
        deposit_paid: 'text-purple-600 bg-purple-100',
        active: 'text-green-600 bg-green-100',
        completed: 'text-gray-600 bg-gray-100',
        cancelled: 'text-red-600 bg-red-100',
        rejected: 'text-red-600 bg-red-100',
        expired: 'text-gray-600 bg-gray-100',
    }
    return colors[status] || 'text-gray-600 bg-gray-100'
}
</script>

<template>
    <Head :title="`Réservation ${booking.booking_number}`" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-gradient-to-r from-teal-600 to-cyan-600 py-8 px-4">
            <div class="max-w-2xl mx-auto text-center">
                <h1 class="text-2xl font-bold text-white">Suivi de réservation</h1>
                <p class="text-teal-100 mt-2">{{ booking.booking_number }}</p>
            </div>
        </header>

        <div class="max-w-2xl mx-auto px-4 py-8">
            <!-- Status Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8 mb-6">
                <div class="text-center mb-8">
                    <div
                        :class="getStatusColor(booking.status)"
                        class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4"
                    >
                        <component :is="getStatusIcon(booking.status)" class="h-10 w-10" />
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ booking.status_label }}</h2>
                    <p class="text-gray-500 mt-2">Créée le {{ booking.created_at }}</p>
                </div>

                <div class="space-y-6">
                    <!-- Customer Info -->
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <UserIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Client</h3>
                            <p class="font-semibold text-gray-900">{{ booking.customer_name }}</p>
                            <p class="text-sm text-gray-600">{{ booking.customer_email }}</p>
                        </div>
                    </div>

                    <!-- Box Info -->
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <CubeIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Box réservé</h3>
                            <p class="font-semibold text-gray-900">{{ booking.box_name }}</p>
                            <p class="text-sm text-gray-600">{{ booking.site_name }}</p>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <CalendarIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Date de début</h3>
                            <p class="font-semibold text-gray-900">{{ booking.start_date }}</p>
                        </div>
                    </div>

                    <!-- Pricing -->
                    <div class="flex items-start space-x-4">
                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <CurrencyEuroIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Tarification</h3>
                            <p class="font-semibold text-gray-900">{{ formatCurrency(booking.monthly_price) }}/mois</p>
                            <p v-if="booking.deposit_amount > 0" class="text-sm text-gray-600">
                                Acompte: {{ formatCurrency(booking.deposit_amount) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Prochaines étapes</h3>
                <div class="space-y-3 text-sm text-gray-600">
                    <div v-if="booking.status === 'pending'" class="flex items-start">
                        <ClockIcon class="h-5 w-5 text-yellow-500 mr-2 flex-shrink-0" />
                        <p>Votre réservation est en attente de confirmation par notre équipe. Vous recevrez un email dès qu'elle sera traitée.</p>
                    </div>
                    <div v-if="booking.status === 'confirmed'" class="flex items-start">
                        <CheckCircleIcon class="h-5 w-5 text-blue-500 mr-2 flex-shrink-0" />
                        <p>Votre réservation est confirmée. Vous pouvez procéder au paiement de l'acompte si nécessaire.</p>
                    </div>
                    <div v-if="booking.status === 'deposit_paid'" class="flex items-start">
                        <CheckCircleIcon class="h-5 w-5 text-purple-500 mr-2 flex-shrink-0" />
                        <p>Votre acompte a été reçu. Votre contrat sera activé à la date de début prévue.</p>
                    </div>
                    <div v-if="booking.status === 'active'" class="flex items-start">
                        <CheckCircleIcon class="h-5 w-5 text-green-500 mr-2 flex-shrink-0" />
                        <p>Votre contrat est actif. Bienvenue parmi nos clients !</p>
                    </div>
                    <div v-if="['cancelled', 'rejected'].includes(booking.status)" class="flex items-start">
                        <XCircleIcon class="h-5 w-5 text-red-500 mr-2 flex-shrink-0" />
                        <p>Cette réservation a été annulée. N'hésitez pas à nous contacter pour plus d'informations.</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500 text-sm">
                <p>Powered by Boxibox</p>
            </div>
        </div>
    </div>
</template>
