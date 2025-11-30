<template>
    <MobileLayout title="Mes Paiements">
        <!-- Payment Summary -->
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-5 text-white mb-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-green-100 text-sm">Total paye</p>
                    <p class="text-3xl font-bold">{{ formatCurrency(stats.total_paid) }}</p>
                </div>
                <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center backdrop-blur">
                    <CreditCardIcon class="w-7 h-7" />
                </div>
            </div>
            <div class="flex justify-between text-sm pt-4 border-t border-white/20">
                <div>
                    <p class="text-green-200">Cette annee</p>
                    <p class="font-bold text-lg">{{ formatCurrency(stats.this_year) }}</p>
                </div>
                <div class="text-center">
                    <p class="text-green-200">Ce mois</p>
                    <p class="font-bold text-lg">{{ formatCurrency(stats.this_month) }}</p>
                </div>
                <div class="text-right">
                    <p class="text-green-200">Paiements</p>
                    <p class="font-bold text-lg">{{ stats.count }}</p>
                </div>
            </div>
        </div>

        <!-- Filter by Year/Month -->
        <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
            <button
                v-for="period in periods"
                :key="period.value"
                @click="selectedPeriod = period.value"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                    selectedPeriod === period.value
                        ? 'bg-primary-600 text-white'
                        : 'bg-white text-gray-700'
                ]"
            >
                {{ period.label }}
            </button>
        </div>

        <!-- Search -->
        <div class="relative mb-4">
            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Rechercher un paiement..."
                class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
            />
        </div>

        <!-- Empty State -->
        <div v-if="filteredPayments.length === 0" class="text-center py-12">
            <CreditCardIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun paiement</h3>
            <p class="text-gray-500">
                {{ searchQuery ? 'Aucun resultat pour votre recherche' : 'Aucun paiement effectue' }}
            </p>
        </div>

        <!-- Payments grouped by month -->
        <div v-else class="space-y-6">
            <div v-for="(group, monthKey) in groupedPayments" :key="monthKey">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    {{ monthKey }}
                </h3>
                <div class="space-y-3">
                    <Link
                        v-for="payment in group"
                        :key="payment.id"
                        :href="route('mobile.payments.show', payment.id)"
                        class="block bg-white rounded-xl shadow-sm overflow-hidden"
                    >
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="w-12 h-12 rounded-xl flex items-center justify-center mr-3"
                                        :class="getMethodBgClass(payment.method)"
                                    >
                                        <component
                                            :is="getMethodIcon(payment.method)"
                                            class="w-6 h-6"
                                            :class="getMethodTextClass(payment.method)"
                                        />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">{{ payment.payment_number }}</h4>
                                        <p class="text-sm text-gray-500">{{ formatDate(payment.paid_at) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-green-600">+{{ formatCurrency(payment.amount) }}</p>
                                    <span class="text-xs text-gray-500">{{ getMethodLabel(payment.method) }}</span>
                                </div>
                            </div>

                            <!-- Related invoice -->
                            <div v-if="payment.invoice" class="mt-3 pt-3 border-t border-gray-100 flex items-center text-sm text-gray-500">
                                <DocumentTextIcon class="w-4 h-4 mr-1.5" />
                                Facture {{ payment.invoice.invoice_number }}
                            </div>
                        </div>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Make Payment Button -->
        <div class="fixed bottom-24 right-4">
            <Link
                :href="route('mobile.pay')"
                class="w-14 h-14 bg-green-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-green-700 transition"
            >
                <PlusIcon class="w-7 h-7" />
            </Link>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CreditCardIcon,
    MagnifyingGlassIcon,
    DocumentTextIcon,
    PlusIcon,
    BanknotesIcon,
    BuildingLibraryIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    payments: Array,
    stats: Object,
})

const searchQuery = ref('')
const selectedPeriod = ref('all')

const periods = [
    { value: 'all', label: 'Tous' },
    { value: 'this_month', label: 'Ce mois' },
    { value: 'last_month', label: 'Mois dernier' },
    { value: 'this_year', label: 'Cette annee' },
]

const filteredPayments = computed(() => {
    let result = props.payments || []

    // Filter by period
    if (selectedPeriod.value !== 'all') {
        const now = new Date()
        result = result.filter(p => {
            const paidAt = new Date(p.paid_at)
            switch (selectedPeriod.value) {
                case 'this_month':
                    return paidAt.getMonth() === now.getMonth() && paidAt.getFullYear() === now.getFullYear()
                case 'last_month':
                    const lastMonth = new Date(now.getFullYear(), now.getMonth() - 1)
                    return paidAt.getMonth() === lastMonth.getMonth() && paidAt.getFullYear() === lastMonth.getFullYear()
                case 'this_year':
                    return paidAt.getFullYear() === now.getFullYear()
                default:
                    return true
            }
        })
    }

    // Filter by search
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        result = result.filter(p =>
            p.payment_number?.toLowerCase().includes(query) ||
            p.invoice?.invoice_number?.toLowerCase().includes(query)
        )
    }

    return result
})

const groupedPayments = computed(() => {
    const groups = {}
    filteredPayments.value.forEach(payment => {
        const date = new Date(payment.paid_at)
        const key = date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
        if (!groups[key]) {
            groups[key] = []
        }
        groups[key].push(payment)
    })
    return groups
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

const getMethodIcon = (method) => {
    const icons = {
        card: CreditCardIcon,
        bank_transfer: BuildingLibraryIcon,
        sepa: BuildingLibraryIcon,
        cash: BanknotesIcon,
        check: DocumentTextIcon,
    }
    return icons[method] || CreditCardIcon
}

const getMethodBgClass = (method) => {
    const classes = {
        card: 'bg-blue-100',
        bank_transfer: 'bg-purple-100',
        sepa: 'bg-indigo-100',
        cash: 'bg-green-100',
        check: 'bg-orange-100',
    }
    return classes[method] || 'bg-gray-100'
}

const getMethodTextClass = (method) => {
    const classes = {
        card: 'text-blue-600',
        bank_transfer: 'text-purple-600',
        sepa: 'text-indigo-600',
        cash: 'text-green-600',
        check: 'text-orange-600',
    }
    return classes[method] || 'text-gray-600'
}
</script>
