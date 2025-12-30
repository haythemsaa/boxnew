<template>
    <MobileLayout title="Mes Factures">
        <!-- Stats Summary with Animated Cards -->
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div
                class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5"
            >
                <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-primary-100 to-primary-50 dark:from-primary-900/30 dark:to-primary-800/20 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-primary-500/30">
                        <DocumentTextIcon class="w-5 h-5 text-white" />
                    </div>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Total</p>
                </div>
            </div>
            <div
                class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5"
            >
                <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-amber-100 to-amber-50 dark:from-amber-900/30 dark:to-amber-800/20 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-amber-500/30">
                        <ClockIcon class="w-5 h-5 text-white" />
                    </div>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.pending }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">En attente</p>
                </div>
            </div>
            <div
                class="relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl p-4 text-center shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5"
            >
                <div class="absolute top-0 right-0 w-16 h-16 bg-gradient-to-br from-red-100 to-red-50 dark:from-red-900/30 dark:to-red-800/20 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl flex items-center justify-center mx-auto mb-2 shadow-lg shadow-red-500/30">
                        <ExclamationCircleIcon class="w-5 h-5 text-white" />
                    </div>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.overdue }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">En retard</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs with Pill Design -->
        <div class="flex space-x-2 mb-5 overflow-x-auto pb-2 scrollbar-hide -mx-4 px-4">
            <button
                v-for="filter in filters"
                :key="filter.value"
                @click="setActiveFilter(filter.value)"
                class="relative px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-300 transform active:scale-95"
                :class="[
                    activeFilter === filter.value
                        ? `${filter.activeClass} text-white shadow-lg ${filter.shadowClass}`
                        : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                ]"
            >
                <span class="flex items-center">
                    <component :is="filter.icon" class="w-4 h-4 mr-1.5" />
                    {{ filter.label }}
                    <span
                        v-if="filter.count > 0"
                        class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold"
                        :class="activeFilter === filter.value ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                    >
                        {{ filter.count }}
                    </span>
                </span>
            </button>
        </div>

        <!-- Search with Enhanced Design -->
        <div class="relative mb-5">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <MagnifyingGlassIcon class="w-5 h-5 text-gray-400" />
            </div>
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher une facture..."
                class="w-full pl-12 pr-12 py-3.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all duration-200 text-gray-900 dark:text-white placeholder-gray-400"
            />
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 scale-75"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-75"
            >
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute inset-y-0 right-0 pr-4 flex items-center"
                >
                    <XCircleIcon class="w-5 h-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" />
                </button>
            </Transition>
        </div>

        <!-- Empty State with Animation -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="filteredInvoices.length === 0" class="text-center py-16">
                <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                    <DocumentTextIcon class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                </div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Aucune facture</h3>
                <p class="text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
                    {{ searchQuery ? 'Aucun resultat pour votre recherche' : 'Aucune facture disponible pour le moment' }}
                </p>
            </div>
        </Transition>

        <!-- Invoice List with Staggered Animation -->
        <TransitionGroup
            v-if="filteredInvoices.length > 0"
            tag="div"
            class="space-y-4"
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-4"
            move-class="transition-all duration-500"
        >
            <div
                v-for="(invoice, index) in filteredInvoices"
                :key="invoice.id"
                :style="{ transitionDelay: `${index * 50}ms` }"
            >
                <Link
                    :href="route('mobile.invoices.show', invoice.id)"
                    class="block bg-white dark:bg-gray-800 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl active:scale-[0.98] transition-all duration-300"
                >
                    <!-- Status Indicator Bar -->
                    <div
                        class="h-1"
                        :class="getStatusBarClass(invoice.status)"
                    ></div>

                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center">
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center mr-4 shadow-lg transition-transform duration-300"
                                    :class="getStatusGradientClass(invoice.status)"
                                >
                                    <component
                                        :is="getStatusIcon(invoice.status)"
                                        class="w-7 h-7 text-white"
                                    />
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-lg">{{ invoice.invoice_number }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center mt-0.5">
                                        <CalendarDaysIcon class="w-4 h-4 mr-1" />
                                        {{ formatDate(invoice.invoice_date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.total) }}</p>
                                <span
                                    class="inline-flex items-center text-xs px-2.5 py-1 rounded-full font-semibold mt-1"
                                    :class="getStatusBadgeClass(invoice.status)"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                                        :class="getStatusDotClass(invoice.status)"
                                    ></span>
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Due date warning for pending/overdue -->
                        <Transition
                            enter-active-class="transition-all duration-300"
                            enter-from-class="opacity-0 -translate-y-2"
                            enter-to-class="opacity-100 translate-y-0"
                        >
                            <div
                                v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                                class="mt-4 p-3 rounded-xl flex items-center justify-between"
                                :class="invoice.status === 'overdue' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-amber-50 dark:bg-amber-900/20'"
                            >
                                <div class="flex items-center text-sm">
                                    <CalendarIcon
                                        class="w-5 h-5 mr-2"
                                        :class="invoice.status === 'overdue' ? 'text-red-500' : 'text-amber-500'"
                                    />
                                    <span :class="invoice.status === 'overdue' ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300'">
                                        Echeance: <span class="font-semibold">{{ formatDate(invoice.due_date) }}</span>
                                    </span>
                                </div>
                                <div
                                    v-if="invoice.status === 'overdue'"
                                    class="flex items-center text-red-600 dark:text-red-400 text-sm font-semibold bg-red-100 dark:bg-red-900/30 px-2.5 py-1 rounded-lg"
                                >
                                    <ExclamationTriangleIcon class="w-4 h-4 mr-1" />
                                    {{ getDaysOverdue(invoice.due_date) }}j
                                </div>
                            </div>
                        </Transition>

                        <!-- Related box info -->
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center">
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center mr-2">
                                    <CubeIcon class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                </div>
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ invoice.contract?.box?.name || 'N/A' }}</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400 ml-auto" />
                        </div>
                    </div>

                    <!-- Action bar for unpaid invoices -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                    >
                        <div
                            v-if="invoice.status === 'sent' || invoice.status === 'overdue'"
                            class="px-5 py-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between"
                            :class="invoice.status === 'overdue' ? 'bg-gradient-to-r from-red-50 to-rose-50 dark:from-red-900/10 dark:to-rose-900/10' : 'bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-800'"
                        >
                            <div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide font-medium">Solde restant</span>
                                <p class="text-lg font-bold" :class="invoice.status === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'">
                                    {{ formatCurrency(invoice.balance) }}
                                </p>
                            </div>
                            <button
                                @click.prevent="payInvoice(invoice)"
                                class="group px-5 py-2.5 font-semibold rounded-xl flex items-center transition-all duration-300 transform hover:scale-105 active:scale-95 shadow-lg"
                                :class="invoice.status === 'overdue'
                                    ? 'bg-gradient-to-r from-red-500 to-rose-600 text-white shadow-red-500/30 hover:shadow-red-500/50'
                                    : 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-primary-500/30 hover:shadow-primary-500/50'"
                            >
                                <CreditCardIcon class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" />
                                Payer
                                <ArrowRightIcon class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
                            </button>
                        </div>
                    </Transition>
                </Link>
            </div>
        </TransitionGroup>

        <!-- Total Outstanding Fixed Banner -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 translate-y-full"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-300 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-full"
        >
            <div v-if="totalOutstanding > 0" class="fixed bottom-24 left-4 right-4 z-40">
                <div class="bg-gradient-to-r from-primary-600 via-primary-700 to-indigo-700 rounded-2xl p-5 shadow-2xl shadow-primary-500/40 flex items-center justify-between">
                    <div class="text-white">
                        <p class="text-sm font-medium text-primary-200">Total a payer</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(totalOutstanding) }}</p>
                        <p class="text-xs text-primary-200 mt-0.5">{{ pendingCount }} facture(s) en attente</p>
                    </div>
                    <Link
                        :href="route('mobile.pay')"
                        class="group px-6 py-3 bg-white text-primary-600 font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 active:scale-95 flex items-center"
                    >
                        <BanknotesIcon class="w-5 h-5 mr-2" />
                        Tout payer
                        <ArrowRightIcon class="w-4 h-4 ml-2 transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentTextIcon,
    MagnifyingGlassIcon,
    CalendarIcon,
    CalendarDaysIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    CubeIcon,
    CreditCardIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    ChevronRightIcon,
    ArrowRightIcon,
    BanknotesIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: Array,
    stats: Object,
})

