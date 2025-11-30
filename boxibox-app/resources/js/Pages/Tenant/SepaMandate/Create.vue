<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    customers: Array,
})

const form = useForm({
    customer_id: '',
    iban: '',
    bic: '',
    bank_name: '',
    account_holder: '',
    mandate_reference: '',
    signed_at: new Date().toISOString().split('T')[0],
    notes: '',
})

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const formatIban = (value) => {
    // Remove spaces and convert to uppercase
    const cleaned = value.replace(/\s/g, '').toUpperCase()
    // Add space every 4 characters
    return cleaned.replace(/(.{4})/g, '$1 ').trim()
}

const onIbanInput = (e) => {
    form.iban = formatIban(e.target.value)
}

const submit = () => {
    form.post(route('tenant.sepa-mandates.store'))
}
</script>

<template>
    <TenantLayout title="Create SEPA Mandate">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.sepa-mandates.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">New SEPA Mandate</h1>
                        <p class="mt-1 text-gray-500">Create a new SEPA Direct Debit mandate</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Customer Selection -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Customer</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Select Customer</label>
                            <select
                                v-model="form.customer_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            >
                                <option value="">Select a customer...</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ getCustomerName(customer) }} - {{ customer.email }}
                                </option>
                            </select>
                            <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Bank Details -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Bank Account Details</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Account Holder Name</label>
                            <input
                                v-model="form.account_holder"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Name as it appears on bank account"
                                required
                            />
                            <p v-if="form.errors.account_holder" class="mt-1 text-sm text-red-600">{{ form.errors.account_holder }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">IBAN</label>
                            <input
                                :value="form.iban"
                                @input="onIbanInput"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono"
                                placeholder="FR76 1234 5678 9012 3456 7890 123"
                                required
                            />
                            <p v-if="form.errors.iban" class="mt-1 text-sm text-red-600">{{ form.errors.iban }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">BIC/SWIFT</label>
                                <input
                                    v-model="form.bic"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono uppercase"
                                    placeholder="BNPAFRPP"
                                    required
                                />
                                <p v-if="form.errors.bic" class="mt-1 text-sm text-red-600">{{ form.errors.bic }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Bank Name</label>
                                <input
                                    v-model="form.bank_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="BNP Paribas"
                                />
                                <p v-if="form.errors.bank_name" class="mt-1 text-sm text-red-600">{{ form.errors.bank_name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mandate Details -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Mandate Details</h2>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Mandate Reference</label>
                                <input
                                    v-model="form.mandate_reference"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 font-mono"
                                    placeholder="Auto-generated if empty"
                                />
                                <p class="mt-1 text-xs text-gray-500">Leave empty to auto-generate</p>
                                <p v-if="form.errors.mandate_reference" class="mt-1 text-sm text-red-600">{{ form.errors.mandate_reference }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Signature Date</label>
                                <input
                                    v-model="form.signed_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.signed_at" class="mt-1 text-sm text-red-600">{{ form.errors.signed_at }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Optional internal notes..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">SEPA Direct Debit Information</h3>
                            <p class="mt-1 text-sm text-blue-700">
                                By creating this mandate, you confirm that the customer has authorized automatic debits from their bank account.
                                The mandate will be in 'pending' status until activated.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('tenant.sepa-mandates.index')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Create Mandate
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
