<script setup>
import { ref, computed } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    invoices: Array,
})

const form = useForm({
    invoice_id: '',
    level: 1,
    type: 'email',
    scheduled_at: new Date().toISOString().split('T')[0],
    notes: '',
})

const selectedInvoice = computed(() => {
    return props.invoices?.find(i => i.id == form.invoice_id)
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

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getDaysOverdue = (dueDate) => {
    const due = new Date(dueDate)
    const today = new Date()
    const diffTime = today - due
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays > 0 ? diffDays : 0
}

const submit = () => {
    form.post(route('tenant.reminders.store'))
}

const levelDescriptions = {
    1: 'Rappel amical - Premier rappel courtois',
    2: 'Relance ferme - Deuxième rappel avec ton plus direct',
    3: 'Mise en demeure - Avant action contentieuse',
    4: 'Dernier avis - Ultime rappel avant procédure',
}
</script>

<template>
    <TenantLayout title="Create Reminder">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.reminders.index')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">New Payment Reminder</h1>
                        <p class="mt-1 text-gray-500">Create a payment reminder for an overdue invoice</p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Invoice Selection -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Select Invoice</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Overdue Invoice</label>
                                <select
                                    v-model="form.invoice_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                >
                                    <option value="">Select an invoice...</option>
                                    <option v-for="invoice in invoices" :key="invoice.id" :value="invoice.id">
                                        {{ invoice.invoice_number }} - {{ getCustomerName(invoice.customer) }} - {{ formatCurrency(invoice.total - invoice.paid_amount) }} ({{ getDaysOverdue(invoice.due_date) }} days overdue)
                                    </option>
                                </select>
                                <p v-if="form.errors.invoice_id" class="mt-1 text-sm text-red-600">{{ form.errors.invoice_id }}</p>
                            </div>
                        </div>

                        <!-- Selected Invoice Preview -->
                        <div v-if="selectedInvoice" class="mt-4 p-4 bg-gray-50 rounded-lg">
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500">Invoice</p>
                                    <p class="text-sm font-medium text-gray-900">{{ selectedInvoice.invoice_number }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Customer</p>
                                    <p class="text-sm font-medium text-gray-900">{{ getCustomerName(selectedInvoice.customer) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Amount Due</p>
                                    <p class="text-sm font-medium text-red-600">{{ formatCurrency(selectedInvoice.total - selectedInvoice.paid_amount) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Days Overdue</p>
                                    <p class="text-sm font-medium text-red-600">{{ getDaysOverdue(selectedInvoice.due_date) }} days</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reminder Settings -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Reminder Settings</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Level -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reminder Level</label>
                                <select
                                    v-model="form.level"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option :value="1">Level 1 - Friendly reminder</option>
                                    <option :value="2">Level 2 - Firm reminder</option>
                                    <option :value="3">Level 3 - Formal notice</option>
                                    <option :value="4">Level 4 - Final notice</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ levelDescriptions[form.level] }}</p>
                                <p v-if="form.errors.level" class="mt-1 text-sm text-red-600">{{ form.errors.level }}</p>
                            </div>

                            <!-- Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Delivery Method</label>
                                <select
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                    <option value="letter">Letter (Print)</option>
                                </select>
                                <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                            </div>

                            <!-- Scheduled Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Scheduled Date</label>
                                <input
                                    v-model="form.scheduled_at"
                                    type="date"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p class="mt-1 text-xs text-gray-500">Leave empty to send immediately</p>
                                <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700">Internal Notes</label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Add any internal notes about this reminder..."
                            ></textarea>
                            <p v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Level Preview -->
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900">Level Preview</h2>
                    </div>
                    <div class="px-6 py-4">
                        <div class="flex space-x-4">
                            <div
                                v-for="level in [1, 2, 3, 4]"
                                :key="level"
                                :class="[
                                    'flex-1 p-4 rounded-lg border-2 cursor-pointer transition-all',
                                    form.level === level
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                                @click="form.level = level"
                            >
                                <div class="text-center">
                                    <div :class="[
                                        'inline-flex items-center justify-center w-10 h-10 rounded-full mb-2',
                                        level === 1 ? 'bg-blue-100 text-blue-600' :
                                        level === 2 ? 'bg-yellow-100 text-yellow-600' :
                                        level === 3 ? 'bg-orange-100 text-orange-600' :
                                        'bg-red-100 text-red-600'
                                    ]">
                                        <span class="font-bold">{{ level }}</span>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ level === 1 ? 'Friendly' : level === 2 ? 'Firm' : level === 3 ? 'Formal' : 'Final' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('tenant.reminders.index')"
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
                        Create Reminder
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
