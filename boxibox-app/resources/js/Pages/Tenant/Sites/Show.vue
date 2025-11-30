<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    site: Object,
    stats: Object,
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

const getBoxStatusColor = (status) => {
    const colors = {
        available: 'bg-green-100 text-green-800',
        occupied: 'bg-blue-100 text-blue-800',
        reserved: 'bg-yellow-100 text-yellow-800',
        maintenance: 'bg-orange-100 text-orange-800',
        unavailable: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const occupancyRate = ((props.stats?.occupied_boxes || 0) / (props.stats?.total_boxes || 1) * 100).toFixed(1)
</script>

<template>
    <TenantLayout :title="site.name">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.sites.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ site.name }}</h1>
                            <p class="mt-1 text-gray-500">{{ site.code }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <Link
                            :href="route('tenant.boxes.plan', site.id)"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                            </svg>
                            View Plan
                        </Link>
                        <Link
                            :href="route('tenant.sites.edit', site.id)"
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

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Boxes</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ stats?.total_boxes || 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Available</dt>
                                    <dd class="text-lg font-semibold text-green-600">{{ stats?.available_boxes || 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Occupied</dt>
                                    <dd class="text-lg font-semibold text-blue-600">{{ stats?.occupied_boxes || 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Occupancy Rate</dt>
                                    <dd class="text-lg font-semibold text-gray-900">{{ occupancyRate }}%</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Site Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Site Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Code</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ site.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Total Area</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ site.total_area || '-' }} m²</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ site.address }}<br>
                                        {{ site.postal_code }} {{ site.city }}<br>
                                        {{ site.country }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ site.phone || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ site.email || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Opening Hours -->
                    <div v-if="site.opening_hours" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Opening Hours</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Monday - Friday</dt>
                                    <dd class="text-gray-900">{{ site.opening_hours?.weekdays || '07:00 - 21:00' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Saturday</dt>
                                    <dd class="text-gray-900">{{ site.opening_hours?.saturday || '08:00 - 18:00' }}</dd>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <dt class="text-gray-500">Sunday</dt>
                                    <dd class="text-gray-900">{{ site.opening_hours?.sunday || 'Closed' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Boxes List -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-lg font-medium text-gray-900">Boxes</h2>
                            <Link
                                :href="route('tenant.boxes.create', { site_id: site.id })"
                                class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                            >
                                Add Box
                            </Link>
                        </div>
                        <div v-if="site.boxes && site.boxes.length > 0">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Size</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="box in site.boxes.slice(0, 10)" :key="box.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <Link :href="route('tenant.boxes.show', box.id)" class="text-primary-600 hover:text-primary-900 font-medium">
                                                {{ box.code }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ box.size }} m²</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ formatCurrency(box.monthly_price) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="getBoxStatusColor(box.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                                {{ box.status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="site.boxes.length > 10" class="px-6 py-4 border-t border-gray-200 text-center">
                                <Link
                                    :href="route('tenant.boxes.index', { site_id: site.id })"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View all {{ site.boxes.length }} boxes
                                </Link>
                            </div>
                        </div>
                        <div v-else class="px-6 py-8 text-center text-gray-500">
                            No boxes found for this site.
                            <Link :href="route('tenant.boxes.create', { site_id: site.id })" class="text-primary-600 hover:text-primary-700 ml-1">
                                Add the first box
                            </Link>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="site.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ site.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Revenue -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 shadow-lg rounded-lg overflow-hidden">
                        <div class="px-6 py-6 text-center">
                            <p class="text-green-100 text-sm font-medium uppercase tracking-wide">Monthly Revenue</p>
                            <p class="mt-2 text-3xl font-bold text-white">{{ formatCurrency(stats?.monthly_revenue || 0) }}</p>
                        </div>
                    </div>

                    <!-- Occupancy Chart -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Occupancy</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="relative pt-1">
                                <div class="flex mb-2 items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-primary-600 bg-primary-200">
                                            {{ occupancyRate }}%
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold inline-block text-primary-600">
                                            {{ stats?.occupied_boxes || 0 }}/{{ stats?.total_boxes || 0 }}
                                        </span>
                                    </div>
                                </div>
                                <div class="overflow-hidden h-2 text-xs flex rounded bg-primary-200">
                                    <div :style="{ width: occupancyRate + '%' }" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500"></div>
                                </div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                                <div>
                                    <p class="text-2xl font-bold text-green-600">{{ stats?.available_boxes || 0 }}</p>
                                    <p class="text-xs text-gray-500">Available</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-blue-600">{{ stats?.occupied_boxes || 0 }}</p>
                                    <p class="text-xs text-gray-500">Occupied</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Features</h2>
                        </div>
                        <div class="px-6 py-4">
                            <ul class="space-y-2">
                                <li class="flex items-center text-sm">
                                    <svg :class="site.has_security ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    24/7 Security
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg :class="site.has_cctv ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    CCTV Monitoring
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg :class="site.has_climate_control ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Climate Control
                                </li>
                                <li class="flex items-center text-sm">
                                    <svg :class="site.has_loading_dock ? 'text-green-500' : 'text-gray-300'" class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    Loading Dock
                                </li>
                            </ul>
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
                                    <dd class="text-gray-900">{{ formatDate(site.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-900">{{ formatDate(site.updated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
