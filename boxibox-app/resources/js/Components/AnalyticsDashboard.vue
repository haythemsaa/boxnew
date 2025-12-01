<template>
    <div class="space-y-6">
        <!-- Revenue Overview -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Revenue Overview</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                    <p class="text-sm text-gray-600 uppercase">Total Revenue</p>
                    <p class="text-2xl font-bold text-blue-600 mt-2">{{ formatCurrency(metrics.totalRevenue) }}</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                    <p class="text-sm text-gray-600 uppercase">This Month</p>
                    <p class="text-2xl font-bold text-green-600 mt-2">{{ formatCurrency(metrics.monthlyRevenue) }}</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                    <p class="text-sm text-gray-600 uppercase">Avg Contract Value</p>
                    <p class="text-2xl font-bold text-purple-600 mt-2">{{ formatCurrency(metrics.avgContractValue) }}</p>
                </div>
                <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
                    <p class="text-sm text-gray-600 uppercase">Outstanding</p>
                    <p class="text-2xl font-bold text-orange-600 mt-2">{{ formatCurrency(metrics.outstanding) }}</p>
                </div>
            </div>
        </div>

        <!-- Contract & Invoice Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Contracts -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Contracts</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Active</span>
                        <span class="font-bold text-gray-900">{{ metrics.activeContracts }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Expiring Soon</span>
                        <span class="font-bold text-orange-600">{{ metrics.expiringContracts }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Terminated</span>
                        <span class="font-bold text-red-600">{{ metrics.terminatedContracts }}</span>
                    </div>
                    <div class="border-t pt-3 flex items-center justify-between">
                        <span class="text-gray-600 font-semibold">Total</span>
                        <span class="font-bold text-gray-900">{{ metrics.totalContracts }}</span>
                    </div>
                </div>
            </div>

            <!-- Invoices -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Invoices</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Paid</span>
                        <span class="font-bold text-green-600">{{ metrics.paidInvoices }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Pending</span>
                        <span class="font-bold text-blue-600">{{ metrics.pendingInvoices }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Overdue</span>
                        <span class="font-bold text-red-600">{{ metrics.overdueInvoices }}</span>
                    </div>
                    <div class="border-t pt-3 flex items-center justify-between">
                        <span class="text-gray-600 font-semibold">Total</span>
                        <span class="font-bold text-gray-900">{{ metrics.totalInvoices }}</span>
                    </div>
                </div>
            </div>

            <!-- Customers -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Customers</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Active</span>
                        <span class="font-bold text-gray-900">{{ metrics.activeCustomers }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">With Contracts</span>
                        <span class="font-bold text-gray-900">{{ metrics.customersWithContracts }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Overdue Balance</span>
                        <span class="font-bold text-red-600">{{ metrics.customersOverdue }}</span>
                    </div>
                    <div class="border-t pt-3 flex items-center justify-between">
                        <span class="text-gray-600 font-semibold">Total</span>
                        <span class="font-bold text-gray-900">{{ metrics.totalCustomers }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recent Transactions</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 px-3 font-semibold text-gray-900">Date</th>
                            <th class="text-left py-2 px-3 font-semibold text-gray-900">Type</th>
                            <th class="text-left py-2 px-3 font-semibold text-gray-900">Amount</th>
                            <th class="text-left py-2 px-3 font-semibold text-gray-900">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="transaction in metrics.recentTransactions" :key="transaction.id" class="border-b hover:bg-gray-50">
                            <td class="py-2 px-3 text-sm text-gray-600">{{ formatDate(transaction.date) }}</td>
                            <td class="py-2 px-3 text-sm font-medium text-gray-900">{{ transaction.type }}</td>
                            <td class="py-2 px-3 text-sm font-bold text-gray-900">{{ formatCurrency(transaction.amount) }}</td>
                            <td class="py-2 px-3">
                                <span :class="getStatusClass(transaction.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ transaction.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    metrics: Object,
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

const getStatusClass = (status) => {
    const classes = {
        paid: 'bg-green-100 text-green-800',
        pending: 'bg-blue-100 text-blue-800',
        overdue: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
