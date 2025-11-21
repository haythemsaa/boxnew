<template>
    <AuthenticatedLayout title="Create Customer">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    :href="route('tenant.customers.index')"
                    class="text-sm text-gray-600 hover:text-gray-900 mb-4 inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Customers
                </Link>
                <h2 class="text-2xl font-bold text-gray-900">Create New Customer</h2>
                <p class="mt-1 text-sm text-gray-600">Add a new customer to your database</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit">
                    <!-- Customer Type -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Customer Type</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input
                                        v-model="form.type"
                                        type="radio"
                                        value="individual"
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Individual</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        v-model="form.type"
                                        type="radio"
                                        value="company"
                                        class="w-4 h-4 text-primary-600 border-gray-300 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700">Company</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Fields -->
                    <div v-if="form.type === 'individual'" class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Personal Information</h3>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="civility" class="block text-sm font-medium text-gray-700 mb-1">
                                    Civility
                                </label>
                                <select
                                    id="civility"
                                    v-model="form.civility"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="">Select</option>
                                    <option value="mr">Mr.</option>
                                    <option value="mrs">Mrs.</option>
                                    <option value="ms">Ms.</option>
                                    <option value="dr">Dr.</option>
                                    <option value="prof">Prof.</option>
                                </select>
                            </div>

                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.first_name }"
                                />
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.first_name }}
                                </p>
                            </div>

                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.last_name }"
                                />
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.last_name }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Birth Date
                                </label>
                                <input
                                    id="birth_date"
                                    v-model="form.birth_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label for="birth_place" class="block text-sm font-medium text-gray-700 mb-1">
                                    Birth Place
                                </label>
                                <input
                                    id="birth_place"
                                    v-model="form.birth_place"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Company Fields -->
                    <div v-if="form.type === 'company'" class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Company Information</h3>

                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Company Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="company_name"
                                v-model="form.company_name"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.company_name }"
                            />
                            <p v-if="form.errors.company_name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.company_name }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="siret" class="block text-sm font-medium text-gray-700 mb-1">
                                    SIRET Number
                                </label>
                                <input
                                    id="siret"
                                    v-model="form.siret"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., 123 456 789 00010"
                                />
                            </div>

                            <div>
                                <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-1">
                                    VAT Number
                                </label>
                                <input
                                    id="vat_number"
                                    v-model="form.vat_number"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="e.g., FR12345678901"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Contact Information</h3>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.email }"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label for="mobile" class="block text-sm font-medium text-gray-700 mb-1">
                                    Mobile
                                </label>
                                <input
                                    id="mobile"
                                    v-model="form.mobile"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ID Information -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Identification (Optional)</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="id_type" class="block text-sm font-medium text-gray-700 mb-1">
                                    ID Type
                                </label>
                                <select
                                    id="id_type"
                                    v-model="form.id_type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="">Select</option>
                                    <option value="passport">Passport</option>
                                    <option value="id_card">ID Card</option>
                                    <option value="driver_license">Driver License</option>
                                    <option value="residence_permit">Residence Permit</option>
                                </select>
                            </div>

                            <div>
                                <label for="id_number" class="block text-sm font-medium text-gray-700 mb-1">
                                    ID Number
                                </label>
                                <input
                                    id="id_number"
                                    v-model="form.id_number"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="id_issue_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Issue Date
                                </label>
                                <input
                                    id="id_issue_date"
                                    v-model="form.id_issue_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label for="id_expiry_date" class="block text-sm font-medium text-gray-700 mb-1">
                                    Expiry Date
                                </label>
                                <input
                                    id="id_expiry_date"
                                    v-model="form.id_expiry_date"
                                    type="date"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.id_expiry_date }"
                                />
                                <p v-if="form.errors.id_expiry_date" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.id_expiry_date }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Primary Address -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Primary Address</h3>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                Street Address <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="address"
                                v-model="form.address"
                                type="text"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500': form.errors.address }"
                            />
                            <p v-if="form.errors.address" class="mt-1 text-sm text-red-600">
                                {{ form.errors.address }}
                            </p>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                                    City <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.city }"
                                />
                                <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.city }}
                                </p>
                            </div>

                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Postal Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="postal_code"
                                    v-model="form.postal_code"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.postal_code }"
                                />
                                <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.postal_code }}
                                </p>
                            </div>

                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                                    Country <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="country"
                                    v-model="form.country"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500': form.errors.country }"
                                />
                                <p v-if="form.errors.country" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.country }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Billing Address -->
                    <div class="space-y-6 mt-8">
                        <div class="flex items-center justify-between border-b pb-2">
                            <h3 class="text-lg font-medium text-gray-900">Billing Address</h3>
                            <label class="flex items-center text-sm">
                                <input
                                    v-model="sameAsPrimary"
                                    type="checkbox"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    @change="copyPrimaryAddress"
                                />
                                <span class="ml-2 text-gray-700">Same as primary</span>
                            </label>
                        </div>

                        <div v-if="!sameAsPrimary">
                            <div>
                                <label for="billing_address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Street Address
                                </label>
                                <input
                                    id="billing_address"
                                    v-model="form.billing_address"
                                    type="text"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>

                            <div class="grid grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label for="billing_city" class="block text-sm font-medium text-gray-700 mb-1">
                                        City
                                    </label>
                                    <input
                                        id="billing_city"
                                        v-model="form.billing_city"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label for="billing_postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                                        Postal Code
                                    </label>
                                    <input
                                        id="billing_postal_code"
                                        v-model="form.billing_postal_code"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    />
                                </div>

                                <div>
                                    <label for="billing_country" class="block text-sm font-medium text-gray-700 mb-1">
                                        Country
                                    </label>
                                    <input
                                        id="billing_country"
                                        v-model="form.billing_country"
                                        type="text"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="space-y-6 mt-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2">Additional Information</h3>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="credit_score" class="block text-sm font-medium text-gray-700 mb-1">
                                    Credit Score (0-1000)
                                </label>
                                <input
                                    id="credit_score"
                                    v-model="form.credit_score"
                                    type="number"
                                    min="0"
                                    max="1000"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Any additional notes about this customer"
                            ></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-8 flex items-center justify-end space-x-3 pt-6 border-t">
                        <Link
                            :href="route('tenant.customers.index')"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span v-if="!form.processing">Create Customer</span>
                            <span v-else class="flex items-center">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Creating...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const sameAsPrimary = ref(false)

const form = useForm({
    type: 'individual',
    civility: '',
    first_name: '',
    last_name: '',
    company_name: '',
    siret: '',
    vat_number: '',
    email: '',
    phone: '',
    mobile: '',
    birth_date: '',
    birth_place: '',
    id_type: '',
    id_number: '',
    id_issue_date: '',
    id_expiry_date: '',
    address: '',
    city: '',
    postal_code: '',
    country: '',
    billing_address: '',
    billing_city: '',
    billing_postal_code: '',
    billing_country: '',
    credit_score: null,
    notes: '',
    status: 'active',
})

const copyPrimaryAddress = () => {
    if (sameAsPrimary.value) {
        form.billing_address = form.address
        form.billing_city = form.city
        form.billing_postal_code = form.postal_code
        form.billing_country = form.country
    } else {
        form.billing_address = ''
        form.billing_city = ''
        form.billing_postal_code = ''
        form.billing_country = ''
    }
}

const submit = () => {
    form.post(route('tenant.customers.store'))
}
</script>
