<template>
    <AuthenticatedLayout title="My Dashboard">
        <!-- Welcome Section -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Welcome, {{ customerName }}!
                </h2>
                <p class="mt-1 text-gray-600">
                    Manage your storage contracts, view invoices, and track payments.
                </p>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Active Contracts -->
            <StatsCard
                title="Active Contracts"
                :value="stats.active_contracts"
                icon="document"
                color="blue"
            />

            <!-- Total Invoices -->
            <StatsCard
                title="Total Invoices"
                :value="stats.total_invoices"
                :subtitle="`${stats.pending_invoices} pending`"
                icon="document"
                color="yellow"
            />

            <!-- Total Paid -->
            <StatsCard
                title="Total Paid"
                :value="formatCurrency(stats.total_paid)"
                icon="check"
                color="green"
            />

            <!-- Outstanding Balance -->
            <StatsCard
                title="Outstanding Balance"
                :value="formatCurrency(stats.outstanding_balance)"
                icon="currency"
                color="red"
            />
        </div>

        <!-- Active Contracts -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-6" v-if="activeContracts.length > 0">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Active Contracts</h3>
                <div class="space-y-4">
                    <div
                        v-for="contract in activeContracts"
                        :key="contract.id"
                        class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 transition-colors"
                    >
                        <div class="flex justify-between items-start">
                            <div>
                                <h4 class="font-medium text-gray-900">{{ contract.box.name }}</h4>
                                <p class="text-sm text-gray-600">{{ contract.box.site.name }}</p>
                                <p class="text-sm text-gray-500 mt-1">Contract: {{ contract.contract_number }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-900">€{{ contract.monthly_price }}/mo</p>
                                <Link
                                    :href="route('portal.contracts.show', contract.id)"
                                    class="text-sm text-blue-600 hover:text-blue-800"
                                >
                                    View Details →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Invoices and Payments Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2">
            <!-- Recent Invoices -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Invoices</h3>
                        <Link
                            :href="route('portal.invoices.index')"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="invoice in recentInvoices"
                            :key="invoice.id"
                            class="flex justify-between items-center border-b border-gray-100 pb-3"
                        >
                            <div>
                                <p class="font-medium text-gray-900">{{ invoice.invoice_number }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(invoice.invoice_date) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900">€{{ invoice.total }}</p>
                                <span
                                    :class="{
                                        'px-2 py-1 text-xs rounded-full': true,
                                        'bg-green-100 text-green-800': invoice.status === 'paid',
                                        'bg-yellow-100 text-yellow-800': invoice.status === 'sent',
                                        'bg-red-100 text-red-800': invoice.status === 'overdue'
                                    }"
                                >
                                    {{ invoice.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Payments</h3>
                        <Link
                            :href="route('portal.payments.index')"
                            class="text-sm text-blue-600 hover:text-blue-800"
                        >
                            View All
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="flex justify-between items-center border-b border-gray-100 pb-3"
                        >
                            <div>
                                <p class="font-medium text-gray-900">{{ payment.payment_number }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(payment.paid_at) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-green-600">€{{ payment.amount }}</p>
                                <span class="text-xs text-gray-500">{{ payment.method }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatsCard from '@/Components/StatsCard.vue';

const props = defineProps({
    customer: Object,
    activeContracts: Array,
    recentInvoices: Array,
    recentPayments: Array,
    stats: Object,
});

const customerName = computed(() => {
    if (props.customer.type === 'company') {
        return props.customer.company_name;
    }
    return `${props.customer.first_name} ${props.customer.last_name}`;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-EU', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>