const searchQuery = ref('')
const activeFilter = ref('all')

const setActiveFilter = (value) => {
    activeFilter.value = value
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(30)
    }
}

const filters = computed(() => [
    {
        value: 'all',
        label: 'Toutes',
        activeClass: 'bg-gradient-to-r from-primary-500 to-primary-600',
        shadowClass: 'shadow-primary-500/30',
        icon: Squares2X2Icon,
        count: props.stats?.total || 0
    },
    {
        value: 'sent',
        label: 'En attente',
        activeClass: 'bg-gradient-to-r from-amber-400 to-orange-500',
        shadowClass: 'shadow-amber-500/30',
        icon: ClockIcon,
        count: props.stats?.pending || 0
    },
    {
        value: 'overdue',
        label: 'En retard',
        activeClass: 'bg-gradient-to-r from-red-500 to-rose-600',
        shadowClass: 'shadow-red-500/30',
        icon: ExclamationCircleIcon,
        count: props.stats?.overdue || 0
    },
    {
        value: 'paid',
        label: 'Payees',
        activeClass: 'bg-gradient-to-r from-green-500 to-emerald-600',
        shadowClass: 'shadow-green-500/30',
        icon: CheckCircleIcon,
        count: props.stats?.paid || 0
    },
])

const filteredInvoices = computed(() => {
    let result = props.invoices || []

    if (activeFilter.value !== 'all') {
        result = result.filter(i => i.status === activeFilter.value)
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(i =>
            i.invoice_number?.toLowerCase().includes(query) ||
            i.contract?.box?.name?.toLowerCase().includes(query)
        )
    }

    return result
})

const totalOutstanding = computed(() => {
    return props.invoices
        ?.filter(i => i.status === 'sent' || i.status === 'overdue')
        .reduce((sum, i) => sum + parseFloat(i.balance || 0), 0) || 0
})

const pendingCount = computed(() => {
    return props.invoices?.filter(i => i.status === 'sent' || i.status === 'overdue').length || 0
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
        month: 'short',
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

const getDaysOverdue = (dueDate) => {
    if (!dueDate) return 0
    const due = new Date(dueDate)
    const now = new Date()
    const diff = Math.floor((now - due) / (1000 * 60 * 60 * 24))
    return Math.max(0, diff)
}

const payInvoice = (invoice) => {
    // Haptic feedback
    if (navigator.vibrate) {
        navigator.vibrate(50)
    }
    router.visit(route('mobile.pay', { invoice_id: invoice.id }))
}
</script>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
