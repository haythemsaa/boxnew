<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    CreditCardIcon,
    UserIcon,
    BuildingLibraryIcon,
    CalendarDaysIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    PauseCircleIcon,
    PlayCircleIcon,
    XCircleIcon,
    ArrowDownTrayIcon,
    PencilSquareIcon,
    ClockIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    mandate: Object,
})

const showCancelModal = ref(false)

const statusConfig = {
    pending: { label: 'En attente', color: 'bg-amber-100 text-amber-700 border-amber-200', icon: ClockIcon },
    active: { label: 'Actif', color: 'bg-emerald-100 text-emerald-700 border-emerald-200', icon: CheckCircleIcon },
    suspended: { label: 'Suspendu', color: 'bg-orange-100 text-orange-700 border-orange-200', icon: PauseCircleIcon },
    cancelled: { label: 'Annulé', color: 'bg-red-100 text-red-700 border-red-200', icon: XCircleIcon },
    expired: { label: 'Expiré', color: 'bg-gray-100 text-gray-600 border-gray-200', icon: ClockIcon },
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const activate = () => {
    if (confirm('Voulez-vous vraiment activer ce mandat ?')) {
        router.post(route('tenant.sepa-mandates.activate', props.mandate.id))
    }
}

const suspend = () => {
    if (confirm('Voulez-vous vraiment suspendre ce mandat ?')) {
        router.post(route('tenant.sepa-mandates.suspend', props.mandate.id))
    }
}

const reactivate = () => {
    if (confirm('Voulez-vous vraiment réactiver ce mandat ?')) {
        router.post(route('tenant.sepa-mandates.reactivate', props.mandate.id))
    }
}

const cancel = () => {
    showCancelModal.value = true
}

const confirmCancel = () => {
    router.post(route('tenant.sepa-mandates.cancel', props.mandate.id))
    showCancelModal.value = false
}
</script>

<template>
    <TenantLayout :title="`Mandat SEPA ${mandate.mandate_reference}`">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Back Button -->
                <Link
                    :href="route('tenant.sepa-mandates.index')"
                    class="inline-flex items-center text-blue-100 hover:text-white transition-colors mb-6"
                >
                    <ArrowLeftIcon class="w-5 h-5 mr-2" />
                    Retour aux mandats SEPA
                </Link>

                <!-- Header Content -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start space-x-5">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <CreditCardIcon class="w-10 h-10 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <h1 class="text-3xl font-bold text-white font-mono">{{ mandate.mandate_reference }}</h1>
                                <span :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold border',
                                    statusConfig[mandate.status]?.color || 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ statusConfig[mandate.status]?.label || mandate.status }}
                                </span>
                            </div>
                            <p class="text-blue-100">Mandat de prélèvement SEPA</p>
                            <p v-if="mandate.customer" class="text-blue-200 mt-1">{{ getCustomerName(mandate.customer) }}</p>
                        </div>
                    </div>
                    <div class="mt-6 lg:mt-0 flex flex-wrap gap-3">
                        <a
                            :href="route('tenant.sepa-mandates.download', mandate.id)"
                            class="inline-flex items-center px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl font-medium hover:bg-white/30 transition-all border border-white/20"
                        >
                            <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                            Télécharger PDF
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Type</p>
                                <p class="text-lg font-bold text-white">{{ mandate.type || 'Récurrent' }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <DocumentTextIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Signé le</p>
                                <p class="text-lg font-bold text-white">{{ formatDate(mandate.signed_at) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <PencilSquareIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Activé le</p>
                                <p class="text-lg font-bold text-white">{{ formatDate(mandate.activated_at) }}</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-500/30 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-200 text-sm">Statut</p>
                                <p class="text-lg font-bold text-white">{{ statusConfig[mandate.status]?.label || mandate.status }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <component :is="statusConfig[mandate.status]?.icon || ClockIcon" class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Bank Account Details -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <BuildingLibraryIcon class="w-5 h-5 text-blue-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Coordonnées bancaires</h2>
                        </div>
                        <div class="p-6">
                            <dl class="space-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                                        Titulaire du compte
                                    </dt>
                                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ mandate.account_holder }}</dd>
                                </div>
                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4">
                                    <dt class="text-sm font-medium text-gray-500 mb-2">IBAN</dt>
                                    <dd class="text-xl font-mono font-bold text-gray-900 tracking-wider">{{ mandate.iban }}</dd>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                                            BIC/SWIFT
                                        </dt>
                                        <dd class="mt-1 text-gray-900 font-mono font-semibold">{{ mandate.bic }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 flex items-center">
                                            <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                            Nom de la banque
                                        </dt>
                                        <dd class="mt-1 text-gray-900 font-semibold">{{ mandate.bank_name || '-' }}</dd>
                                    </div>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Mandate Details -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <DocumentTextIcon class="w-5 h-5 text-blue-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Détails du mandat</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Référence du mandat</dt>
                                    <dd class="mt-1 text-gray-900 font-mono font-semibold">{{ mandate.mandate_reference }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type de prélèvement</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                            {{ mandate.type || 'Récurrent' }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date de signature</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(mandate.signed_at) }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Date d'activation</dt>
                                    <dd class="mt-1 text-gray-900">{{ formatDate(mandate.activated_at) }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="mandate.notes" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ mandate.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Sidebar) -->
                <div class="space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <UserIcon class="w-5 h-5 text-blue-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Client</h2>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                    <span class="text-xl font-bold text-white">
                                        {{ (mandate.customer?.first_name?.[0] || mandate.customer?.company_name?.[0] || '?').toUpperCase() }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-lg font-semibold text-gray-900">{{ getCustomerName(mandate.customer) }}</p>
                                    <p class="text-sm text-gray-500">{{ mandate.customer?.email }}</p>
                                </div>
                            </div>
                            <Link
                                :href="route('tenant.customers.show', mandate.customer?.id)"
                                class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium text-sm"
                            >
                                Voir le profil client
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <ShieldCheckIcon class="w-5 h-5 text-blue-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Actions</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <button
                                v-if="mandate.status === 'pending'"
                                @click="activate"
                                class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl transition-all"
                            >
                                <CheckCircleIcon class="w-5 h-5 mr-2" />
                                Activer le mandat
                            </button>

                            <button
                                v-if="mandate.status === 'active'"
                                @click="suspend"
                                class="w-full flex items-center justify-center px-4 py-3 bg-amber-50 text-amber-700 border-2 border-amber-200 rounded-xl font-medium hover:bg-amber-100 transition-all"
                            >
                                <PauseCircleIcon class="w-5 h-5 mr-2" />
                                Suspendre le mandat
                            </button>

                            <button
                                v-if="mandate.status === 'suspended'"
                                @click="reactivate"
                                class="w-full flex items-center justify-center px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl font-medium shadow-lg shadow-emerald-500/25 hover:shadow-xl transition-all"
                            >
                                <PlayCircleIcon class="w-5 h-5 mr-2" />
                                Réactiver le mandat
                            </button>

                            <button
                                v-if="mandate.status !== 'cancelled'"
                                @click="cancel"
                                class="w-full flex items-center justify-center px-4 py-3 bg-red-50 text-red-700 border-2 border-red-200 rounded-xl font-medium hover:bg-red-100 transition-all"
                            >
                                <XCircleIcon class="w-5 h-5 mr-2" />
                                Annuler le mandat
                            </button>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <CalendarDaysIcon class="w-5 h-5 text-blue-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Chronologie</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Création</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(mandate.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <PencilSquareIcon class="w-4 h-4 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Dernière modification</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(mandate.updated_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="mandate.cancelled_at" class="flex items-start">
                                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <XCircleIcon class="w-4 h-4 text-red-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-red-600">Annulé</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(mandate.cancelled_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cancel Modal -->
        <Teleport to="body">
            <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="showCancelModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Annuler le mandat</h3>
                                <p class="text-sm text-gray-500">Cette action est irréversible</p>
                            </div>
                        </div>
                        <p class="text-gray-600 mb-6">
                            Êtes-vous sûr de vouloir annuler ce mandat SEPA ? Les prélèvements ne pourront plus être effectués sur ce compte.
                        </p>
                        <div class="flex justify-end space-x-3">
                            <button
                                @click="showCancelModal = false"
                                class="px-4 py-2 text-gray-700 font-medium hover:text-gray-900 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="confirmCancel"
                                class="px-4 py-2 bg-red-600 text-white rounded-xl font-medium hover:bg-red-700 transition-colors"
                            >
                                Confirmer l'annulation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>
