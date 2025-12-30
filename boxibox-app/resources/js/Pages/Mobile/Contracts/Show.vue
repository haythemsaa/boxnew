<template>
    <MobileLayout title="Detail Contrat" :show-back="true">
        <!-- Contract Header Card with Visual Design -->
        <div class="relative mb-6 overflow-hidden">
            <!-- Background Pattern -->
            <div
                class="absolute inset-0 rounded-3xl"
                :class="contract.status === 'active'
                    ? 'bg-gradient-to-br from-green-500 via-emerald-500 to-teal-600'
                    : 'bg-gradient-to-br from-gray-500 via-gray-600 to-gray-700'"
            >
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute -top-20 -right-20 w-64 h-64 bg-white rounded-full"></div>
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white rounded-full"></div>
                </div>
            </div>

            <div class="relative p-6 rounded-3xl">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <p class="text-white/70 text-sm font-medium mb-1">Contrat</p>
                        <h2 class="text-2xl font-bold text-white">{{ contract.contract_number }}</h2>
                    </div>
                    <span
                        class="px-4 py-2 rounded-full text-sm font-bold uppercase tracking-wide bg-white/20 backdrop-blur-sm text-white"
                    >
                        {{ getStatusLabel(contract.status) }}
                    </span>
                </div>

                <!-- Box Visual Card -->
                <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4">
                    <div class="flex items-center">
                        <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center mr-4 shadow-lg">
                            <CubeIcon class="w-8 h-8 text-primary-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-white text-xl">{{ contract.box?.name }}</h3>
                            <p class="text-white/80 flex items-center mt-1">
                                <MapPinIcon class="w-4 h-4 mr-1.5" />
                                {{ contract.box?.site?.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Price Badge -->
                <div class="mt-4 flex justify-center">
                    <div class="bg-white rounded-2xl px-8 py-4 shadow-xl transform -mb-12 z-10">
                        <p class="text-gray-500 text-xs text-center mb-1">Prix mensuel</p>
                        <p class="text-3xl font-bold text-primary-600 text-center">
                            {{ contract.monthly_price }}<span class="text-lg text-gray-400">EUR/mois</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Spacer for floating price badge -->
        <div class="h-8"></div>

        <!-- Contract Details Section -->
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden mb-4 transform transition-all duration-300 hover:shadow-md">
            <div class="p-6">
                <div class="flex items-center mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-100 to-primary-200 rounded-xl flex items-center justify-center mr-3">
                        <DocumentTextIcon class="w-5 h-5 text-primary-600" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Details du contrat</h3>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between items-center py-4 border-b border-gray-100 group hover:bg-gray-50 -mx-6 px-6 transition-colors duration-200">
                        <div class="flex items-center">
                            <CalendarIcon class="w-5 h-5 text-gray-400 mr-3" />
                            <span class="text-gray-600">Date de debut</span>
                        </div>
                        <span class="font-semibold text-gray-900">{{ formatDate(contract.start_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-4 border-b border-gray-100 group hover:bg-gray-50 -mx-6 px-6 transition-colors duration-200">
                        <div class="flex items-center">
                            <CalendarDaysIcon class="w-5 h-5 text-gray-400 mr-3" />
                            <span class="text-gray-600">Date de fin</span>
                        </div>
                        <span class="font-semibold text-gray-900">{{ formatDate(contract.end_date) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-4 border-b border-gray-100 group hover:bg-gray-50 -mx-6 px-6 transition-colors duration-200">
                        <div class="flex items-center">
                            <DocumentTextIcon class="w-5 h-5 text-gray-400 mr-3" />
                            <span class="text-gray-600">Type de contrat</span>
                        </div>
                        <span class="font-semibold text-gray-900 px-3 py-1 bg-primary-50 rounded-full text-primary-700 text-sm">
                            {{ getContractTypeLabel(contract.contract_type) }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-4 border-b border-gray-100 group hover:bg-gray-50 -mx-6 px-6 transition-colors duration-200">
                        <div class="flex items-center">
                            <CreditCardIcon class="w-5 h-5 text-gray-400 mr-3" />
                            <span class="text-gray-600">Mode de paiement</span>
                        </div>
                        <span class="font-semibold text-gray-900">{{ getPaymentModeLabel(contract.payment_mode) }}</span>
                    </div>
                    <div v-if="contract.deposit" class="flex justify-between items-center py-4 group hover:bg-gray-50 -mx-6 px-6 transition-colors duration-200">
                        <div class="flex items-center">
                            <BanknotesIcon class="w-5 h-5 text-gray-400 mr-3" />
                            <span class="text-gray-600">Depot de garantie</span>
                        </div>
                        <span class="font-semibold text-gray-900">{{ formatCurrency(contract.deposit) }}</span>
                    </div>
                </div>

                <!-- Progress bar with Animation -->
                <div v-if="contract.status === 'active'" class="mt-6 p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl">
                    <div class="flex justify-between text-sm mb-3">
                        <span class="text-gray-600 font-medium">Progression du contrat</span>
                        <span class="font-bold text-primary-600">{{ getProgressPercent() }}%</span>
                    </div>
                    <div class="relative w-full h-4 bg-gray-200 rounded-full overflow-hidden">
                        <div
                            class="absolute left-0 top-0 h-full bg-gradient-to-r from-primary-400 via-primary-500 to-primary-600 rounded-full transition-all duration-1000 ease-out"
                            :style="{ width: getProgressPercent() + '%' }"
                        >
                            <!-- Animated shine effect -->
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                        </div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>{{ formatDate(contract.start_date) }}</span>
                        <span>{{ formatDate(contract.end_date) }}</span>
                    </div>
                </div>

                <!-- Expiring Warning with Enhanced Design -->
                <Transition
                    enter-active-class="transition-all duration-500"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div
                        v-if="contract.days_until_expiry && contract.days_until_expiry <= 30 && contract.status === 'active'"
                        class="mt-6 p-5 bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-200"
                    >
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center mr-4 animate-pulse shadow-lg shadow-amber-500/30">
                                <ExclamationTriangleIcon class="w-6 h-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-amber-800 text-lg">Contrat expire bientot</h4>
                                <p class="text-sm text-amber-700 mt-1">
                                    Votre contrat expire dans <span class="font-bold">{{ contract.days_until_expiry }} jours</span>. Pensez a le renouveler pour conserver votre box.
                                </p>
                                <button
                                    @click="showRenewalModal"
                                    class="mt-3 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-semibold rounded-xl text-sm transition-all duration-300 hover:shadow-lg active:scale-[0.98]"
                                >
                                    Renouveler maintenant
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Box Details Section with Visual Grid -->
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden mb-4 transform transition-all duration-300 hover:shadow-md">
            <div class="p-6">
                <div class="flex items-center mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center mr-3">
                        <CubeIcon class="w-5 h-5 text-purple-600" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Details du box</h3>
                </div>

                <!-- Box Stats Grid -->
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div class="relative overflow-hidden bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-4 text-center group hover:scale-[1.02] transition-transform duration-300">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-blue-200/50 rounded-full"></div>
                        <div class="relative">
                            <div class="w-10 h-10 mx-auto mb-2 bg-blue-500 rounded-xl flex items-center justify-center">
                                <Square3Stack3DIcon class="w-5 h-5 text-white" />
                            </div>
                            <p class="text-gray-600 text-xs font-medium mb-1">Surface</p>
                            <p class="font-bold text-gray-900 text-xl">{{ contract.box?.area || 'N/A' }} m<sup>2</sup></p>
                        </div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-4 text-center group hover:scale-[1.02] transition-transform duration-300">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-purple-200/50 rounded-full"></div>
                        <div class="relative">
                            <div class="w-10 h-10 mx-auto mb-2 bg-purple-500 rounded-xl flex items-center justify-center">
                                <CubeTransparentIcon class="w-5 h-5 text-white" />
                            </div>
                            <p class="text-gray-600 text-xs font-medium mb-1">Volume</p>
                            <p class="font-bold text-gray-900 text-xl">{{ contract.box?.volume || 'N/A' }} m<sup>3</sup></p>
                        </div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-4 text-center group hover:scale-[1.02] transition-transform duration-300">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-green-200/50 rounded-full"></div>
                        <div class="relative">
                            <div class="w-10 h-10 mx-auto mb-2 bg-green-500 rounded-xl flex items-center justify-center">
                                <BuildingOfficeIcon class="w-5 h-5 text-white" />
                            </div>
                            <p class="text-gray-600 text-xs font-medium mb-1">Etage</p>
                            <p class="font-bold text-gray-900 text-xl">{{ contract.box?.floor || 'RDC' }}</p>
                        </div>
                    </div>
                    <div class="relative overflow-hidden bg-gradient-to-br from-amber-50 to-amber-100 rounded-2xl p-4 text-center group hover:scale-[1.02] transition-transform duration-300">
                        <div class="absolute -right-4 -top-4 w-16 h-16 bg-amber-200/50 rounded-full"></div>
                        <div class="relative">
                            <div class="w-10 h-10 mx-auto mb-2 bg-amber-500 rounded-xl flex items-center justify-center">
                                <HashtagIcon class="w-5 h-5 text-white" />
                            </div>
                            <p class="text-gray-600 text-xs font-medium mb-1">Code</p>
                            <p class="font-bold text-gray-900 text-xl">{{ contract.box?.code }}</p>
                        </div>
                    </div>
                </div>

                <!-- Features Tags -->
                <div v-if="contract.box?.features?.length" class="mt-4">
                    <p class="text-sm font-medium text-gray-600 mb-3">Caracteristiques</p>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="feature in contract.box.features"
                            :key="feature"
                            class="px-4 py-2 bg-gradient-to-r from-primary-50 to-primary-100 text-primary-700 rounded-full text-sm font-medium transition-transform duration-200 hover:scale-105"
                        >
                            {{ feature }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Invoices Section -->
        <div class="bg-white rounded-3xl shadow-sm overflow-hidden mb-4 transform transition-all duration-300 hover:shadow-md">
            <div class="p-6">
                <div class="flex justify-between items-center mb-5">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center mr-3">
                            <CurrencyEuroIcon class="w-5 h-5 text-green-600" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Factures</h3>
                    </div>
                    <Link
                        :href="route('mobile.invoices', { contract_id: contract.id })"
                        class="text-sm text-primary-600 font-semibold flex items-center hover:text-primary-700 transition-colors"
                    >
                        Voir tout
                        <ChevronRightIcon class="w-4 h-4 ml-1" />
                    </Link>
                </div>

                <TransitionGroup
                    tag="div"
                    class="space-y-3"
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 translate-x-4"
                    enter-to-class="opacity-100 translate-x-0"
                >
                    <Link
                        v-for="invoice in contract.invoices?.slice(0, 3)"
                        :key="invoice.id"
                        :href="route('mobile.invoices.show', invoice.id)"
                        class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100/50 rounded-2xl group hover:shadow-md transition-all duration-300 hover:scale-[1.01]"
                    >
                        <div class="flex items-center">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center mr-4 transition-transform duration-300 group-hover:scale-110"
                                :class="getInvoiceIconBgClass(invoice.status)"
                            >
                                <DocumentTextIcon
                                    class="w-6 h-6"
                                    :class="getInvoiceStatusTextClass(invoice.status)"
                                />
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ invoice.invoice_number }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(invoice.invoice_date) }}</p>
                            </div>
                        </div>
                        <div class="text-right flex items-center">
                            <span
                                class="text-xs px-3 py-1.5 rounded-full font-semibold mr-3"
                                :class="getInvoiceStatusBadgeClass(invoice.status)"
                            >
                                {{ getInvoiceStatusLabel(invoice.status) }}
                            </span>
                            <span class="font-bold text-gray-900 text-lg">{{ invoice.total }}EUR</span>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400 ml-2 transition-transform duration-300 group-hover:translate-x-1" />
                        </div>
                    </Link>
                </TransitionGroup>

                <div v-if="!contract.invoices?.length" class="text-center py-8">
                    <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <DocumentTextIcon class="w-8 h-8 text-gray-400" />
                    </div>
                    <p class="text-gray-500">Aucune facture</p>
                </div>
            </div>
        </div>

        <!-- Actions Section -->
        <div class="space-y-3 mb-8">
            <!-- Access Button -->
            <Link
                :href="route('mobile.access', { contract_id: contract.id })"
                class="w-full py-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white font-bold rounded-2xl flex items-center justify-center shadow-lg shadow-purple-500/30 transform transition-all duration-300 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99]"
            >
                <KeyIcon class="w-6 h-6 mr-3" />
                Acceder a mon box
            </Link>

            <!-- Renew Button (if active) -->
            <button
                v-if="contract.status === 'active'"
                @click="showRenewalModal"
                class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold rounded-2xl flex items-center justify-center shadow-lg shadow-green-500/30 transform transition-all duration-300 hover:shadow-xl hover:scale-[1.01] active:scale-[0.99]"
            >
                <ArrowPathIcon class="w-6 h-6 mr-3" />
                Renouveler le contrat
            </button>

            <!-- Download PDF -->
            <a
                :href="route('mobile.contracts.pdf', contract.id)"
                target="_blank"
                class="w-full py-4 bg-white border-2 border-gray-200 text-gray-700 font-bold rounded-2xl flex items-center justify-center transform transition-all duration-300 hover:bg-gray-50 hover:border-gray-300 active:scale-[0.99]"
            >
                <ArrowDownTrayIcon class="w-6 h-6 mr-3" />
                Telecharger le contrat
            </a>

            <!-- Terminate Button (if active) -->
            <button
                v-if="contract.status === 'active'"
                @click="showTerminateModal = true"
                class="w-full py-4 bg-red-50 text-red-600 font-bold rounded-2xl flex items-center justify-center border-2 border-red-100 transform transition-all duration-300 hover:bg-red-100 hover:border-red-200 active:scale-[0.99]"
            >
                <XMarkIcon class="w-6 h-6 mr-3" />
                Resilier le contrat
            </button>
        </div>

        <!-- Terminate Confirmation Modal -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showTerminateModal" class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showTerminateModal = false"></div>
                <Transition
                    enter-active-class="transition-all duration-300 delay-100"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition-all duration-200"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div v-if="showTerminateModal" class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[2rem] shadow-2xl">
                        <!-- Handle Bar -->
                        <div class="flex justify-center pt-4 pb-2">
                            <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                        </div>

                        <div class="px-6 pb-6">
                            <!-- Warning Icon -->
                            <div class="text-center mb-6">
                                <div class="w-20 h-20 mx-auto mb-4 bg-gradient-to-br from-red-400 to-red-600 rounded-full flex items-center justify-center shadow-lg shadow-red-500/30 animate-pulse">
                                    <ExclamationTriangleIcon class="w-10 h-10 text-white" />
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Resilier le contrat ?</h3>
                                <p class="text-gray-500 mt-2 max-w-xs mx-auto">
                                    Cette action est irreversible. Vous perdrez l'acces a votre box a la date de fin de preavis.
                                </p>
                            </div>

                            <!-- Contract Info -->
                            <div class="bg-red-50 rounded-2xl p-4 mb-6 border border-red-100">
                                <div class="flex items-center">
                                    <CubeIcon class="w-8 h-8 text-red-500 mr-3" />
                                    <div>
                                        <p class="font-bold text-gray-900">{{ contract.box?.name }}</p>
                                        <p class="text-sm text-gray-500">{{ contract.contract_number }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex space-x-3">
                                <button
                                    @click="showTerminateModal = false"
                                    class="flex-1 py-4 bg-gray-100 text-gray-700 font-bold rounded-2xl transition-all duration-300 hover:bg-gray-200 active:scale-[0.98]"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="terminateContract"
                                    class="flex-1 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white font-bold rounded-2xl shadow-lg shadow-red-500/30 transition-all duration-300 hover:shadow-xl active:scale-[0.98]"
                                >
                                    Confirmer
                                </button>
                            </div>
                        </div>
                        <!-- Safe Area -->
                        <div class="h-8 bg-white"></div>
                    </div>
                </Transition>
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
    CalendarIcon,
    CalendarDaysIcon,
    CreditCardIcon,
    BanknotesIcon,
    CurrencyEuroIcon,
    ChevronRightIcon,
    Square3Stack3DIcon,
    CubeTransparentIcon,
    BuildingOfficeIcon,
    HashtagIcon,
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

const getInvoiceIconBgClass = (status) => {
    const classes = {
        draft: 'bg-gray-100',
        sent: 'bg-yellow-100',
        paid: 'bg-green-100',
        overdue: 'bg-red-100',
        cancelled: 'bg-gray-100',
    }
    return classes[status] || 'bg-gray-100'
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

<style scoped>
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 2s infinite;
}
</style>
