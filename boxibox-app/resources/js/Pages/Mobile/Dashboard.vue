<template>
    <MobileLayout>
        <!-- Welcome Banner with Glassmorphism -->
        <div class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 rounded-3xl p-6 text-white mb-6 shadow-2xl shadow-primary-500/30">
            <!-- Background decoration -->
            <div class="absolute top-0 right-0 w-40 h-40 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-primary-400/20 rounded-full translate-y-1/2 -translate-x-1/2 blur-xl"></div>

            <div class="relative">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-primary-200 text-sm font-medium">{{ greeting }},</p>
                        <h2 class="text-2xl font-bold mt-1">{{ customerName }}</h2>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-xl ring-2 ring-white/30">
                        <span class="text-2xl font-bold">{{ userInitial }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                        <p class="text-primary-200 text-xs font-medium mb-1">Solde en cours</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(stats.outstanding_balance) }}</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4">
                        <p class="text-primary-200 text-xs font-medium mb-1">Prochain paiement</p>
                        <p class="text-2xl font-bold">{{ nextPaymentDate }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weather & Notifications Quick Row -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <!-- Weather Widget -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Météo</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ weather.temp }}°</p>
                    </div>
                    <div class="text-3xl">{{ weather.icon }}</div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ weather.description }}</p>
            </div>

            <!-- Notifications Widget -->
            <Link
                :href="route('mobile.notifications')"
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Notifications</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ unreadNotifications }}</p>
                    </div>
                    <div class="relative">
                        <BellIcon class="w-8 h-8 text-primary-500" />
                        <span v-if="unreadNotifications > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                            <span class="text-[10px] text-white font-bold">{{ unreadNotifications > 9 ? '9+' : unreadNotifications }}</span>
                        </span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">non lues</p>
            </Link>
        </div>

        <!-- Promo Banner (if any) -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="promoBanner" class="mb-6">
                <div class="relative overflow-hidden bg-gradient-to-r from-violet-500 via-purple-500 to-fuchsia-500 rounded-2xl p-5 text-white shadow-xl shadow-purple-500/20">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                    <div class="relative flex items-center">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <GiftIcon class="w-7 h-7" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold">{{ promoBanner.title }}</h4>
                            <p class="text-purple-100 text-sm mt-0.5">{{ promoBanner.description }}</p>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-white/60" />
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Quick Stats with Modern Cards -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg shadow-green-500/30">
                        <DocumentCheckIcon class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-xs font-semibold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30 px-2 py-1 rounded-full">Actifs</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.active_contracts }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Contrats</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-600 rounded-xl flex items-center justify-center shadow-lg shadow-orange-500/30">
                        <ClockIcon class="w-6 h-6 text-white" />
                    </div>
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 px-2 py-1 rounded-full">En attente</span>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ stats.pending_invoices }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Factures</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                        <CurrencyEuroIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_paid) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total payé</p>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-400 to-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-purple-500/30">
                        <CubeIcon class="w-6 h-6 text-white" />
                    </div>
                </div>
                <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ activeContracts?.length || 0 }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Mes box</p>
            </div>
        </div>

        <!-- Quick Actions with Modern Design -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
            <div class="grid grid-cols-4 gap-3">
                <Link
                    v-for="action in quickActions"
                    :key="action.route"
                    :href="route(action.route)"
                    class="flex flex-col items-center bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl active:scale-95 transition-all duration-200"
                >
                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center mb-3 shadow-lg"
                        :class="action.bgGradient"
                    >
                        <component :is="action.icon" class="w-7 h-7 text-white" />
                    </div>
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 text-center">{{ action.label }}</span>
                </Link>
            </div>
        </div>

        <!-- Storage Tips Carousel -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Conseils stockage</h3>
            <div class="relative overflow-hidden">
                <div class="flex gap-4 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide -mx-4 px-4">
                    <div
                        v-for="tip in storageTips"
                        :key="tip.id"
                        class="flex-shrink-0 w-[280px] bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 snap-center"
                    >
                        <div class="flex items-start">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center mr-3 flex-shrink-0"
                                :class="tip.bgColor"
                            >
                                <component :is="tip.icon" class="w-5 h-5 text-white" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ tip.title }}</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ tip.description }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert for overdue invoices with animation -->
        <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
        >
            <div v-if="overdueInvoices?.length > 0" class="mb-6">
                <div class="bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl p-5 text-white shadow-xl shadow-red-500/30">
                    <div class="flex items-start">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mr-4 flex-shrink-0">
                            <ExclamationTriangleIcon class="w-7 h-7" />
                        </div>
                        <div class="flex-1">
                            <h4 class="font-bold text-lg">Attention !</h4>
                            <p class="text-red-100 mt-1">
                                {{ overdueInvoices.length }} facture(s) en retard
                            </p>
                            <p class="text-2xl font-bold mt-2">{{ formatCurrency(overdueTotal) }}</p>
                            <Link
                                :href="route('mobile.pay')"
                                class="inline-flex items-center mt-3 px-4 py-2 bg-white text-red-600 rounded-xl font-semibold text-sm hover:bg-red-50 transition"
                            >
                                Payer maintenant
                                <ArrowRightIcon class="w-4 h-4 ml-2" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- My Boxes with Modern Cards -->
        <div class="mb-6" v-if="activeContracts?.length > 0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Mes Box</h3>
                <Link :href="route('mobile.boxes')" class="text-sm text-primary-600 dark:text-primary-400 font-semibold flex items-center hover:text-primary-700">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1" />
                </Link>
            </div>

            <div class="space-y-4">
                <Link
                    v-for="contract in activeContracts.slice(0, 2)"
                    :key="contract.id"
                    :href="route('mobile.contracts.show', contract.id)"
                    class="block bg-white dark:bg-gray-800 rounded-2xl p-5 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 hover:shadow-xl active:scale-[0.98] transition-all duration-200"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl flex items-center justify-center mr-4 shadow-lg shadow-primary-500/30">
                                <CubeIcon class="w-7 h-7 text-white" />
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg">{{ contract.box?.name }}</h4>
                                <div class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    <MapPinIcon class="w-4 h-4 mr-1" />
                                    {{ contract.box?.site?.name }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ contract.monthly_price }}€</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400">/mois</span>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <Square3Stack3DIcon class="w-4 h-4 mr-1" />
                                {{ contract.box?.area || 'N/A' }} m²
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span>
                                Actif
                            </span>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Invoices with Timeline Design -->
        <div class="mb-6" v-if="recentInvoices?.length > 0">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Factures récentes</h3>
                <Link :href="route('mobile.invoices')" class="text-sm text-primary-600 dark:text-primary-400 font-semibold flex items-center hover:text-primary-700">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1" />
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                <Link
                    v-for="(invoice, index) in recentInvoices.slice(0, 3)"
                    :key="invoice.id"
                    :href="route('mobile.invoices.show', invoice.id)"
                    class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                    :class="{ 'border-t border-gray-100 dark:border-gray-700': index > 0 }"
                >
                    <div class="flex items-center">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mr-4"
                            :class="getStatusBgClass(invoice.status)"
                        >
                            <component
                                :is="getStatusIcon(invoice.status)"
                                class="w-6 h-6"
                                :class="getStatusIconClass(invoice.status)"
                            />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(invoice.invoice_date) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-right mr-3">
                            <p class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(invoice.total) }}</p>
                            <span
                                class="text-xs px-2.5 py-1 rounded-full font-semibold"
                                :class="getStatusBadgeClass(invoice.status)"
                            >
                                {{ getStatusLabel(invoice.status) }}
                            </span>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Recent Payments -->
        <div v-if="recentPayments?.length > 0" class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Derniers paiements</h3>
                <Link :href="route('mobile.payments')" class="text-sm text-primary-600 dark:text-primary-400 font-semibold flex items-center hover:text-primary-700">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1" />
                </Link>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 overflow-hidden">
                <Link
                    v-for="(payment, index) in recentPayments.slice(0, 3)"
                    :key="payment.id"
                    :href="route('mobile.payments.show', payment.id)"
                    class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 active:bg-gray-100 dark:active:bg-gray-700 transition-colors"
                    :class="{ 'border-t border-gray-100 dark:border-gray-700': index > 0 }"
                >
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-600 rounded-xl flex items-center justify-center mr-4 shadow-lg shadow-green-500/20">
                            <CheckCircleIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ payment.payment_number }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(payment.paid_at) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="text-right mr-3">
                            <p class="font-bold text-green-600 dark:text-green-400">+{{ formatCurrency(payment.amount) }}</p>
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ getMethodLabel(payment.method) }}</span>
                        </div>
                        <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Quick Help Section -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Besoin d'aide ?</h3>
                <Link :href="route('mobile.help')" class="text-sm text-primary-600 dark:text-primary-400 font-semibold flex items-center hover:text-primary-700">
                    Voir tout
                    <ChevronRightIcon class="w-4 h-4 ml-1" />
                </Link>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <Link
                    :href="route('mobile.support')"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 flex items-center hover:shadow-xl transition-all"
                >
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl flex items-center justify-center mr-3">
                        <ChatBubbleLeftRightIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Chat</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Support en ligne</p>
                    </div>
                </Link>
                <a
                    href="tel:+33100000000"
                    class="bg-white dark:bg-gray-800 rounded-2xl p-4 shadow-lg shadow-gray-200/50 dark:shadow-none border border-gray-100 dark:border-gray-700 flex items-center hover:shadow-xl transition-all"
                >
                    <div class="w-10 h-10 bg-gradient-to-br from-green-400 to-green-600 rounded-xl flex items-center justify-center mr-3">
                        <PhoneIcon class="w-5 h-5 text-white" />
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white text-sm">Appeler</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">01 00 00 00 00</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!activeContracts?.length && !recentInvoices?.length" class="text-center py-12">
            <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <CubeIcon class="w-12 h-12 text-gray-400" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Bienvenue sur Boxibox !</h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Vous n'avez pas encore de box. Réservez votre premier espace de stockage.</p>
            <Link
                :href="route('mobile.reserve')"
                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/30 hover:shadow-xl transition-all"
            >
                <PlusCircleIcon class="w-5 h-5 mr-2" />
                Réserver un box
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { computed, ref, reactive, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    DocumentCheckIcon,
    ClockIcon,
    CheckCircleIcon,
    CubeIcon,
    BanknotesIcon,
    PlusCircleIcon,
    KeyIcon,
    ChatBubbleLeftRightIcon,
    ChevronRightIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    MapPinIcon,
    ArrowRightIcon,
    CurrencyEuroIcon,
    ExclamationCircleIcon,
    Square3Stack3DIcon,
    BellIcon,
    GiftIcon,
    PhoneIcon,
    LightBulbIcon,
    ShieldCheckIcon,
    ArchiveBoxIcon,
    SunIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    customer: Object,
    activeContracts: Array,
    recentInvoices: Array,
    recentPayments: Array,
    overdueInvoices: Array,
    stats: Object,
    notifications: {
        type: Array,
        default: () => []
    },
    promo: {
        type: Object,
        default: null
    }
})

