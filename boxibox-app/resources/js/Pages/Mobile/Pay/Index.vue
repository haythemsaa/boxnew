<template>
    <MobileLayout title="Payer" :show-back="true">
        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded-xl">
            {{ $page.props.flash.error }}
        </div>
        <div v-if="$page.props.flash?.warning" class="mb-4 p-4 bg-yellow-100 text-yellow-700 rounded-xl">
            {{ $page.props.flash.warning }}
        </div>

        <!-- Amount Summary -->
        <div class="bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-5 text-white mb-6 shadow-lg">
            <p class="text-primary-100 text-sm">Montant a payer</p>
            <p class="text-4xl font-bold mt-1">{{ formatCurrency(totalAmount) }}</p>
            <p class="text-primary-200 text-sm mt-2">
                {{ selectedInvoices.length }} facture(s) selectionnee(s)
            </p>
        </div>

        <!-- Select Invoices -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="text-lg font-semibold text-gray-900">Factures a payer</h3>
                <button
                    @click="selectAll"
                    class="text-sm text-primary-600 font-medium"
                >
                    {{ allSelected ? 'Tout deselectionner' : 'Tout selectionner' }}
                </button>
            </div>

            <div class="space-y-3">
                <label
                    v-for="invoice in unpaidInvoices"
                    :key="invoice.id"
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="isSelected(invoice.id) ? 'ring-2 ring-primary-500' : ''"
                >
                    <input
                        type="checkbox"
                        :value="invoice.id"
                        v-model="selectedInvoiceIds"
                        class="w-5 h-5 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <div class="flex-1 ml-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ invoice.invoice_number }}</p>
                                <p class="text-sm text-gray-500">{{ formatDate(invoice.due_date) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">{{ formatCurrency(invoice.balance) }}</p>
                                <span
                                    v-if="invoice.status === 'overdue'"
                                    class="text-xs text-red-600"
                                >
                                    En retard
                                </span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <div v-if="unpaidInvoices.length === 0" class="text-center py-8">
                <CheckCircleIcon class="w-16 h-16 mx-auto text-green-300 mb-4" />
                <p class="text-gray-500">Aucune facture a payer</p>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="mb-6" v-if="selectedInvoices.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Mode de paiement</h3>

            <div class="space-y-3">
                <!-- Saved Cards -->
                <label
                    v-for="card in savedCards"
                    :key="card.id"
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="selectedMethod === `card_${card.id}` ? 'ring-2 ring-primary-500' : ''"
                >
                    <input
                        type="radio"
                        :value="`card_${card.id}`"
                        v-model="selectedMethod"
                        class="w-5 h-5 border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <div class="flex-1 ml-3 flex items-center justify-between">
                        <div class="flex items-center">
                            <CreditCardIcon class="w-8 h-8 mr-3 text-blue-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ card.brand }} **** {{ card.last4 }}</p>
                                <p class="text-sm text-gray-500">Expire {{ card.expiry }}</p>
                            </div>
                        </div>
                        <span v-if="card.is_default" class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                            Par defaut
                        </span>
                    </div>
                </label>

                <!-- New Card -->
                <label
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="selectedMethod === 'new_card' ? 'ring-2 ring-primary-500' : ''"
                >
                    <input
                        type="radio"
                        value="new_card"
                        v-model="selectedMethod"
                        class="w-5 h-5 border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <div class="flex-1 ml-3 flex items-center">
                        <PlusCircleIcon class="w-8 h-8 mr-3 text-primary-600" />
                        <div>
                            <p class="font-medium text-gray-900">Nouvelle carte</p>
                            <p class="text-sm text-gray-500">Visa, Mastercard, CB</p>
                        </div>
                    </div>
                </label>

                <!-- PayPal -->
                <label
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="selectedMethod === 'paypal' ? 'ring-2 ring-primary-500' : ''"
                >
                    <input
                        type="radio"
                        value="paypal"
                        v-model="selectedMethod"
                        class="w-5 h-5 border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <div class="flex-1 ml-3 flex items-center justify-between">
                        <div class="flex items-center">
                            <!-- PayPal Icon -->
                            <div class="w-8 h-8 mr-3 flex items-center justify-center">
                                <svg viewBox="0 0 24 24" class="w-7 h-7 text-[#003087]" fill="currentColor">
                                    <path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.632h6.927c2.3 0 4.145.562 5.342 1.593 1.133.97 1.605 2.419 1.363 4.188-.262 1.932-1.047 3.485-2.335 4.62-1.307 1.152-3.02 1.74-5.096 1.74h-2.21a.77.77 0 0 0-.758.632l-.87 5.476zm.647-14.58l-1.49 9.377h1.898c1.514 0 2.77-.387 3.734-1.152.982-.78 1.54-1.89 1.658-3.3.086-.987-.15-1.764-.706-2.314-.578-.572-1.49-.863-2.712-.863h-2.382z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">PayPal</p>
                                <p class="text-sm text-gray-500">Payer avec votre compte PayPal</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Stripe Card Element (for new card) -->
        <div v-if="selectedMethod === 'new_card' && selectedInvoices.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Informations de carte</h3>

            <div class="bg-white rounded-xl shadow-sm p-4">
                <StripeCardElement
                    v-if="stripeKey"
                    ref="stripeCardRef"
                    :stripe-key="stripeKey"
                    :client-secret="clientSecret"
                    @ready="onStripeReady"
                    @change="onCardChange"
                    @complete="onCardComplete"
                    @error="onCardError"
                />

                <label class="flex items-center mt-4">
                    <input
                        type="checkbox"
                        v-model="saveCard"
                        class="w-4 h-4 rounded border-gray-300 text-primary-600"
                    />
                    <span class="ml-2 text-sm text-gray-600">Enregistrer cette carte pour plus tard</span>
                </label>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="mb-6 p-4 bg-red-50 text-red-700 rounded-xl flex items-start gap-3">
            <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0 mt-0.5" />
            <p>{{ errorMessage }}</p>
        </div>

        <!-- Pay Button -->
        <div class="fixed bottom-24 left-4 right-4">
            <button
                @click="processPayment"
                :disabled="!canPay || processing"
                class="w-full py-4 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
                <span v-if="processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Traitement en cours...
                </span>
                <span v-else>
                    <span v-if="selectedMethod === 'paypal'">Payer avec PayPal</span>
                    <span v-else>Payer {{ formatCurrency(totalAmount) }}</span>
                </span>
            </button>

            <!-- Secure Payment Badge -->
            <div class="flex items-center justify-center mt-3 text-gray-400 text-sm">
                <LockClosedIcon class="w-4 h-4 mr-1" />
                Paiement securise SSL
            </div>
        </div>

        <!-- Bottom spacing for fixed button -->
        <div class="h-32"></div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import StripeCardElement from '@/Components/Payment/StripeCardElement.vue'
import {
    CreditCardIcon,
    CheckCircleIcon,
    PlusCircleIcon,
    LockClosedIcon,
    ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    unpaidInvoices: Array,
    savedCards: Array,
    hasSepa: Boolean,
    preselectedInvoiceId: [Number, String],
    stripeKey: String,
    paypalClientId: String,
})

// State
const selectedInvoiceIds = ref(props.preselectedInvoiceId ? [parseInt(props.preselectedInvoiceId)] : [])
const selectedMethod = ref(props.savedCards?.length > 0 ? `card_${props.savedCards[0].id}` : 'new_card')
const saveCard = ref(false)
const processing = ref(false)
const errorMessage = ref(null)
const clientSecret = ref(null)
const cardComplete = ref(false)
const stripeCardRef = ref(null)
const stripeReady = ref(false)

// Computed
const selectedInvoices = computed(() => {
    return (props.unpaidInvoices || []).filter(i => selectedInvoiceIds.value.includes(i.id))
})

const totalAmount = computed(() => {
    return selectedInvoices.value.reduce((sum, inv) => sum + parseFloat(inv.balance || 0), 0)
})

const allSelected = computed(() => {
    return selectedInvoiceIds.value.length === (props.unpaidInvoices || []).length
})

const canPay = computed(() => {
    if (selectedInvoices.value.length === 0) return false
    if (!selectedMethod.value) return false

    // For new card, need Stripe to be ready and card complete
    if (selectedMethod.value === 'new_card') {
        return stripeReady.value && cardComplete.value
    }

    return true
})

// Methods
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

const isSelected = (id) => {
    return selectedInvoiceIds.value.includes(id)
}

const selectAll = () => {
    if (allSelected.value) {
        selectedInvoiceIds.value = []
    } else {
        selectedInvoiceIds.value = (props.unpaidInvoices || []).map(i => i.id)
    }
}

// Stripe handlers
const onStripeReady = (data) => {
    stripeReady.value = true
}

const onCardChange = (data) => {
    cardComplete.value = data.complete
    if (!data.complete) {
        errorMessage.value = null
    }
}

const onCardComplete = () => {
    cardComplete.value = true
    errorMessage.value = null
}

const onCardError = (error) => {
    errorMessage.value = error.message
}

// Payment processing
const processPayment = async () => {
    if (!canPay.value || processing.value) return

    processing.value = true
    errorMessage.value = null

    try {
        if (selectedMethod.value === 'paypal') {
            await processPayPal()
        } else if (selectedMethod.value === 'new_card') {
            await processNewCard()
        } else if (selectedMethod.value.startsWith('card_')) {
            await processSavedCard()
        }
    } catch (error) {
        errorMessage.value = error.message || 'Une erreur est survenue'
        processing.value = false
    }
}

const processNewCard = async () => {
    // 1. Create payment intent
    const intentResponse = await axios.post(route('mobile.pay.stripe.intent'), {
        invoice_ids: selectedInvoiceIds.value,
        save_card: saveCard.value,
    })

    const { clientSecret: secret, paymentIntentId } = intentResponse.data

    // 2. Confirm payment with Stripe
    const stripeCard = stripeCardRef.value
    if (!stripeCard) {
        throw new Error('Stripe not initialized')
    }

    const paymentIntent = await stripeCard.confirmPayment(secret)

    // 3. Confirm with backend
    const confirmResponse = await axios.post(route('mobile.pay.stripe.confirm'), {
        payment_intent_id: paymentIntent.id,
        invoice_ids: selectedInvoiceIds.value,
    })

    if (confirmResponse.data.success) {
        router.visit(confirmResponse.data.redirect)
    } else {
        throw new Error(confirmResponse.data.error)
    }
}

const processSavedCard = async () => {
    const cardId = selectedMethod.value.replace('card_', '')

    const response = await axios.post(route('mobile.pay.charge-saved'), {
        payment_method_id: cardId,
        invoice_ids: selectedInvoiceIds.value,
    })

    if (response.data.success) {
        router.visit(response.data.redirect)
    } else {
        throw new Error(response.data.error)
    }
}

const processPayPal = async () => {
    const response = await axios.post(route('mobile.pay.paypal.create'), {
        invoice_ids: selectedInvoiceIds.value,
    })

    if (response.data.approvalUrl) {
        // Redirect to PayPal
        window.location.href = response.data.approvalUrl
    } else {
        throw new Error('Erreur PayPal: URL non disponible')
    }
}

// Auto-select first invoice if preselected
onMounted(() => {
    if (props.preselectedInvoiceId) {
        const id = parseInt(props.preselectedInvoiceId)
        if (!selectedInvoiceIds.value.includes(id)) {
            selectedInvoiceIds.value.push(id)
        }
    }
})
</script>
