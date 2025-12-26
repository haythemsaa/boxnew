<template>
    <div class="stripe-card-element">
        <!-- Card Element Container -->
        <div
            ref="cardElementRef"
            class="p-4 border border-gray-300 rounded-xl bg-white transition-all"
            :class="{
                'border-primary-500 ring-2 ring-primary-100': focused,
                'border-red-500': error,
            }"
        ></div>

        <!-- Error Message -->
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>

        <!-- Powered by Stripe -->
        <div class="mt-2 flex items-center justify-end gap-1 text-xs text-gray-400">
            <LockClosedIcon class="w-3 h-3" />
            <span>Securise par</span>
            <svg class="h-4" viewBox="0 0 60 25" fill="currentColor">
                <path d="M59.64 14.28h-8.06c.19 1.93 1.6 2.55 3.2 2.55 1.64 0 2.96-.37 4.05-.95v3.32a10.4 10.4 0 0 1-4.56 1c-4.01 0-6.83-2.5-6.83-7.48 0-4.19 2.39-7.52 6.3-7.52 3.92 0 5.96 3.28 5.96 7.5 0 .4-.02 1.04-.06 1.58zm-8.07-2.8h4.24c0-1.41-.56-2.82-2.02-2.82-1.36 0-2.1 1.36-2.22 2.82zM40.95 20h4.14V5.94h-4.14V20zM35.78 13.44c0-2.58.79-4.43 2.66-4.43.54 0 .97.08 1.32.24V5.42c-.41-.17-.85-.28-1.42-.28-1.55 0-2.83 1.16-3.43 2.85h-.08l-.14-2.38H31.5c.07 1.35.11 2.91.11 4.94V20h4.17v-6.56zM28.32 9.52c-1.19 0-2.12.27-3.04.77l-.38-2.65h-3.48c.06 1.33.1 2.88.1 5.02V20h4.15v-9.03c.52-.38 1.25-.6 2.04-.6.34 0 .64.03.91.1l.34-4.58c-.2-.04-.41-.07-.64-.07v.7zM17.61 14.66c0-3.67-5.45-3.36-5.45-5.4 0-.72.6-1.15 1.7-1.15 1.12 0 2.33.42 3.26 1.03l.95-3.17A9.13 9.13 0 0 0 14 5.14c-3.2 0-5.46 1.72-5.46 4.57 0 3.61 5.46 3.24 5.46 5.43 0 .85-.72 1.28-1.88 1.28-1.37 0-2.82-.55-3.92-1.34l-1.01 3.25c1.26.73 2.9 1.2 4.65 1.2 3.44 0 5.77-1.62 5.77-4.87zM6.17 8.26C6.17 7.6 6.63 7.1 7.3 7.1c.67 0 1.13.5 1.13 1.16 0 .66-.46 1.17-1.13 1.17-.67 0-1.13-.51-1.13-1.17z"/>
            </svg>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { loadStripe } from '@stripe/stripe-js'
import { LockClosedIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    stripeKey: {
        type: String,
        required: true,
    },
    clientSecret: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['ready', 'change', 'error', 'complete'])

const cardElementRef = ref(null)
const focused = ref(false)
const error = ref(null)

let stripe = null
let elements = null
let cardElement = null

onMounted(async () => {
    if (!props.stripeKey) {
        error.value = 'Stripe key not configured'
        return
    }

    try {
        stripe = await loadStripe(props.stripeKey)

        if (!stripe) {
            error.value = 'Failed to load Stripe'
            return
        }

        // Create elements instance
        const options = props.clientSecret
            ? { clientSecret: props.clientSecret }
            : {}

        elements = stripe.elements(options)

        // Create card element with French locale
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#374151',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    '::placeholder': {
                        color: '#9CA3AF',
                    },
                },
                invalid: {
                    color: '#EF4444',
                    iconColor: '#EF4444',
                },
            },
            hidePostalCode: true,
        })

        // Mount to DOM
        cardElement.mount(cardElementRef.value)

        // Event listeners
        cardElement.on('focus', () => {
            focused.value = true
        })

        cardElement.on('blur', () => {
            focused.value = false
        })

        cardElement.on('change', (event) => {
            if (event.error) {
                error.value = event.error.message
                emit('error', event.error)
            } else {
                error.value = null
            }

            emit('change', {
                complete: event.complete,
                empty: event.empty,
                brand: event.brand,
            })

            if (event.complete) {
                emit('complete')
            }
        })

        cardElement.on('ready', () => {
            emit('ready', { stripe, elements, cardElement })
        })
    } catch (e) {
        error.value = 'Erreur de chargement Stripe'
        console.error('Stripe initialization error:', e)
    }
})

onUnmounted(() => {
    if (cardElement) {
        cardElement.destroy()
    }
})

// Expose methods for parent component
defineExpose({
    getStripe: () => stripe,
    getElements: () => elements,
    getCardElement: () => cardElement,
    confirmPayment: async (clientSecret, billingDetails = {}) => {
        if (!stripe || !cardElement) {
            throw new Error('Stripe not initialized')
        }

        const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(
            clientSecret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: billingDetails,
                },
            }
        )

        if (confirmError) {
            throw confirmError
        }

        return paymentIntent
    },
})
</script>

<style scoped>
.stripe-card-element {
    /* Stripe injects iframe with height */
}
</style>
