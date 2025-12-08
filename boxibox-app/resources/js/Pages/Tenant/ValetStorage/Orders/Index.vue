<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    orders: Object,
    drivers: Array,
    filters: Object,
    statuses: Object,
    types: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    type: props.filters?.type || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
    driver_id: props.filters?.driver_id || '',
});

const applyFilters = () => {
    router.get(route('tenant.valet.orders'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = { status: '', type: '', date_from: '', date_to: '', driver_id: '' };
    applyFilters();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
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

const getTypeIcon = (type) => {
    if (type === 'pickup') return '↑';
    if (type === 'delivery') return '↓';
    return '↕';
};
</script>

<template>
    <Head :title="$t('valet.orders')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('valet.all_orders') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('valet.orders_subtitle') }}
                        </p>
                    </div>
                    <Link
                        :href="route('tenant.valet.orders.create')"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        {{ $t('valet.new_order') }}
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <select v-model="localFilters.status" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_statuses') }}</option>
                                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <select v-model="localFilters.type" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_types') }}</option>
                                    <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <input type="date" v-model="localFilters.date_from" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm" />
                            </div>
                            <div>
                                <input type="date" v-model="localFilters.date_to" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm" />
                            </div>
                            <div>
                                <select v-model="localFilters.driver_id" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_drivers') }}</option>
                                    <option v-for="driver in drivers" :key="driver.id" :value="driver.user_id">
                                        {{ driver.user?.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <button @click="resetFilters" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm hover:bg-gray-50">
                                    {{ $t('common.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.order_number') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.customer') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.type') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.date') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.address') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.driver') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="route('tenant.valet.orders.show', order.id)" class="text-primary-600 hover:text-primary-800 font-medium">
                                        {{ order.order_number }}
                                    </Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ order.customer?.first_name }} {{ order.customer?.last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <span class="mr-2 text-lg">{{ getTypeIcon(order.type) }}</span>
                                        {{ types[order.type] || order.type }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ formatDate(order.requested_date) }}</div>
                                    <div class="text-xs">{{ order.time_slot === 'morning' ? '8h-12h' : order.time_slot === 'afternoon' ? '12h-18h' : '18h-20h' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ order.city }}</div>
                                    <div class="text-xs">{{ order.postal_code }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ order.driver?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(order.status)]">
                                        {{ statuses[order.status] || order.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(order.total_fee) }}
                                </td>
                            </tr>
                            <tr v-if="orders.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('valet.no_orders_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="orders.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                {{ $t('common.showing') }} {{ orders.from }} - {{ orders.to }} {{ $t('common.of') }} {{ orders.total }}
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in orders.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active ? 'bg-primary-600 text-white' : link.url ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                ></Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
