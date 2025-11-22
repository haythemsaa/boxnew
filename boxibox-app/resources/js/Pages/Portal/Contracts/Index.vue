<template>
    <AuthenticatedLayout title="My Contracts">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">My Storage Contracts</h2>

                <div class="space-y-4">
                    <div
                        v-for="contract in contracts.data"
                        :key="contract.id"
                        class="border border-gray-200 rounded-lg p-6 hover:border-blue-500 transition-colors"
                    >
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ contract.box.name }}</h3>
                                    <span
                                        :class="{
                                            'px-2 py-1 text-xs rounded-full font-medium': true,
                                            'bg-green-100 text-green-800': contract.status === 'active',
                                            'bg-gray-100 text-gray-800': contract.status === 'expired',
                                            'bg-red-100 text-red-800': contract.status === 'cancelled'
                                        }"
                                    >
                                        {{ contract.status }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">{{ contract.box.site.name }}</p>
                                <p class="text-sm text-gray-500 mt-2">Contract: {{ contract.contract_number }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ formatDate(contract.start_date) }} - {{ formatDate(contract.end_date) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold text-gray-900">€{{ contract.monthly_price }}</p>
                                <p class="text-sm text-gray-500">/month</p>
                                <div class="mt-4 space-x-2">
                                    <Link
                                        :href="route('portal.contracts.show', contract.id)"
                                        class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                                    >
                                        View Details
                                    </Link>
                                    <Link
                                        :href="route('portal.contracts.pdf', contract.id)"
                                        class="inline-flex items-center px-3 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200"
                                    >
                                        Download PDF
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="contracts.data.length === 0" class="text-center py-12">
                    <p class="text-gray-500">You have no contracts yet.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    contracts: Object,
    filters: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
