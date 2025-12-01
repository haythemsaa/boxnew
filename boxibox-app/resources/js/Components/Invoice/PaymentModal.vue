<template>
    <div v-if="isOpen" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">Record Payment</h3>
                <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <p class="text-sm text-gray-700">
                    <span class="font-semibold">Amount Due:</span> {{ formatCurrency(remainingAmount) }}
                </p>
            </div>

            <form @submit.prevent="submitPayment" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                    <input
                        v-model.number="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="remainingAmount"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select v-model="form.payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                        <option value="cash">Cash</option>
                        <option value="sepa">SEPA</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                    <input
                        v-model="form.payment_date"
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference (Optional)</label>
                    <input
                        v-model="form.reference"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Transaction ID or reference number"
                    />
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="submit" :disabled="isSubmitting" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        {{ isSubmitting ? 'Recording...' : 'Record Payment' }}
                    </button>
                    <button type="button" @click="closeModal" class="flex-1 px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    invoice: Object,
})

const emit = defineEmits(['close'])

const isOpen = ref(false)
const isSubmitting = ref(false)

const form = useForm({
    amount: props.invoice ? props.invoice.remaining_amount : 0,
    payment_method: 'bank_transfer',
    payment_date: new Date().toISOString().split('T')[0],
    reference: '',
})

const remainingAmount = props.invoice ? props.invoice.total - props.invoice.paid_amount : 0

const openModal = () => {
    isOpen.value = true
}

const closeModal = () => {
    isOpen.value = false
    emit('close')
}

const submitPayment = () => {
    isSubmitting.value = true
    form.post(route('tenant.invoices.record-payment', props.invoice.id), {
        onFinish: () => {
            isSubmitting.value = false
            closeModal()
        },
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

defineExpose({
    openModal,
    closeModal,
})
</script>
