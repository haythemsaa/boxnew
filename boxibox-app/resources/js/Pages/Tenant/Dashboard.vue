<template>
    <AuthenticatedLayout title="Dashboard">
        <!-- Primary Stats Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Total Sites -->
            <StatsCard
                title="Total Sites"
                :value="stats.total_sites"
                :subtitle="`${stats.active_sites} active`"
                icon="map"
                color="blue"
            />

            <!-- Total Boxes -->
            <StatsCard
                title="Total Boxes"
                :value="stats.total_boxes"
                :subtitle="`${stats.available_boxes} available`"
                icon="box"
                color="purple"
            />

            <!-- Total Customers -->
            <StatsCard
                title="Total Customers"
                :value="stats.total_customers"
                :subtitle="`${stats.active_customers} active`"
                icon="users"
                color="indigo"
            />

            <!-- Active Contracts -->
            <StatsCard
                title="Active Contracts"
                :value="stats.active_contracts"
                :subtitle="`${stats.expiring_soon} expiring soon`"
                icon="document"
                color="teal"
            />
        </div>

        <!-- Financial Stats Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Monthly Revenue -->
            <StatsCard
                title="Monthly Revenue"
                :value="formatCurrency(stats.monthly_revenue)"
                icon="currency"
                color="emerald"
            />

            <!-- Annual Projection -->
            <StatsCard
                title="Annual Projection"
                :value="formatCurrency(stats.annual_revenue_projection)"
                icon="chart"
                color="green"
            />

            <!-- Total Collected -->
            <StatsCard
                title="Total Collected"
                :value="formatCurrency(stats.total_collected)"
                :subtitle="`${stats.total_payments} payments`"
                icon="check"
                color="blue"
            />

            <!-- Occupation Rate -->
            <StatsCard
                title="Occupation Rate"
                :value="stats.occupation_rate + '%'"
                :subtitle="`${stats.occupied_boxes}/${stats.total_boxes} occupied`"
                icon="chart"
                color="pink"
            />
        </div>

        <!-- Invoice & Payment Stats -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Pending Invoices -->
            <StatsCard
                title="Pending Invoices"
                :value="stats.pending_invoices"
                icon="document"
                color="yellow"
            />

            <!-- Overdue Invoices -->
            <StatsCard
                title="Overdue Invoices"
                :value="stats.overdue_invoices"
                icon="document"
                color="red"
            />

            <!-- Paid Invoices -->
            <StatsCard
                title="Paid Invoices"
                :value="stats.paid_invoices"
                icon="check"
                color="green"
            />

            <!-- Pending Payments -->
            <StatsCard
                title="Pending Payments"
                :value="stats.pending_payments"
                icon="clock"
                color="orange"
            />
        </div>

        <!-- Revenue Trend -->
        <div v-if="revenueTrend.length > 0" class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Revenue Trend (Last 6 Months)</h3>
            <div class="space-y-3">
                <div
                    v-for="(trend, index) in revenueTrend"
                    :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                    <span class="text-sm font-medium text-gray-700">{{ trend.month }}</span>
                    <span class="text-sm font-semibold text-emerald-600">
                        {{ formatCurrency(trend.revenue) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 lg:grid-cols-3">
            <!-- Recent Contracts -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Contracts</h3>
                <div v-if="recentContracts.length > 0" class="space-y-3">
                    <div
                        v-for="contract in recentContracts"
                        :key="contract.id"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ contract.customer_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ contract.box_name }} - {{ contract.contract_number }}
                            </p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ formatCurrency(contract.monthly_price) }}
                            </p>
                            <span
                                :class="getStatusClass(contract.status)"
                                class="text-xs px-2 py-1 rounded-full"
                            >
                                {{ contract.status }}
                            </span>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">No recent contracts</p>
            </div>

            <!-- Expiring Contracts -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    Expiring Contracts
                    <span v-if="expiringContracts.length > 0" class="text-sm font-normal text-gray-500">
                        ({{ expiringContracts.length }})
                    </span>
                </h3>
                <div v-if="expiringContracts.length > 0" class="space-y-3">
                    <div
                        v-for="contract in expiringContracts"
                        :key="contract.id"
                        class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ contract.customer_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ contract.box_name }} - {{ contract.contract_number }}
                            </p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm font-semibold text-orange-600">
                                {{ contract.days_until_expiry }} days
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ contract.end_date }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">No contracts expiring soon</p>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Payments</h3>
                <div v-if="recentPayments.length > 0" class="space-y-3">
                    <div
                        v-for="payment in recentPayments"
                        :key="payment.id"
                        class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors"
                    >
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ payment.customer_name }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ payment.payment_number }} - {{ payment.method }}
                            </p>
                        </div>
                        <div class="text-right ml-4">
                            <p class="text-sm font-semibold text-emerald-600">
                                {{ formatCurrency(payment.amount) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ payment.paid_at }}
                            </p>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">No recent payments</p>
            </div>
        </div>

        <!-- Overdue Invoices -->
        <div v-if="overdueInvoices.length > 0" class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-red-900 mb-4">
                Overdue Invoices
                <span class="text-sm font-normal text-gray-500">({{ overdueInvoices.length }})</span>
            </h3>
            <div class="space-y-3">
                <div
                    v-for="invoice in overdueInvoices"
                    :key="invoice.id"
                    class="flex items-center justify-between p-3 bg-red-50 rounded-lg hover:bg-red-100 transition-colors"
                >
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ invoice.customer_name }}
                        </p>
                        <p class="text-xs text-gray-500">
                            Invoice: {{ invoice.invoice_number }}
                        </p>
                    </div>
                    <div class="text-right ml-4">
                        <p class="text-sm font-semibold text-red-600">
                            {{ formatCurrency(invoice.total) }}
                        </p>
                        <p class="text-xs text-red-500">
                            {{ invoice.days_overdue }} days overdue
                        </p>
                        <p class="text-xs text-gray-500">
                            Due: {{ invoice.due_date }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'

defineProps({
    stats: Object,
    revenueTrend: Array,
    recentContracts: Array,
    expiringContracts: Array,
    overdueInvoices: Array,
    recentPayments: Array,
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value)
}

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800',
        pending_signature: 'bg-yellow-100 text-yellow-800',
        expired: 'bg-gray-100 text-gray-800',
        terminated: 'bg-red-100 text-red-800',
        draft: 'bg-blue-100 text-blue-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>
