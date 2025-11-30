<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    invoice: Object,
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
        draft: 'bg-gray-100 text-gray-800',
        sent: 'bg-blue-100 text-blue-800',
        paid: 'bg-green-100 text-green-800',
        partial: 'bg-yellow-100 text-yellow-800',
        overdue: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getTypeColor = (type) => {
    const colors = {
        invoice: 'bg-blue-100 text-blue-800',
        credit_note: 'bg-purple-100 text-purple-800',
        proforma: 'bg-indigo-100 text-indigo-800',
    }
    return colors[type] || 'bg-gray-100 text-gray-800'
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const items = ref(typeof props.invoice.items === 'string' ? JSON.parse(props.invoice.items) : props.invoice.items || [])

const remainingAmount = props.invoice.total - props.invoice.paid_amount
</script>

<template>
    <TenantLayout :title="`Invoice ${invoice.invoice_number}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.invoices.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ invoice.invoice_number }}</h1>
                            <div class="mt-1 flex items-center space-x-3">
                                <span :class="getStatusColor(invoice.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ invoice.status }}
                                </span>
                                <span :class="getTypeColor(invoice.type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ invoice.type.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a
                            :href="route('tenant.invoices.pdf', invoice.id)"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                        <Link
                            :href="route('tenant.invoices.edit', invoice.id)"
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
                    <!-- Invoice Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Invoice Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Invoice Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.invoice_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Due Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.due_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Period</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Paid At</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(invoice.paid_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Line Items</h2>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(item, index) in items" :key="index">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ item.quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(item.quantity * item.unit_price) }}</td>
                                </tr>
                                <tr v-if="items.length === 0">
                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">No items</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Totals -->
                        <div class="bg-gray-50 px-6 py-4">
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Subtotal</dt>
                                    <dd class="text-gray-900">{{ formatCurrency(invoice.subtotal) }}</dd>
                                </div>
                                <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Discount</dt>
                                    <dd class="text-red-600">-{{ formatCurrency(invoice.discount_amount) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Tax ({{ invoice.tax_rate }}%)</dt>
                                    <dd class="text-gray-900">{{ formatCurrency(invoice.tax_amount) }}</dd>
                                </div>
                                <div class="flex justify-between text-base font-medium border-t border-gray-200 pt-2">
                                    <dt class="text-gray-900">Total</dt>
                                    <dd class="text-gray-900">{{ formatCurrency(invoice.total) }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Paid Amount</dt>
                                    <dd class="text-green-600">{{ formatCurrency(invoice.paid_amount) }}</dd>
                                </div>
                                <div class="flex justify-between text-base font-medium">
                                    <dt class="text-gray-900">Remaining</dt>
                                    <dd :class="remainingAmount > 0 ? 'text-red-600' : 'text-green-600'">{{ formatCurrency(remainingAmount) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div v-if="invoice.payments && invoice.payments.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Payments</h2>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="payment in invoice.payments" :key="payment.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatDate(payment.payment_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">{{ payment.payment_method?.replace('_', ' ') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ payment.reference || '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(payment.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Notes -->
                    <div v-if="invoice.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ invoice.notes }}</p>
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
                                        {{ (invoice.customer?.first_name?.[0] || invoice.customer?.company_name?.[0] || '?').toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ getCustomerName(invoice.customer) }}</p>
                                    <p class="text-sm text-gray-500">{{ invoice.customer?.email }}</p>
                                </div>
                            </div>
                            <dl class="space-y-2 text-sm">
                                <div v-if="invoice.customer?.phone">
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="text-gray-900">{{ invoice.customer.phone }}</dd>
                                </div>
                                <div v-if="invoice.customer?.address">
                                    <dt class="text-gray-500">Address</dt>
                                    <dd class="text-gray-900">
                                        {{ invoice.customer.address }}<br>
                                        {{ invoice.customer.postal_code }} {{ invoice.customer.city }}
                                    </dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.customers.show', invoice.customer?.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Customer Profile
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Contract Info -->
                    <div v-if="invoice.contract" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Contract</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Contract Number</dt>
                                    <dd class="text-gray-900">{{ invoice.contract.contract_number }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Box</dt>
                                    <dd class="text-gray-900">{{ invoice.contract.box?.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Monthly Price</dt>
                                    <dd class="text-gray-900">{{ formatCurrency(invoice.contract.monthly_price) }}</dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.contracts.show', invoice.contract.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Contract
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Reminders -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Reminders</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Reminder Count</dt>
                                    <dd class="text-gray-900">{{ invoice.reminder_count }}</dd>
                                </div>
                                <div v-if="invoice.last_reminder_sent">
                                    <dt class="text-gray-500">Last Reminder Sent</dt>
                                    <dd class="text-gray-900">{{ formatDate(invoice.last_reminder_sent) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
