<template>
    <MobileLayout title="Payer" :show-back="true">
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

        <!-- Payment Method -->
        <div class="mb-6" v-if="selectedInvoices.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Mode de paiement</h3>

            <div class="space-y-3">
                <label
                    v-for="method in paymentMethods"
                    :key="method.id"
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="selectedMethod === method.id ? 'ring-2 ring-primary-500' : ''"
                >
                    <input
                        type="radio"
                        :value="method.id"
                        v-model="selectedMethod"
                        class="w-5 h-5 border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <div class="flex-1 ml-3 flex items-center justify-between">
                        <div class="flex items-center">
                            <component :is="method.icon" class="w-8 h-8 mr-3" :class="method.iconClass" />
                            <div>
                                <p class="font-medium text-gray-900">{{ method.name }}</p>
                                <p class="text-sm text-gray-500">{{ method.description }}</p>
                            </div>
                        </div>
                        <span v-if="method.badge" class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">
                            {{ method.badge }}
                        </span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Card Form (if card selected) -->
        <div v-if="selectedMethod === 'card' && selectedInvoices.length > 0" class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Informations de carte</h3>

            <div class="bg-white rounded-xl shadow-sm p-4 space-y-4">
                <!-- Saved Cards -->
                <div v-if="savedCards.length > 0" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cartes enregistrees</label>
                    <div class="space-y-2">
                        <label
                            v-for="card in savedCards"
                            :key="card.id"
                            class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer"
                            :class="selectedCard === card.id ? 'ring-2 ring-primary-500' : ''"
                        >
                            <input
                                type="radio"
                                :value="card.id"
                                v-model="selectedCard"
                                class="w-4 h-4 border-gray-300 text-primary-600"
                            />
                            <div class="ml-3 flex items-center">
                                <CreditCardIcon class="w-6 h-6 text-gray-400 mr-2" />
                                <span class="font-medium">**** {{ card.last4 }}</span>
                                <span class="text-gray-500 ml-2">{{ card.expiry }}</span>
                            </div>
                        </label>
                        <label
                            class="flex items-center p-3 bg-gray-50 rounded-lg cursor-pointer"
                            :class="selectedCard === 'new' ? 'ring-2 ring-primary-500' : ''"
                        >
                            <input
                                type="radio"
                                value="new"
                                v-model="selectedCard"
                                class="w-4 h-4 border-gray-300 text-primary-600"
                            />
                            <div class="ml-3 flex items-center">
                                <PlusCircleIcon class="w-6 h-6 text-primary-600 mr-2" />
                                <span class="font-medium text-primary-600">Nouvelle carte</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- New Card Form -->
                <div v-if="selectedCard === 'new' || savedCards.length === 0">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Numero de carte</label>
                        <input
                            type="text"
                            v-model="cardNumber"
                            placeholder="1234 5678 9012 3456"
                            maxlength="19"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiration</label>
                            <input
                                type="text"
                                v-model="cardExpiry"
                                placeholder="MM/YY"
                                maxlength="5"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">CVV</label>
                            <input
                                type="text"
                                v-model="cardCvv"
                                placeholder="123"
                                maxlength="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                    </div>
                    <label class="flex items-center mt-4">
                        <input
                            type="checkbox"
                            v-model="saveCard"
                            class="w-4 h-4 rounded border-gray-300 text-primary-600"
                        />
                        <span class="ml-2 text-sm text-gray-600">Enregistrer cette carte</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- SEPA Form (if sepa selected) -->
        <div v-if="selectedMethod === 'sepa' && selectedInvoices.length > 0" class="mb-6">
            <div class="bg-blue-50 rounded-xl p-4 flex items-start">
                <InformationCircleIcon class="w-6 h-6 text-blue-500 mr-3 flex-shrink-0" />
                <p class="text-sm text-blue-700">
                    Le prelevement SEPA sera effectue dans les 3 jours ouvrables. Assurez-vous que votre compte dispose des fonds necessaires.
                </p>
            </div>
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
                    Payer {{ formatCurrency(totalAmount) }}
                </span>
            </button>

            <!-- Secure Payment Badge -->
            <div class="flex items-center justify-center mt-3 text-gray-400 text-sm">
                <LockClosedIcon class="w-4 h-4 mr-1" />
                Paiement securise SSL
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    CreditCardIcon,
    CheckCircleIcon,
    PlusCircleIcon,
    LockClosedIcon,
    InformationCircleIcon,
    BuildingLibraryIcon,
    BanknotesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    unpaidInvoices: Array,
    savedCards: Array,
    preselectedInvoiceId: Number,
})

const selectedInvoiceIds = ref(props.preselectedInvoiceId ? [props.preselectedInvoiceId] : [])
const selectedMethod = ref('card')
const selectedCard = ref(props.savedCards?.length > 0 ? props.savedCards[0].id : 'new')
const cardNumber = ref('')
const cardExpiry = ref('')
const cardCvv = ref('')
const saveCard = ref(false)
const processing = ref(false)

const paymentMethods = [
    {
        id: 'card',
        name: 'Carte bancaire',
        description: 'Visa, Mastercard, CB',
        icon: CreditCardIcon,
        iconClass: 'text-blue-500',
        badge: 'Recommande',
    },
    {
        id: 'sepa',
        name: 'Prelevement SEPA',
        description: 'Depuis votre compte bancaire',
        icon: BuildingLibraryIcon,
        iconClass: 'text-purple-500',
    },
]

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

    if (selectedMethod.value === 'card') {
        if (selectedCard.value === 'new' || props.savedCards?.length === 0) {
            return cardNumber.value && cardExpiry.value && cardCvv.value
        }
        return true
    }

    return true
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

const processPayment = () => {
    if (!canPay.value || processing.value) return

    processing.value = true

    const paymentData = {
        invoice_ids: selectedInvoiceIds.value,
        method: selectedMethod.value,
        amount: totalAmount.value,
    }

    if (selectedMethod.value === 'card') {
        if (selectedCard.value !== 'new') {
            paymentData.card_id = selectedCard.value
        } else {
            paymentData.card_number = cardNumber.value
            paymentData.card_expiry = cardExpiry.value
            paymentData.card_cvv = cardCvv.value
            paymentData.save_card = saveCard.value
        }
    }

    router.post(route('mobile.pay.process'), paymentData, {
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
