<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contract: Object,
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
        pending_signature: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        expired: 'bg-red-100 text-red-800',
        terminated: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getTypeColor = (type) => {
    const colors = {
        standard: 'bg-blue-100 text-blue-800',
        short_term: 'bg-purple-100 text-purple-800',
        long_term: 'bg-indigo-100 text-indigo-800',
    }
    return colors[type] || 'bg-gray-100 text-gray-800'
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getInvoiceStatusColor = (status) => {
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
</script>

<template>
    <TenantLayout :title="`Contract ${contract.contract_number}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.contracts.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ contract.contract_number }}</h1>
                            <div class="mt-1 flex items-center space-x-3">
                                <span :class="getStatusColor(contract.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ contract.status.replace('_', ' ') }}
                                </span>
                                <span :class="getTypeColor(contract.type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ contract.type.replace('_', ' ') }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a
                            :href="route('tenant.contracts.pdf', contract.id)"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                        <Link
                            :href="route('tenant.contracts.edit', contract.id)"
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
                    <!-- Contract Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Contract Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(contract.start_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(contract.end_date) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Monthly Price</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ formatCurrency(contract.monthly_price) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Deposit</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatCurrency(contract.deposit_amount) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Payment Method</dt>
                                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ contract.payment_method?.replace('_', ' ') || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Billing Day</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ contract.billing_day || 1 }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Insurance</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span v-if="contract.insurance_included" class="text-green-600">Included</span>
                                        <span v-else class="text-gray-500">Not included</span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Auto-Renewal</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        <span v-if="contract.auto_renewal" class="text-green-600">Yes</span>
                                        <span v-else class="text-gray-500">No</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Box Information -->
                    <div v-if="contract.box" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Box Information</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Box Code</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ contract.box.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ contract.box.size }} m²</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Site</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ contract.site?.name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Floor</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ contract.box.floor?.name || 'Ground' }}</dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.boxes.index')"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View All Boxes
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Invoices -->
                    <div v-if="contract.invoices && contract.invoices.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Invoices</h2>
                            <span class="text-sm text-gray-500">{{ contract.invoices.length }} invoice(s)</span>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="invoice in contract.invoices" :key="invoice.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('tenant.invoices.show', invoice.id)" class="text-primary-600 hover:text-primary-900">
                                            {{ invoice.invoice_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(invoice.invoice_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getInvoiceStatusColor(invoice.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                            {{ invoice.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">{{ formatCurrency(invoice.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Special Conditions -->
                    <div v-if="contract.special_conditions" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Special Conditions</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ contract.special_conditions }}</p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="contract.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ contract.notes }}</p>
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
                                        {{ (contract.customer?.first_name?.[0] || contract.customer?.company_name?.[0] || '?').toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ getCustomerName(contract.customer) }}</p>
                                    <p class="text-sm text-gray-500">{{ contract.customer?.email }}</p>
                                </div>
                            </div>
                            <dl class="space-y-2 text-sm">
                                <div v-if="contract.customer?.phone">
                                    <dt class="text-gray-500">Phone</dt>
                                    <dd class="text-gray-900">{{ contract.customer.phone }}</dd>
                                </div>
                                <div v-if="contract.customer?.address">
                                    <dt class="text-gray-500">Address</dt>
                                    <dd class="text-gray-900">
                                        {{ contract.customer.address }}<br>
                                        {{ contract.customer.postal_code }} {{ contract.customer.city }}
                                    </dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.customers.show', contract.customer?.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Customer Profile
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Signature</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Signed At</dt>
                                    <dd class="text-gray-900">{{ formatDate(contract.signed_at) }}</dd>
                                </div>
                                <div v-if="contract.signature">
                                    <dt class="text-gray-500">Signature</dt>
                                    <dd class="text-green-600">Completed</dd>
                                </div>
                            </dl>
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
                                    <dd class="text-gray-900">{{ formatDate(contract.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-900">{{ formatDate(contract.updated_at) }}</dd>
                                </div>
                                <div v-if="contract.terminated_at">
                                    <dt class="text-gray-500">Terminated</dt>
                                    <dd class="text-red-600">{{ formatDate(contract.terminated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
