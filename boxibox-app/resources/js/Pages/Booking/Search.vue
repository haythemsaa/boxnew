<template>
    <GuestLayout title="Book Your Storage Space">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-12 mb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h1 class="text-4xl font-bold mb-4">Find Your Perfect Storage Space</h1>
                <p class="text-xl">Secure, affordable, and convenient storage solutions</p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Filter Storage Units</h2>
                <form @submit.prevent="search" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select
                            v-model="filterForm.site_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="">All Locations</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }} - {{ site.city }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                        <select
                            v-model="filterForm.size_category"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-500"
                        >
                            <option value="">All Sizes</option>
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                            <option value="extra_large">Extra Large</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Max Price</label>
                        <input
                            v-model="filterForm.max_price"
                            type="number"
                            placeholder="€/month"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select
                            v-model="filterForm.sort_by"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="price_asc">Price: Low to High</option>
                            <option value="price_desc">Price: High to Low</option>
                            <option value="size_asc">Size: Small to Large</option>
                            <option value="size_desc">Size: Large to Small</option>
                        </select>
                    </div>

                    <div class="md:col-span-4 flex justify-end">
                        <button
                            type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Search
                        </button>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Available Storage Units
                    <span class="text-gray-500 text-lg">({{ boxes.total }} found)</span>
                </h2>
            </div>

            <!-- Box Grid -->
            <div v-if="boxes.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="box in boxes.data"
                    :key="box.id"
                    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow"
                >
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">{{ box.name }}</h3>
                                <p class="text-sm text-gray-600">{{ box.site.name }}</p>
                                <p class="text-xs text-gray-500">{{ box.site.city }}</p>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                                Available
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                                </svg>
                                {{ box.length }} x {{ box.width }} x {{ box.height }} m
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                                {{ box.volume.toFixed(2) }} m³
                            </div>
                            <div class="flex items-center text-sm">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded">
                                    {{ formatSizeCategory(box.size_category) }}
                                </span>
                            </div>
                        </div>

                        <div class="border-t pt-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-3xl font-bold text-blue-600">€{{ box.current_price }}</p>
                                    <p class="text-sm text-gray-500">/month</p>
                                </div>
                                <Link
                                    :href="route('booking.show', box.id)"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                                >
                                    View Details
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No storage units found</h3>
                <p class="mt-1 text-sm text-gray-500">Try adjusting your search filters</p>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    sites: Array,
    boxes: Object,
    filters: Object,
});

const filterForm = ref({
    site_id: props.filters.site_id || '',
    size_category: props.filters.size_category || '',
    max_price: props.filters.max_price || '',
    sort_by: props.filters.sort_by || 'price_asc',
});

const search = () => {
    router.get(route('booking.index'), filterForm.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatSizeCategory = (category) => {
    const map = {
        small: 'Small',
        medium: 'Medium',
        large: 'Large',
        extra_large: 'Extra Large',
    };
    return map[category] || category;
};
</script>
