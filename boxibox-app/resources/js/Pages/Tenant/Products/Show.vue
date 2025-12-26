<template>
    <TenantLayout :title="product.name">
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

        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
                <div>
                    <Link :href="route('tenant.products.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux produits
                    </Link>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold text-gray-900">{{ product.name }}</h1>
                        <span
                            v-if="!product.is_active"
                            class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600"
                        >
                            Inactif
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-500">
                        <span class="px-2.5 py-1 rounded-full bg-gray-100">{{ product.category_label }}</span>
                        <span class="px-2.5 py-1 rounded-full" :class="product.type === 'recurring' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100'">
                            {{ product.type_label }}
                        </span>
                        <span v-if="product.sku">SKU: {{ product.sku }}</span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button
                        @click="toggleActive"
                        class="inline-flex items-center px-4 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                    >
                        <template v-if="product.is_active">
                            <svg class="h-4 w-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Désactiver
                        </template>
                        <template v-else>
                            <svg class="h-4 w-4 mr-2 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Activer
                        </template>
                    </button>
                    <Link
                        :href="route('tenant.products.edit', product.id)"
                        class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-colors"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Modifier
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Product Details -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Détails du produit</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p class="text-sm text-gray-500">Prix de vente</p>
                                <p class="text-2xl font-bold text-primary-600">{{ product.formatted_price }}</p>
                            </div>
                            <div v-if="product.cost_price">
                                <p class="text-sm text-gray-500">Prix d'achat</p>
                                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(product.cost_price) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Taux de TVA</p>
                                <p class="text-lg font-semibold text-gray-900">{{ product.tax_rate }}%</p>
                            </div>
                            <div v-if="product.type === 'recurring'">
                                <p class="text-sm text-gray-500">Période de facturation</p>
                                <p class="text-lg font-semibold text-gray-900">{{ product.billing_period_label }}</p>
                            </div>
                        </div>
                        <div v-if="product.description" class="mt-6 pt-6 border-t border-gray-100">
                            <p class="text-sm text-gray-500 mb-2">Description</p>
                            <p class="text-gray-700">{{ product.description }}</p>
                        </div>
                    </div>

                    <!-- Stock Management -->
                    <div v-if="product.track_inventory" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-lg font-semibold text-gray-900">Gestion du stock</h2>
                            <button
                                @click="showStockModal = true"
                                class="text-sm font-medium text-primary-600 hover:text-primary-700"
                            >
                                Ajuster le stock
                            </button>
                        </div>
                        <div class="grid grid-cols-3 gap-6">
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-3xl font-bold" :class="{
                                    'text-emerald-600': product.stock_status === 'in_stock',
                                    'text-amber-600': product.stock_status === 'low_stock',
                                    'text-red-600': product.stock_status === 'out_of_stock'
                                }">
                                    {{ product.stock_quantity }}
                                </p>
                                <p class="text-sm text-gray-500 mt-1">En stock</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-3xl font-bold text-gray-900">{{ product.min_quantity }}</p>
                                <p class="text-sm text-gray-500 mt-1">Qté minimum</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-xl">
                                <p class="text-3xl font-bold text-gray-900">{{ product.max_quantity || '∞' }}</p>
                                <p class="text-sm text-gray-500 mt-1">Qté maximum</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Sales -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Ventes récentes</h2>
                        <div v-if="recentSales.length > 0" class="space-y-4">
                            <div
                                v-for="item in recentSales"
                                :key="item.id"
                                class="flex items-center justify-between p-4 bg-gray-50 rounded-xl"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ item.sale?.customer?.first_name }} {{ item.sale?.customer?.last_name }}
                                    </p>
                                    <p class="text-sm text-gray-500">{{ formatDate(item.created_at) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-gray-900">{{ item.quantity }} x {{ formatCurrency(item.unit_price) }}</p>
                                    <p class="text-sm text-primary-600 font-medium">{{ formatCurrency(item.total) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            <p>Aucune vente pour ce produit</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Stats -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Total vendu</span>
                                <span class="font-semibold text-gray-900">{{ salesStats.total_sold }} unités</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">Chiffre d'affaires</span>
                                <span class="font-semibold text-primary-600">{{ formatCurrency(salesStats.total_revenue) }}</span>
                            </div>
                            <template v-if="product.type === 'recurring'">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Contrats actifs</span>
                                    <span class="font-semibold text-gray-900">{{ salesStats.active_addons }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-500">Revenu mensuel</span>
                                    <span class="font-semibold text-emerald-600">{{ formatCurrency(salesStats.monthly_recurring) }}/mois</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions rapides</h3>
                        <div class="space-y-3">
                            <Link
                                :href="route('tenant.sales.create') + '?product_id=' + product.id"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-emerald-50 text-emerald-700 rounded-xl font-medium hover:bg-emerald-100 transition-colors"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Nouvelle vente
                            </Link>
                            <button
                                @click="confirmDelete"
                                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-red-50 text-red-700 rounded-xl font-medium hover:bg-red-100 transition-colors"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Adjustment Modal -->
        <div v-if="showStockModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showStockModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Ajuster le stock</h3>
                    <form @submit.prevent="submitStockAdjustment">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ajustement</label>
                                <input
                                    v-model="stockForm.adjustment"
                                    type="number"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="Ex: +10 ou -5"
                                />
                                <p class="mt-1 text-sm text-gray-500">
                                    Nouveau stock: {{ product.stock_quantity + (parseInt(stockForm.adjustment) || 0) }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Raison (optionnel)</label>
                                <input
                                    v-model="stockForm.reason"
                                    type="text"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="Ex: Réception commande"
                                />
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                @click="showStockModal = false"
                                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="stockForm.processing"
                                class="flex-1 px-4 py-2.5 bg-primary-600 text-white rounded-xl font-medium"
                            >
                                Confirmer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    product: Object,
    recentSales: Array,
    activeAddons: Array,
    salesStats: Object,
})

const showStockModal = ref(false)

const stockForm = useForm({
    adjustment: 0,
    reason: '',
})

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
    })
}

const toggleActive = () => {
    router.post(route('tenant.products.toggle-active', props.product.id))
}

const submitStockAdjustment = () => {
    stockForm.post(route('tenant.products.adjust-stock', props.product.id), {
        onSuccess: () => {
            showStockModal.value = false
            stockForm.reset()
        },
    })
}

const confirmDelete = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        router.delete(route('tenant.products.destroy', props.product.id))
    }
}
</script>
