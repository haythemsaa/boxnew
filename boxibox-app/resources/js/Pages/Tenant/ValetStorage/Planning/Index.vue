<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    orders: Array,
    drivers: Array,
    routes: Array,
    date: String,
});

const selectedDate = ref(props.date);
const showRouteModal = ref(false);
const selectedOrders = ref([]);
const selectedDriver = ref('');

const changeDate = () => {
    router.get(route('tenant.valet.planning'), { date: selectedDate.value }, {
        preserveState: true,
    });
};

const formatTime = (slot) => {
    const times = { morning: '8h-12h', afternoon: '12h-18h', evening: '18h-20h' };
    return times[slot] || slot;
};

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-500',
        confirmed: 'bg-blue-500',
        scheduled: 'bg-indigo-500',
        in_progress: 'bg-orange-500',
        completed: 'bg-green-500',
        cancelled: 'bg-red-500',
    };
    return colors[status] || 'bg-gray-500';
};

const getTypeIcon = (type) => {
    if (type === 'pickup') return '↑';
    if (type === 'delivery') return '↓';
    return '↕';
};

const toggleOrderSelection = (orderId) => {
    const index = selectedOrders.value.indexOf(orderId);
    if (index > -1) {
        selectedOrders.value.splice(index, 1);
    } else {
        selectedOrders.value.push(orderId);
    }
};

const openRouteModal = () => {
    if (selectedOrders.value.length > 0) {
        showRouteModal.value = true;
    }
};

const createRoute = () => {
    router.post(route('tenant.valet.routes.store'), {
        driver_id: selectedDriver.value,
        date: selectedDate.value,
        order_ids: selectedOrders.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showRouteModal.value = false;
            selectedOrders.value = [];
            selectedDriver.value = '';
        },
    });
};

const getDriverStatusColor = (status) => {
    const colors = { available: 'bg-green-500', busy: 'bg-yellow-500', offline: 'bg-gray-400' };
    return colors[status] || 'bg-gray-400';
};

const unassignedOrders = props.orders.filter(o => !o.assigned_driver_id && o.status !== 'cancelled');
</script>

