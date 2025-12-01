<template>
    <TenantLayout title="Renewal Options">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('tenant.contracts.show', contract.id)"
                    class="text-sm text-primary-600 hover:text-primary-900 mb-4 inline-flex items-center"
                >
                    ← Return to contract
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Renewal Options</h1>
                <p class="mt-2 text-gray-600">Contract #{{ contract.contract_number }}</p>
            </div>

            <!-- Contract Summary -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Current Contract Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border-l-4 border-blue-500 pl-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Box</p>
                        <p class="text-lg font-bold text-gray-900">{{ contract.box?.number }}</p>
                    </div>
                    <div class="border-l-4 border-purple-500 pl-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Customer</p>
                        <p class="text-lg font-bold text-gray-900">{{ getCustomerName(contract.customer) }}</p>
                    </div>
                    <div class="border-l-4 border-green-500 pl-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Current End Date</p>
                        <p class="text-lg font-bold text-gray-900">{{ formatDate(contract.end_date) }}</p>
                    </div>
                    <div class="border-l-4 border-amber-500 pl-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase">Monthly Price</p>
                        <p class="text-lg font-bold text-gray-900">{{ contract.monthly_price }}€/month</p>
                    </div>
                </div>
            </div>

            <!-- Renewal Options -->
            <div class="bg-white shadow-lg rounded-lg p-8 mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Select Renewal Period</h2>
                <div class="space-y-4">
                    <div
                        v-for="option in renewalOptions"
                        :key="option.months"
                        @click="selectOption(option.months)"
                        :class="[
                            'p-6 border-2 rounded-lg cursor-pointer transition-all',
                            selectedMonths === option.months
                                ? 'border-blue-500 bg-blue-50'
                                : 'border-gray-200 bg-white hover:border-gray-300',
                        ]"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-lg font-bold text-gray-900">{{ option.label }}</p>
                                <p class="text-sm text-gray-600">
                                    New end date: {{ formatDate(calculateNewEndDate(option.months)) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-gray-900">{{ option.months }} months</p>
                                <div
                                    :class="[
                                        'inline-block w-6 h-6 rounded-full mt-2 border-2',
                                        selectedMonths === option.months
                                            ? 'bg-blue-500 border-blue-500'
                                            : 'border-gray-300',
                                    ]"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-bold text-blue-900 mb-2">Important Information</h3>
                <ul class="text-sm text-blue-800 space-y-2">
                    <li>• The renewal will extend your contract from the current end date</li>
                    <li>• Monthly price remains the same unless otherwise agreed</li>
                    <li>• Renewal terms are subject to agreement</li>
                    <li>• No additional deposit required for renewal</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-4">
                <button
                    @click="submitRenewal"
                    :disabled="selectedMonths === null || isSubmitting"
                    :class="[
                        'flex-1 px-6 py-3 rounded-lg font-bold text-white transition-colors',
                        selectedMonths === null || isSubmitting
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-green-600 hover:bg-green-700',
                    ]"
                >
                    <span v-if="isSubmitting">Renewing...</span>
                    <span v-else>Confirm Renewal</span>
                </button>
                <Link
                    :href="route('tenant.contracts.show', contract.id)"
                    class="flex-1 px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-bold text-center"
                >
                    Cancel
                </Link>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contract: Object,
})

const selectedMonths = ref(null)
const renewalOptions = [
    { months: 1, label: '1 Month' },
    { months: 3, label: '3 Months (Quarter)' },
    { months: 6, label: '6 Months' },
    { months: 12, label: '12 Months (1 Year)' },
]

const renewalForm = useForm({
    months: null,
})

const isSubmitting = ref(false)

const selectOption = (months) => {
    selectedMonths.value = months
    renewalForm.months = months
}

const calculateNewEndDate = (months) => {
    if (!props.contract.end_date || !months) return null
    const endDate = new Date(props.contract.end_date)
    endDate.setMonth(endDate.getMonth() + months)
    return endDate
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const submitRenewal = () => {
    if (selectedMonths.value === null) return

    isSubmitting.value = true
    renewalForm.post(route('tenant.contracts.renew', props.contract.id), {
        onFinish: () => {
            isSubmitting.value = false
        },
    })
}
</script>

<style scoped></style>
