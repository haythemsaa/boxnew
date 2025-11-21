<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    customers: Array,
    invoices: Array,
    contracts: Array,
})

const form = useForm({
    customer_id: '',
    invoice_id: '',
    contract_id: '',
    payment_number: '',
    type: 'payment',
    status: 'completed',
    amount: '',
    fee: 0,
    currency: 'EUR',
    method: 'card',
    gateway: 'stripe',
    gateway_payment_id: '',
    card_brand: '',
    card_last_four: '',
    paid_at: new Date().toISOString().split('T')[0],
    notes: '',
})

const filteredInvoices = computed(() => {
    if (!form.customer_id) return props.invoices
    return props.invoices.filter((invoice) => invoice.customer_id == form.customer_id)
})

const filteredContracts = computed(() => {
    if (!form.customer_id) return props.contracts
    return props.contracts.filter((contract) => contract.customer_id == form.customer_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const submit = () => {
    form.post(route('tenant.payments.store'))
}
</script>

<template>
    <TenantLayout title="Create Payment">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <Link
                    :href="route('tenant.payments.index')"
                    class="text-sm text-primary-600 hover:text-primary-900 mb-4 inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Payments
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Create New Payment</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Basic Information</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer <span class="text-red-500">*</span></label>
                            <select id="customer_id" v-model="form.customer_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">Select a customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ getCustomerName(customer) }}</option>
                            </select>
                            <div v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</div>
                        </div>

                        <div>
                            <label for="invoice_id" class="block text-sm font-medium text-gray-700">Related Invoice (Optional)</label>
                            <select id="invoice_id" v-model="form.invoice_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="">No invoice</option>
                                <option v-for="invoice in filteredInvoices" :key="invoice.id" :value="invoice.id">{{ invoice.invoice_number }} - {{ (invoice.total - invoice.paid_amount).toFixed(2) }} EUR</option>
                            </select>
                        </div>

                        <div>
                            <label for="payment_number" class="block text-sm font-medium text-gray-700">Payment Number</label>
                            <input id="payment_number" v-model="form.payment_number" type="text" placeholder="Leave empty for auto-generation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                            <p class="mt-1 text-xs text-gray-500">Leave empty to auto-generate</p>
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                            <select id="type" v-model="form.type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="payment">Payment</option>
                                <option value="refund">Refund</option>
                                <option value="deposit">Deposit</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                            <select id="status" v-model="form.status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="completed">Completed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                            <input id="amount" v-model.number="form.amount" type="number" step="0.01" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                        </div>

                        <div>
                            <label for="method" class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                            <select id="method" v-model="form.method" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="sepa">SEPA</option>
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>

                        <div>
                            <label for="gateway" class="block text-sm font-medium text-gray-700">Gateway <span class="text-red-500">*</span></label>
                            <select id="gateway" v-model="form.gateway" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                                <option value="sepa">SEPA</option>
                                <option value="manual">Manual</option>
                            </select>
                        </div>

                        <div>
                            <label for="paid_at" class="block text-sm font-medium text-gray-700">Payment Date</label>
                            <input id="paid_at" v-model="form.paid_at" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" />
                        </div>

                        <div class="sm:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea id="notes" v-model="form.notes" rows="3" maxlength="2000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-4">
                    <Link :href="route('tenant.payments.index')" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50">
                        <span v-if="form.processing">Creating...</span>
                        <span v-else>Create Payment</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
