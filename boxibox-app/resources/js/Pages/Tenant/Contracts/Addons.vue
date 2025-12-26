<template>
    <TenantLayout :title="`Addons - ${contract.contract_number}`">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <Link :href="route('tenant.contracts.show', contract.id)" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour au contrat
                </Link>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                            Services additionnels
                        </h1>
                        <p class="mt-1 text-gray-500">
                            Contrat {{ contract.contract_number }} - {{ getCustomerName(contract.customer) }}
                        </p>
                    </div>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un service
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 rounded-xl">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Services actifs</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.active }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 rounded-xl">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">En pause</p>
                            <p class="text-2xl font-bold text-gray-900">{{ stats.paused }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total mensuel</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.monthlyTotal) }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Loyer + Addons</p>
                            <p class="text-2xl font-bold text-primary-600">{{ formatCurrency(contract.monthly_price + stats.monthlyTotal) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Addons List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Services souscrits</h2>
                </div>

                <div v-if="addons.length === 0" class="p-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun service additionnel</h3>
                    <p class="text-gray-500 mb-6">Ajoutez des services récurrents comme l'électricité, le wifi ou la sécurité.</p>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Ajouter un service
                    </button>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div
                        v-for="addon in addons"
                        :key="addon.id"
                        class="p-6 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-4">
                                <div class="p-3 rounded-xl" :class="getCategoryColor(addon.product?.category)">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="getCategoryIcon(addon.product?.category)" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="flex items-center gap-3 mb-1">
                                        <h3 class="font-semibold text-gray-900">{{ addon.product_name }}</h3>
                                        <span
                                            class="px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="getStatusClass(addon.status)"
                                        >
                                            {{ statusLabels[addon.status] }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-500">
                                        <span>{{ formatCurrency(addon.unit_price) }} x {{ addon.quantity }} / {{ billingPeriodLabels[addon.billing_period] }}</span>
                                        <span class="text-gray-300">|</span>
                                        <span>Depuis le {{ formatDate(addon.start_date) }}</span>
                                        <span v-if="addon.next_billing_date" class="text-gray-300">|</span>
                                        <span v-if="addon.next_billing_date">Prochaine facturation: {{ formatDate(addon.next_billing_date) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-lg text-gray-900">
                                    {{ formatCurrency(addon.quantity * addon.unit_price) }}
                                </span>
                                <div class="relative ml-4">
                                    <button
                                        @click="toggleMenu(addon.id)"
                                        class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                        </svg>
                                    </button>
                                    <div
                                        v-if="openMenu === addon.id"
                                        class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-10"
                                    >
                                        <button
                                            @click="editAddon(addon)"
                                            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Modifier
                                        </button>
                                        <button
                                            v-if="addon.status === 'active'"
                                            @click="pauseAddon(addon)"
                                            class="w-full px-4 py-2 text-left text-sm text-amber-600 hover:bg-amber-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Mettre en pause
                                        </button>
                                        <button
                                            v-if="addon.status === 'paused'"
                                            @click="resumeAddon(addon)"
                                            class="w-full px-4 py-2 text-left text-sm text-emerald-600 hover:bg-emerald-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Reprendre
                                        </button>
                                        <button
                                            v-if="addon.status !== 'cancelled'"
                                            @click="cancelAddon(addon)"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Annuler
                                        </button>
                                        <button
                                            @click="deleteAddon(addon)"
                                            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div v-if="showAddModal || showEditModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ showEditModal ? 'Modifier le service' : 'Ajouter un service' }}
                        </h3>
                    </div>
                    <form @submit.prevent="submitAddon" class="p-6 space-y-6">
                        <div v-if="showAddModal">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Produit/Service *</label>
                            <select
                                v-model="form.product_id"
                                @change="onProductChange"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            >
                                <option value="">Sélectionner un produit...</option>
                                <option v-for="product in recurringProducts" :key="product.id" :value="product.id">
                                    {{ product.name }} - {{ formatCurrency(product.price) }}/{{ billingPeriodLabels[product.billing_period] }}
                                </option>
                            </select>
                            <p v-if="form.errors.product_id" class="mt-1 text-sm text-red-600">{{ form.errors.product_id }}</p>
                        </div>

                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service</label>
                            <p class="text-gray-900 font-medium">{{ editingAddon?.product_name }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantité *</label>
                                <input
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Prix unitaire *</label>
                                <div class="relative">
                                    <input
                                        v-model="form.unit_price"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-8"
                                        required
                                    />
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Période de facturation *</label>
                            <select
                                v-model="form.billing_period"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            >
                                <option value="monthly">Mensuelle</option>
                                <option value="quarterly">Trimestrielle</option>
                                <option value="yearly">Annuelle</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de début *</label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de fin (optionnel)</label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-xl border-gray-200 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-xl font-medium shadow-lg disabled:opacity-50 transition-all"
                            >
                                {{ form.processing ? 'Enregistrement...' : (showEditModal ? 'Modifier' : 'Ajouter') }}
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-xl font-medium hover:bg-gray-200 transition-colors"
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    contract: Object,
    addons: Array,
    products: Array,
    billingPeriods: Object,
})

const showAddModal = ref(false)
const showEditModal = ref(false)
const editingAddon = ref(null)
const openMenu = ref(null)

const statusLabels = {
    active: 'Actif',
    paused: 'En pause',
    cancelled: 'Annulé',
}

const billingPeriodLabels = {
    monthly: 'mois',
    quarterly: 'trimestre',
    yearly: 'an',
}

const form = useForm({
    product_id: '',
    quantity: 1,
    unit_price: '',
    billing_period: 'monthly',
    start_date: new Date().toISOString().split('T')[0],
    end_date: '',
})

const recurringProducts = computed(() => {
    return props.products.filter(p => p.type === 'recurring' && p.is_active)
})

const stats = computed(() => {
    const active = props.addons.filter(a => a.status === 'active').length
    const paused = props.addons.filter(a => a.status === 'paused').length
    const monthlyTotal = props.addons
        .filter(a => a.status === 'active')
        .reduce((sum, addon) => {
            let amount = addon.quantity * addon.unit_price
            if (addon.billing_period === 'quarterly') amount = amount / 3
            if (addon.billing_period === 'yearly') amount = amount / 12
            return sum + amount
        }, 0)
    return { active, paused, monthlyTotal }
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const getCategoryColor = (category) => {
    const colors = {
        electricity: 'bg-yellow-100 text-yellow-600',
        wifi: 'bg-blue-100 text-blue-600',
        security: 'bg-red-100 text-red-600',
        cleaning: 'bg-green-100 text-green-600',
        insurance: 'bg-purple-100 text-purple-600',
    }
    return colors[category] || 'bg-gray-100 text-gray-600'
}

const getCategoryIcon = (category) => {
    const icons = {
        electricity: 'M13 10V3L4 14h7v7l9-11h-7z',
        wifi: 'M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0',
        security: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        cleaning: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        insurance: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    }
    return icons[category] || 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'
}

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700',
        paused: 'bg-amber-100 text-amber-700',
        cancelled: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const toggleMenu = (id) => {
    openMenu.value = openMenu.value === id ? null : id
}

const onProductChange = () => {
    const product = props.products.find(p => p.id === form.product_id)
    if (product) {
        form.unit_price = product.price
        form.billing_period = product.billing_period || 'monthly'
    }
}

const closeModal = () => {
    showAddModal.value = false
    showEditModal.value = false
    editingAddon.value = null
    form.reset()
    form.clearErrors()
}

const editAddon = (addon) => {
    openMenu.value = null
    editingAddon.value = addon
    form.product_id = addon.product_id
    form.quantity = addon.quantity
    form.unit_price = addon.unit_price
    form.billing_period = addon.billing_period
    form.start_date = addon.start_date?.split('T')[0] || ''
    form.end_date = addon.end_date?.split('T')[0] || ''
    showEditModal.value = true
}

const submitAddon = () => {
    if (showEditModal.value && editingAddon.value) {
        form.put(route('tenant.contracts.addons.update', [props.contract.id, editingAddon.value.id]), {
            onSuccess: () => closeModal(),
        })
    } else {
        form.post(route('tenant.contracts.addons.store', props.contract.id), {
            onSuccess: () => closeModal(),
        })
    }
}

const pauseAddon = (addon) => {
    openMenu.value = null
    router.post(route('tenant.contracts.addons.pause', [props.contract.id, addon.id]))
}

const resumeAddon = (addon) => {
    openMenu.value = null
    router.post(route('tenant.contracts.addons.resume', [props.contract.id, addon.id]))
}

const cancelAddon = (addon) => {
    openMenu.value = null
    if (confirm('Êtes-vous sûr de vouloir annuler ce service ?')) {
        router.post(route('tenant.contracts.addons.cancel', [props.contract.id, addon.id]))
    }
}

const deleteAddon = (addon) => {
    openMenu.value = null
    if (confirm('Êtes-vous sûr de vouloir supprimer ce service ? Cette action est irréversible.')) {
        router.delete(route('tenant.contracts.addons.destroy', [props.contract.id, addon.id]))
    }
}
</script>
