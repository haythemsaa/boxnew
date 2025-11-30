<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    payment: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        refunded: 'bg-purple-100 text-purple-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getMethodIcon = (method) => {
    const icons = {
        card: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        bank_transfer: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
        sepa: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
        check: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    }
    return icons[method] || icons.cash
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <TenantLayout :title="`Payment ${payment.payment_number || payment.id}`">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.payments.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                Payment #{{ payment.payment_number || payment.id }}
                            </h1>
                            <div class="mt-1 flex items-center space-x-3">
                                <span :class="getStatusColor(payment.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ payment.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('tenant.payments.edit', payment.id)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Amount Card -->
                    <div class="bg-gradient-to-r from-primary-600 to-primary-700 shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-8 text-center">
                            <p class="text-primary-100 text-sm font-medium uppercase tracking-wide">Amount</p>
                            <p class="mt-2 text-4xl font-bold text-white">{{ formatCurrency(payment.amount) }}</p>
                            <div class="mt-4 flex items-center justify-center space-x-2">
                                <svg class="h-5 w-5 text-primary-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getMethodIcon(payment.payment_method)" />
                                </svg>
                                <span class="text-primary-100 capitalize">{{ payment.payment_method?.replace('_', ' ') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Payment Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Payment Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(payment.payment_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ payment.payment_method?.replace('_', ' ') }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Reference</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ payment.reference || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Transaction ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono text-xs">{{ payment.transaction_id || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Invoice Association -->
                    <div v-if="payment.invoice" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Associated Invoice</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ payment.invoice.invoice_number }}</p>
                                    <p class="text-sm text-gray-500">{{ formatDate(payment.invoice.invoice_date) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">{{ formatCurrency(payment.invoice.total) }}</p>
                                    <span :class="[
                                        payment.invoice.status === 'paid' ? 'text-green-600' : 'text-yellow-600'
                                    ]" class="text-sm">
                                        {{ payment.invoice.status }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.invoices.show', payment.invoice.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Invoice
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="payment.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ payment.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Customer</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-medium">
                                        {{ (payment.customer?.first_name?.[0] || payment.customer?.company_name?.[0] || '?').toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ getCustomerName(payment.customer) }}</p>
                                    <p class="text-sm text-gray-500">{{ payment.customer?.email }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.customers.show', payment.customer?.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Customer Profile
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Timeline</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Created</dt>
                                    <dd class="text-gray-900">{{ formatDate(payment.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-900">{{ formatDate(payment.updated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Actions</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <button class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                Print Receipt
                            </button>
                            <button class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Send Confirmation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