// Dynamic greeting based on time of day
const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Bonjour'
    if (hour < 18) return 'Bon après-midi'
    return 'Bonsoir'
})

// Weather data (mock - would be fetched from API in production)
const weather = reactive({
    temp: 12,
    icon: '☀️',
    description: 'Ensoleillé'
})

// Update weather based on time (mock)
onMounted(() => {
    const hour = new Date().getHours()
    if (hour >= 6 && hour < 12) {
        weather.temp = 14
        weather.icon = '🌤️'
        weather.description = 'Nuageux'
    } else if (hour >= 12 && hour < 18) {
        weather.temp = 18
        weather.icon = '☀️'
        weather.description = 'Ensoleillé'
    } else if (hour >= 18 && hour < 21) {
        weather.temp = 15
        weather.icon = '🌅'
        weather.description = 'Coucher de soleil'
    } else {
        weather.temp = 10
        weather.icon = '🌙'
        weather.description = 'Nuit claire'
    }
})

// Unread notifications count
const unreadNotifications = computed(() => {
    return props.notifications?.filter(n => !n.read_at).length || 3
})

// Promo banner
const promoBanner = computed(() => {
    if (props.promo) return props.promo
    // Default promo if none provided
    return {
        title: '-20% sur le 2ème box',
        description: 'Offre valable jusqu\'au 31 janvier'
    }
})

