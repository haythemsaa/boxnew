<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    invoice: Object,
    customers: Array,
    contracts: Array,
})

const form = useForm({
    customer_id: props.invoice.customer_id,
    contract_id: props.invoice.contract_id,
    invoice_number: props.invoice.invoice_number,
    type: props.invoice.type,
    status: props.invoice.status,
    invoice_date: props.invoice.invoice_date,
    due_date: props.invoice.due_date,
    paid_at: props.invoice.paid_at,
    period_start: props.invoice.period_start,
    period_end: props.invoice.period_end,
    items: props.invoice.items || [{ description: '', quantity: 1, unit_price: 0, total: 0 }],
    subtotal: props.invoice.subtotal,
    tax_rate: props.invoice.tax_rate,
    tax_amount: props.invoice.tax_amount,
    discount_amount: props.invoice.discount_amount || 0,
    total: props.invoice.total,
    paid_amount: props.invoice.paid_amount || 0,
    currency: props.invoice.currency,
    notes: props.invoice.notes,
    is_recurring: props.invoice.is_recurring,
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

const addItem = () => {
    form.items.push({ description: '', quantity: 1, unit_price: 0, total: 0 })
}

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1)
        calculateTotals()
    }
}

const calculateItemTotal = (index) => {
    const item = form.items[index]
    item.total = (item.quantity * item.unit_price).toFixed(2)
    calculateTotals()
}

const calculateTotals = () => {
    form.subtotal = form.items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0)
    form.tax_amount = (form.subtotal * (form.tax_rate / 100)).toFixed(2)
    form.total = (
        parseFloat(form.subtotal) +
        parseFloat(form.tax_amount) -
        parseFloat(form.discount_amount || 0)
    ).toFixed(2)
}

watch(() => form.tax_rate, calculateTotals)
watch(() => form.discount_amount, calculateTotals)

const submit = () => {
    form.put(route('tenant.invoices.update', props.invoice.id))
}
</script>

<template>
    <TenantLayout title="Edit Invoice">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('tenant.invoices.index')"
                    class="text-sm text-primary-600 hover:text-primary-900 mb-4 inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Back to Invoices
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Edit Invoice</h1>
                <p class="mt-2 text-sm text-gray-700">Update invoice {{ invoice.invoice_number }}</p>
            </div>

            <!-- Form (same structure as Create.vue) -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700">
                                Customer <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="customer_id"
                                v-model="form.customer_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">Select a customer</option>
                                <option
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    :value="customer.id"
                                >
                                    {{ getCustomerName(customer) }}
                                </option>
                            </select>
                            <div v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.customer_id }}
                            </div>
                        </div>

                        <div>
                            <label for="contract_id" class="block text-sm font-medium text-gray-700">
                                Related Contract (Optional)
                            </label>
                            <select
                                id="contract_id"
                                v-model="form.contract_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">No contract</option>
                                <option
                                    v-for="contract in filteredContracts"
                                    :key="contract.id"
                                    :value="contract.id"
                                >
                                    {{ contract.contract_number }}
                                </option>
                            </select>
                            <div v-if="form.errors.contract_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.contract_id }}
                            </div>
                        </div>

                        <div>
                            <label for="invoice_number" class="block text-sm font-medium text-gray-700">
                                Invoice Number
                            </label>
                            <input
                                id="invoice_number"
                                v-model="form.invoice_number"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.invoice_number" class="mt-1 text-sm text-red-600">
                                {{ form.errors.invoice_number }}
                            </div>
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="type"
                                v-model="form.type"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="invoice">Invoice</option>
                                <option value="credit_note">Credit Note</option>
                                <option value="proforma">Proforma</option>
                            </select>
                            <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                                {{ form.errors.type }}
                            </div>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="paid">Paid</option>
                                <option value="partial">Partial</option>
                                <option value="overdue">Overdue</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                                {{ form.errors.status }}
                            </div>
                        </div>

                        <div>
                            <label for="currency" class="block text-sm font-medium text-gray-700">
                                Currency <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="currency"
                                v-model="form.currency"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="EUR">EUR (€)</option>
                                <option value="USD">USD ($)</option>
                                <option value="GBP">GBP (£)</option>
                            </select>
                            <div v-if="form.errors.currency" class="mt-1 text-sm text-red-600">
                                {{ form.errors.currency }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dates -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Dates</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="invoice_date" class="block text-sm font-medium text-gray-700">
                                Invoice Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="invoice_date"
                                v-model="form.invoice_date"
                                type="date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.invoice_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.invoice_date }}
                            </div>
                        </div>

                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">
                                Due Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="due_date"
                                v-model="form.due_date"
                                type="date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.due_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.due_date }}
                            </div>
                        </div>

                        <div>
                            <label for="period_start" class="block text-sm font-medium text-gray-700">
                                Period Start
                            </label>
                            <input
                                id="period_start"
                                v-model="form.period_start"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label for="period_end" class="block text-sm font-medium text-gray-700">
                                Period End
                            </label>
                            <input
                                id="period_end"
                                v-model="form.period_end"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <div v-if="form.status === 'paid'">
                            <label for="paid_at" class="block text-sm font-medium text-gray-700">
                                Paid At
                            </label>
                            <input
                                id="paid_at"
                                v-model="form.paid_at"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center border-b pb-2 mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Line Items</h3>
                        <button
                            type="button"
                            @click="addItem"
                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                        >
                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Item
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="grid grid-cols-12 gap-4 items-start p-4 bg-gray-50 rounded-lg"
                        >
                            <div class="col-span-12 sm:col-span-5">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Description <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="item.description"
                                    type="text"
                                    required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                />
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Quantity <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    @input="calculateItemTotal(index)"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                />
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Unit Price <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    @input="calculateItemTotal(index)"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                                />
                            </div>

                            <div class="col-span-3 sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Total</label>
                                <input
                                    :value="item.total"
                                    type="text"
                                    readonly
                                    class="block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm text-sm"
                                />
                            </div>

                            <div class="col-span-1 flex items-end">
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-2 text-red-600 hover:text-red-800"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Totals</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700">
                                Tax Rate (%) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="tax_rate"
                                v-model.number="form.tax_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">
                                Discount Amount ({{form.currency}})
                            </label>
                            <input
                                id="discount_amount"
                                v-model.number="form.discount_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                        </div>

                        <div class="sm:col-span-2 bg-gray-50 p-4 rounded-lg space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">{{ form.subtotal }} {{ form.currency }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax ({{ form.tax_rate }}%):</span>
                                <span class="font-medium">{{ form.tax_amount }} {{ form.currency }}</span>
                            </div>
                            <div v-if="form.discount_amount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-600">Discount:</span>
                                <span class="font-medium text-red-600">-{{ form.discount_amount }} {{ form.currency }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold border-t pt-2">
                                <span>Total:</span>
                                <span>{{ form.total }} {{ form.currency }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">
                        Additional Information
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            ></textarea>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="form.is_recurring"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">This is a recurring invoice</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4">
                    <Link
                        :href="route('tenant.invoices.index')"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                    >
                        <span v-if="form.processing">Updating...</span>
                        <span v-else>Update Invoice</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
