<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-gray-900">Welcome back, {{ customer.first_name || customer.company_name }}</h1>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 uppercase">Active Contracts</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.active_contracts }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                    <p class="text-sm text-gray-600 uppercase">Outstanding</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ formatCurrency(stats.outstanding_balance) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <p class="text-sm text-gray-600 uppercase">Paid Invoices</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.paid_invoices }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                    <p class="text-sm text-gray-600 uppercase">Overdue</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.overdue_invoices }}</p>
                </div>
            </div>

            <!-- Recent Items -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-bold">Recent Contracts</h2>
                    </div>
                    <div class="divide-y">
                        <div v-for="contract in contracts.slice(0, 3)" :key="contract.id" class="px-6 py-4">
                            <p class="font-semibold">{{ contract.contract_number }}</p>
                            <p class="text-sm text-gray-600">Box {{ contract.box?.number }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-bold">Recent Invoices</h2>
                    </div>
                    <div class="divide-y">
                        <div v-for="invoice in invoices" :key="invoice.id" class="px-6 py-4">
                            <p class="font-semibold">{{ invoice.invoice_number }}</p>
                            <p class="text-sm text-gray-600">{{ formatDate(invoice.invoice_date) }} - {{ formatCurrency(invoice.total) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    customer: Object,
    contracts: Array,
    invoices: Array,
    stats: Object,
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}
</script>
