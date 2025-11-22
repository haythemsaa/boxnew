<template>
    <AuthenticatedLayout title="Invoice Details">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Invoice {{ invoice.invoice_number }}</h2>
                        <p class="text-gray-600 mt-1">Issued: {{ formatDate(invoice.invoice_date) }}</p>
                    </div>
                    <Link
                        :href="route('portal.invoices.pdf', invoice.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700"
                    >
                        Download PDF
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ invoice.status }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Due Date</h3>
                        <p class="mt-1 text-gray-900">{{ formatDate(invoice.due_date) }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Total Amount</h3>
                        <p class="mt-1 text-2xl font-bold text-gray-900">€{{ invoice.total }}</p>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Balance Due</h3>
                        <p class="mt-1 text-2xl font-bold text-red-600">€{{ invoice.balance }}</p>
                    </div>
                </div>

                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Line Items</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(item, index) in invoice.items" :key="index">
                                <td class="px-4 py-3">{{ item.description }}</td>
                                <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-right">€{{ item.unit_price }}</td>
                                <td class="px-4 py-3 text-right">€{{ item.total }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="px-4 py-2"></td></tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right font-medium">Subtotal:</td>
                                <td class="px-4 py-2 text-right">€{{ invoice.subtotal }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-4 py-2 text-right font-medium">Tax ({{ invoice.tax_rate }}%):</td>
                                <td class="px-4 py-2 text-right">€{{ invoice.tax_amount }}</td>
                            </tr>
                            <tr class="font-bold">
                                <td colspan="3" class="px-4 py-2 text-right text-lg">Total:</td>
                                <td class="px-4 py-2 text-right text-lg">€{{ invoice.total }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    invoice: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};
</script>
