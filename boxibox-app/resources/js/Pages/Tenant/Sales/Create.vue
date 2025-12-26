<template>
    <TenantLayout title="Nouvelle Vente">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('tenant.sales.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux ventes
                </Link>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    Nouvelle Vente
                </h1>
                <p class="mt-1 text-gray-500">Créez une nouvelle vente de produits</p>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Customer Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Client</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sélectionner un client *</label>
                                    <select
                                        v-model="form.customer_id"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="">Choisir un client...</option>
                                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                            {{ customer.type === 'company' ? customer.company_name : `${customer.first_name} ${customer.last_name}` }}
                                            ({{ customer.email }})
                                        </option>
                                    </select>
                                    <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                                </div>
                                <div v-if="sites.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Site (optionnel)</label>
                                    <select
                                        v-model="form.site_id"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="">Aucun site</option>
                                        <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Products Selection -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="text-lg font-semibold text-gray-900">Produits</h2>
                                <span class="text-sm text-gray-500">{{ cart.length }} article(s)</span>
                            </div>

                            <!-- Product Search -->
                            <div class="mb-6">
                                <div class="flex gap-4">
                                    <select
                                        v-model="selectedCategory"
                                        class="rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="">Toutes catégories</option>
                                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                    <input
                                        v-model="productSearch"
                                        type="text"
                                        placeholder="Rechercher un produit..."
                                        class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    />
                                </div>
                            </div>

                            <!-- Available Products Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 mb-6 max-h-64 overflow-y-auto">
                                <button
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    type="button"
                                    @click="addToCart(product)"
                                    class="p-4 border border-gray-200 rounded-xl text-left hover:border-primary-500 hover:bg-primary-50 transition-all"
                                    :class="{ 'opacity-50 cursor-not-allowed': !product.is_in_stock }"
                                    :disabled="!product.is_in_stock"
                                >
                                    <p class="font-medium text-gray-900 truncate">{{ product.name }}</p>
                                    <p class="text-sm text-primary-600 font-semibold">{{ product.formatted_price }}</p>
                                    <p v-if="product.track_inventory" class="text-xs text-gray-500 mt-1">
                                        Stock: {{ product.stock_quantity }}
                                    </p>
                                </button>
                            </div>

                            <!-- Cart Items -->
                            <div v-if="cart.length > 0" class="border-t border-gray-100 pt-6">
                                <h3 class="font-medium text-gray-900 mb-4">Panier</h3>
                                <div class="space-y-4">
                                    <div
                                        v-for="(item, index) in cart"
                                        :key="index"
                                        class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl"
                                    >
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900">{{ item.product.name }}</p>
                                            <p class="text-sm text-gray-500">{{ formatCurrency(item.unit_price) }} / unité</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="decrementQuantity(index)"
                                                class="p-1 rounded-lg border border-gray-300 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>
                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="1"
                                                class="w-16 text-center rounded-lg border-gray-200"
                                            />
                                            <button
                                                type="button"
                                                @click="incrementQuantity(index)"
                                                class="p-1 rounded-lg border border-gray-300 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="font-semibold text-gray-900 w-24 text-right">
                                            {{ formatCurrency(item.quantity * item.unit_price) }}
                                        </p>
                                        <button
                                            type="button"
                                            @click="removeFromCart(index)"
                                            class="p-1 text-red-500 hover:text-red-700"
                                        >
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <p v-if="form.errors.items" class="mt-4 text-sm text-red-600">{{ form.errors.items }}</p>
                        </div>

                        <!-- Notes -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Notes optionnelles pour cette vente..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Sidebar - Summary -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sticky top-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-6">Récapitulatif</h2>

                            <div class="space-y-4 mb-6">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Sous-total</span>
                                    <span class="font-medium text-gray-900">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">TVA</span>
                                    <span class="font-medium text-gray-900">{{ formatCurrency(taxAmount) }}</span>
                                </div>
                                <div class="border-t border-gray-100 pt-4 flex justify-between">
                                    <span class="font-semibold text-gray-900">Total</span>
                                    <span class="text-xl font-bold text-primary-600">{{ formatCurrency(total) }}</span>
                                </div>
                            </div>

                            <!-- Payment -->
                            <div class="space-y-4 mb-6">
                                <label class="flex items-center gap-3">
                                    <input
                                        v-model="form.mark_as_paid"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                    />
                                    <span class="text-sm text-gray-700">Marquer comme payée</span>
                                </label>

                                <div v-if="form.mark_as_paid">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Méthode de paiement</label>
                                    <select
                                        v-model="form.payment_method"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="cash">Espèces</option>
                                        <option value="card">Carte bancaire</option>
                                        <option value="bank_transfer">Virement</option>
                                        <option value="other">Autre</option>
                                    </select>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing || cart.length === 0"
                                class="w-full px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold shadow-lg disabled:opacity-50 transition-all"
                            >
                                <span v-if="form.processing">Création...</span>
                                <span v-else>Créer la vente</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    customers: Array,
    products: Array,
    sites: Array,
    selectedCustomer: Object,
    categories: Object,
})

const cart = ref([])
const productSearch = ref('')
const selectedCategory = ref('')

const form = useForm({
    customer_id: props.selectedCustomer?.id || '',
    site_id: '',
    items: [],
    notes: '',
    payment_method: 'cash',
    mark_as_paid: true,
})

const filteredProducts = computed(() => {
    let filtered = props.products

    if (selectedCategory.value) {
        filtered = filtered.filter(p => p.category === selectedCategory.value)
    }

    if (productSearch.value) {
        const search = productSearch.value.toLowerCase()
        filtered = filtered.filter(p =>
            p.name.toLowerCase().includes(search) ||
            (p.sku && p.sku.toLowerCase().includes(search))
        )
    }

    return filtered
})

const subtotal = computed(() => {
    return cart.value.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
})

const taxAmount = computed(() => {
    return cart.value.reduce((sum, item) => {
        const itemSubtotal = item.quantity * item.unit_price
        return sum + (itemSubtotal * (item.tax_rate / 100))
    }, 0)
})

const total = computed(() => {
    return subtotal.value + taxAmount.value
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const addToCart = (product) => {
    const existingIndex = cart.value.findIndex(item => item.product.id === product.id)

    if (existingIndex >= 0) {
        cart.value[existingIndex].quantity++
    } else {
        cart.value.push({
            product: product,
            product_id: product.id,
            quantity: 1,
            unit_price: parseFloat(product.price),
            tax_rate: parseFloat(product.tax_rate) || 20,
        })
    }
}

const removeFromCart = (index) => {
    cart.value.splice(index, 1)
}

const incrementQuantity = (index) => {
    cart.value[index].quantity++
}

const decrementQuantity = (index) => {
    if (cart.value[index].quantity > 1) {
        cart.value[index].quantity--
    } else {
        removeFromCart(index)
    }
}

const submit = () => {
    form.items = cart.value.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        unit_price: item.unit_price,
    }))

    form.post(route('tenant.sales.store'))
}
</script>
