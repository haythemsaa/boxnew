<script setup>
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    mandate: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        active: 'bg-green-100 text-green-800',
        suspended: 'bg-orange-100 text-orange-800',
        cancelled: 'bg-red-100 text-red-800',
        expired: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const activate = () => {
    if (confirm('Are you sure you want to activate this mandate?')) {
        router.post(route('tenant.sepa-mandates.activate', props.mandate.id))
    }
}

const suspend = () => {
    if (confirm('Are you sure you want to suspend this mandate?')) {
        router.post(route('tenant.sepa-mandates.suspend', props.mandate.id))
    }
}

const reactivate = () => {
    if (confirm('Are you sure you want to reactivate this mandate?')) {
        router.post(route('tenant.sepa-mandates.reactivate', props.mandate.id))
    }
}

const cancel = () => {
    if (confirm('Are you sure you want to cancel this mandate? This action cannot be undone.')) {
        router.post(route('tenant.sepa-mandates.cancel', props.mandate.id))
    }
}
</script>

<template>
    <TenantLayout :title="`SEPA Mandate ${mandate.mandate_reference}`">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <Link
                            :href="route('tenant.sepa-mandates.index')"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ mandate.mandate_reference }}</h1>
                            <div class="mt-1 flex items-center space-x-3">
                                <span :class="getStatusColor(mandate.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                    {{ mandate.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a
                            :href="route('tenant.sepa-mandates.download', mandate.id)"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg class="h-5 w-5 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Bank Account Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Bank Account Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-1 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Account Holder</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ mandate.account_holder }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">IBAN</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ mandate.iban }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">BIC/SWIFT</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ mandate.bic }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Bank Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ mandate.bank_name || '-' }}</dd>
                                    </div>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Mandate Details -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Mandate Details</h2>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-2 gap-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Mandate Reference</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ mandate.mandate_reference }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ mandate.type || 'Recurrent' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Signed Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(mandate.signed_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Activated Date</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(mandate.activated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="mandate.notes" class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ mandate.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Customer</h2>
                        </div>
                        <div class="px-6 py-4">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-medium">
                                        {{ (mandate.customer?.first_name?.[0] || mandate.customer?.company_name?.[0] || '?').toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ getCustomerName(mandate.customer) }}</p>
                                    <p class="text-sm text-gray-500">{{ mandate.customer?.email }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <Link
                                    :href="route('tenant.customers.show', mandate.customer?.id)"
                                    class="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                >
                                    View Customer Profile
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Actions</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <button
                                v-if="mandate.status === 'pending'"
                                @click="activate"
                                class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Activate Mandate
                            </button>

                            <button
                                v-if="mandate.status === 'active'"
                                @click="suspend"
                                class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                            >
                                <svg class="h-5 w-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Suspend Mandate
                            </button>

                            <button
                                v-if="mandate.status === 'suspended'"
                                @click="reactivate"
                                class="w-full flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Reactivate Mandate
                            </button>

                            <button
                                v-if="mandate.status !== 'cancelled'"
                                @click="cancel"
                                class="w-full flex items-center justify-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50"
                            >
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Cancel Mandate
                            </button>
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
                                    <dd class="text-gray-900">{{ formatDate(mandate.created_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-gray-500">Last Updated</dt>
                                    <dd class="text-gray-900">{{ formatDate(mandate.updated_at) }}</dd>
                                </div>
                                <div v-if="mandate.cancelled_at">
                                    <dt class="text-gray-500">Cancelled</dt>
                                    <dd class="text-red-600">{{ formatDate(mandate.cancelled_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
