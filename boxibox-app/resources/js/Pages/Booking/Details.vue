<template>
    <GuestLayout :title="`Storage Unit ${box.name}`">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <Link
                    :href="route('booking.index')"
                    class="text-blue-600 hover:text-blue-800"
                >
                    ← Back to Search
                </Link>
            </nav>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md p-8">
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">{{ box.name }}</h1>
                                <p class="text-lg text-gray-600 mt-2">{{ box.site.name }}</p>
                                <p class="text-gray-500">{{ box.site.address }}, {{ box.site.city }}</p>
                            </div>
                            <span class="px-4 py-2 bg-green-100 text-green-800 font-semibold rounded-full">
                                Available Now
                            </span>
                        </div>

                        <!-- Specifications -->
                        <div class="border-t border-b py-6 my-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Specifications</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Dimensions</p>
                                    <p class="text-lg font-medium">{{ box.length }} x {{ box.width }} x {{ box.height }} m</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Volume</p>
                                    <p class="text-lg font-medium">{{ box.volume.toFixed(2) }} m³</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Size Category</p>
                                    <p class="text-lg font-medium">{{ formatSizeCategory(box.size_category) }}</p>
                                </div>
                                <div v-if="box.floor">
                                    <p class="text-sm text-gray-600">Floor</p>
                                    <p class="text-lg font-medium">{{ box.floor }}</p>
                                </div>
                                <div v-if="box.zone">
                                    <p class="text-sm text-gray-600">Zone</p>
                                    <p class="text-lg font-medium">{{ box.zone }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Condition</p>
                                    <p class="text-lg font-medium capitalize">{{ box.condition || 'Good' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div v-if="box.features && box.features.length > 0" class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Features</h2>
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    v-for="feature in box.features"
                                    :key="feature"
                                    class="flex items-center"
                                >
                                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-gray-700">{{ feature }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Site Information -->
                        <div class="border-t pt-6">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Facility Information</h2>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-sm text-gray-600">Address</p>
                                    <p class="text-gray-900">{{ box.site.address }}</p>
                                    <p class="text-gray-900">{{ box.site.postal_code }} {{ box.site.city }}, {{ box.site.country }}</p>
                                </div>
                                <div v-if="box.site.phone">
                                    <p class="text-sm text-gray-600">Phone</p>
                                    <p class="text-gray-900">{{ box.site.phone }}</p>
                                </div>
                                <div v-if="box.site.email">
                                    <p class="text-sm text-gray-600">Email</p>
                                    <p class="text-gray-900">{{ box.site.email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Similar Units -->
                    <div v-if="similarBoxes.length > 0" class="mt-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Similar Storage Units</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="similarBox in similarBoxes"
                                :key="similarBox.id"
                                class="bg-white rounded-lg shadow-md p-4"
                            >
                                <h3 class="font-semibold text-gray-900">{{ similarBox.name }}</h3>
                                <p class="text-sm text-gray-600 mb-2">
                                    {{ similarBox.length }} x {{ similarBox.width }} x {{ similarBox.height }} m
                                </p>
                                <div class="flex justify-between items-center">
                                    <p class="text-xl font-bold text-blue-600">€{{ similarBox.current_price }}/mo</p>
                                    <Link
                                        :href="route('booking.show', similarBox.id)"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        View →
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Card (Sticky) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <div class="text-center mb-6">
                            <p class="text-4xl font-bold text-blue-600">€{{ box.current_price }}</p>
                            <p class="text-gray-600">/month</p>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Monthly Price:</span>
                                <span class="font-medium">€{{ box.current_price }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Security Deposit:</span>
                                <span class="font-medium">€{{ box.current_price }}</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between">
                                <span class="font-semibold">Due Today:</span>
                                <span class="font-bold text-lg">€{{ (box.current_price * 2).toFixed(2) }}</span>
                            </div>
                        </div>

                        <Link
                            :href="route('booking.checkout', box.id)"
                            class="block w-full py-3 px-4 bg-blue-600 text-white text-center font-semibold rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Reserve Now
                        </Link>

                        <div class="mt-6 pt-6 border-t space-y-3">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Move in anytime</p>
                                    <p class="text-sm text-gray-600">Choose your start date</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">No long-term commitment</p>
                                    <p class="text-sm text-gray-600">Cancel anytime</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-medium text-gray-900">Secure & Monitored</p>
                                    <p class="text-sm text-gray-600">24/7 security</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    box: Object,
    similarBoxes: Array,
});

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
