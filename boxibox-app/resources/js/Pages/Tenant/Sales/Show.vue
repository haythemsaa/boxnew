<template>
    <TenantLayout :title="`Vente ${sale.sale_number}`">
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

        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-8">
                <div>
                    <Link :href="route('tenant.sales.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Retour aux ventes
                    </Link>
                    <div class="flex items-center gap-3 mb-2">
                        <h1 class="text-3xl font-bold text-gray-900">{{ sale.sale_number }}</h1>
                        <span
                            class="px-3 py-1 text-xs font-medium rounded-full"
                            :class="getStatusClass(sale.status)"
                        >
                            {{ getStatusLabel(sale.status) }}
                        </span>
                    </div>
                    <p class="text-gray-500">{{ formatDate(sale.sold_at) }}</p>
                </div>
                <div class="flex gap-3">
                    <button
                        v-if="sale.status === 'pending' && sale.payment_status === 'paid'"
                        @click="completeSale"
                        class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors"
                    >
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Compléter
                    </button>
                    <button
                        v-if="sale.status === 'pending'"
                        @click="showCancelModal = true"
                        class="inline-flex items-center px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        v-if="sale.status === 'completed' && sale.payment_status === 'paid'"
                        @click="showRefundModal = true"
                        class="inline-flex items-center px-4 py-2.5 border border-red-300 text-red-700 rounded-xl text-sm font-medium hover:bg-red-50 transition-colors"
                    >
                        Rembourser
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Items -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900">Articles</h2>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div
                                v-for="item in sale.items"
                                :key="item.id"
                                class="p-6 flex items-center justify-between"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">{{ item.product_name }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                                        <span v-if="item.product_sku" class="ml-2">SKU: {{ item.product_sku }}</span>
                                    </p>
                                </div>
                                <p class="font-semibold text-gray-900">{{ formatCurrency(item.total) }}</p>
                            </div>
                        </div>
                        <div class="p-6 bg-gray-50 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Sous-total</span>
                                <span class="font-medium text-gray-900">{{ formatCurrency(sale.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">TVA</span>
                                <span class="font-medium text-gray-900">{{ formatCurrency(sale.tax_amount) }}</span>
                            </div>
                            <div v-if="sale.discount_amount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-500">Remise</span>
                                <span class="font-medium text-red-600">-{{ formatCurrency(sale.discount_amount) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 flex justify-between">
                                <span class="font-semibold text-gray-900">Total</span>
                                <span class="text-xl font-bold text-primary-600">{{ formatCurrency(sale.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="sale.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes</h2>
                        <p class="text-gray-700">{{ sale.notes }}</p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Customer Info -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Client</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="font-medium text-gray-900">
                                    {{ sale.customer?.first_name }} {{ sale.customer?.last_name }}
                                </p>
                                <p v-if="sale.customer?.company_name" class="text-sm text-gray-500">
                                    {{ sale.customer.company_name }}
                                </p>
                            </div>
                            <p v-if="sale.customer?.email" class="text-sm text-gray-500">
                                {{ sale.customer.email }}
                            </p>
                            <p v-if="sale.customer?.phone" class="text-sm text-gray-500">
                                {{ sale.customer.phone }}
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.customers.show', sale.customer?.id)"
                            class="mt-4 inline-flex items-center text-sm text-primary-600 hover:text-primary-700"
                        >
                            Voir le profil
                            <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </Link>
                    </div>

                    <!-- Payment Info -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Paiement</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Statut</span>
                                <span
                                    class="px-2.5 py-1 text-xs font-medium rounded-full"
                                    :class="getPaymentStatusClass(sale.payment_status)"
                                >
                                    {{ getPaymentStatusLabel(sale.payment_status) }}
                                </span>
                            </div>
                            <div v-if="sale.payment_method" class="flex justify-between">
                                <span class="text-gray-500">Méthode</span>
                                <span class="font-medium text-gray-900">{{ getPaymentMethodLabel(sale.payment_method) }}</span>
                            </div>
                            <div v-if="sale.paid_at" class="flex justify-between">
                                <span class="text-gray-500">Payé le</span>
                                <span class="font-medium text-gray-900">{{ formatDate(sale.paid_at) }}</span>
                            </div>
                            <div v-if="sale.refunded_amount > 0" class="flex justify-between">
                                <span class="text-gray-500">Remboursé</span>
                                <span class="font-medium text-red-600">{{ formatCurrency(sale.refunded_amount) }}</span>
                            </div>
                        </div>

                        <!-- Mark as Paid Button -->
                        <button
                            v-if="sale.payment_status === 'pending'"
                            @click="showPaymentModal = true"
                            class="mt-4 w-full px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 transition-colors"
                        >
                            Enregistrer le paiement
                        </button>
                    </div>

                    <!-- Sale Info -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Vendeur</span>
                                <span class="font-medium text-gray-900">{{ sale.seller?.name || 'N/A' }}</span>
                            </div>
                            <div v-if="sale.site" class="flex justify-between">
                                <span class="text-gray-500">Site</span>
                                <span class="font-medium text-gray-900">{{ sale.site.name }}</span>
                            </div>
                            <div v-if="sale.invoice" class="flex justify-between">
                                <span class="text-gray-500">Facture</span>
                                <Link
                                    :href="route('tenant.invoices.show', sale.invoice.id)"
                                    class="font-medium text-primary-600 hover:text-primary-700"
                                >
                                    {{ sale.invoice.invoice_number }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showPaymentModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Enregistrer le paiement</h3>
                    <form @submit.prevent="submitPayment">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Méthode de paiement</label>
                                <select
                                    v-model="paymentForm.payment_method"
                                    class="w-full rounded-xl border-gray-200"
                                >
                                    <option value="cash">Espèces</option>
                                    <option value="card">Carte bancaire</option>
                                    <option value="bank_transfer">Virement</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Référence (optionnel)</label>
                                <input
                                    v-model="paymentForm.payment_reference"
                                    type="text"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="N° transaction..."
                                />
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                @click="showPaymentModal = false"
                                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="paymentForm.processing"
                                class="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-xl font-medium"
                            >
                                Confirmer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Refund Modal -->
        <div v-if="showRefundModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showRefundModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Rembourser la vente</h3>
                    <form @submit.prevent="submitRefund">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Montant à rembourser</label>
                                <div class="relative">
                                    <input
                                        v-model="refundForm.amount"
                                        type="number"
                                        step="0.01"
                                        :max="sale.total - sale.refunded_amount"
                                        class="w-full rounded-xl border-gray-200 pr-12"
                                    />
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Maximum: {{ formatCurrency(sale.total - sale.refunded_amount) }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Raison du remboursement</label>
                                <textarea
                                    v-model="refundForm.reason"
                                    rows="2"
                                    class="w-full rounded-xl border-gray-200"
                                    required
                                ></textarea>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button
                                type="button"
                                @click="showRefundModal = false"
                                class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl font-medium"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                :disabled="refundForm.processing"
                                class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl font-medium"
                            >
                                Rembourser
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
    sale: Object,
})

const showPaymentModal = ref(false)
const showRefundModal = ref(false)
const showCancelModal = ref(false)

const paymentForm = useForm({
    payment_method: 'cash',
    payment_reference: '',
})

const refundForm = useForm({
    amount: props.sale.total - props.sale.refunded_amount,
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
        month: 'long',
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

const getPaymentMethodLabel = (method) => {
    const labels = {
        cash: 'Espèces',
        card: 'Carte bancaire',
        bank_transfer: 'Virement',
        stripe: 'Stripe',
        other: 'Autre',
    }
    return labels[method] || method
}

const completeSale = () => {
    router.post(route('tenant.sales.complete', props.sale.id))
}

const submitPayment = () => {
    paymentForm.post(route('tenant.sales.mark-paid', props.sale.id), {
        onSuccess: () => {
            showPaymentModal.value = false
        },
    })
}

const submitRefund = () => {
    refundForm.post(route('tenant.sales.refund', props.sale.id), {
        onSuccess: () => {
            showRefundModal.value = false
        },
    })
}
</script>