// Storage tips carousel
const storageTips = [
    {
        id: 1,
        icon: LightBulbIcon,
        bgColor: 'bg-gradient-to-br from-amber-400 to-orange-500',
        title: 'Optimisez l\'espace',
        description: 'Utilisez des étagères pour maximiser le volume disponible'
    },
    {
        id: 2,
        icon: ShieldCheckIcon,
        bgColor: 'bg-gradient-to-br from-blue-400 to-indigo-600',
        title: 'Protégez vos biens',
        description: 'Utilisez des housses anti-poussière pour vos meubles'
    },
    {
        id: 3,
        icon: ArchiveBoxIcon,
        bgColor: 'bg-gradient-to-br from-green-400 to-emerald-600',
        title: 'Étiquetez tout',
        description: 'Numérotez vos cartons et tenez un inventaire'
    },
    {
        id: 4,
        icon: SunIcon,
        bgColor: 'bg-gradient-to-br from-purple-400 to-violet-600',
        title: 'Aérez régulièrement',
        description: 'Visitez votre box de temps en temps pour l\'aérer'
    }
]

// Quick actions configuration
const quickActions = [
    {
        route: 'mobile.pay',
        icon: BanknotesIcon,
        label: 'Payer',
        bgGradient: 'bg-gradient-to-br from-green-400 to-emerald-600 shadow-green-500/30'
    },
    {
        route: 'mobile.reserve',
        icon: PlusCircleIcon,
        label: 'Reserver',
        bgGradient: 'bg-gradient-to-br from-blue-400 to-indigo-600 shadow-blue-500/30'
    },
    {
        route: 'mobile.access',
        icon: KeyIcon,
        label: 'Acces',
        bgGradient: 'bg-gradient-to-br from-purple-400 to-violet-600 shadow-purple-500/30'
    },
    {
        route: 'mobile.support',
        icon: ChatBubbleLeftRightIcon,
        label: 'Support',
        bgGradient: 'bg-gradient-to-br from-orange-400 to-red-500 shadow-orange-500/30'
    },
]

