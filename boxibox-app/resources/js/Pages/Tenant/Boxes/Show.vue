<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    box: Object,
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

const getStatusColor = (status) => {
    const colors = {
        available: 'bg-green-100 text-green-800',
        occupied: 'bg-blue-100 text-blue-800',
        reserved: 'bg-yellow-100 text-yellow-800',
        maintenance: 'bg-orange-100 text-orange-800',
        unavailable: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getContractStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-100 text-gray-800',
        pending_signature: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        expired: 'bg-red-100 text-red-800',
        terminated: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <TenantLayout :title="`Box ${box.code}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.boxes.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Box {{ box.code }}</h1>
                            <div class="mt-1 flex items-center space-x-3">
                                <span :class="getStatusColor(box.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ box.status }}
                                </span>
                                <span class="text-gray-500 text-sm">{{ box.site?.name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('tenant.boxes.edit', box.id)"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Box Visual -->
                    <div class="bg-gradient-to-br from-primary-500 to-primary-600 shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-8 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-white/20 mb-4">
                                <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-white">{{ box.code }}</h2>
                            <p class="mt-2 text-primary-100">{{ box.size }} m² - {{ box.type || 'Standard' }}</p>
                            <p class="mt-4 text-3xl font-bold text-white">{{ formatCurrency(box.monthly_price) }}<span class="text-lg font-normal text-primary-100">/month</span></p>
                        </div>
                    </div>

                    <!-- Box Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Box Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Size</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ box.size }} m²</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ box.type || 'Standard' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Floor</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ box.floor?.name || 'Ground' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Monthly Price</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ formatCurrency(box.monthly_price) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Width</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ box.width || '-' }} m</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Depth</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ box.depth || '-' }} m</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Height</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ box.height || '-' }} m</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Access Code</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ box.access_code || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Features</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="flex items-center">
                                    <svg :class="box.is_climate_controlled ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Climate Controlled</span>
                                </div>
                                <div class="flex items-center">
                                    <svg :class="box.has_electricity ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Electricity</span>
                                </div>
                                <div class="flex items-center">
                                    <svg :class="box.is_ground_floor ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Ground Floor Access</span>
                                </div>
                                <div class="flex items-center">
                                    <svg :class="box.has_drive_up ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-sm text-gray-700">Drive-up Access</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Current Contract -->
                    <div v-if="box.current_contract" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Current Contract</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ box.current_contract.contract_number }}</p>
                                    <p class="text-sm text-gray-500">{{ getCustomerName(box.current_contract.customer) }}</p>
                                </div>
                                <div class="text-right">
                                    <span :class="getContractStatusColor(box.current_contract.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                        {{ box.current_contract.status.replace('_', ' ') }}
                                    </span>
                                    <p class="mt-1 text-sm text-gray-500">{{ formatDate(box.current_contract.start_date) }} - {{ formatDate(box.current_contract.end_date) }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.contracts.show', box.current_contract.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Contract
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Contract History -->
                    <div v-if="box.contracts && box.contracts.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Contract History</h2>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contract</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="contract in box.contracts" :key="contract.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('tenant.contracts.show', contract.id)" class="text-primary-600 hover:text-primary-900">
                                            {{ contract.contract_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ getCustomerName(contract.customer) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(contract.start_date) }} - {{ formatDate(contract.end_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getContractStatusColor(contract.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                            {{ contract.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Notes -->
                    <div v-if="box.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ box.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Site Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Site</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Name</dt>
                                    <dd class="text-gray-900 font-medium">{{ box.site?.name }}</dd>
                                </div>
                                <div v-if="box.site?.address">
                                    <dt class="text-gray-500">Address</dt>
                                    <dd class="text-gray-900">
                                        {{ box.site.address }}<br>
                                        {{ box.site.postal_code }} {{ box.site.city }}
                                    </dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.sites.show', box.site?.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Site
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Statistics</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-3">
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Total Contracts</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ box.contracts?.length || 0 }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Occupancy Rate</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ box.occupancy_rate || 0 }}%</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt class="text-sm text-gray-500">Revenue Generated</dt>
                                    <dd class="text-sm font-medium text-gray-900">{{ formatCurrency(box.total_revenue || 0) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Timeline</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2 text-sm">
                                <div>
                                    <dt class="text-gray-500">Created</dt>
                                    <dd class="text-gray-900">{{ formatDate(box.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-900">{{ formatDate(box.updated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Actions</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <Link
                                v-if="box.status === 'available'"
                                :href="route('tenant.contracts.create', { box_id: box.id })"
                                class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Contract
                            </Link>
                            <button class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Set Maintenance
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
