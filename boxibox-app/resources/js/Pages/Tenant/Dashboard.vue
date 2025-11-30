<template>
    <TenantLayout title="Dashboard">
        <!-- Welcome Banner -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-700 to-indigo-800 p-8 mb-8 text-white">
            <div class="absolute inset-0 bg-grid-white/10"></div>
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-3xl"></div>

            <div class="relative flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold mb-2">
                        Bonjour, {{ $page.props.auth.user?.name?.split(' ')[0] || 'Admin' }} !
                    </h1>
                    <p class="text-primary-100 text-sm md:text-base">
                        Voici un apercu de votre activite aujourd'hui. Vous avez
                        <span class="font-semibold text-white">{{ stats.pending_actions || 0 }}</span> actions en attente.
                    </p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <Link
                        :href="route('tenant.contracts.create')"
                        class="inline-flex items-center px-4 py-2 bg-white text-primary-700 rounded-lg font-medium text-sm hover:bg-primary-50 transition-colors shadow-lg shadow-primary-900/20"
                    >
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouveau contrat
                    </Link>
                    <Link
                        :href="route('tenant.bulk-invoicing.index')"
                        class="inline-flex items-center px-4 py-2 bg-white/10 text-white rounded-lg font-medium text-sm hover:bg-white/20 transition-colors border border-white/20"
                    >
                        <DocumentDuplicateIcon class="w-4 h-4 mr-2" />
                        Facturation
                    </Link>
                </div>
            </div>
        </div>

        <!-- Key Metrics Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <StatsCard
                title="Revenu mensuel"
                :value="formatCurrency(stats.monthly_revenue)"
                :subtitle="'vs ' + formatCurrency(stats.last_month_revenue) + ' dernier mois'"
                :trend="calculateTrend(stats.monthly_revenue, stats.last_month_revenue)"
                icon="banknotes"
                color="emerald"
                variant="gradient"
            />
            <StatsCard
                title="Taux d'occupation"
                :value="stats.occupation_rate + '%'"
                :subtitle="stats.occupied_boxes + '/' + stats.total_boxes + ' boxes'"
                :progress="stats.occupation_rate"
                progress-label="Capacite utilisee"
                icon="chart"
                color="purple"
            />
            <StatsCard
                title="Clients actifs"
                :value="stats.active_customers"
                :subtitle="'+' + (stats.new_customers_this_month || 0) + ' ce mois'"
                :trend="stats.new_customers_this_month > 0 ? 12 : 0"
                icon="users"
                color="blue"
            />
            <StatsCard
                title="Factures en retard"
                :value="stats.overdue_invoices"
                :subtitle="formatCurrency(stats.overdue_amount) + ' a recouvrer'"
                icon="clock"
                :color="stats.overdue_invoices > 0 ? 'red' : 'green'"
            />
        </div>

        <!-- Advanced KPIs Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">RevPAU</span>
                    <ChartBarSquareIcon class="w-4 h-4 text-indigo-500" />
                </div>
                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(stats.revpau) }}</p>
                <p class="text-xs text-gray-500">Par box/mois</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">Valeur moy.</span>
                    <CurrencyEuroIcon class="w-4 h-4 text-emerald-500" />
                </div>
                <p class="text-xl font-bold text-gray-900">{{ formatCurrency(stats.average_contract_value) }}</p>
                <p class="text-xs text-gray-500">Par contrat</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">Churn</span>
                    <ArrowTrendingDownIcon class="w-4 h-4" :class="stats.churn_rate > 5 ? 'text-red-500' : 'text-green-500'" />
                </div>
                <p class="text-xl font-bold" :class="stats.churn_rate > 5 ? 'text-red-600' : 'text-green-600'">{{ stats.churn_rate }}%</p>
                <p class="text-xs text-gray-500">Ce mois</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">Duree moy.</span>
                    <CalendarDaysIcon class="w-4 h-4 text-purple-500" />
                </div>
                <p class="text-xl font-bold text-gray-900">{{ stats.avg_contract_duration }} mois</p>
                <p class="text-xs text-gray-500">Contrats actifs</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">Prospects</span>
                    <UserGroupIcon class="w-4 h-4 text-amber-500" />
                </div>
                <p class="text-xl font-bold text-gray-900">{{ stats.hot_prospects || 0 }}</p>
                <p class="text-xs text-gray-500">{{ stats.total_prospects || 0 }} total</p>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-gray-500 uppercase">Conversion</span>
                    <ArrowPathIcon class="w-4 h-4 text-blue-500" />
                </div>
                <p class="text-xl font-bold text-blue-600">{{ stats.conversion_rate || 0 }}%</p>
                <p class="text-xs text-gray-500">Prospects > Clients</p>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Evolution des revenus</h3>
                        <p class="text-sm text-gray-500">6 derniers mois</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            v-for="period in ['6M', '12M', 'YTD']"
                            :key="period"
                            :class="[
                                'px-3 py-1 text-xs font-medium rounded-lg transition-colors',
                                selectedPeriod === period
                                    ? 'bg-primary-100 text-primary-700'
                                    : 'text-gray-500 hover:bg-gray-100'
                            ]"
                            @click="selectedPeriod = period"
                        >
                            {{ period }}
                        </button>
                    </div>
                </div>
                <div class="h-72">
                    <Line :data="revenueChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Occupation Donut -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Repartition des boxes</h3>
                <div class="h-56 flex items-center justify-center">
                    <Doughnut :data="occupationChartData" :options="doughnutOptions" />
                </div>
                <div class="mt-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 mr-2"></span>
                            Occupes
                        </span>
                        <span class="font-medium">{{ stats.occupied_boxes }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-gray-300 mr-2"></span>
                            Disponibles
                        </span>
                        <span class="font-medium">{{ stats.available_boxes }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-amber-500 mr-2"></span>
                            Reserves
                        </span>
                        <span class="font-medium">{{ stats.reserved_boxes || 0 }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <Link
                v-for="action in quickActions"
                :key="action.name"
                :href="action.href"
                class="group bg-white rounded-xl border border-gray-100 p-4 hover:shadow-lg hover:border-primary-200 hover:-translate-y-1 transition-all duration-300"
            >
                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3', action.bgColor]">
                    <component :is="action.icon" :class="['w-5 h-5', action.iconColor]" />
                </div>
                <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600 transition-colors">
                    {{ action.name }}
                </p>
                <p class="text-xs text-gray-500 mt-0.5">{{ action.description }}</p>
            </Link>
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- Recent Contracts -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Contrats recents</h3>
                    <Link :href="route('tenant.contracts.index')" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Voir tout
                    </Link>
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="contract in recentContracts"
                        :key="contract.id"
                        class="px-6 py-4 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center text-white font-medium text-sm">
                                    {{ contract.customer_name?.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ contract.customer_name }}</p>
                                    <p class="text-xs text-gray-500">{{ contract.box_name }} - {{ contract.contract_number }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(contract.monthly_price) }}</p>
                                <span :class="getStatusBadgeClass(contract.status)" class="text-xs px-2 py-0.5 rounded-full">
                                    {{ getStatusLabel(contract.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-if="!recentContracts || recentContracts.length === 0" class="px-6 py-8 text-center text-gray-500 text-sm">
                        Aucun contrat recent
                    </div>
                </div>
            </div>

            <!-- Expiring Contracts Alert -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <ExclamationTriangleIcon class="w-5 h-5 text-amber-500" />
                        <h3 class="font-semibold text-gray-900">Contrats expirant</h3>
                    </div>
                    <span v-if="expiringContracts?.length" class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ expiringContracts.length }}
                    </span>
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="contract in expiringContracts?.slice(0, 5)"
                        :key="contract.id"
                        class="px-6 py-4 hover:bg-amber-50/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ contract.customer_name }}</p>
                                <p class="text-xs text-gray-500">{{ contract.box_name }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-amber-600">{{ contract.days_until_expiry }}j</p>
                                <p class="text-xs text-gray-500">{{ contract.end_date }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="!expiringContracts || expiringContracts.length === 0" class="px-6 py-8 text-center text-gray-500 text-sm">
                        Aucun contrat expirant prochainement
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Paiements recents</h3>
                    <Link :href="route('tenant.invoices.index')" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Voir tout
                    </Link>
                </div>
                <div class="divide-y divide-gray-50">
                    <div
                        v-for="payment in recentPayments"
                        :key="payment.id"
                        class="px-6 py-4 hover:bg-emerald-50/50 transition-colors"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                    <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ payment.customer_name }}</p>
                                    <p class="text-xs text-gray-500">{{ payment.method }} - {{ payment.paid_at }}</p>
                                </div>
                            </div>
                            <p class="text-sm font-bold text-emerald-600">+{{ formatCurrency(payment.amount) }}</p>
                        </div>
                    </div>
                    <div v-if="!recentPayments || recentPayments.length === 0" class="px-6 py-8 text-center text-gray-500 text-sm">
                        Aucun paiement recent
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Invoices Alert -->
        <div v-if="overdueInvoices && overdueInvoices.length > 0" class="bg-gradient-to-r from-red-50 to-red-100 rounded-2xl border border-red-200 p-6 mb-8">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0 w-12 h-12 bg-red-500 rounded-xl flex items-center justify-center">
                    <ExclamationCircleIcon class="w-6 h-6 text-white" />
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-red-900 mb-1">
                        {{ overdueInvoices.length }} facture(s) en retard
                    </h3>
                    <p class="text-sm text-red-700 mb-4">
                        Total de {{ formatCurrency(overdueInvoices.reduce((sum, inv) => sum + inv.total, 0)) }} a recouvrer. Action requise.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <Link
                            :href="route('tenant.reminders.index')"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 transition-colors"
                        >
                            <BellAlertIcon class="w-4 h-4 mr-2" />
                            Envoyer des relances
                        </Link>
                        <Link
                            :href="route('tenant.invoices.index') + '?status=overdue'"
                            class="inline-flex items-center px-4 py-2 bg-white text-red-700 rounded-lg text-sm font-medium hover:bg-red-50 transition-colors border border-red-200"
                        >
                            Voir les factures
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Summary -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-semibold text-gray-900">Resume du mois</h3>
                <span class="text-sm text-gray-500">{{ currentMonthName }}</span>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center p-4 bg-emerald-50 rounded-xl">
                    <p class="text-3xl font-bold text-emerald-600">{{ formatCurrency(monthlySummary?.revenue || 0) }}</p>
                    <p class="text-sm text-emerald-700 mt-1">Revenus encaisses</p>
                </div>
                <div class="text-center p-4 bg-blue-50 rounded-xl">
                    <p class="text-3xl font-bold text-blue-600">{{ monthlySummary?.invoices_count || 0 }}</p>
                    <p class="text-sm text-blue-700 mt-1">Factures emises</p>
                </div>
                <div class="text-center p-4 bg-purple-50 rounded-xl">
                    <p class="text-3xl font-bold text-purple-600">{{ monthlySummary?.new_contracts || 0 }}</p>
                    <p class="text-sm text-purple-700 mt-1">Nouveaux contrats</p>
                </div>
                <div class="text-center p-4 bg-indigo-50 rounded-xl">
                    <p class="text-3xl font-bold text-indigo-600">{{ monthlySummary?.new_customers || 0 }}</p>
                    <p class="text-sm text-indigo-700 mt-1">Nouveaux clients</p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import StatsCard from '@/Components/StatsCard.vue'
import { Line, Doughnut } from 'vue-chartjs'
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ArcElement,
} from 'chart.js'
import {
    PlusIcon,
    DocumentDuplicateIcon,
    UserPlusIcon,
    UsersIcon,
    ArchiveBoxIcon,
    DocumentTextIcon,
    ReceiptPercentIcon,
    CreditCardIcon,
    PencilSquareIcon,
    ChartBarIcon,
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    BellAlertIcon,
    ChartBarSquareIcon,
    CurrencyEuroIcon,
    ArrowTrendingDownIcon,
    CalendarDaysIcon,
    UserGroupIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ArcElement
)

const props = defineProps({
    stats: Object,
    monthlySummary: Object,
    revenueTrend: Array,
    recentContracts: Array,
    expiringContracts: Array,
    overdueInvoices: Array,
    recentPayments: Array,
})

const selectedPeriod = ref('6M')

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0)
}

