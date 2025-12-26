<template>
    <TenantLayout title="Nouveau Produit">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('tenant.products.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux produits
                </Link>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                    Nouveau Produit
                </h1>
                <p class="mt-1 text-gray-500">Créez un nouveau produit ou service pour votre catalogue</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Type Selection -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Type de produit</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label
                            class="relative flex flex-col p-6 border-2 rounded-xl cursor-pointer transition-all"
                            :class="form.type === 'one_time' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input v-model="form.type" type="radio" value="one_time" class="sr-only" />
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <span class="font-semibold text-gray-900">Achat unique</span>
                            </div>
                            <span class="text-sm text-gray-500">Produit vendu une seule fois (cadenas, cartons, etc.)</span>
                        </label>
                        <label
                            class="relative flex flex-col p-6 border-2 rounded-xl cursor-pointer transition-all"
                            :class="form.type === 'recurring' ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                        >
                            <input v-model="form.type" type="radio" value="recurring" class="sr-only" />
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <span class="font-semibold text-gray-900">Récurrent</span>
                            </div>
                            <span class="text-sm text-gray-500">Service facturé périodiquement (électricité, wifi, etc.)</span>
                        </label>
                    </div>
                    <p v-if="form.errors.type" class="mt-2 text-sm text-red-600">{{ form.errors.type }}</p>
                </div>

                <!-- Basic Info -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations de base</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom du produit *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Ex: Cadenas haute sécurité"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                            <select
                                v-model="form.category"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">Sélectionner...</option>
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Référence (SKU)</label>
                            <input
                                v-model="form.sku"
                                type="text"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Laissez vide pour génération auto"
                            />
                            <p v-if="form.errors.sku" class="mt-1 text-sm text-red-600">{{ form.errors.sku }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Description du produit..."
                            ></textarea>
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Tarification</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prix de vente (HT) *</label>
                            <div class="relative">
                                <input
                                    v-model="form.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-12"
                                    placeholder="0.00"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                            </div>
                            <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prix d'achat (HT)</label>
                            <div class="relative">
                                <input
                                    v-model="form.cost_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-12"
                                    placeholder="0.00"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Taux de TVA *</label>
                            <div class="relative">
                                <input
                                    v-model="form.tax_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-12"
                                />
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                            </div>
                            <p v-if="form.errors.tax_rate" class="mt-1 text-sm text-red-600">{{ form.errors.tax_rate }}</p>
                        </div>
                        <div v-if="form.type === 'recurring'">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Période de facturation *</label>
                            <select
                                v-model="form.billing_period"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option v-for="(label, key) in billingPeriods" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="form.errors.billing_period" class="mt-1 text-sm text-red-600">{{ form.errors.billing_period }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Unité</label>
                            <select
                                v-model="form.unit"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option v-for="(label, key) in units" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Inventory -->
                <div v-if="form.type === 'one_time'" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Gestion du stock</h2>
                    <div class="space-y-6">
                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.track_inventory"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-700">Suivre le stock de ce produit</span>
                        </label>

                        <div v-if="form.track_inventory" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité en stock</label>
                                <input
                                    v-model="form.stock_quantity"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité minimum</label>
                                <input
                                    v-model="form.min_quantity"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité maximum</label>
                                <input
                                    v-model="form.max_quantity"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="Illimité"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Options -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Options</h2>
                    <div class="space-y-4">
                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-700">Produit actif (visible et disponible à la vente)</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.is_featured"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-700">Mettre en avant ce produit</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input
                                v-model="form.requires_contract"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                            />
                            <span class="text-sm text-gray-700">Nécessite un contrat actif pour l'achat</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-4">
                    <Link
                        :href="route('tenant.products.index')"
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/25 hover:shadow-xl disabled:opacity-50 transition-all"
                    >
                        <span v-if="form.processing">Création...</span>
                        <span v-else>Créer le produit</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    categories: Object,
    types: Object,
    billingPeriods: Object,
    units: Object,
})

const form = useForm({
    name: '',
    description: '',
    sku: '',
    type: 'one_time',
    category: '',
    price: '',
    cost_price: '',
    tax_rate: 20,
    billing_period: 'monthly',
    unit: 'piece',
    stock_quantity: 0,
    min_quantity: 1,
    max_quantity: null,
    track_inventory: false,
    requires_contract: false,
    is_featured: false,
    is_active: true,
})

const submit = () => {
    form.post(route('tenant.products.store'))
}
</script>
