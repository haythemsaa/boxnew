<template>
    <AuthenticatedLayout title="Dashboard">
        <!-- Stats Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
            <!-- Total Sites -->
            <StatsCard
                title="Total Sites"
                :value="stats.total_sites"
                icon="map"
                color="blue"
            />

            <!-- Total Boxes -->
            <StatsCard
                title="Total Boxes"
                :value="stats.total_boxes"
                icon="box"
                color="purple"
            />

            <!-- Available Boxes -->
            <StatsCard
                title="Available Boxes"
                :value="stats.available_boxes"
                icon="check"
                color="green"
            />

            <!-- Occupied Boxes -->
            <StatsCard
                title="Occupied Boxes"
                :value="stats.occupied_boxes"
                icon="lock"
                color="orange"
            />

            <!-- Total Customers -->
            <StatsCard
                title="Total Customers"
                :value="stats.total_customers"
                icon="users"
                color="indigo"
            />

            <!-- Active Contracts -->
            <StatsCard
                title="Active Contracts"
                :value="stats.active_contracts"
                icon="document"
                color="teal"
            />

            <!-- Monthly Revenue -->
            <StatsCard
                title="Monthly Revenue"
                :value="formatCurrency(stats.monthly_revenue)"
                icon="currency"
                color="emerald"
            />

            <!-- Occupation Rate -->
            <StatsCard
                title="Occupation Rate"
                :value="stats.occupation_rate + '%'"
                icon="chart"
                color="pink"
            />
        </div>

        <!-- Content Grid -->
        <div class="grid gap-6 mb-8 md:grid-cols-2">
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
    recentContracts: Array,
    expiringContracts: Array,
    overdueInvoices: Array,
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
