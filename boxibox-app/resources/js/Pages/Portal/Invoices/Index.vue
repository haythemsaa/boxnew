<template>
    <AuthenticatedLayout title="My Invoices">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">My Invoices</h2>

                <!-- Totals -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Outstanding</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ totals.outstanding }}</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">Paid</p>
                        <p class="text-2xl font-bold text-gray-900">€{{ totals.paid }}</p>
                    </div>
                </div>

                <!-- Invoices List -->
                <div class="space-y-3">
                    <div
                        v-for="invoice in invoices.data"
                        :key="invoice.id"
                        class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors"
                    >
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ invoice.invoice_number }}</h3>
                                <p class="text-sm text-gray-600">{{ formatDate(invoice.invoice_date) }}</p>
                                <p class="text-sm text-gray-500">Due: {{ formatDate(invoice.due_date) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-gray-900">€{{ invoice.total }}</p>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded-full font-medium': true,
                                        'bg-green-100 text-green-800': invoice.status === 'paid',
                                        'bg-yellow-100 text-yellow-800': invoice.status === 'sent',
                                        'bg-red-100 text-red-800': invoice.status === 'overdue'
                                    }"
                                >
                                    {{ invoice.status }}
                                </span>
                                <div class="mt-2 space-x-2">
                                    <Link
                                        :href="route('portal.invoices.show', invoice.id)"
                                        class="text-sm text-blue-600 hover:text-blue-800"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('portal.invoices.pdf', invoice.id)"
                                        class="text-sm text-gray-600 hover:text-gray-800"
                                    >
                                        PDF
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="invoices.data.length === 0" class="text-center py-12">
                    <p class="text-gray-500">You have no invoices yet.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    invoices: Object,
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
