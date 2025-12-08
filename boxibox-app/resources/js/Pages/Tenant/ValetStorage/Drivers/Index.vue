<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    drivers: Array,
});

const getStatusColor = (status) => {
    const colors = {
        available: 'bg-green-100 text-green-800',
        busy: 'bg-yellow-100 text-yellow-800',
        offline: 'bg-gray-100 text-gray-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = { available: 'Disponible', busy: 'Occupé', offline: 'Hors ligne' };
    return labels[status] || status;
};

const getVehicleLabel = (type) => {
    const labels = { bike: 'Vélo cargo', van: 'Camionnette', truck: 'Camion' };
    return labels[type] || type;
};

const updateStatus = (driver, status) => {
    router.put(route('tenant.valet.drivers.update-status', driver.id), { status }, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('valet.drivers')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('valet.drivers') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('valet.drivers_subtitle') }}
                        </p>
                    </div>
                    <Link
                        :href="route('tenant.valet.drivers.create')"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        {{ $t('valet.add_driver') }}
                    </Link>
                </div>

                <!-- Drivers Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="driver in drivers"
                        :key="driver.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow"
                    >
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="relative">
                                        <div class="w-14 h-14 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                            <span class="text-xl font-medium text-gray-600 dark:text-gray-300">
                                                {{ driver.user?.name?.charAt(0) }}
                                            </span>
                                        </div>
                                        <span :class="[
                                            'absolute bottom-0 right-0 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800',
                                            driver.status === 'available' ? 'bg-green-500' : driver.status === 'busy' ? 'bg-yellow-500' : 'bg-gray-400'
                                        ]"></span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ driver.user?.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ driver.phone }}</p>
                                    </div>
                                </div>
                                <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(driver.status)]">
                                    {{ getStatusLabel(driver.status) }}
                                </span>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('valet.vehicle') }}</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ getVehicleLabel(driver.vehicle_type) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('valet.plate') }}</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ driver.vehicle_plate || '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('valet.capacity') }}</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ driver.max_capacity_kg ? `${driver.max_capacity_kg} kg` : '-' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('valet.todays_orders') }}</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ driver.todays_orders_count || 0 }}</p>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <p class="text-xs text-gray-500 mb-2">{{ $t('valet.change_status') }}</p>
                                <div class="flex space-x-2">
                                    <button
                                        @click="updateStatus(driver, 'available')"
                                        :class="[
                                            'flex-1 px-3 py-1.5 text-xs rounded-md transition-colors',
                                            driver.status === 'available'
                                                ? 'bg-green-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-green-100'
                                        ]"
                                    >
                                        {{ $t('valet.available') }}
                                    </button>
                                    <button
                                        @click="updateStatus(driver, 'busy')"
                                        :class="[
                                            'flex-1 px-3 py-1.5 text-xs rounded-md transition-colors',
                                            driver.status === 'busy'
                                                ? 'bg-yellow-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-yellow-100'
                                        ]"
                                    >
                                        {{ $t('valet.busy') }}
                                    </button>
                                    <button
                                        @click="updateStatus(driver, 'offline')"
                                        :class="[
                                            'flex-1 px-3 py-1.5 text-xs rounded-md transition-colors',
                                            driver.status === 'offline'
                                                ? 'bg-gray-600 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                        ]"
                                    >
                                        {{ $t('valet.offline') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="drivers.length === 0" class="col-span-full">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="mt-4 text-gray-600 dark:text-gray-400">{{ $t('valet.no_drivers') }}</p>
                            <Link
                                :href="route('tenant.valet.drivers.create')"
                                class="mt-4 inline-block px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                {{ $t('valet.add_first_driver') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
