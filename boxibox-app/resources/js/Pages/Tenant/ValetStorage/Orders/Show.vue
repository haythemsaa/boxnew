<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    order: Object,
    statuses: Object,
    availableDrivers: Array,
});

const showAssignModal = ref(false);
const selectedDriver = ref('');

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR');
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        scheduled: 'bg-indigo-100 text-indigo-800',
        in_progress: 'bg-orange-100 text-orange-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getTypeLabel = (type) => {
    const labels = { pickup: 'Collecte', delivery: 'Livraison', pickup_delivery: 'Collecte & Livraison' };
    return labels[type] || type;
};

const getTimeSlotLabel = (slot) => {
    const labels = { morning: 'Matin (8h-12h)', afternoon: 'Après-midi (12h-18h)', evening: 'Soir (18h-20h)' };
    return labels[slot] || slot;
};

const updateStatus = (status) => {
    router.put(route('tenant.valet.orders.update-status', props.order.id), { status }, {
        preserveScroll: true,
    });
};

const assignDriver = () => {
    router.put(route('tenant.valet.orders.assign-driver', props.order.id), {
        driver_id: selectedDriver.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAssignModal.value = false;
        },
    });
};

const cancelOrder = () => {
    if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
        router.post(route('tenant.valet.orders.cancel', props.order.id));
    }
};
</script>

