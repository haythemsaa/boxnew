<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contract: Object,
})

const showTerminationModal = ref(false)
const terminationForm = useForm({
    termination_reason: 'customer_request',
    termination_notes: '',
    effective_date: new Date().toISOString().split('T')[0],
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

const submitTermination = () => {
    terminationForm.post(route('tenant.contracts.terminate', props.contract.id), {
        onSuccess: () => {
            showTerminationModal.value = false
            terminationForm.reset()
        },
    })
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
                            v-if="contract.status === 'pending_signature'"
                            :href="route('tenant.contracts.sign', contract.id)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Sign Contract
                        </Link>
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

                    <!-- Contract Actions -->
                    <div v-if="contract.status === 'active'" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Actions</h2>
                        </div>
                        <div class="px-6 py-4 space-y-3">
                            <Link
                                :href="route('tenant.contracts.renewal-options', contract.id)"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                Renew
                            </Link>
                            <button
                                @click="showTerminationModal = true"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Terminate
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Termination Modal -->
            <div v-if="showTerminationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Terminate Contract</h3>
                        <form @submit.prevent="submitTermination" class="space-y-4 text-left">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                                <select v-model="terminationForm.termination_reason" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="customer_request">Customer Request</option>
                                    <option value="non_payment">Non-Payment</option>
                                    <option value="breach">Breach of Contract</option>
                                    <option value="end_of_term">End of Term</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Effective Date</label>
                                <input v-model="terminationForm.effective_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                                <textarea v-model="terminationForm.termination_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Terminate</button>
                                <button type="button" @click="showTerminationModal = false" class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