const calculateTrend = (current, previous) => {
    if (!previous || previous === 0) return 0
    return Math.round(((current - previous) / previous) * 100)
}

const currentMonthName = computed(() => {
    return new Date().toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const quickActions = [
    {
        name: 'Nouveau client',
        description: 'Ajouter un client',
        href: route('tenant.customers.create'),
        icon: UserPlusIcon,
        bgColor: 'bg-primary-100',
        iconColor: 'text-primary-600',
    },
    {
        name: 'Plan des boxes',
        description: 'Vue visuelle',
        href: route('tenant.boxes.plan'),
        icon: ArchiveBoxIcon,
        bgColor: 'bg-purple-100',
        iconColor: 'text-purple-600',
    },
    {
        name: 'Facturation',
        description: 'Factures groupees',
        href: route('tenant.bulk-invoicing.index'),
        icon: DocumentDuplicateIcon,
        bgColor: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
    },
    {
        name: 'Mandats SEPA',
        description: 'Prelevements',
        href: route('tenant.sepa-mandates.index'),
        icon: CreditCardIcon,
        bgColor: 'bg-indigo-100',
        iconColor: 'text-indigo-600',
    },
    {
        name: 'Relances',
        description: 'Impayes',
        href: route('tenant.reminders.index'),
        icon: BellAlertIcon,
        bgColor: 'bg-amber-100',
        iconColor: 'text-amber-600',
    },
    {
        name: 'Signatures',
        description: 'Documents',
        href: route('tenant.signatures.index'),
        icon: PencilSquareIcon,
        bgColor: 'bg-pink-100',
        iconColor: 'text-pink-600',
    },
]

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700',
        pending_signature: 'bg-amber-100 text-amber-700',
        expired: 'bg-gray-100 text-gray-700',
        terminated: 'bg-red-100 text-red-700',
        draft: 'bg-blue-100 text-blue-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        pending_signature: 'En attente',
        expired: 'Expire',
        terminated: 'Resilie',
        draft: 'Brouillon',
    }
    return labels[status] || status
}

