<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import {
    ArrowLeftIcon,
    CalendarIcon,
    ClockIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    CheckCircleIcon,
    XCircleIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    booking: Object,
});

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        confirmed: 'bg-blue-100 text-blue-800 border-blue-200',
        completed: 'bg-green-100 text-green-800 border-green-200',
        converted: 'bg-purple-100 text-purple-800 border-purple-200',
        cancelled_by_customer: 'bg-red-100 text-red-800 border-red-200',
        cancelled_by_merchant: 'bg-red-100 text-red-800 border-red-200',
        no_show: 'bg-gray-100 text-gray-800 border-gray-200',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 border-gray-200';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        confirmed: 'Confirmé',
        completed: 'Terminé',
        converted: 'Converti en contrat',
        cancelled_by_customer: 'Annulé par le client',
        cancelled_by_merchant: 'Annulé',
        no_show: 'Client absent',
    };
    return labels[status] || status;
};

const getServiceLabel = (type) => {
    const labels = {
        visit: 'Visite',
        move_in: 'Emménagement',
        consultation: 'Consultation',
    };
    return labels[type] || type;
};

const confirmBooking = () => {
    router.post(route('tenant.google-reserve.confirm', props.booking.id));
};

const completeBooking = () => {
    router.post(route('tenant.google-reserve.complete', props.booking.id));
};

const cancelBooking = () => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
        router.post(route('tenant.google-reserve.cancel', props.booking.id));
    }
};

const convertBooking = () => {
    router.post(route('tenant.google-reserve.convert', props.booking.id));
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <TenantLayout title="Détails de la réservation">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('tenant.google-reserve.index')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Réservation #{{ booking.id }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">{{ booking.google_booking_id }}</p>
                    </div>
                </div>
                <span :class="['px-4 py-2 rounded-full text-sm font-medium border', getStatusColor(booking.status)]">
                    {{ getStatusLabel(booking.status) }}
                </span>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Date & Time -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <CalendarIcon class="w-5 h-5" />
                            Date et heure
                        </h3>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Date</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white capitalize">
                                    {{ formatDate(booking.booking_date) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Horaire</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ booking.start_time }} - {{ booking.end_time }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Type de service</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ getServiceLabel(booking.service_type) }}
                                </p>
                            </div>
                            <div v-if="booking.box_size_requested">
                                <p class="text-sm text-gray-500">Taille demandée</p>
                                <p class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ booking.box_size_requested }} m²
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                            <UserIcon class="w-5 h-5" />
                            Informations client
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <UserIcon class="w-5 h-5 text-gray-400" />
                                <span class="text-gray-900 dark:text-white">{{ booking.customer_name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                                <a :href="'mailto:' + booking.customer_email" class="text-blue-600 hover:underline">
                                    {{ booking.customer_email }}
                                </a>
                            </div>
                            <div v-if="booking.customer_phone" class="flex items-center gap-3">
                                <PhoneIcon class="w-5 h-5 text-gray-400" />
                                <a :href="'tel:' + booking.customer_phone" class="text-blue-600 hover:underline">
                                    {{ booking.customer_phone }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="booking.customer_notes" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Notes du client</h3>
                        <p class="text-gray-700 dark:text-gray-300">{{ booking.customer_notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Actions</h3>
                        <div class="space-y-3">
                            <button
                                v-if="booking.status === 'pending'"
                                @click="confirmBooking"
                                class="w-full btn-primary flex items-center justify-center gap-2"
                            >
                                <CheckCircleIcon class="w-5 h-5" />
                                Confirmer
                            </button>
                            <button
                                v-if="booking.status === 'confirmed'"
                                @click="completeBooking"
                                class="w-full btn-success flex items-center justify-center gap-2"
                            >
                                <CheckCircleIcon class="w-5 h-5" />
                                Marquer comme terminé
                            </button>
                            <button
                                v-if="booking.status === 'completed'"
                                @click="convertBooking"
                                class="w-full btn-purple flex items-center justify-center gap-2"
                            >
                                <ArrowPathIcon class="w-5 h-5" />
                                Convertir en contrat
                            </button>
                            <button
                                v-if="['pending', 'confirmed'].includes(booking.status)"
                                @click="cancelBooking"
                                class="w-full btn-danger flex items-center justify-center gap-2"
                            >
                                <XCircleIcon class="w-5 h-5" />
                                Annuler
                            </button>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Historique</h3>
                        <div class="space-y-4">
                            <div class="flex gap-3">
                                <div class="w-2 h-2 mt-2 rounded-full bg-blue-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Créée</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.confirmed_at" class="flex gap-3">
                                <div class="w-2 h-2 mt-2 rounded-full bg-green-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Confirmée</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.confirmed_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.completed_at" class="flex gap-3">
                                <div class="w-2 h-2 mt-2 rounded-full bg-purple-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Terminée</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.completed_at) }}</p>
                                </div>
                            </div>
                            <div v-if="booking.cancelled_at" class="flex gap-3">
                                <div class="w-2 h-2 mt-2 rounded-full bg-red-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Annulée</p>
                                    <p class="text-xs text-gray-500">{{ formatDateTime(booking.cancelled_at) }}</p>
                                    <p v-if="booking.cancellation_reason" class="text-xs text-gray-500">{{ booking.cancellation_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversion Value -->
                    <div v-if="booking.converted_value" class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-6">
                        <p class="text-sm text-purple-600 dark:text-purple-400">Valeur de conversion</p>
                        <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ booking.converted_value }}€</p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition; }
.btn-success { @apply px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition; }
.btn-danger { @apply px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition; }
.btn-purple { @apply px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition; }
</style>
