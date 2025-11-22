<template>
    <GuestLayout title="Complete Your Booking">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('booking.show', box.id)"
                    class="text-blue-600 hover:text-blue-800 mb-4 inline-block"
                >
                    ← Back to Details
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Complete Your Booking</h1>
                <p class="text-gray-600 mt-2">Just a few more details to secure your storage unit</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="submit" class="bg-white rounded-lg shadow-md p-8">
                        <!-- Customer Type -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Customer Type</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input
                                        v-model="form.customer_type"
                                        type="radio"
                                        value="individual"
                                        class="sr-only peer"
                                    />
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                        <p class="font-semibold text-gray-900">Individual</p>
                                        <p class="text-sm text-gray-600">Personal storage</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input
                                        v-model="form.customer_type"
                                        type="radio"
                                        value="company"
                                        class="sr-only peer"
                                    />
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                        <p class="font-semibold text-gray-900">Company</p>
                                        <p class="text-sm text-gray-600">Business storage</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Individual Fields -->
                        <div v-if="form.customer_type === 'individual'" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Personal Information</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input
                                        v-model="form.first_name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <p v-if="form.errors.first_name" class="text-sm text-red-600 mt-1">{{ form.errors.first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input
                                        v-model="form.last_name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <p v-if="form.errors.last_name" class="text-sm text-red-600 mt-1">{{ form.errors.last_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Company Fields -->
                        <div v-if="form.customer_type === 'company'" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Company Information</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <p v-if="form.errors.company_name" class="text-sm text-red-600 mt-1">{{ form.errors.company_name }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Registration Number</label>
                                        <input
                                            v-model="form.company_registration"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">VAT Number</label>
                                        <input
                                            v-model="form.vat_number"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Information</h2>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <p v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        <p v-if="form.errors.phone" class="text-sm text-red-600 mt-1">{{ form.errors.phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Address</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Street Address *</label>
                                    <input
                                        v-model="form.address"
                                        type="text"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    />
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                                        <input
                                            v-model="form.country"
                                            type="text"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Details -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Rental Details</h2>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Move-in Date *</label>
                                        <input
                                            v-model="form.start_date"
                                            type="date"
                                            :min="today"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Frequency *</label>
                                        <select
                                            v-model="form.billing_frequency"
                                            required
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="monthly">Monthly</option>
                                            <option value="quarterly">Quarterly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method *</label>
                                    <select
                                        v-model="form.payment_method"
                                        required
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="direct_debit">Direct Debit</option>
                                    </select>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        v-model="form.auto_renew"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label class="ml-2 text-sm text-gray-700">Automatically renew my contract</label>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-8">
                            <div class="flex items-start">
                                <input
                                    v-model="form.agree_terms"
                                    type="checkbox"
                                    required
                                    class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                />
                                <label class="ml-2 text-sm text-gray-700">
                                    I agree to the <a href="#" class="text-blue-600 hover:underline">Terms and Conditions</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a> *
                                </label>
                            </div>
                            <p v-if="form.errors.agree_terms" class="text-sm text-red-600 mt-1">{{ form.errors.agree_terms }}</p>
                        </div>

                        <!-- Submit -->
                        <div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ form.processing ? 'Processing...' : 'Complete Booking' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Booking Summary</h2>

                        <div class="mb-4">
                            <h3 class="font-medium text-gray-900">{{ box.name }}</h3>
                            <p class="text-sm text-gray-600">{{ box.site.name }}</p>
                            <p class="text-sm text-gray-500">{{ box.site.city }}</p>
                        </div>

                        <div class="border-t border-b py-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Monthly Price:</span>
                                <span class="font-medium">€{{ pricing.monthly_price.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Security Deposit:</span>
                                <span class="font-medium">€{{ pricing.deposit_amount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">First Month Rent:</span>
                                <span class="font-medium">€{{ pricing.first_month_rent.toFixed(2) }}</span>
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Total Due Today:</span>
                                <span class="text-2xl font-bold text-blue-600">€{{ pricing.total_due.toFixed(2) }}</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-2 text-sm text-gray-600">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Secure online booking</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Instant confirmation</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>No hidden fees</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    box: Object,
    pricing: Object,
});

const today = computed(() => {
    const date = new Date();
    return date.toISOString().split('T')[0];
});

const form = useForm({
    customer_type: 'individual',
    first_name: '',
    last_name: '',
    company_name: '',
    company_registration: '',
    vat_number: '',
    email: '',
    phone: '',
    mobile: '',
    address: '',
    city: '',
    postal_code: '',
    country: '',
    start_date: today.value,
    billing_frequency: 'monthly',
    payment_method: 'bank_transfer',
    auto_renew: false,
    agree_terms: false,
});

const submit = () => {
    form.post(route('booking.store', props.box.id));
};
</script>