// Chart data
const revenueChartData = computed(() => ({
    labels: props.revenueTrend?.map(t => t.month) || ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun'],
    datasets: [
        {
            label: 'Revenus',
            data: props.revenueTrend?.map(t => t.revenue) || [0, 0, 0, 0, 0, 0],
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
        },
    ],
}))

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#fff',
            bodyColor: '#fff',
            padding: 12,
            cornerRadius: 8,
            displayColors: false,
            callbacks: {
                label: (context) => formatCurrency(context.raw),
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            ticks: {
                color: '#9ca3af',
            },
        },
        y: {
            grid: {
                color: '#f3f4f6',
            },
            ticks: {
                color: '#9ca3af',
                callback: (value) => formatCurrency(value),
            },
        },
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
}

const occupationChartData = computed(() => ({
    labels: ['Occupes', 'Disponibles', 'Reserves'],
    datasets: [
        {
            data: [
                props.stats?.occupied_boxes || 0,
                props.stats?.available_boxes || 0,
                props.stats?.reserved_boxes || 0,
            ],
            backgroundColor: ['#10b981', '#e5e7eb', '#f59e0b'],
            borderWidth: 0,
            hoverOffset: 4,
        },
    ],
}))

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#1f2937',
            titleColor: '#fff',
            bodyColor: '#fff',
            padding: 12,
            cornerRadius: 8,
        },
    },
}
</script>

<style>
.bg-grid-white\/10 {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='rgb(255 255 255 / 0.1)'%3e%3cpath d='M0 .5H31.5V32'/%3e%3c/svg%3e");
}
</style>
