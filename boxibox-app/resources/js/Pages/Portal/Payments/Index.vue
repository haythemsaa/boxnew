<template>
    <AuthenticatedLayout title="My Payments">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment History</h2>

                <!-- Totals -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Total Paid</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ totals.total_paid }}</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ totals.pending }}</p>
                    </div>
                </div>

                <!-- Payments List -->
                <div class="space-y-3">
                    <div
                        v-for="payment in payments.data"
                        :key="payment.id"
                        class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors"
                    >
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ payment.payment_number }}</h3>
                                <p class="text-sm text-gray-600">{{ formatDate(payment.paid_at) }}</p>
                                <p class="text-sm text-gray-500">Method: {{ payment.method }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-green-600">€{{ payment.amount }}</p>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded-full font-medium': true,
                                        'bg-green-100 text-green-800': payment.status === 'completed',
                                        'bg-yellow-100 text-yellow-800': payment.status === 'pending',
                                        'bg-red-100 text-red-800': payment.status === 'failed'
                                    }"
                                >
                                    {{ payment.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="payments.data.length === 0" class="text-center py-12">
                    <p class="text-gray-500">You have no payment history yet.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    payments: Object,
    totals: Object,
    filters: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>
