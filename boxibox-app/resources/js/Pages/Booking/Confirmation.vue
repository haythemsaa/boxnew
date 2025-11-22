<template>
    <GuestLayout title="Booking Confirmed!">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Success Message -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Booking Confirmed!</h1>
                <p class="text-lg text-gray-600">Your storage unit has been successfully reserved</p>
            </div>

            <!-- Contract Details -->
            <div class="bg-white rounded-lg shadow-md p-8 mb-8">
                <h2 class="text-2xl font-semibold text-gray-900 mb-6">Booking Details</h2>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Contract Number</p>
                        <p class="text-lg font-semibold text-gray-900">{{ contract.contract_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <span class="inline-flex px-3 py-1 bg-green-100 text-green-800 text-sm font-semibold rounded-full">
                            {{ contract.status }}
                        </span>
                    </div>
                </div>

                <div class="border-t border-b py-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Storage Unit</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Unit Name</p>
                            <p class="font-medium text-gray-900">{{ contract.box.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Location</p>
                            <p class="font-medium text-gray-900">{{ contract.box.site.name }}</p>
                            <p class="text-sm text-gray-500">{{ contract.box.site.city }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Dimensions</p>
                            <p class="font-medium text-gray-900">
                                {{ contract.box.length }} x {{ contract.box.width }} x {{ contract.box.height }} m
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Monthly Price</p>
                            <p class="text-xl font-bold text-blue-600">€{{ contract.monthly_price }}</p>
                        </div>
                    </div>
                </div>

                <div class="border-b pb-6 mb-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Rental Period</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Start Date</p>
                            <p class="font-medium text-gray-900">{{ formatDate(contract.start_date) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Billing Frequency</p>
                            <p class="font-medium text-gray-900 capitalize">{{ contract.billing_frequency }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="font-semibold text-gray-900 mb-4">Customer Information</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Name</p>
                            <p class="font-medium text-gray-900">{{ customerName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Email</p>
                            <p class="font-medium text-gray-900">{{ contract.customer.email }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Phone</p>
                            <p class="font-medium text-gray-900">{{ contract.customer.phone }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Payment Method</p>
                            <p class="font-medium text-gray-900 capitalize">{{ formatPaymentMethod(contract.payment_method) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-blue-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">What Happens Next?</h3>
                <div class="space-y-3">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                1
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Check Your Email</p>
                            <p class="text-sm text-gray-600">
                                We've sent a confirmation email to {{ contract.customer.email }} with your contract details
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                2
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Complete Payment</p>
                            <p class="text-sm text-gray-600">
                                Please transfer €{{ (contract.monthly_price * 2).toFixed(2) }} (deposit + first month) to the account details in your email
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full font-semibold text-sm">
                                3
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="font-medium text-gray-900">Move In!</p>
                            <p class="text-sm text-gray-600">
                                Once payment is confirmed, you'll receive your access code and can move in on {{ formatDate(contract.start_date) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Need Help?</h3>
                <p class="text-gray-600 mb-4">If you have any questions about your booking, please contact us:</p>
                <div class="space-y-2">
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ contract.box.site.email || 'info@boxibox.com' }}
                    </div>
                    <div class="flex items-center text-gray-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ contract.box.site.phone || '+33 1 23 45 67 89' }}
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <Link
                    :href="route('booking.index')"
                    class="px-6 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 text-center"
                >
                    Browse More Units
                </Link>
                <button
                    @click="printPage"
                    class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700"
                >
                    Print Confirmation
                </button>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

const props = defineProps({
    contract: Object,
});

const customerName = computed(() => {
    if (props.contract.customer.type === 'company') {
        return props.contract.customer.company_name;
    }
    return `${props.contract.customer.first_name} ${props.contract.customer.last_name}`;
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-EU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatPaymentMethod = (method) => {
    const map = {
        bank_transfer: 'Bank Transfer',
        credit_card: 'Credit Card',
        direct_debit: 'Direct Debit',
    };
    return map[method] || method;
};

const printPage = () => {
    window.print();
};
</script>
