<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contract: Object,
    sites: Array,
    customers: Array,
    boxes: Array,
})

const form = useForm({
    site_id: props.contract.site_id,
    customer_id: props.contract.customer_id,
    box_id: props.contract.box_id,
    contract_number: props.contract.contract_number,
    status: props.contract.status,
    type: props.contract.type,
    start_date: props.contract.start_date,
    end_date: props.contract.end_date,
    actual_end_date: props.contract.actual_end_date,
    notice_period_days: props.contract.notice_period_days,
    auto_renew: props.contract.auto_renew,
    renewal_period: props.contract.renewal_period,
    monthly_price: props.contract.monthly_price,
    deposit_amount: props.contract.deposit_amount,
    deposit_paid: props.contract.deposit_paid,
    discount_percentage: props.contract.discount_percentage,
    discount_amount: props.contract.discount_amount,
    billing_frequency: props.contract.billing_frequency,
    billing_day: props.contract.billing_day,
    payment_method: props.contract.payment_method,
    auto_pay: props.contract.auto_pay,
    access_code: props.contract.access_code,
    key_given: props.contract.key_given,
    key_returned: props.contract.key_returned,
    signed_by_customer: props.contract.signed_by_customer,
    customer_signed_at: props.contract.customer_signed_at,
    signed_by_staff: props.contract.signed_by_staff,
    staff_user_id: props.contract.staff_user_id,
    termination_reason: props.contract.termination_reason,
    termination_notes: props.contract.termination_notes,
})

const filteredBoxes = computed(() => {
    if (!form.site_id) return props.boxes
    return props.boxes.filter((box) => box.site_id == form.site_id)
})