const customerName = computed(() => {
    if (!props.customer) return 'Client'
    if (props.customer.type === 'company') {
        return props.customer.company_name
    }
    return `${props.customer.first_name} ${props.customer.last_name}`
})

const userInitial = computed(() => {
    if (!props.customer) return 'C'
    return props.customer.first_name?.charAt(0)?.toUpperCase() || 'C'
})

const nextPaymentDate = computed(() => {
    if (props.recentInvoices?.length > 0) {
        const pendingInvoice = props.recentInvoices.find(i => i.status === 'sent')
        if (pendingInvoice?.due_date) {
            return formatDate(pendingInvoice.due_date)
        }
    }
    return '-'
})

const overdueTotal = computed(() => {
    return props.overdueInvoices?.reduce((sum, inv) => sum + parseFloat(inv.total || 0), 0) || 0
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    })
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        sent: 'A payer',
        paid: 'Payee',
        overdue: 'En retard',
        cancelled: 'Annulee',
    }
    return labels[status] || status
}

const getStatusBgClass = (status) => {
    const classes = {
        paid: 'bg-gradient-to-br from-green-400 to-emerald-600',
        sent: 'bg-gradient-to-br from-amber-400 to-orange-500',
        overdue: 'bg-gradient-to-br from-red-400 to-rose-600',
        draft: 'bg-gradient-to-br from-gray-400 to-gray-600',
    }
    return classes[status] || 'bg-gradient-to-br from-gray-400 to-gray-600'
}

const getStatusIcon = (status) => {
    const icons = {
        paid: CheckCircleIcon,
        sent: ClockIcon,
        overdue: ExclamationCircleIcon,
        draft: DocumentTextIcon,
    }
    return icons[status] || DocumentTextIcon
}

const getStatusIconClass = (status) => {
    return 'text-white'
}

const getStatusBadgeClass = (status) => {
    const classes = {
        paid: 'bg-green-100 text-green-700',
        sent: 'bg-amber-100 text-amber-700',
        overdue: 'bg-red-100 text-red-700',
        draft: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getMethodLabel = (method) => {
    const labels = {
        card: 'Carte',
        bank_transfer: 'Virement',
        sepa: 'SEPA',
        cash: 'Especes',
        check: 'Cheque',
    }
    return labels[method] || method
}
</script>
