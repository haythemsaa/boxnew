<template>
    <MobileLayout title="Facture" :show-back="true">
        <!-- Invoice Header Card -->
        <div class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 mb-5">
            <!-- Status Indicator Bar -->
            <div
                class="h-1.5"
                :class="getStatusBarClass(invoice.status)"
            ></div>

            <!-- Background Decoration -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-gradient-to-br opacity-10 rounded-full -translate-y-1/2 translate-x-1/2"
                 :class="getStatusDecorationClass(invoice.status)"></div>

            <div class="p-6 relative">
                <!-- Header Row -->
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <div class="flex items-center mb-2">
                            <div
                                class="w-12 h-12 rounded-xl flex items-center justify-center mr-3 shadow-lg"
                                :class="getStatusGradientClass(invoice.status)"
                            >
                                <component :is="getStatusIcon(invoice.status)" class="w-6 h-6 text-white" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                    <CalendarDaysIcon class="w-4 h-4 mr-1" />
                                    Emise le {{ formatDate(invoice.invoice_date) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <span
                        class="inline-flex items-center px-3.5 py-1.5 rounded-full text-sm font-semibold shadow-sm"
                        :class="getStatusBadgeClass(invoice.status)"
                    >
                        <span
                            class="w-2 h-2 rounded-full mr-2"
                            :class="getStatusDotClass(invoice.status)"
                        ></span>
                        {{ getStatusLabel(invoice.status) }}
                    </span>
                </div>

                <!-- Amount Section -->
                <div class="text-center py-8 px-6 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mb-2">Montant total</p>
                    <p class="text-5xl font-extrabold bg-gradient-to-r from-gray-900 to-gray-700 dark:from-white dark:to-gray-300 bg-clip-text text-transparent">
                        {{ formatCurrency(invoice.total) }}
                    </p>
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="invoice.balance > 0 && invoice.balance !== invoice.total" class="mt-4 inline-flex items-center px-4 py-2 bg-red-50 dark:bg-red-900/20 rounded-xl">
                            <ExclamationCircleIcon class="w-5 h-5 text-red-500 mr-2" />
                            <span class="text-sm text-gray-600 dark:text-gray-400">Reste a payer:</span>
                            <span class="font-bold text-red-600 dark:text-red-400 ml-1.5">{{ formatCurrency(invoice.balance) }}</span>
                        </div>
                    </Transition>
                </div>

                <!-- Due Date Alert -->
                <Transition
                    enter-active-class="transition-all duration-500 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="invoice.status === 'sent' || invoice.status === 'overdue'" class="mt-5">
                        <div
                            class="flex items-center justify-between p-4 rounded-2xl border"
                            :class="invoice.status === 'overdue'
                                ? 'bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/20 dark:to-rose-900/20 border-red-200 dark:border-red-800'
                                : 'bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-amber-200 dark:border-amber-800'"
                        >
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center mr-3"
                                    :class="invoice.status === 'overdue' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-amber-100 dark:bg-amber-900/30'"
                                >
                                    <CalendarIcon
                                        class="w-5 h-5"
                                        :class="invoice.status === 'overdue' ? 'text-red-600' : 'text-amber-600'"
                                    />
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide" :class="invoice.status === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'">
                                        Echeance
                                    </p>
                                    <p class="font-bold" :class="invoice.status === 'overdue' ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300'">
                                        {{ formatDate(invoice.due_date) }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="invoice.status === 'overdue'"
                                class="flex items-center px-3 py-1.5 bg-red-100 dark:bg-red-900/40 rounded-xl"
                            >
                                <ExclamationTriangleIcon class="w-5 h-5 text-red-600 mr-1.5" />
                                <span class="text-sm font-bold text-red-700 dark:text-red-300">
                                    {{ getDaysOverdue(invoice.due_date) }} jours de retard
                                </span>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- Invoice Details Section -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden mb-5">
            <div class="p-6">
                <div class="flex items-center mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center mr-3 shadow-lg shadow-primary-500/30">
                        <DocumentTextIcon class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Details de la facture</h3>
                </div>

                <!-- Line items -->
                <div class="space-y-1">
                    <TransitionGroup
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-x-4"
                        enter-to-class="opacity-100 translate-x-0"
                    >
                        <div
                            v-for="(item, index) in invoice.items"
                            :key="index"
                            class="group relative"
                            :style="{ transitionDelay: `${index * 100}ms` }"
                        >
                            <div class="flex justify-between items-start p-4 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-200">
                                <!-- Item indicator -->
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center mr-3 text-sm font-bold text-gray-500 dark:text-gray-400 flex-shrink-0">
                                        {{ index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ item.description }}</p>
                                        <div class="flex items-center mt-1.5 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-md mr-2">
                                                {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p class="font-bold text-gray-900 dark:text-white text-lg flex-shrink-0 ml-4">
                                    {{ formatCurrency(item.total) }}
                                </p>
                            </div>
                            <div v-if="index < invoice.items.length - 1" class="mx-4 border-b border-gray-100 dark:border-gray-700"></div>
                        </div>
                    </TransitionGroup>
                </div>

                <!-- Totals Section -->
                <div class="mt-6 pt-6 border-t-2 border-dashed border-gray-200 dark:border-gray-700">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <div class="w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center mr-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">HT</span>
                                </div>
                                Sous-total
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(invoice.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <span class="text-gray-600 dark:text-gray-400 flex items-center">
                                <div class="w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded flex items-center justify-center mr-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">%</span>
                                </div>
                                TVA ({{ invoice.tax_rate || 21 }}%)
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(invoice.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between items-center p-4 rounded-2xl bg-gradient-to-r from-primary-50 to-indigo-50 dark:from-primary-900/20 dark:to-indigo-900/20 border border-primary-100 dark:border-primary-800">
                            <span class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/50 rounded-lg flex items-center justify-center mr-3">
                                    <BanknotesIcon class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                </div>
                                Total TTC
                            </span>
                            <span class="text-2xl font-extrabold text-primary-600 dark:text-primary-400">{{ formatCurrency(invoice.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contract Info Section -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden mb-5">
            <div class="p-6">
                <div class="flex items-center mb-5">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-violet-600 rounded-xl flex items-center justify-center mr-3 shadow-lg shadow-purple-500/30">
                        <DocumentDuplicateIcon class="w-5 h-5 text-white" />
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Contrat associe</h3>
                </div>
                <Link
                    v-if="invoice.contract"
                    :href="route('mobile.contracts.show', invoice.contract.id)"
                    class="group flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-2xl border border-gray-100 dark:border-gray-700 hover:shadow-lg transition-all duration-300 active:scale-[0.98]"
                >
                    <div class="flex items-center">
                        <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center mr-4 shadow-lg shadow-primary-500/30 transition-transform duration-300 group-hover:scale-105">
                            <CubeIcon class="w-7 h-7 text-white" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white text-lg">{{ invoice.contract.box?.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-0.5">
                                <HashtagIcon class="w-4 h-4 mr-1" />
                                {{ invoice.contract.contract_number }}
                            </p>
                        </div>
                    </div>
                    <div class="w-10 h-10 bg-white dark:bg-gray-700 rounded-xl flex items-center justify-center shadow-sm transition-transform duration-300 group-hover:translate-x-1">
                        <ChevronRightIcon class="w-5 h-5 text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Payment History Section -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="invoice.payments?.length > 0" class="bg-white dark:bg-gray-800 rounded-3xl shadow-xl shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden mb-5">
                <div class="p-6">
                    <div class="flex items-center mb-5">
                        <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center mr-3 shadow-lg shadow-green-500/30">
                            <CheckBadgeIcon class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Historique des paiements</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ invoice.payments.length }} paiement(s) effectue(s)</p>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <TransitionGroup
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                        >
                            <div
                                v-for="(payment, index) in invoice.payments"
                                :key="payment.id"
                                class="relative flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-2xl border border-green-100 dark:border-green-800"
                                :style="{ transitionDelay: `${index * 100}ms` }"
                            >
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center mr-4 shadow-lg shadow-green-500/30">
                                        <CheckCircleIcon class="w-6 h-6 text-white" />
                                    </div>
                                    <div>
                                        <p class="font-bold text-green-700 dark:text-green-400 text-lg">{{ formatCurrency(payment.amount) }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-0.5">
                                            <CalendarDaysIcon class="w-4 h-4 mr-1" />
                                            {{ formatDate(payment.paid_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1.5 bg-white dark:bg-gray-800 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 shadow-sm">
                                        <component :is="getPaymentMethodIcon(payment.method)" class="w-4 h-4 mr-1.5 text-gray-500" />
                                        {{ getMethodLabel(payment.method) }}
                                    </span>
                                </div>
                            </div>
                        </TransitionGroup>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Actions Section -->
        <div class="space-y-4 mb-8">
            <!-- Pay Button - Primary CTA -->
            <Transition
                enter-active-class="transition-all duration-500 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
            >
                <button
                    v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                    @click="payInvoice"
                    class="group relative w-full overflow-hidden py-4 font-bold rounded-2xl flex items-center justify-center shadow-xl transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
                    :class="invoice.status === 'overdue'
                        ? 'bg-gradient-to-r from-red-500 via-red-600 to-rose-600 text-white shadow-red-500/40 hover:shadow-red-500/60'
                        : 'bg-gradient-to-r from-primary-500 via-primary-600 to-indigo-600 text-white shadow-primary-500/40 hover:shadow-primary-500/60'"
                >
                    <!-- Animated background effect -->
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/20 to-white/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000"></div>

                    <div class="relative flex items-center">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center mr-3">
                            <CreditCardIcon class="w-6 h-6" />
                        </div>
                        <div class="text-left">
                            <span class="block text-lg">Payer maintenant</span>
                            <span class="text-sm opacity-80">{{ formatCurrency(invoice.balance || invoice.total) }}</span>
                        </div>
                        <ArrowRightIcon class="w-6 h-6 ml-4 transition-transform duration-300 group-hover:translate-x-2" />
                    </div>
                </button>
            </Transition>

            <!-- Secondary Actions -->
            <div class="grid grid-cols-2 gap-3">
                <!-- Download PDF -->
                <a
                    :href="route('mobile.invoices.pdf', invoice.id)"
                    target="_blank"
                    class="group flex flex-col items-center p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none hover:shadow-xl transition-all duration-300 active:scale-95"
                >
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-3 shadow-lg shadow-blue-500/30 transition-transform duration-300 group-hover:scale-110">
                        <ArrowDownTrayIcon class="w-6 h-6 text-white" />
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white text-sm">Telecharger PDF</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Facture complete</span>
                </a>

                <!-- Contact Support -->
                <Link
                    :href="route('mobile.support', { invoice_id: invoice.id })"
                    class="group flex flex-col items-center p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none hover:shadow-xl transition-all duration-300 active:scale-95"
                >
                    <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-500 rounded-xl flex items-center justify-center mb-3 shadow-lg shadow-orange-500/30 transition-transform duration-300 group-hover:scale-110">
                        <ChatBubbleLeftRightIcon class="w-6 h-6 text-white" />
                    </div>
                    <span class="font-semibold text-gray-900 dark:text-white text-sm">Contacter support</span>
                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Besoin d'aide?</span>
                </Link>
            </div>

            <!-- Share Invoice -->
            <button
                @click="shareInvoice"
                class="group w-full flex items-center justify-center p-4 bg-gray-100 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300 font-semibold rounded-2xl hover:bg-gray-200 dark:hover:bg-gray-700 transition-all duration-200 active:scale-95"
            >
                <ShareIcon class="w-5 h-5 mr-2 transition-transform duration-200 group-hover:scale-110" />
                Partager la facture
            </button>
        </div>
    </MobileLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CalendarIcon,
    CalendarDaysIcon,
    CubeIcon,
    ChevronRightIcon,
    CheckCircleIcon,
    CreditCardIcon,
    ArrowDownTrayIcon,
    ChatBubbleLeftRightIcon,
    DocumentTextIcon,
    DocumentDuplicateIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    XCircleIcon,
    BanknotesIcon,
    HashtagIcon,
    CheckBadgeIcon,
    ArrowRightIcon,
    ShareIcon,
    BuildingLibraryIcon,
    BanknotesIcon as CashIcon,
    ReceiptPercentIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: Object,
})

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
        draft: 'Brouillon',
        sent: 'En attente',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getStatusBarClass = (status) => {
    const classes = {
        draft: 'bg-gray-300 dark:bg-gray-600',
        sent: 'bg-gradient-to-r from-amber-400 to-orange-500',
        paid: 'bg-gradient-to-r from-green-400 to-emerald-500',
        overdue: 'bg-gradient-to-r from-red-500 to-rose-600',
        cancelled: 'bg-gray-300 dark:bg-gray-600',
    }
    return classes[status] || 'bg-gray-300 dark:bg-gray-600'
}

const getStatusDecorationClass = (status) => {
    const classes = {
        draft: 'from-gray-400 to-gray-500',
        sent: 'from-amber-400 to-orange-500',
        paid: 'from-green-400 to-emerald-500',
        overdue: 'from-red-500 to-rose-600',
        cancelled: 'from-gray-400 to-gray-500',
    }
    return classes[status] || 'from-gray-400 to-gray-500'
}

const getStatusGradientClass = (status) => {
    const classes = {
        draft: 'bg-gradient-to-br from-gray-400 to-gray-500 shadow-gray-500/30',
        sent: 'bg-gradient-to-br from-amber-400 to-orange-500 shadow-amber-500/30',
        paid: 'bg-gradient-to-br from-green-400 to-emerald-600 shadow-green-500/30',
        overdue: 'bg-gradient-to-br from-red-500 to-rose-600 shadow-red-500/30',
        cancelled: 'bg-gradient-to-br from-gray-400 to-gray-500 shadow-gray-500/30',
    }
    return classes[status] || 'bg-gradient-to-br from-gray-400 to-gray-500 shadow-gray-500/30'
}

const getStatusIcon = (status) => {
    const icons = {
        draft: DocumentTextIcon,
        sent: ClockIcon,
        paid: CheckCircleIcon,
        overdue: ExclamationCircleIcon,
        cancelled: XCircleIcon,
    }
    return icons[status] || DocumentTextIcon
}

const getStatusBadgeClass = (status) => {
    const classes = {
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
        sent: 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400',
        paid: 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400',
        overdue: 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400',
        cancelled: 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
    }
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'
}

const getStatusDotClass = (status) => {
    const classes = {
        draft: 'bg-gray-500',
        sent: 'bg-amber-500 animate-pulse',
        paid: 'bg-green-500',
        overdue: 'bg-red-500 animate-pulse',
        cancelled: 'bg-gray-500',
    }
    return classes[status] || 'bg-gray-500'
}

const getMethodLabel = (method) => {
    const labels = {
        card: 'Carte bancaire',
        bank_transfer: 'Virement',
        sepa: 'Prelevement SEPA',
        cash: 'Especes',
        check: 'Cheque',
    }
    return labels[method] || method
}

const getPaymentMethodIcon = (method) => {
    const icons = {
        card: CreditCardIcon,
        bank_transfer: BuildingLibraryIcon,
        sepa: BuildingLibraryIcon,
        cash: BanknotesIcon,
        check: ReceiptPercentIcon,
    }
    return icons[method] || CreditCardIcon
}

const getDaysOverdue = (dueDate) => {
    if (!dueDate) return 0
    const due = new Date(dueDate)
    const now = new Date()
    const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24))
    return Math.max(0, diff)
}

const payInvoice = () => {
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(50)
    }
    router.visit(route('mobile.pay', { invoice_id: props.invoice.id }))
}

const shareInvoice = async () => {
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(30)
    }

    if (navigator.share) {
        try {
            await navigator.share({
                title: `Facture ${props.invoice.invoice_number}`,
                text: `Facture ${props.invoice.invoice_number} - ${formatCurrency(props.invoice.total)}`,
                url: window.location.href,
            })
        } catch (err) {
            console.log('Share cancelled or failed')
        }
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href)
    }
}
</script>
