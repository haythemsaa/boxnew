<template>
    <MobileLayout title="Reserver un Box">
        <!-- Step Indicator -->
        <div class="flex items-center justify-between mb-6 px-2">
            <div v-for="(stepInfo, index) in steps" :key="index" class="flex items-center">
                <div
                    class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-sm"
                    :class="step >= index + 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-500'"
                >
                    <CheckIcon v-if="step > index + 1" class="w-4 h-4" />
                    <span v-else>{{ index + 1 }}</span>
                </div>
                <div v-if="index < steps.length - 1" class="w-6 h-0.5 mx-0.5" :class="step > index + 1 ? 'bg-primary-600' : 'bg-gray-200'"></div>
            </div>
        </div>

        <!-- Step 1: Select Site -->
        <div v-if="step === 1">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Choisir un site</h2>

            <div class="relative mb-4">
                <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                    v-model="siteSearch"
                    type="text"
                    placeholder="Rechercher par ville ou nom..."
                    class="w-full pl-10 pr-4 py-3 bg-white border-0 rounded-xl shadow-sm focus:ring-2 focus:ring-primary-500"
                />
            </div>

            <div class="space-y-3">
                <button
                    v-for="site in filteredSites"
                    :key="site.id"
                    @click="selectSite(site)"
                    class="w-full bg-white rounded-xl shadow-sm p-4 text-left hover:shadow-md transition"
                    :class="selectedSite?.id === site.id ? 'ring-2 ring-primary-500' : ''"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start">
                            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center mr-3">
                                <BuildingOfficeIcon class="w-6 h-6 text-primary-600" />
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ site.name }}</h3>
                                <p class="text-sm text-gray-500 flex items-center mt-0.5">
                                    <MapPinIcon class="w-4 h-4 mr-1" />
                                    {{ site.city }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-green-600">{{ site.boxes?.length || 0 }} dispo</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Step 2: Select Box Size -->
        <div v-if="step === 2">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Choisir un box</h2>
            <p class="text-gray-500 mb-4">Site: {{ selectedSite?.name }}</p>

            <div class="flex space-x-2 mb-4 overflow-x-auto pb-2">
                <button
                    v-for="size in sizeFilters"
                    :key="size.value"
                    @click="selectedSize = size.value"
                    :class="[
                        'px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition',
                        selectedSize === size.value ? 'bg-primary-600 text-white' : 'bg-white text-gray-700'
                    ]"
                >
                    {{ size.label }}
                </button>
            </div>

            <div class="space-y-3">
                <button
                    v-for="box in filteredBoxes"
                    :key="box.id"
                    @click="selectBox(box)"
                    class="w-full bg-white rounded-xl shadow-sm overflow-hidden text-left hover:shadow-md transition"
                    :class="selectedBox?.id === box.id ? 'ring-2 ring-primary-500' : ''"
                >
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-bold text-gray-900">Box {{ box.number }}</h3>
                                <p class="text-sm text-gray-500">{{ box.volume }} m³</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xl font-bold text-primary-600">{{ box.monthly_price }}€</p>
                                <p class="text-xs text-gray-400">/mois</p>
                            </div>
                        </div>
                    </div>
                </button>
            </div>

            <div v-if="filteredBoxes.length === 0" class="text-center py-12">
                <CubeIcon class="w-16 h-16 mx-auto text-gray-300 mb-4" />
                <p class="text-gray-500">Aucun box disponible</p>
            </div>
        </div>

        <!-- Step 3: Select Dates -->
        <div v-if="step === 3">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Date de debut</h2>
            <p class="text-gray-500 mb-4">Box {{ selectedBox?.number }}</p>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'entree</label>
                    <input
                        type="date"
                        v-model="startDate"
                        :min="minStartDate"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500"
                    />
                </div>
            </div>

            <!-- Options -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-3">Options</h3>

                <label class="flex items-center justify-between py-3 border-b border-gray-100">
                    <div>
                        <p class="font-medium text-gray-900">Depot de garantie</p>
                        <p class="text-sm text-gray-500">2 mois de loyer ({{ selectedBox?.monthly_price * 2 }}€)</p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="includeDeposit"
                        class="w-5 h-5 rounded border-gray-300 text-primary-600"
                    />
                </label>

                <label class="flex items-center justify-between py-3">
                    <div>
                        <p class="font-medium text-gray-900">Assurance</p>
                        <p class="text-sm text-gray-500">Protection du contenu (15€/mois)</p>
                    </div>
                    <input
                        type="checkbox"
                        v-model="includeInsurance"
                        class="w-5 h-5 rounded border-gray-300 text-primary-600"
                    />
                </label>
            </div>
        </div>

        <!-- Step 4: Confirmation & Summary -->
        <div v-if="step === 4">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Recapitulatif</h2>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Site</span>
                        <span class="font-medium text-gray-900">{{ selectedSite?.name }}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Box</span>
                        <span class="font-medium text-gray-900">{{ selectedBox?.number }} ({{ selectedBox?.volume }} m³)</span>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100">
                        <span class="text-gray-500">Date de debut</span>
                        <span class="font-medium text-gray-900">{{ formatDate(startDate) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-3">A payer maintenant</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">1er mois de location</span>
                        <span class="text-gray-900">{{ selectedBox?.monthly_price }}€</span>
                    </div>
                    <div v-if="includeDeposit" class="flex justify-between">
                        <span class="text-gray-500">Depot de garantie</span>
                        <span class="text-gray-900">{{ selectedBox?.monthly_price * 2 }}€</span>
                    </div>
                    <div v-if="includeInsurance" class="flex justify-between">
                        <span class="text-gray-500">Assurance</span>
                        <span class="text-gray-900">15€</span>
                    </div>
                    <div class="flex justify-between pt-3 border-t border-gray-200">
                        <span class="font-bold text-gray-900">Total</span>
                        <span class="font-bold text-primary-600 text-xl">{{ totalAmount }}€</span>
                    </div>
                </div>
            </div>

            <label class="flex items-start bg-white rounded-xl shadow-sm p-4 mb-4">
                <input
                    type="checkbox"
                    v-model="acceptTerms"
                    class="mt-1 rounded border-gray-300 text-primary-600"
                />
                <span class="ml-3 text-sm text-gray-600">
                    J'accepte les <a href="#" class="text-primary-600">conditions generales</a>
                </span>
            </label>
        </div>

        <!-- Step 5: Payment -->
        <div v-if="step === 5">
            <h2 class="text-xl font-bold text-gray-900 mb-2">Paiement</h2>
            <p class="text-gray-500 mb-4">Montant: <span class="font-bold text-primary-600">{{ totalAmount }}€</span></p>

            <!-- Payment Method Selection -->
            <div class="space-y-3 mb-6">
                <label
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="paymentMethod === 'card' ? 'ring-2 ring-primary-500' : ''"
                >
                    <input type="radio" value="card" v-model="paymentMethod" class="w-5 h-5 text-primary-600" />
                    <div class="ml-3 flex items-center">
                        <CreditCardIcon class="w-6 h-6 text-blue-500 mr-2" />
                        <span class="font-medium">Carte bancaire</span>
                    </div>
                </label>

                <label
                    class="flex items-center bg-white rounded-xl shadow-sm p-4 cursor-pointer"
                    :class="paymentMethod === 'paypal' ? 'ring-2 ring-primary-500' : ''"
                >
                    <input type="radio" value="paypal" v-model="paymentMethod" class="w-5 h-5 text-primary-600" />
                    <div class="ml-3 flex items-center">
                        <div class="w-6 h-6 mr-2 text-[#003087]">
                            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.632h6.927c2.3 0 4.145.562 5.342 1.593 1.133.97 1.605 2.419 1.363 4.188-.262 1.932-1.047 3.485-2.335 4.62-1.307 1.152-3.02 1.74-5.096 1.74h-2.21a.77.77 0 0 0-.758.632l-.87 5.476z"/></svg>
                        </div>
                        <span class="font-medium">PayPal</span>
                    </div>
                </label>
            </div>

            <!-- Stripe Card Element -->
            <div v-if="paymentMethod === 'card'" class="bg-white rounded-xl shadow-sm p-4 mb-4">
                <StripeCardElement
                    v-if="stripeKey"
                    ref="stripeCardRef"
                    :stripe-key="stripeKey"
                    @ready="stripeReady = true"
                    @change="onCardChange"
                    @complete="cardComplete = true"
                />
            </div>

            <!-- PayPal Info -->
            <div v-if="paymentMethod === 'paypal'" class="bg-blue-50 rounded-xl p-4 mb-4">
                <p class="text-sm text-blue-700">Vous serez redirige vers PayPal pour finaliser le paiement.</p>
            </div>

            <!-- Error -->
            <div v-if="errorMessage" class="bg-red-50 text-red-700 rounded-xl p-4 mb-4">
                {{ errorMessage }}
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="fixed bottom-24 left-4 right-4 flex space-x-3">
            <button
                v-if="step > 1"
                @click="previousStep"
                class="flex-1 py-3.5 bg-gray-100 text-gray-700 font-semibold rounded-xl"
            >
                Retour
            </button>
            <button
                @click="nextStep"
                :disabled="!canProceed || processing"
                class="flex-1 py-3.5 bg-primary-600 text-white font-semibold rounded-xl disabled:opacity-50 flex items-center justify-center"
            >
                <span v-if="processing" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Traitement...
                </span>
                <span v-else>
                    {{ step === 5 ? (paymentMethod === 'paypal' ? 'Payer avec PayPal' : 'Payer ' + totalAmount + '€') : (step === 4 ? 'Passer au paiement' : 'Continuer') }}
                </span>
            </button>
        </div>

        <div class="h-32"></div>
    </MobileLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import StripeCardElement from '@/Components/Payment/StripeCardElement.vue'
import {
    MagnifyingGlassIcon,
    MapPinIcon,
    BuildingOfficeIcon,
    CubeIcon,
    CheckIcon,
    CreditCardIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    customer: Object,
    stripeKey: String,
})

const step = ref(1)
const steps = ['Site', 'Box', 'Options', 'Resume', 'Paiement']

// Step 1
const siteSearch = ref('')
const selectedSite = ref(null)

// Step 2
const selectedSize = ref('all')
const selectedBox = ref(null)

// Step 3
const startDate = ref(new Date().toISOString().split('T')[0])
const includeDeposit = ref(true)
const includeInsurance = ref(false)

// Step 4
const acceptTerms = ref(false)

// Step 5
const paymentMethod = ref('card')
const stripeCardRef = ref(null)
const stripeReady = ref(false)
const cardComplete = ref(false)
const processing = ref(false)
const errorMessage = ref(null)

const sizeFilters = [
    { value: 'all', label: 'Tous' },
    { value: 'small', label: '1-5 m³' },
    { value: 'medium', label: '5-15 m³' },
    { value: 'large', label: '15+ m³' },
]

const minStartDate = computed(() => new Date().toISOString().split('T')[0])

const filteredSites = computed(() => {
    if (!siteSearch.value) return props.sites || []
    const query = siteSearch.value.toLowerCase()
    return (props.sites || []).filter(s =>
        s.name?.toLowerCase().includes(query) ||
        s.city?.toLowerCase().includes(query)
    )
})

const filteredBoxes = computed(() => {
    if (!selectedSite.value?.boxes) return []
    let boxes = selectedSite.value.boxes.filter(b => b.status === 'available')

    if (selectedSize.value !== 'all') {
        boxes = boxes.filter(b => {
            const vol = b.volume
            switch (selectedSize.value) {
                case 'small': return vol >= 1 && vol < 5
                case 'medium': return vol >= 5 && vol < 15
                case 'large': return vol >= 15
                default: return true
            }
        })
    }
    return boxes
})

const totalAmount = computed(() => {
    if (!selectedBox.value) return 0
    let total = selectedBox.value.monthly_price
    if (includeDeposit.value) total += selectedBox.value.monthly_price * 2
    if (includeInsurance.value) total += 15
    return total
})

const canProceed = computed(() => {
    switch (step.value) {
        case 1: return selectedSite.value !== null
        case 2: return selectedBox.value !== null
        case 3: return startDate.value
        case 4: return acceptTerms.value
        case 5:
            if (paymentMethod.value === 'card') return stripeReady.value && cardComplete.value
            return true
        default: return false
    }
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

const selectSite = (site) => { selectedSite.value = site }
const selectBox = (box) => { selectedBox.value = box }

const onCardChange = (data) => { cardComplete.value = data.complete }

const previousStep = () => { if (step.value > 1) step.value-- }

const nextStep = async () => {
    if (step.value < 5) {
        step.value++
    } else {
        await processPayment()
    }
}

const processPayment = async () => {
    processing.value = true
    errorMessage.value = null

    try {
        if (paymentMethod.value === 'paypal') {
            await processPayPal()
        } else {
            await processStripe()
        }
    } catch (error) {
        errorMessage.value = error.message || 'Erreur de paiement'
        processing.value = false
    }
}

const processStripe = async () => {
    // Create payment intent
    const intentResponse = await axios.post(route('mobile.reserve.payment-intent'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    const { clientSecret, reservationToken } = intentResponse.data

    // Confirm payment
    const stripeCard = stripeCardRef.value
    const paymentIntent = await stripeCard.confirmPayment(clientSecret)

    // Confirm reservation
    const confirmResponse = await axios.post(route('mobile.reserve.confirm'), {
        payment_intent_id: paymentIntent.id,
        reservation_token: reservationToken,
    })

    if (confirmResponse.data.success) {
        router.visit(confirmResponse.data.redirect)
    } else {
        throw new Error(confirmResponse.data.error)
    }
}

const processPayPal = async () => {
    const response = await axios.post(route('mobile.reserve.paypal.create'), {
        box_id: selectedBox.value.id,
        start_date: startDate.value,
        include_deposit: includeDeposit.value,
        include_insurance: includeInsurance.value,
    })

    if (response.data.approvalUrl) {
        window.location.href = response.data.approvalUrl
    } else {
        throw new Error('Erreur PayPal')
    }
}
</script>
