<template>
    <AuthenticatedLayout title="Payment Details">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment {{ payment.payment_number }}</h2>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Amount</h3>
                        <p class="mt-1 text-2xl font-bold text-green-600">€{{ payment.amount }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ payment.status }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Payment Date</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(payment.paid_at) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Method</h3>
                        <p class="mt-1 text-gray-900">{{ payment.method }}</p>
                    </div>
                    <div v-if="payment.reference">
                        <h3 class="text-sm font-medium text-gray-500">Reference</h3>
                        <p class="mt-1 text-gray-900">{{ payment.reference }}</p>
                    </div>
                    <div v-if="payment.transaction_id">
                        <h3 class="text-sm font-medium text-gray-500">Transaction ID</h3>
                        <p class="mt-1 text-gray-900">{{ payment.transaction_id }}</p>
                    </div>
                </div>

                <div v-if="payment.invoice" class="mt-8 border-t pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Invoice</h3>
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-900">{{ payment.invoice.invoice_number }}</p>
                            <p class="text-sm text-gray-600">{{ formatDate(payment.invoice.invoice_date) }}</p>
                        </div>
                        <Link
                            :href="route('portal.invoices.show', payment.invoice.id)"
                            class="text-blue-600 hover:text-blue-800"
                        >
                            View Invoice →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    payment: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