<template>
    <Head :title="`Commande ${order.order_number}`" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('tenant.valet.orders')" class="text-sm text-gray-600 hover:text-gray-900 flex items-center mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ order.order_number }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ getTypeLabel(order.type) }} - {{ formatDate(order.requested_date) }}
                            </p>
                        </div>
                        <span :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusColor(order.status)]">
                            {{ statuses[order.status] || order.status }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Customer & Address -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.customer_info') }}</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('valet.customer') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('valet.site') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ order.site?.name }}</p>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('valet.address') }}</h3>
                                <p class="text-gray-900 dark:text-white">{{ order.address_line1 }}</p>
                                <p v-if="order.address_line2" class="text-gray-600 dark:text-gray-400">{{ order.address_line2 }}</p>
                                <p class="text-gray-600 dark:text-gray-400">{{ order.postal_code }} {{ order.city }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span v-if="order.floor" class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-sm">
                                        {{ $t('valet.floor') }}: {{ order.floor }}
                                    </span>
                                    <span v-if="order.has_elevator" class="px-2 py-1 bg-green-100 text-green-800 rounded text-sm">
                                        {{ $t('valet.elevator') }}
                                    </span>
                                    <span v-if="order.access_code" class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-sm">
                                        Code: {{ order.access_code }}
                                    </span>
                                </div>
                                <p v-if="order.access_instructions" class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ order.access_instructions }}
                                </p>
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ $t('valet.contact') }}</h3>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $t('valet.name') }}</p>
                                        <p class="font-medium">{{ order.contact_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $t('valet.phone') }}</p>
                                        <p class="font-medium">{{ order.contact_phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $t('valet.email') }}</p>
                                        <p class="font-medium">{{ order.contact_email || '-' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                {{ $t('valet.items') }} ({{ order.order_items?.length || 0 }})
                            </h2>
                            <div class="space-y-3">
                                <div
                                    v-for="orderItem in order.order_items"
                                    :key="orderItem.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 bg-white dark:bg-gray-600 rounded">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ orderItem.item?.name || orderItem.item_description || 'Article' }}
                                            </p>
                                            <p v-if="orderItem.item?.barcode" class="text-xs text-gray-500 font-mono">
                                                {{ orderItem.item.barcode }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ orderItem.category }} - {{ orderItem.size }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">x{{ orderItem.quantity }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="order.notes || order.driver_notes" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.notes') }}</h2>
                            <div v-if="order.notes" class="mb-4">
                                <p class="text-sm text-gray-500 mb-1">{{ $t('valet.customer_notes') }}</p>
                                <p class="text-gray-700 dark:text-gray-300">{{ order.notes }}</p>
                            </div>
                            <div v-if="order.driver_notes">
                                <p class="text-sm text-gray-500 mb-1">{{ $t('valet.driver_notes') }}</p>
                                <p class="text-gray-700 dark:text-gray-300">{{ order.driver_notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Schedule -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.schedule') }}</h2>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500">{{ $t('valet.requested_date') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(order.requested_date) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">{{ $t('valet.time_slot') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ getTimeSlotLabel(order.time_slot) }}</p>
                                </div>
                                <div v-if="order.started_at">
                                    <p class="text-sm text-gray-500">{{ $t('valet.started_at') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDateTime(order.started_at) }}</p>
                                </div>
                                <div v-if="order.completed_at">
                                    <p class="text-sm text-gray-500">{{ $t('valet.completed_at') }}</p>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ formatDateTime(order.completed_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Driver -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.driver') }}</h2>
                            <div v-if="order.driver" class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                    <span class="font-medium">{{ order.driver.name?.charAt(0) }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ order.driver.name }}</p>
                                </div>
                            </div>
                            <div v-else class="text-center py-4">
                                <p class="text-gray-500 dark:text-gray-400 mb-3">{{ $t('valet.no_driver_assigned') }}</p>
                                <button
                                    @click="showAssignModal = true"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 text-sm"
                                >
                                    {{ $t('valet.assign_driver') }}
                                </button>
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.pricing') }}</h2>
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">{{ $t('valet.base_fee') }}</span>
                                    <span>{{ formatCurrency(order.base_fee) }}</span>
                                </div>
                                <div v-if="order.distance_fee > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">{{ $t('valet.zone_fee') }}</span>
                                    <span>{{ formatCurrency(order.distance_fee) }}</span>
                                </div>
                                <div v-if="order.floor_fee > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">{{ $t('valet.floor_fee') }}</span>
                                    <span>{{ formatCurrency(order.floor_fee) }}</span>
                                </div>
                                <div v-if="order.item_fee > 0" class="flex justify-between text-sm">
                                    <span class="text-gray-500">{{ $t('valet.item_fee') }}</span>
                                    <span>{{ formatCurrency(order.item_fee) }}</span>
                                </div>
                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700 flex justify-between font-medium">
                                    <span>{{ $t('valet.total') }}</span>
                                    <span class="text-lg">{{ formatCurrency(order.total_fee) }}</span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <span :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    order.is_paid ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                ]">
                                    {{ order.is_paid ? $t('valet.paid') : $t('valet.unpaid') }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('common.actions') }}</h2>
                            <div class="space-y-2">
                                <button
                                    v-if="order.status === 'pending'"
                                    @click="updateStatus('confirmed')"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm"
                                >
                                    {{ $t('valet.confirm_order') }}
                                </button>
                                <button
                                    v-if="order.status === 'confirmed' || order.status === 'scheduled'"
                                    @click="updateStatus('in_progress')"
                                    class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm"
                                >
                                    {{ $t('valet.start_order') }}
                                </button>
                                <button
                                    v-if="order.status === 'in_progress'"
                                    @click="updateStatus('completed')"
                                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                >
                                    {{ $t('valet.complete_order') }}
                                </button>
                                <button
                                    v-if="['pending', 'confirmed', 'scheduled'].includes(order.status)"
                                    @click="cancelOrder"
                                    class="w-full px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm"
                                >
                                    {{ $t('valet.cancel_order') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assign Driver Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showAssignModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.assign_driver') }}</h3>
                    <select v-model="selectedDriver" class="w-full rounded-md border-gray-300 shadow-sm mb-4">
                        <option value="">{{ $t('valet.select_driver') }}</option>
                        <option v-for="driver in availableDrivers" :key="driver.id" :value="driver.user_id">
                            {{ driver.user?.name }} ({{ driver.vehicle_type }})
                        </option>
                    </select>
                    <div class="flex justify-end space-x-3">
                        <button @click="showAssignModal = false" class="px-4 py-2 border border-gray-300 rounded-md">
                            {{ $t('common.cancel') }}
                        </button>
                        <button @click="assignDriver" :disabled="!selectedDriver" class="px-4 py-2 bg-primary-600 text-white rounded-md disabled:opacity-50">
                            {{ $t('valet.assign') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
