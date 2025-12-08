<script setup>
import { Head, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    stats: Object,
    recentOrders: Array,
    todaysOrders: Array,
    drivers: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
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

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        confirmed: 'Confirmé',
        scheduled: 'Planifié',
        in_progress: 'En cours',
        completed: 'Terminé',
        cancelled: 'Annulé',
    };
    return labels[status] || status;
};

const getTypeLabel = (type) => {
    const labels = {
        pickup: 'Collecte',
        delivery: 'Livraison',
        pickup_delivery: 'Collecte & Livraison',
    };
    return labels[type] || type;
};

const getDriverStatusColor = (status) => {
    const colors = {
        available: 'bg-green-500',
        busy: 'bg-yellow-500',
        offline: 'bg-gray-400',
    };
    return colors[status] || 'bg-gray-400';
};
</script>

<template>
    <Head :title="$t('valet.dashboard')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('valet.title') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('valet.subtitle') }}
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('tenant.valet.orders.create')"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('valet.new_order') }}
                        </Link>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('valet.total_items') }}</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_items }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-green-600">{{ stats.stored_items }}</span> {{ $t('valet.stored') }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('valet.pending_pickups') }}</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.pending_pickups }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-blue-600">{{ stats.pending_deliveries }}</span> {{ $t('valet.deliveries_pending') }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('valet.todays_orders') }}</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.todays_orders }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <span class="text-green-600">{{ stats.active_drivers }}</span> {{ $t('valet.drivers_active') }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $t('valet.monthly_revenue') }}</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ formatCurrency(stats.monthly_revenue) }}</p>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ stats.total_volume_m3?.toFixed(1) || 0 }} m³ {{ $t('valet.stored') }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Today's Schedule -->
                    <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $t('valet.todays_schedule') }}
                                </h2>
                                <Link
                                    :href="route('tenant.valet.planning')"
                                    class="text-sm text-primary-600 hover:text-primary-800"
                                >
                                    {{ $t('valet.view_planning') }}
                                </Link>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="order in todaysOrders"
                                :key="order.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div :class="[
                                            'w-2 h-2 rounded-full',
                                            order.type === 'pickup' ? 'bg-orange-500' : order.type === 'delivery' ? 'bg-blue-500' : 'bg-purple-500'
                                        ]"></div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ getTypeLabel(order.type) }} - {{ order.city }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ order.time_slot === 'morning' ? '8h-12h' : order.time_slot === 'afternoon' ? '12h-18h' : '18h-20h' }}
                                        </span>
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(order.status)]">
                                            {{ getStatusLabel(order.status) }}
                                        </span>
                                        <Link
                                            :href="route('tenant.valet.orders.show', order.id)"
                                            class="text-primary-600 hover:text-primary-800"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                            <div v-if="todaysOrders.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('valet.no_orders_today') }}
                            </div>
                        </div>
                    </div>

                    <!-- Drivers Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $t('valet.drivers') }}
                                </h2>
                                <Link
                                    :href="route('tenant.valet.drivers')"
                                    class="text-sm text-primary-600 hover:text-primary-800"
                                >
                                    {{ $t('common.manage') }}
                                </Link>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="driver in drivers"
                                :key="driver.id"
                                class="p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="relative">
                                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                                    {{ driver.user?.name?.charAt(0) }}
                                                </span>
                                            </div>
                                            <span :class="['absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white dark:border-gray-800', getDriverStatusColor(driver.status)]"></span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ driver.user?.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ driver.vehicle_type === 'van' ? 'Camionnette' : driver.vehicle_type === 'truck' ? 'Camion' : 'Vélo' }}
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ driver.todays_orders_count || 0 }} {{ $t('valet.stops') }}
                                    </span>
                                </div>
                            </div>
                            <div v-if="drivers.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $t('valet.no_drivers') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $t('valet.recent_orders') }}
                            </h2>
                            <Link
                                :href="route('tenant.valet.orders')"
                                class="text-sm text-primary-600 hover:text-primary-800"
                            >
                                {{ $t('common.view_all') }}
                            </Link>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.order_number') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.customer') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.type') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.date') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.status') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('tenant.valet.orders.show', order.id)" class="text-primary-600 hover:text-primary-800 font-medium">
                                            {{ order.order_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ getTypeLabel(order.type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ formatDate(order.requested_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(order.status)]">
                                            {{ getStatusLabel(order.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(order.total_fee) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Link
                        :href="route('tenant.valet.items')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center space-x-3"
                    >
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('valet.inventory') }}</span>
                    </Link>

                    <Link
                        :href="route('tenant.valet.orders')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center space-x-3"
                    >
                        <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('valet.all_orders') }}</span>
                    </Link>

                    <Link
                        :href="route('tenant.valet.planning')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center space-x-3"
                    >
                        <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('valet.planning') }}</span>
                    </Link>

                    <Link
                        :href="route('tenant.valet.settings')"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center space-x-3"
                    >
                        <div class="p-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $t('valet.settings') }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
