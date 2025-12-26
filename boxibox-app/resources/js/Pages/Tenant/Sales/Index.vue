<template>
    <TenantLayout title="Ventes">
        <!-- Flash Messages -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3">
                <svg class="h-5 w-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-sm font-medium text-emerald-800">{{ $page.props.flash.success }}</p>
            </div>
        </transition>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    Ventes de Produits
                </h1>
                <p class="mt-1 text-gray-500">Gérez vos ventes de produits et services</p>
            </div>
            <div class="flex gap-3">
                <Link
                    :href="route('tenant.products.index')"
                    class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    Catalogue
                </Link>
                <Link
                    :href="route('tenant.sales.create')"
                    class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 hover:shadow-xl transition-all"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Nouvelle Vente
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Total Ventes</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_sales }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Aujourd'hui</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.today_sales }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">CA Aujourd'hui</p>
                        <p class="text-2xl font-bold text-primary-600 mt-1">{{ formatCurrency(stats.today_revenue) }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-primary-500 to-indigo-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">CA du Mois</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.month_revenue) }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">En Attente</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ formatCurrency(stats.pending_payment) }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher une vente..."
                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
                <select
                    v-model="filterStatus"
                    class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Tous les statuts</option>
                    <option value="pending">En attente</option>
                    <option value="completed">Complétée</option>
                    <option value="cancelled">Annulée</option>
                    <option value="refunded">Remboursée</option>
                </select>
                <select
                    v-model="filterPayment"
                    class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Tous les paiements</option>
                    <option value="pending">Non payé</option>
                    <option value="paid">Payé</option>
                    <option value="failed">Échoué</option>
                </select>
            </div>
        </div>

        <!-- Sales List -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div v-if="sales.data.length > 0" class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">N° Vente</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Articles</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Paiement</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm font-medium text-gray-900">{{ sale.sale_number }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ sale.customer?.first_name }} {{ sale.customer?.last_name }}
                                    </p>
                                    <p v-if="sale.customer?.company_name" class="text-sm text-gray-500">
                                        {{ sale.customer.company_name }}
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600">{{ sale.items_count }} article(s)</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-semibold text-gray-900">{{ formatCurrency(sale.total) }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 text-xs font-medium rounded-full"
                                    :class="getStatusClass(sale.status)"
                                >
                                    {{ getStatusLabel(sale.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 text-xs font-medium rounded-full"
                                    :class="getPaymentStatusClass(sale.payment_status)"
                                >
                                    {{ getPaymentStatusLabel(sale.payment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(sale.sold_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link
                                    :href="route('tenant.sales.show', sale.id)"
                                    class="text-primary-600 hover:text-primary-900 font-medium text-sm"
                                >
                                    Voir
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="p-12 text-center">
                <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucune vente</h3>
                <p class="text-gray-500 mb-6">Commencez par créer votre première vente.</p>
                <Link
                    :href="route('tenant.sales.create')"
                    class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-colors"
                >
                    Nouvelle vente
                </Link>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="sales.links && sales.links.length > 3" class="mt-8 flex justify-center">
            <nav class="flex gap-2">
                <Link
                    v-for="link in sales.links"
                    :key="link.label"
                    :href="link.url"
                    v-html="link.label"
                    :class="{
                        'px-3 py-2 rounded-lg text-sm font-medium transition-colors': true,
                        'bg-primary-600 text-white': link.active,
                        'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50': !link.active && link.url,
                        'bg-gray-100 text-gray-400 cursor-not-allowed': !link.url
                    }"
                />
            </nav>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    sales: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const filterStatus = ref(props.filters.status || '')
const filterPayment = ref(props.filters.payment_status || '')

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700',
        completed: 'bg-emerald-100 text-emerald-700',
        cancelled: 'bg-gray-100 text-gray-700',
        refunded: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        completed: 'Complétée',
        cancelled: 'Annulée',
        refunded: 'Remboursée',
    }
    return labels[status] || status
}

const getPaymentStatusClass = (status) => {
    const classes = {
        pending: 'bg-amber-100 text-amber-700',
        paid: 'bg-emerald-100 text-emerald-700',
        failed: 'bg-red-100 text-red-700',
        refunded: 'bg-purple-100 text-purple-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getPaymentStatusLabel = (status) => {
    const labels = {
        pending: 'Non payé',
        paid: 'Payé',
        failed: 'Échoué',
        refunded: 'Remboursé',
    }
    return labels[status] || status
}

const updateFilters = () => {
    router.get(route('tenant.sales.index'), {
        search: search.value || undefined,
        status: filterStatus.value || undefined,
        payment_status: filterPayment.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

let searchTimeout = null
watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(updateFilters, 300)
})

watch([filterStatus, filterPayment], updateFilters)
</script>
