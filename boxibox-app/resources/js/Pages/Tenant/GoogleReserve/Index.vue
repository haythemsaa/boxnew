<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { CalendarIcon, ClockIcon, CheckCircleIcon, XCircleIcon, UserGroupIcon, CurrencyEuroIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: Array,
    selectedSiteId: Number,
    bookings: Array,
    statistics: Object,
    upcomingBookings: Array,
    todayBookings: Array,
    dateRange: Object,
});

const selectedSite = ref(props.selectedSiteId);

const changeSite = () => {
    router.get(route('tenant.google-reserve.index'), { site_id: selectedSite.value }, { preserveState: true });
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        converted: 'bg-purple-100 text-purple-800',
        cancelled_by_customer: 'bg-red-100 text-red-800',
        cancelled_by_merchant: 'bg-red-100 text-red-800',
        no_show: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        confirmed: 'Confirmé',
        completed: 'Terminé',
        converted: 'Converti',
        cancelled_by_customer: 'Annulé (client)',
        cancelled_by_merchant: 'Annulé',
        no_show: 'Absent',
    };
    return labels[status] || status;
};

const confirmBooking = (id) => {
    router.post(route('tenant.google-reserve.confirm', id));
};

const completeBooking = (id) => {
    router.post(route('tenant.google-reserve.complete', id));
};
</script>

<template>
    <TenantLayout title="Google Reserve">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Google Reserve</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gérez vos réservations depuis Google</p>
                </div>
                <div class="flex items-center gap-4">
                    <select v-model="selectedSite" @change="changeSite" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">Tous les sites</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                    </select>
                    <Link :href="route('tenant.google-reserve.settings')" class="btn-secondary">
                        Paramètres
                    </Link>
                </div>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <CalendarIcon class="w-5 h-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.total || 0 }}</p>
                            <p class="text-sm text-gray-500">Total</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <CheckCircleIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.confirmed || 0 }}</p>
                            <p class="text-sm text-gray-500">Confirmées</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <UserGroupIcon class="w-5 h-5 text-purple-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.converted || 0 }}</p>
                            <p class="text-sm text-gray-500">Converties</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-100 dark:bg-red-900 rounded-lg">
                            <XCircleIcon class="w-5 h-5 text-red-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.cancelled || 0 }}</p>
                            <p class="text-sm text-gray-500">Annulées</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900 rounded-lg">
                            <CurrencyEuroIcon class="w-5 h-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ (statistics?.total_value || 0).toLocaleString() }}€</p>
                            <p class="text-sm text-gray-500">Valeur</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <ClockIcon class="w-5 h-5 text-indigo-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ statistics?.conversion_rate || 0 }}%</p>
                            <p class="text-sm text-gray-500">Conversion</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Bookings -->
            <div v-if="todayBookings?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <CalendarIcon class="w-5 h-5" />
                        Aujourd'hui
                    </h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="booking in todayBookings" :key="booking.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-center">
                                <p class="text-lg font-bold text-blue-600">{{ booking.start_time }}</p>
                                <p class="text-xs text-gray-500">{{ booking.end_time }}</p>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ booking.customer_name }}</p>
                                <p class="text-sm text-gray-500">{{ booking.customer_email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(booking.status)]">
                                {{ getStatusLabel(booking.status) }}
                            </span>
                            <button v-if="booking.status === 'pending'" @click="confirmBooking(booking.id)" class="btn-sm btn-primary">
                                Confirmer
                            </button>
                            <button v-if="booking.status === 'confirmed'" @click="completeBooking(booking.id)" class="btn-sm btn-success">
                                Terminé
                            </button>
                            <Link :href="route('tenant.google-reserve.show', booking.id)" class="btn-sm btn-secondary">
                                Détails
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Bookings -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Prochaines réservations</h2>
                </div>
                <div v-if="upcomingBookings?.length" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="booking in upcomingBookings" :key="booking.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="text-center bg-gray-100 dark:bg-gray-700 rounded-lg px-3 py-2">
                                <p class="text-xs text-gray-500">{{ new Date(booking.booking_date).toLocaleDateString('fr-FR', { weekday: 'short' }) }}</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ new Date(booking.booking_date).getDate() }}</p>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">{{ booking.customer_name }}</p>
                                <p class="text-sm text-gray-500">{{ booking.start_time }} - {{ booking.service_type }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusColor(booking.status)]">
                                {{ getStatusLabel(booking.status) }}
                            </span>
                            <Link :href="route('tenant.google-reserve.show', booking.id)" class="text-blue-600 hover:underline text-sm">
                                Voir
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="px-6 py-12 text-center text-gray-500">
                    Aucune réservation à venir
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.btn-sm { @apply px-3 py-1.5 text-sm rounded-lg font-medium transition; }
.btn-primary { @apply bg-blue-600 text-white hover:bg-blue-700; }
.btn-secondary { @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300; }
.btn-success { @apply bg-green-600 text-white hover:bg-green-700; }
</style>
