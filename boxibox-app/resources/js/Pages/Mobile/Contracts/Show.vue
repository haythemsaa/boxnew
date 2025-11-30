<template>
    <MobileLayout title="Detail Contrat" :show-back="true">
        <!-- Contract Header -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div
                class="p-5"
                :class="contract.status === 'active' ? 'bg-gradient-to-br from-green-500 to-green-600' : 'bg-gradient-to-br from-gray-500 to-gray-600'"
            >
                <div class="flex items-center justify-between text-white">
                    <div>
                        <p class="text-white/80 text-sm">Contrat</p>
                        <h2 class="text-xl font-bold">{{ contract.contract_number }}</h2>
                    </div>
                    <span
                        class="px-3 py-1.5 rounded-full text-sm font-medium bg-white/20"
                    >
                        {{ getStatusLabel(contract.status) }}
                    </span>
                </div>
            </div>

            <div class="p-5">
                <!-- Box Info -->
                <div class="flex items-center mb-4">
                    <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mr-4">
                        <CubeIcon class="w-7 h-7 text-primary-600" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 text-lg">{{ contract.box?.name }}</h3>
                        <p class="text-gray-500 flex items-center">
                            <MapPinIcon class="w-4 h-4 mr-1" />
                            {{ contract.box?.site?.name }}
                        </p>
                    </div>
                </div>

                <!-- Price -->
                <div class="bg-gray-50 rounded-xl p-4 text-center">
                    <p class="text-gray-500 text-sm mb-1">Prix mensuel</p>
                    <p class="text-3xl font-bold text-primary-600">{{ contract.monthly_price }}€<span class="text-lg text-gray-400">/mois</span></p>
                </div>
            </div>
        </div>

        <!-- Contract Details -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details du contrat</h3>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Date de debut</span>
                        <span class="font-medium text-gray-900">{{ formatDate(contract.start_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Date de fin</span>
                        <span class="font-medium text-gray-900">{{ formatDate(contract.end_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Type de contrat</span>
                        <span class="font-medium text-gray-900">{{ getContractTypeLabel(contract.contract_type) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Mode de paiement</span>
                        <span class="font-medium text-gray-900">{{ getPaymentModeLabel(contract.payment_mode) }}</span>
                    </div>
                    <div v-if="contract.deposit" class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Depot de garantie</span>
                        <span class="font-medium text-gray-900">{{ formatCurrency(contract.deposit) }}</span>
                    </div>
                </div>

                <!-- Progress bar -->
                <div v-if="contract.status === 'active'" class="mt-6">
                    <div class="flex justify-between text-sm text-gray-500 mb-2">
                        <span>Progression du contrat</span>
                        <span>{{ getProgressPercent() }}%</span>
                    </div>
                    <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-primary-500 to-primary-600 rounded-full transition-all duration-300"
                            :style="{ width: getProgressPercent() + '%' }"
                        ></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>{{ formatDate(contract.start_date) }}</span>
                        <span>{{ formatDate(contract.end_date) }}</span>
                    </div>
                </div>

                <!-- Expiring Warning -->
                <div
                    v-if="contract.days_until_expiry && contract.days_until_expiry <= 30 && contract.status === 'active'"
                    class="mt-4 p-4 bg-yellow-50 rounded-xl flex items-start"
                >
                    <ExclamationTriangleIcon class="w-6 h-6 text-yellow-500 mr-3 flex-shrink-0" />
                    <div>
                        <h4 class="font-semibold text-yellow-800">Contrat expire bientot</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Votre contrat expire dans {{ contract.days_until_expiry }} jours. Pensez a le renouveler pour conserver votre box.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box Details -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details du box</h3>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-gray-500 text-xs mb-1">Surface</p>
                        <p class="font-bold text-gray-900">{{ contract.box?.area || 'N/A' }} m²</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-gray-500 text-xs mb-1">Volume</p>
                        <p class="font-bold text-gray-900">{{ contract.box?.volume || 'N/A' }} m³</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-gray-500 text-xs mb-1">Etage</p>
                        <p class="font-bold text-gray-900">{{ contract.box?.floor || 'RDC' }}</p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-gray-500 text-xs mb-1">Code</p>
                        <p class="font-bold text-gray-900">{{ contract.box?.code }}</p>
                    </div>
                </div>

                <!-- Features -->
                <div v-if="contract.box?.features?.length" class="flex flex-wrap gap-2">
                    <span
                        v-for="feature in contract.box.features"
                        :key="feature"
                        class="px-3 py-1 bg-primary-50 text-primary-700 rounded-full text-xs"
                    >
                        {{ feature }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Related Invoices -->
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-4">
            <div class="p-5">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Factures</h3>
                    <Link
                        :href="route('mobile.invoices', { contract_id: contract.id })"
                        class="text-sm text-primary-600 font-medium"
                    >
                        Voir tout
                    </Link>
                </div>

                <div v-if="contract.invoices?.length > 0" class="space-y-3">
                    <Link
                        v-for="invoice in contract.invoices.slice(0, 3)"
                        :key="invoice.id"
                        :href="route('mobile.invoices.show', invoice.id)"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                    >
                        <div class="flex items-center">
                            <DocumentTextIcon
                                class="w-5 h-5 mr-2"
                                :class="getInvoiceStatusTextClass(invoice.status)"
                            />
                            <div>
                                <p class="font-medium text-gray-900 text-sm">{{ invoice.invoice_number }}</p>
                                <p class="text-xs text-gray-500">{{ formatDate(invoice.invoice_date) }}</p>
                            </div>
                        </div>
                        <div class="text-right flex items-center">
                            <span
                                class="text-xs px-2 py-0.5 rounded-full mr-2"
                                :class="getInvoiceStatusBadgeClass(invoice.status)"
                            >
                                {{ getInvoiceStatusLabel(invoice.status) }}
                            </span>
                            <span class="font-semibold text-gray-900">{{ invoice.total }}€</span>
                        </div>
                    </Link>
                </div>
                <div v-else class="text-center py-4 text-gray-500">
                    Aucune facture
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="space-y-3 mb-6">
            <!-- Access Button -->
            <Link
                :href="route('mobile.access', { contract_id: contract.id })"
                class="w-full py-3.5 bg-purple-600 text-white font-semibold rounded-xl flex items-center justify-center"
            >
                <KeyIcon class="w-5 h-5 mr-2" />
                Acceder a mon box
            </Link>

            <!-- Renew Button (if active) -->
            <button
                v-if="contract.status === 'active'"
                @click="showRenewalModal"
                class="w-full py-3.5 bg-green-600 text-white font-semibold rounded-xl flex items-center justify-center"
            >
                <ArrowPathIcon class="w-5 h-5 mr-2" />
                Renouveler le contrat
            </button>

            <!-- Download PDF -->
            <a
                :href="route('mobile.contracts.pdf', contract.id)"
                target="_blank"
                class="w-full py-3.5 bg-white border border-gray-300 text-gray-700 font-semibold rounded-xl flex items-center justify-center"
            >
                <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                Telecharger le contrat
            </a>

            <!-- Terminate Button (if active) -->
            <button
                v-if="contract.status === 'active'"
                @click="showTerminateModal = true"
                class="w-full py-3.5 bg-red-50 text-red-600 font-semibold rounded-xl flex items-center justify-center"
            >
                <XMarkIcon class="w-5 h-5 mr-2" />
                Resilier le contrat
            </button>
        </div>

        <!-- Terminate Confirmation Modal -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showTerminateModal" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" @click="showTerminateModal = false"></div>
                <div class="absolute bottom-0 left-0 right-0 bg-white rounded-t-3xl shadow-xl">
                    <div class="w-12 h-1 bg-gray-300 rounded-full mx-auto mt-3"></div>
                    <div class="p-6">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <ExclamationTriangleIcon class="w-8 h-8 text-red-600" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Resilier le contrat ?</h3>
                        <p class="text-gray-500 text-center mb-6">
                            Cette action est irreversible. Vous perdrez l'acces a votre box a la date de fin de preavis.
                        </p>

                        <div class="flex space-x-3">
                            <button
                                @click="showTerminateModal = false"
                                class="flex-1 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl"
                            >
                                Annuler
                            </button>
                            <button
                                @click="terminateContract"
                                class="flex-1 py-3 bg-red-600 text-white font-semibold rounded-xl"
                            >
                                Confirmer
                            </button>
                        </div>
                    </div>
                    <div class="h-8 bg-white"></div>
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CubeIcon,
    MapPinIcon,
    DocumentTextIcon,
    KeyIcon,
    ArrowPathIcon,
    ArrowDownTrayIcon,
    XMarkIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contract: Object,
})

const showTerminateModal = ref(false)

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        terminated: 'Termine',
        pending: 'En attente',
        cancelled: 'Annule',
    }
    return labels[status] || status
}

const getContractTypeLabel = (type) => {
    const labels = {
        standard: 'Standard',
        premium: 'Premium',
        enterprise: 'Entreprise',
    }
    return labels[type] || type || 'Standard'
}

const getPaymentModeLabel = (mode) => {
    const labels = {
        monthly: 'Mensuel',
        quarterly: 'Trimestriel',
        yearly: 'Annuel',
        sepa: 'Prelevement SEPA',
    }
    return labels[mode] || mode || 'Mensuel'
}

const getProgressPercent = () => {
    if (!props.contract.start_date || !props.contract.end_date) return 0
    const start = new Date(props.contract.start_date).getTime()
    const end = new Date(props.contract.end_date).getTime()
    const now = Date.now()

    if (now >= end) return 100
    if (now <= start) return 0

    return Math.round(((now - start) / (end - start)) * 100)
}

const getInvoiceStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        sent: 'En attente',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getInvoiceStatusTextClass = (status) => {
    const classes = {
        draft: 'text-gray-500',
        sent: 'text-yellow-500',
        paid: 'text-green-500',
        overdue: 'text-red-500',
        cancelled: 'text-gray-500',
    }
    return classes[status] || 'text-gray-500'
}

const getInvoiceStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-yellow-100 text-yellow-700',
        paid: 'bg-green-100 text-green-700',
        overdue: 'bg-red-100 text-red-700',
        cancelled: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const showRenewalModal = () => {
    router.visit(route('mobile.contracts.renew-form', props.contract.id))
}

const terminateContract = () => {
    router.post(route('mobile.contracts.terminate', props.contract.id), {}, {
        onSuccess: () => {
            showTerminateModal.value = false
        },
    })
}
</script>
