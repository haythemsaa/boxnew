<template>
    <TenantLayout title="Produits & Services">
        <!-- Flash Messages -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
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
                    Produits & Services
                </h1>
                <p class="mt-1 text-gray-500">Gérez votre catalogue de produits et services additionnels</p>
            </div>
            <div class="flex gap-3">
                <Link
                    :href="route('tenant.sales.create')"
                    class="inline-flex items-center px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Nouvelle Vente
                </Link>
                <Link
                    :href="route('tenant.products.create')"
                    class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl text-sm font-semibold shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 transition-all"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nouveau Produit
                </Link>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Total</p>
                        <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Actifs</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.active }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-emerald-500 to-green-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Stock Faible</p>
                        <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.low_stock }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Rupture</p>
                        <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.out_of_stock }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase">Récurrents</p>
                        <p class="text-2xl font-bold text-blue-600 mt-1">{{ stats.recurring }}</p>
                    </div>
                    <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
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
                        placeholder="Rechercher un produit..."
                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                </div>
                <select
                    v-model="filterCategory"
                    class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Toutes les catégories</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                </select>
                <select
                    v-model="filterType"
                    class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Tous les types</option>
                    <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                </select>
                <select
                    v-model="filterStatus"
                    class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                    <option value="low_stock">Stock faible</option>
                    <option value="out_of_stock">Rupture de stock</option>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <div v-if="products.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div
                v-for="product in products.data"
                :key="product.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-all group"
            >
                <!-- Product Image or Placeholder -->
                <div class="h-40 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                    <img
                        v-if="product.image_path"
                        :src="product.image_path"
                        :alt="product.name"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="text-center">
                        <svg class="h-16 w-16 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                </div>

                <div class="p-5">
                    <!-- Category Badge -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-700">
                            {{ product.category_label }}
                        </span>
                        <span
                            v-if="product.type === 'recurring'"
                            class="px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700"
                        >
                            Récurrent
                        </span>
                        <span
                            v-if="product.is_featured"
                            class="px-2.5 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700"
                        >
                            Mis en avant
                        </span>
                    </div>

                    <!-- Product Name & SKU -->
                    <h3 class="font-semibold text-gray-900 mb-1 group-hover:text-primary-600 transition-colors">
                        {{ product.name }}
                    </h3>
                    <p v-if="product.sku" class="text-xs text-gray-500 mb-3">SKU: {{ product.sku }}</p>

                    <!-- Price -->
                    <p class="text-xl font-bold text-primary-600 mb-3">
                        {{ product.formatted_price }}
                    </p>

                    <!-- Stock Status -->
                    <div class="flex items-center gap-2 mb-4">
                        <span
                            class="px-2.5 py-1 text-xs font-medium rounded-full"
                            :class="{
                                'bg-emerald-100 text-emerald-700': product.stock_status === 'in_stock' || product.stock_status === 'unlimited',
                                'bg-amber-100 text-amber-700': product.stock_status === 'low_stock',
                                'bg-red-100 text-red-700': product.stock_status === 'out_of_stock'
                            }"
                        >
                            {{ product.stock_status_label }}
                            <template v-if="product.track_inventory && product.stock_status !== 'unlimited'">
                                ({{ product.stock_quantity }})
                            </template>
                        </span>
                        <span
                            v-if="!product.is_active"
                            class="px-2.5 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-500"
                        >
                            Inactif
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link
                            :href="route('tenant.products.show', product.id)"
                            class="flex-1 px-3 py-2 text-center text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                        >
                            Voir
                        </Link>
                        <Link
                            :href="route('tenant.products.edit', product.id)"
                            class="flex-1 px-3 py-2 text-center text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                        >
                            Modifier
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun produit</h3>
            <p class="text-gray-500 mb-6">Commencez par créer votre premier produit ou service.</p>
            <Link
                :href="route('tenant.products.create')"
                class="inline-flex items-center px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-colors"
            >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Créer un produit
            </Link>
        </div>

        <!-- Pagination -->
        <div v-if="products.links && products.links.length > 3" class="mt-8 flex justify-center">
            <nav class="flex gap-2">
                <Link
                    v-for="link in products.links"
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
    products: Object,
    stats: Object,
    filters: Object,
    categories: Object,
    types: Object,
})

const search = ref(props.filters.search || '')
const filterCategory = ref(props.filters.category || '')
const filterType = ref(props.filters.type || '')
const filterStatus = ref(props.filters.status || '')

const updateFilters = () => {
    router.get(route('tenant.products.index'), {
        search: search.value || undefined,
        category: filterCategory.value || undefined,
        type: filterType.value || undefined,
        status: filterStatus.value || undefined,
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

watch([filterCategory, filterType, filterStatus], updateFilters)
</script>