const getCustomerName = (customer) => {
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getBoxLabel = (box) => {
    const parts = [box.code]
    if (box.site) parts.push(box.site.name)
    if (box.building) parts.push(box.building.name)
    if (box.floor) parts.push(`Floor ${box.floor.floor_number}`)
    if (box.status === 'occupied' && box.id !== props.contract.box_id) {
        parts.push('(Occupied)')
    }
    return parts.join(' - ')
}

const submit = () => {
    form.put(route('tenant.contracts.update', props.contract.id))
}
</script>

<template>
    <TenantLayout title="Edit Contract">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('tenant.contracts.index')"
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
                    Back to Contracts
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Edit Contract</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Update contract {{ contract.contract_number }}
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">
                        Basic Information
                    </h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="contract_number" class="block text-sm font-medium text-gray-700">
                                Contract Number
                            </label>
                            <input
                                id="contract_number"
                                v-model="form.contract_number"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.contract_number" class="mt-1 text-sm text-red-600">
                                {{ form.errors.contract_number }}
                            </div>
                        </div>

                        <div>
                            <label for="site_id" class="block text-sm font-medium text-gray-700">
                                Site <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="site_id"
                                v-model="form.site_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">Select a site</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.site_id }}
                            </div>
                        </div>

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

                        <div class="sm:col-span-2">
                            <label for="box_id" class="block text-sm font-medium text-gray-700">
                                Box <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="box_id"
                                v-model="form.box_id"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">Select a box</option>
                                <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                    {{ getBoxLabel(box) }} - €{{ box.base_price }}/month
                                </option>
                            </select>
                            <div v-if="form.errors.box_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.box_id }}
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
                                <option value="pending_signature">Pending Signature</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="terminated">Terminated</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <div v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                                {{ form.errors.status }}
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
                                <option value="standard">Standard</option>
                                <option value="short_term">Short Term</option>
                                <option value="long_term">Long Term</option>
                            </select>
                            <div v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                                {{ form.errors.type }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contract Period -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">
                        Contract Period
                    </h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">
                                Start Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="start_date"
                                v-model="form.start_date"
                                type="date"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.start_date }}
                            </div>
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">
                                End Date
                            </label>
                            <input
                                id="end_date"
                                v-model="form.end_date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.end_date }}
                            </div>
                        </div>

                        <div>
                            <label for="actual_end_date" class="block text-sm font-medium text-gray-700">
                                Actual End Date
                            </label>
                            <input
                                id="actual_end_date"
                                v-model="form.actual_end_date"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.actual_end_date" class="mt-1 text-sm text-red-600">
                                {{ form.errors.actual_end_date }}
                            </div>
                        </div>

                        <div>
                            <label
                                for="notice_period_days"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Notice Period (days) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="notice_period_days"
                                v-model.number="form.notice_period_days"
                                type="number"
                                min="0"
                                max="365"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.notice_period_days" class="mt-1 text-sm text-red-600">
                                {{ form.errors.notice_period_days }}
                            </div>
                        </div>

                        <div>
                            <label for="renewal_period" class="block text-sm font-medium text-gray-700">
                                Renewal Period <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="renewal_period"
                                v-model="form.renewal_period"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div v-if="form.errors.renewal_period" class="mt-1 text-sm text-red-600">
                                {{ form.errors.renewal_period }}
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center h-full">
                                <input
                                    v-model="form.auto_renew"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Auto-renew contract</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Pricing & Payment -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">
                        Pricing & Payment
                    </h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="monthly_price" class="block text-sm font-medium text-gray-700">
                                Monthly Price (€) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="monthly_price"
                                v-model.number="form.monthly_price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.monthly_price" class="mt-1 text-sm text-red-600">
                                {{ form.errors.monthly_price }}
                            </div>
                        </div>

                        <div>
                            <label for="deposit_amount" class="block text-sm font-medium text-gray-700">
                                Deposit Amount (€) <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="deposit_amount"
                                v-model.number="form.deposit_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.deposit_amount" class="mt-1 text-sm text-red-600">
                                {{ form.errors.deposit_amount }}
                            </div>
                        </div>

                        <div>
                            <label
                                for="discount_percentage"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Discount Percentage (%)
                            </label>
                            <input
                                id="discount_percentage"
                                v-model.number="form.discount_percentage"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.discount_percentage" class="mt-1 text-sm text-red-600">
                                {{ form.errors.discount_percentage }}
                            </div>
                        </div>

                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">
                                Discount Amount (€)
                            </label>
                            <input
                                id="discount_amount"
                                v-model.number="form.discount_amount"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.discount_amount" class="mt-1 text-sm text-red-600">
                                {{ form.errors.discount_amount }}
                            </div>
                        </div>

                        <div>
                            <label
                                for="billing_frequency"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Billing Frequency <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="billing_frequency"
                                v-model="form.billing_frequency"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div v-if="form.errors.billing_frequency" class="mt-1 text-sm text-red-600">
                                {{ form.errors.billing_frequency }}
                            </div>
                        </div>

                        <div>
                            <label for="billing_day" class="block text-sm font-medium text-gray-700">
                                Billing Day <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="billing_day"
                                v-model.number="form.billing_day"
                                type="number"
                                min="1"
                                max="31"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.billing_day" class="mt-1 text-sm text-red-600">
                                {{ form.errors.billing_day }}
                            </div>
                        </div>

                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="card">Card</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="sepa">SEPA</option>
                            </select>
                            <div v-if="form.errors.payment_method" class="mt-1 text-sm text-red-600">
                                {{ form.errors.payment_method }}
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center h-full">
                                <input
                                    v-model="form.deposit_paid"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Deposit paid</span>
                            </label>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="flex items-center">
                                <input
                                    v-model="form.auto_pay"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Auto-pay enabled</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Access & Keys -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Access & Keys</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label for="access_code" class="block text-sm font-medium text-gray-700">
                                Access Code
                            </label>
                            <input
                                id="access_code"
                                v-model="form.access_code"
                                type="text"
                                maxlength="10"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div v-if="form.errors.access_code" class="mt-1 text-sm text-red-600">
                                {{ form.errors.access_code }}
                            </div>
                        </div>

                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input
                                    v-model="form.key_given"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Key given</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="form.key_returned"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Key returned</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Signature -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Signature</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="form.signed_by_customer"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Signed by customer</span>
                            </label>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input
                                    v-model="form.signed_by_staff"
                                    type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Signed by staff</span>
                            </label>
                        </div>

                        <div v-if="form.signed_by_customer">
                            <label
                                for="customer_signed_at"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Customer Signed At
                            </label>
                            <input
                                id="customer_signed_at"
                                v-model="form.customer_signed_at"
                                type="date"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <div
                                v-if="form.errors.customer_signed_at"
                                class="mt-1 text-sm text-red-600"
                            >
                                {{ form.errors.customer_signed_at }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Termination -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-6">Termination</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label
                                for="termination_reason"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Termination Reason
                            </label>
                            <select
                                id="termination_reason"
                                v-model="form.termination_reason"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">No termination</option>
                                <option value="customer_request">Customer Request</option>
                                <option value="non_payment">Non-Payment</option>
                                <option value="breach">Breach of Contract</option>
                                <option value="end_of_term">End of Term</option>
                                <option value="other">Other</option>
                            </select>
                            <div v-if="form.errors.termination_reason" class="mt-1 text-sm text-red-600">
                                {{ form.errors.termination_reason }}
                            </div>
                        </div>

                        <div class="sm:col-span-2">
                            <label
                                for="termination_notes"
                                class="block text-sm font-medium text-gray-700"
                            >
                                Termination Notes
                            </label>
                            <textarea
                                id="termination_notes"
                                v-model="form.termination_notes"
                                rows="3"
                                maxlength="2000"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            ></textarea>
                            <div v-if="form.errors.termination_notes" class="mt-1 text-sm text-red-600">
                                {{ form.errors.termination_notes }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end space-x-4">
                    <Link
                        :href="route('tenant.contracts.index')"
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
                        <span v-else>Update Contract</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
