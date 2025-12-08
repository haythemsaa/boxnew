<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    items: Object,
    customers: Array,
    sites: Array,
    filters: Object,
    categories: Object,
    statuses: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    customer_id: props.filters?.customer_id || '',
    site_id: props.filters?.site_id || '',
    category: props.filters?.category || '',
    search: props.filters?.search || '',
});

const applyFilters = () => {
    router.get(route('tenant.valet.items'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = { status: '', customer_id: '', site_id: '', category: '', search: '' };
    applyFilters();
};

const getStatusColor = (status) => {
    const colors = {
        pending_pickup: 'bg-yellow-100 text-yellow-800',
        in_transit_to_storage: 'bg-blue-100 text-blue-800',
        stored: 'bg-green-100 text-green-800',
        pending_delivery: 'bg-orange-100 text-orange-800',
        in_transit_to_customer: 'bg-purple-100 text-purple-800',
        delivered: 'bg-gray-100 text-gray-800',
        disposed: 'bg-red-100 text-red-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};
</script>

<template>
    <Head :title="$t('valet.inventory')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('valet.inventory') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('valet.inventory_subtitle') }}
                        </p>
                    </div>
                    <Link
                        :href="route('tenant.valet.items.create')"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                    >
                        {{ $t('valet.add_item') }}
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <input
                                    type="text"
                                    v-model="localFilters.search"
                                    @keyup.enter="applyFilters"
                                    :placeholder="$t('common.search')"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm"
                                />
                            </div>
                            <div>
                                <select v-model="localFilters.status" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_statuses') }}</option>
                                    <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <select v-model="localFilters.customer_id" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_customers') }}</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.first_name }} {{ customer.last_name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <select v-model="localFilters.site_id" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_sites') }}</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                </select>
                            </div>
                            <div>
                                <select v-model="localFilters.category" @change="applyFilters" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option value="">{{ $t('valet.all_categories') }}</option>
                                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
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

                <!-- Items Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.barcode') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.item_name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.customer') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.category') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.size') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.monthly_fee') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('valet.status') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white">{{ item.barcode }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                    <div v-if="item.is_fragile" class="text-xs text-red-600">Fragile</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.customer?.first_name }} {{ item.customer?.last_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ categories[item.category] || item.category || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ item.size === 'small' ? 'Petit' : item.size === 'medium' ? 'Moyen' : item.size === 'large' ? 'Grand' : 'Très grand' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(item.monthly_fee) }}/mois
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusColor(item.status)]">
                                        {{ statuses[item.status] || item.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <Link :href="route('tenant.valet.items.show', item.id)" class="text-primary-600 hover:text-primary-800 mr-3">
                                        {{ $t('common.view') }}
                                    </Link>
                                    <Link :href="route('tenant.valet.items.edit', item.id)" class="text-gray-600 hover:text-gray-800">
                                        {{ $t('common.edit') }}
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="items.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('valet.no_items_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="items.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">
                                {{ $t('common.showing') }} {{ items.from }} - {{ items.to }} {{ $t('common.of') }} {{ items.total }}
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in items.links"
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
