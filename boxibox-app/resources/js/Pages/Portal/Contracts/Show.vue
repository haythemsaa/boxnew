<template>
    <AuthenticatedLayout title="Contract Details">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Contract {{ contract.contract_number }}</h2>
                        <p class="text-gray-600 mt-1">{{ contract.box.name }} at {{ contract.box.site.name }}</p>
                    </div>
                    <Link
                        :href="route('portal.contracts.pdf', contract.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                    >
                        Download PDF
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ contract.status }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Monthly Price</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">€{{ contract.monthly_price }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Start Date</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(contract.start_date) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">End Date</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(contract.end_date) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Billing Frequency</h3>
                        <p class="mt-1 text-gray-900">{{ contract.billing_frequency }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Payment Method</h3>
                        <p class="mt-1 text-gray-900">{{ contract.payment_method }}</p>
                    </div>
                </div>

                <div v-if="contract.invoices && contract.invoices.length > 0" class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Invoices</h3>
                    <div class="space-y-2">
                        <div
                            v-for="invoice in contract.invoices"
                            :key="invoice.id"
                            class="flex justify-between items-center p-3 border border-gray-200 rounded"
                        >
                            <div>
                                <span class="font-medium">{{ invoice.invoice_number }}</span>
                                <span class="text-gray-500 ml-2">{{ formatDate(invoice.invoice_date) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold">€{{ invoice.total }}</span>
                                <span class="text-sm text-gray-500 ml-2">{{ invoice.status }}</span>
                            </div>
                        </div>
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
    contract: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