<template>
    <Head :title="$t('valet.planning')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('valet.planning') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('valet.planning_subtitle') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="changeDate"
                            class="rounded-md border-gray-300 shadow-sm"
                        />
                        <button
                            v-if="selectedOrders.length > 0"
                            @click="openRouteModal"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        >
                            {{ $t('valet.create_route') }} ({{ selectedOrders.length }})
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <!-- Unassigned Orders -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $t('valet.unassigned') }} ({{ unassignedOrders.length }})
                                </h2>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[600px] overflow-y-auto">
                                <div
                                    v-for="order in unassignedOrders"
                                    :key="order.id"
                                    :class="[
                                        'p-3 cursor-pointer transition-colors',
                                        selectedOrders.includes(order.id) ? 'bg-primary-50 dark:bg-primary-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50'
                                    ]"
                                    @click="toggleOrderSelection(order.id)"
                                >
                                    <div class="flex items-start justify-between">
                                        <div class="flex items-start space-x-2">
                                            <input
                                                type="checkbox"
                                                :checked="selectedOrders.includes(order.id)"
                                                class="mt-1 rounded border-gray-300 text-primary-600"
                                                @click.stop
                                                @change="toggleOrderSelection(order.id)"
                                            />
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ order.city }} - {{ formatTime(order.time_slot) }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="text-lg">{{ getTypeIcon(order.type) }}</span>
                                    </div>
                                </div>
                                <div v-if="unassignedOrders.length === 0" class="p-4 text-center text-gray-500">
                                    {{ $t('valet.no_unassigned_orders') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule View -->
                    <div class="lg:col-span-3">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('valet.daily_schedule') }}</h2>
                            </div>

                            <!-- Time slots -->
                            <div class="grid grid-cols-3 gap-4 p-4">
                                <!-- Morning -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-t-lg">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $t('valet.morning') }}</h3>
                                        <p class="text-sm text-gray-500">8h - 12h</p>
                                    </div>
                                    <div class="p-3 space-y-2 min-h-[200px]">
                                        <div
                                            v-for="order in orders.filter(o => o.time_slot === 'morning')"
                                            :key="order.id"
                                            class="p-2 bg-gray-50 dark:bg-gray-700 rounded border-l-4"
                                            :class="[`border-l-${order.type === 'pickup' ? 'orange' : order.type === 'delivery' ? 'blue' : 'purple'}-500`]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">{{ order.customer?.first_name }}</span>
                                                <span :class="['w-2 h-2 rounded-full', getStatusColor(order.status)]"></span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ order.city }}</p>
                                            <p class="text-xs text-gray-400">{{ order.driver?.name || 'Non assigné' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Afternoon -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-t-lg">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $t('valet.afternoon') }}</h3>
                                        <p class="text-sm text-gray-500">12h - 18h</p>
                                    </div>
                                    <div class="p-3 space-y-2 min-h-[200px]">
                                        <div
                                            v-for="order in orders.filter(o => o.time_slot === 'afternoon')"
                                            :key="order.id"
                                            class="p-2 bg-gray-50 dark:bg-gray-700 rounded border-l-4"
                                            :class="[`border-l-${order.type === 'pickup' ? 'orange' : order.type === 'delivery' ? 'blue' : 'purple'}-500`]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">{{ order.customer?.first_name }}</span>
                                                <span :class="['w-2 h-2 rounded-full', getStatusColor(order.status)]"></span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ order.city }}</p>
                                            <p class="text-xs text-gray-400">{{ order.driver?.name || 'Non assigné' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evening -->
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-t-lg">
                                        <h3 class="font-medium text-gray-900 dark:text-white">{{ $t('valet.evening') }}</h3>
                                        <p class="text-sm text-gray-500">18h - 20h</p>
                                    </div>
                                    <div class="p-3 space-y-2 min-h-[200px]">
                                        <div
                                            v-for="order in orders.filter(o => o.time_slot === 'evening')"
                                            :key="order.id"
                                            class="p-2 bg-gray-50 dark:bg-gray-700 rounded border-l-4"
                                            :class="[`border-l-${order.type === 'pickup' ? 'orange' : order.type === 'delivery' ? 'blue' : 'purple'}-500`]"
                                        >
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium">{{ order.customer?.first_name }}</span>
                                                <span :class="['w-2 h-2 rounded-full', getStatusColor(order.status)]"></span>
                                            </div>
                                            <p class="text-xs text-gray-500">{{ order.city }}</p>
                                            <p class="text-xs text-gray-400">{{ order.driver?.name || 'Non assigné' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Drivers Overview -->
                        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="font-semibold text-gray-900 dark:text-white">{{ $t('valet.drivers_overview') }}</h2>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div
                                        v-for="driver in drivers"
                                        :key="driver.id"
                                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                                    >
                                        <div class="flex items-center justify-between mb-3">
                                            <div class="flex items-center space-x-3">
                                                <div class="relative">
                                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                                                        <span class="font-medium">{{ driver.user?.name?.charAt(0) }}</span>
                                                    </div>
                                                    <span :class="['absolute bottom-0 right-0 w-3 h-3 rounded-full border-2 border-white', getDriverStatusColor(driver.status)]"></span>
                                                </div>
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ driver.user?.name }}</p>
                                                    <p class="text-xs text-gray-500">{{ driver.vehicle_type }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ orders.filter(o => o.assigned_driver_id === driver.user_id).length }} {{ $t('valet.orders_assigned') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Route Modal -->
        <div v-if="showRouteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50" @click="showRouteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ $t('valet.create_route') }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ selectedOrders.length }} {{ $t('valet.orders_selected') }}
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('valet.select_driver') }}
                        </label>
                        <select v-model="selectedDriver" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">{{ $t('valet.choose_driver') }}</option>
                            <option v-for="driver in drivers" :key="driver.id" :value="driver.id">
                                {{ driver.user?.name }} ({{ driver.vehicle_type }})
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button @click="showRouteModal = false" class="px-4 py-2 border border-gray-300 rounded-md">
                            {{ $t('common.cancel') }}
                        </button>
                        <button
                            @click="createRoute"
                            :disabled="!selectedDriver"
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
                        >
                            {{ $t('valet.create_route') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
