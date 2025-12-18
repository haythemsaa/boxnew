<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import {
    CubeIcon,
    MapPinIcon,
    CalendarIcon,
    CheckIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    TicketIcon,
    ExclamationCircleIcon,
    MagnifyingGlassIcon,
    InformationCircleIcon,
    ShieldCheckIcon,
    ClockIcon,
    BoltIcon,
    SunIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    XMarkIcon,
    ArrowLeftIcon,
    DevicePhoneMobileIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon, StarIcon, CreditCardIcon, LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    settings: Object,
    tenant: Object,
    sites: Array,
    preselectedSite: {
        type: Number,
        default: null,
    },
    preselectedBox: {
        type: Number,
        default: null,
    },
})

// Mobile detection
const isMobile = ref(false)
const checkMobile = () => {
    isMobile.value = window.innerWidth < 768 || /iPhone|iPad|iPod|Android/i.test(navigator.userAgent)
}

// Progress tracking for conversion optimization
const currentStep = computed(() => {
    if (!selectedSite.value) return 1
    if (!selectedBox.value) return 2
    if (!form.value.start_date) return 3
    if (!form.value.customer_email) return 4
    return 5
})

const progressPercent = computed(() => (currentStep.value / 5) * 100)

// Auto-save to localStorage for mobile users who might lose connection
const STORAGE_KEY = 'boxibox_checkout_draft'

const saveFormDraft = () => {
    if (typeof localStorage !== 'undefined') {
        const draft = {
            selectedSite: selectedSite.value,
            selectedBox: selectedBox.value,
            form: form.value,
            savedAt: Date.now(),
        }
        localStorage.setItem(STORAGE_KEY, JSON.stringify(draft))
    }
}

const loadFormDraft = () => {
    if (typeof localStorage !== 'undefined') {
        try {
            const draft = JSON.parse(localStorage.getItem(STORAGE_KEY))
            if (draft && Date.now() - draft.savedAt < 24 * 60 * 60 * 1000) { // 24h max
                // Restore only if site/box still available
                if (draft.selectedSite && props.sites.find(s => s.id === draft.selectedSite)) {
                    selectedSite.value = draft.selectedSite
                }
                if (draft.form) {
                    Object.assign(form.value, draft.form)
                }
                return true
            }
        } catch (e) {}
    }
    return false
}

const clearFormDraft = () => {
    if (typeof localStorage !== 'undefined') {
        localStorage.removeItem(STORAGE_KEY)
    }
}

// Apple Pay / Google Pay support
const walletPaymentAvailable = ref(false)
const paymentRequest = ref(null)

const initWalletPayments = async () => {
    if (!props.settings?.stripe_publishable_key || !stripeInstance.value) return

    try {
        paymentRequest.value = stripeInstance.value.paymentRequest({
            country: 'FR',
            currency: 'eur',
            total: {
                label: 'Réservation Box',
                amount: Math.round((totalDueNow.value || 100) * 100),
            },
            requestPayerName: true,
            requestPayerEmail: true,
            requestPayerPhone: true,
        })

        const result = await paymentRequest.value.canMakePayment()
        walletPaymentAvailable.value = !!result

        if (result) {
            paymentRequest.value.on('paymentmethod', async (ev) => {
                await handleWalletPayment(ev)
            })
        }
    } catch (e) {
        console.log('Wallet payments not available:', e)
    }
}

const handleWalletPayment = async (ev) => {
    try {
        // Auto-fill form from wallet
        if (ev.payerName) {
            const nameParts = ev.payerName.split(' ')
            form.value.customer_first_name = nameParts[0] || ''
            form.value.customer_last_name = nameParts.slice(1).join(' ') || ''
        }
        if (ev.payerEmail) form.value.customer_email = ev.payerEmail
        if (ev.payerPhone) form.value.customer_phone = ev.payerPhone

        // Create payment intent
        const intentResponse = await fetch(route('public.booking.create-payment-intent'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                amount: totalDueNow.value,
                customer_email: form.value.customer_email,
                customer_name: `${form.value.customer_first_name} ${form.value.customer_last_name}`,
            }),
        })

        const intentData = await intentResponse.json()

        const { error, paymentIntent } = await stripeInstance.value.confirmCardPayment(
            intentData.client_secret,
            { payment_method: ev.paymentMethod.id },
            { handleActions: false }
        )

        if (error) {
            ev.complete('fail')
            cardError.value = error.message
            return
        }

        ev.complete('success')

        // Submit booking with payment
        await submitBookingWithPayment(paymentIntent.id)

    } catch (e) {
        ev.complete('fail')
        cardError.value = e.message || 'Erreur de paiement'
    }
}

const submitBookingWithPayment = async (paymentIntentId) => {
    processing.value = true

    try {
        const response = await fetch(route('public.booking.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                site_id: selectedSite.value,
                box_id: selectedBox.value,
                ...form.value,
                promo_code: promoApplied.value?.code,
                source: isMobile.value ? 'mobile_checkout' : 'desktop_checkout',
                payment_method: 'wallet',
                payment_intent_id: paymentIntentId,
                amount_paid: totalDueNow.value,
            }),
        })

        const data = await response.json()

        if (data.success) {
            clearFormDraft()
            success.value = true
            bookingResult.value = data.booking
            window.scrollTo({ top: 0, behavior: 'smooth' })

            // Track conversion
            trackConversion('booking_completed', { method: 'wallet_payment' })
        } else {
            errors.value = data.errors || {}
        }
    } catch (e) {
        errors.value.general = 'Une erreur est survenue'
    } finally {
        processing.value = false
    }
}

// Conversion tracking
const trackConversion = (event, data = {}) => {
    // Google Analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', event, { ...data, currency: 'EUR', value: totalDueNow.value })
    }
    // Facebook Pixel
    if (typeof fbq !== 'undefined') {
        fbq('track', event === 'booking_completed' ? 'Purchase' : 'InitiateCheckout', {
            currency: 'EUR',
            value: totalDueNow.value,
        })
    }
}

// Urgency indicators for conversion
const urgencyMessage = computed(() => {
    if (!selectedBoxData.value) return null

    const available = availableBoxes.value.filter(b =>
        b.volume === selectedBoxData.value.volume
    ).length

    if (available <= 2) {
        return { type: 'critical', message: `Plus que ${available} box de cette taille !` }
    }
    if (available <= 5) {
        return { type: 'warning', message: `Seulement ${available} box disponibles` }
    }
    return null
})

// Recently viewed (social proof)
const recentViewers = ref(Math.floor(Math.random() * 8) + 3)

// Reactive state
const selectedSite = ref(props.preselectedSite || null)
const selectedBox = ref(props.preselectedBox || null)
const promoCode = ref('')
const promoApplied = ref(null)
const promoError = ref('')
const processing = ref(false)
const success = ref(false)
const bookingResult = ref(null)
const showSizeGuide = ref(false)
const showPromoInput = ref(false)
const sizeFilter = ref('all')

// Payment
const paymentMethod = ref('at_signing')
const paymentProcessing = ref(false)
const stripeLoaded = ref(false)
const cardElement = ref(null)
const stripeInstance = ref(null)
const stripeElements = ref(null)
const cardError = ref('')

// Expanded sections (all open by default for one-page flow)
const expandedSections = ref({
    site: true,
    box: true,
    date: true,
    customer: true,
    payment: true,
})

// Form data
const form = ref({
    customer_first_name: '',
    customer_last_name: '',
    customer_email: '',
    customer_phone: '',
    customer_address: '',
    customer_city: '',
    customer_postal_code: '',
    customer_country: 'FR',
    customer_company: '',
    start_date: '',
    duration_type: 'month_to_month',
    notes: '',
    terms_accepted: false,
})

const errors = ref({})

// Size categories
const sizeCategories = [
    { value: 'all', label: 'Toutes', icon: '📦' },
    { value: 'xs', label: 'Casier', icon: '📦', min: 0, max: 2 },
    { value: 'small', label: 'Petit', icon: '🛋️', min: 2, max: 5 },
    { value: 'medium', label: 'Moyen', icon: '🏠', min: 5, max: 10 },
    { value: 'large', label: 'Grand', icon: '🏘️', min: 10, max: 20 },
    { value: 'xl', label: 'Très grand', icon: '🏢', min: 20, max: Infinity },
]

// Size guide examples
const sizeExamples = [
    { volume: 1, icon: '📦', title: 'Casier', desc: '10 cartons', examples: ['Affaires perso', 'Dossiers'] },
    { volume: 3, icon: '🛋️', title: 'Studio', desc: '1 canapé + cartons', examples: ['1 lit', '20 cartons'] },
    { volume: 5, icon: '🏠', title: 'T2', desc: 'Appartement complet', examples: ['Salon', 'Chambre'] },
    { volume: 10, icon: '🏘️', title: 'T3/T4', desc: '2-3 chambres', examples: ['Électroménager', 'Meubles'] },
    { volume: 20, icon: '🏡', title: 'Maison', desc: 'Mobilier complet', examples: ['4+ pièces', 'Jardin'] },
]

// Computed properties
const availableBoxes = computed(() => {
    if (!selectedSite.value) return []
    const site = props.sites.find(s => s.id === selectedSite.value)
    let boxes = site?.boxes?.filter(b => b.status === 'available') || []

    // Apply size filter
    if (sizeFilter.value !== 'all') {
        const category = sizeCategories.find(c => c.value === sizeFilter.value)
        if (category) {
            boxes = boxes.filter(b => b.volume >= category.min && b.volume < category.max)
        }
    }

    // Sort by price
    return [...boxes].sort((a, b) => a.current_price - b.current_price)
})

const selectedSiteData = computed(() => {
    return props.sites.find(s => s.id === selectedSite.value)
})

const selectedBoxData = computed(() => {
    if (!selectedBox.value || !selectedSite.value) return null
    const site = props.sites.find(s => s.id === selectedSite.value)
    return site?.boxes?.find(b => b.id === selectedBox.value)
})

const monthlyPrice = computed(() => {
    if (!selectedBoxData.value) return 0
    let price = selectedBoxData.value.current_price || 0
    if (promoApplied.value) {
        price -= promoApplied.value.calculated_discount
    }
    return Math.max(0, price)
})

const depositAmount = computed(() => {
    if (!props.settings?.require_deposit) return 0
    if (props.settings.deposit_percentage > 0) {
        return monthlyPrice.value * (props.settings.deposit_percentage / 100)
    }
    return props.settings.deposit_amount || 0
})

const totalDueNow = computed(() => {
    if (paymentMethod.value === 'at_signing') return 0
    return monthlyPrice.value + depositAmount.value
})

const totalDueAtSigning = computed(() => {
    return monthlyPrice.value + depositAmount.value
})

const isFormComplete = computed(() => {
    return selectedSite.value &&
           selectedBox.value &&
           form.value.customer_first_name &&
           form.value.customer_last_name &&
           form.value.customer_email &&
           form.value.start_date &&
           form.value.terms_accepted
})

const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

// Helper functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    })
}

const getSizeCategory = (volume) => {
    for (const cat of sizeCategories) {
        if (cat.value !== 'all' && volume >= cat.min && volume < cat.max) {
            return cat
        }
    }
    return sizeCategories[1]
}

// Promo code handling
const validatePromo = async () => {
    if (!promoCode.value) return

    promoError.value = ''
    try {
        const response = await fetch(route('public.booking.validate-promo'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                code: promoCode.value,
                tenant_id: props.tenant.id,
                site_id: selectedSite.value,
                monthly_price: selectedBoxData.value?.current_price || 0,
            }),
        })

        const data = await response.json()
        if (data.valid) {
            promoApplied.value = data.promo
            showPromoInput.value = false
        } else {
            promoError.value = data.message
            promoApplied.value = null
        }
    } catch (e) {
        promoError.value = 'Erreur lors de la validation'
    }
}

const removePromo = () => {
    promoCode.value = ''
    promoApplied.value = null
    promoError.value = ''
}

// Stripe handling
const loadStripe = async () => {
    if (stripeLoaded.value || !props.settings?.stripe_publishable_key) return

    return new Promise((resolve) => {
        if (window.Stripe) {
            initStripe()
            resolve()
            return
        }

        const script = document.createElement('script')
        script.src = 'https://js.stripe.com/v3/'
        script.onload = () => {
            initStripe()
            resolve()
        }
        document.head.appendChild(script)
    })
}

const initStripe = () => {
    if (!props.settings?.stripe_publishable_key) return

    stripeInstance.value = window.Stripe(props.settings.stripe_publishable_key)
    stripeElements.value = stripeInstance.value.elements({ locale: 'fr' })

    cardElement.value = stripeElements.value.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1f2937',
                fontFamily: 'system-ui, -apple-system, sans-serif',
                '::placeholder': { color: '#9ca3af' },
            },
            invalid: { color: '#ef4444', iconColor: '#ef4444' },
        },
        hidePostalCode: true,
    })

    setTimeout(() => {
        const container = document.getElementById('card-element')
        if (container) {
            cardElement.value.mount('#card-element')
            stripeLoaded.value = true
            cardElement.value.on('change', (event) => {
                cardError.value = event.error ? event.error.message : ''
            })
        }
    }, 100)
}

const processPayment = async () => {
    if (!stripeInstance.value || !cardElement.value) {
        cardError.value = 'Erreur de chargement du paiement'
        return null
    }

    paymentProcessing.value = true
    cardError.value = ''

    try {
        const intentResponse = await fetch(route('public.booking.create-payment-intent'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                amount: totalDueNow.value,
                customer_email: form.value.customer_email,
                customer_name: `${form.value.customer_first_name} ${form.value.customer_last_name}`,
            }),
        })

        const intentData = await intentResponse.json()
        if (!intentData.client_secret) {
            throw new Error('Erreur lors de la création du paiement')
        }

        const { error, paymentIntent } = await stripeInstance.value.confirmCardPayment(
            intentData.client_secret,
            {
                payment_method: {
                    card: cardElement.value,
                    billing_details: {
                        name: `${form.value.customer_first_name} ${form.value.customer_last_name}`,
                        email: form.value.customer_email,
                        phone: form.value.customer_phone || undefined,
                    },
                },
            }
        )

        if (error) {
            cardError.value = error.message
            return null
        }

        return paymentIntent.id
    } catch (e) {
        cardError.value = e.message || 'Erreur lors du paiement'
        return null
    } finally {
        paymentProcessing.value = false
    }
}

// Form validation
const validateForm = () => {
    errors.value = {}

    if (!form.value.customer_first_name) errors.value.customer_first_name = 'Prénom requis'
    if (!form.value.customer_last_name) errors.value.customer_last_name = 'Nom requis'
    if (!form.value.customer_email) errors.value.customer_email = 'Email requis'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.customer_email)) {
        errors.value.customer_email = 'Email invalide'
    }
    if (!form.value.start_date) errors.value.start_date = 'Date requise'
    if (!selectedSite.value) errors.value.site = 'Sélectionnez un site'
    if (!selectedBox.value) errors.value.box = 'Sélectionnez un box'
    if (!form.value.terms_accepted) errors.value.terms = 'Acceptez les conditions'

    return Object.keys(errors.value).length === 0
}

// Submit booking
const submitBooking = async () => {
    if (!validateForm()) {
        // Scroll to first error
        const firstError = document.querySelector('.text-red-500')
        if (firstError) {
            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' })
        }
        return
    }

    processing.value = true
    errors.value = {}

    let paymentIntentId = null

    if (paymentMethod.value === 'now' && props.settings?.stripe_publishable_key) {
        paymentIntentId = await processPayment()
        if (!paymentIntentId) {
            processing.value = false
            return
        }
    }

    try {
        const response = await fetch(route('public.booking.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                site_id: selectedSite.value,
                box_id: selectedBox.value,
                ...form.value,
                promo_code: promoApplied.value?.code,
                source: 'website_onepage',
                payment_method: paymentMethod.value,
                payment_intent_id: paymentIntentId,
                amount_paid: paymentIntentId ? totalDueNow.value : 0,
            }),
        })

        const data = await response.json()

        if (data.success) {
            success.value = true
            bookingResult.value = data.booking
            window.scrollTo({ top: 0, behavior: 'smooth' })
        } else {
            errors.value = data.errors || {}
        }
    } catch (e) {
        errors.value.general = 'Une erreur est survenue'
    } finally {
        processing.value = false
    }
}

// Watch for payment method change to load Stripe
watch(() => paymentMethod.value, async (newMethod) => {
    if (newMethod === 'now' && props.settings?.stripe_publishable_key && !stripeLoaded.value) {
        await loadStripe()
    }
})

// Watch for site change
watch(selectedSite, () => {
    selectedBox.value = null
    sizeFilter.value = 'all'
})

// Quick date selection
const setQuickDate = (days) => {
    const date = new Date()
    date.setDate(date.getDate() + days)
    form.value.start_date = date.toISOString().split('T')[0]
}

const setNextMonth = () => {
    const date = new Date()
    date.setMonth(date.getMonth() + 1, 1)
    form.value.start_date = date.toISOString().split('T')[0]
}

// Auto-save form on changes (debounced)
let saveTimeout = null
watch([selectedSite, selectedBox, form], () => {
    clearTimeout(saveTimeout)
    saveTimeout = setTimeout(saveFormDraft, 1000)
}, { deep: true })

// Initialize
onMounted(() => {
    // Check mobile
    checkMobile()
    window.addEventListener('resize', checkMobile)

    // Try to restore draft
    const hasDraft = loadFormDraft()

    if (props.sites.length === 1) {
        selectedSite.value = props.sites[0].id
    }

    // Set default date to today if not restored
    if (!form.value.start_date) {
        form.value.start_date = minDate.value
    }

    // Preload Stripe if available
    if (props.settings?.stripe_publishable_key) {
        loadStripe().then(() => {
            initWalletPayments()
        })
    }

    // Track checkout initiation
    trackConversion('checkout_started')
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
})
</script>

<template>
    <Head :title="`Réserver - ${settings?.company_name || tenant.name}`">
        <meta name="description" :content="`Réservez votre box de stockage chez ${settings?.company_name || tenant.name}. Réservation rapide et sécurisée.`" />
    </Head>

    <div class="min-h-screen bg-gray-50">
        <!-- Compact Header with Progress -->
        <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="/" class="flex items-center gap-2 text-gray-600 hover:text-gray-900">
                            <ArrowLeftIcon class="h-5 w-5" />
                            <span class="hidden sm:inline">Retour</span>
                        </a>
                        <div class="h-6 w-px bg-gray-200"></div>
                        <h1 class="text-lg font-bold" :style="{ color: settings?.primary_color }">
                            {{ settings?.company_name || tenant.name }}
                        </h1>
                    </div>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span class="hidden md:flex items-center gap-1">
                            <ShieldCheckIcon class="h-4 w-4 text-green-500" />
                            Paiement sécurisé
                        </span>
                        <span class="hidden md:flex items-center gap-1">
                            <LockClosedIcon class="h-4 w-4 text-green-500" />
                            SSL
                        </span>
                    </div>
                </div>
            </div>
            <!-- Progress Bar (Mobile-friendly) -->
            <div class="h-1 bg-gray-100">
                <div
                    class="h-full transition-all duration-500 ease-out"
                    :style="{ width: progressPercent + '%', backgroundColor: settings?.primary_color }"
                ></div>
            </div>
        </header>

        <!-- Mobile Quick Actions (Sticky bottom on mobile) -->
        <div
            v-if="isMobile && selectedBoxData && !success"
            class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-2xl p-4 safe-area-inset-bottom"
        >
            <!-- Urgency Message -->
            <div
                v-if="urgencyMessage"
                class="mb-3 text-center text-sm font-medium animate-pulse"
                :class="urgencyMessage.type === 'critical' ? 'text-red-600' : 'text-orange-600'"
            >
                {{ urgencyMessage.message }}
            </div>

            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-xl font-bold" :style="{ color: settings?.primary_color }">
                        {{ formatCurrency(totalDueAtSigning) }}
                    </p>
                </div>

                <!-- Apple Pay / Google Pay Button (if available) -->
                <div v-if="walletPaymentAvailable && paymentRequest" class="flex-1">
                    <div id="payment-request-button" class="w-full"></div>
                </div>

                <button
                    v-else
                    @click="submitBooking"
                    :disabled="processing || !isFormComplete"
                    class="flex-1 py-3 px-6 rounded-xl text-white font-semibold transition-all disabled:opacity-50 flex items-center justify-center gap-2"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    <svg v-if="processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ processing ? 'En cours...' : 'Réserver' }}</span>
                </button>
            </div>
        </div>

        <!-- Social Proof Banner -->
        <div
            v-if="!success && selectedSiteData"
            class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-blue-100"
        >
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-center gap-4 text-sm">
                <span class="flex items-center gap-1 text-blue-700">
                    <UserIcon class="h-4 w-4" />
                    {{ recentViewers }} personnes consultent ce site
                </span>
                <span class="hidden sm:flex items-center gap-1 text-green-700">
                    <CheckCircleIcon class="h-4 w-4" />
                    Réservation instantanée
                </span>
            </div>
        </div>

        <!-- Success State -->
        <div v-if="success" class="max-w-2xl mx-auto px-4 py-12">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <CheckCircleIcon class="h-12 w-12 text-green-500" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-3">Réservation confirmée !</h2>
                <p class="text-gray-600 mb-6">
                    Votre réservation <strong>{{ bookingResult?.booking_number }}</strong> a été enregistrée.
                    <br>Un email de confirmation a été envoyé à <strong>{{ form.customer_email }}</strong>
                </p>
                <div class="bg-gray-50 rounded-xl p-6 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-left">
                        <div>
                            <span class="text-gray-500 text-sm">Prix mensuel</span>
                            <p class="text-xl font-bold" :style="{ color: settings?.primary_color }">
                                {{ formatCurrency(bookingResult?.monthly_price) }}
                            </p>
                        </div>
                        <div v-if="bookingResult?.deposit_amount > 0">
                            <span class="text-gray-500 text-sm">Dépôt de garantie</span>
                            <p class="text-xl font-bold text-gray-900">
                                {{ formatCurrency(bookingResult?.deposit_amount) }}
                            </p>
                        </div>
                    </div>
                </div>
                <a
                    :href="`/book/status/${bookingResult?.uuid}`"
                    class="inline-flex items-center px-6 py-3 rounded-xl text-white font-semibold transition-all hover:opacity-90"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Suivre ma réservation
                </a>
            </div>
        </div>

        <!-- Main Checkout -->
        <div v-else class="max-w-7xl mx-auto px-4 py-6">
            <div class="lg:grid lg:grid-cols-3 lg:gap-8">
                <!-- Left Column: Form -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Step 1: Site Selection -->
                    <section class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <button
                            @click="expandedSections.site = !expandedSections.site"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: selectedSite ? '#10B981' : settings?.primary_color }"
                                >
                                    <CheckIcon v-if="selectedSite" class="h-5 w-5" />
                                    <span v-else>1</span>
                                </div>
                                <div class="text-left">
                                    <h2 class="font-semibold text-gray-900">Choisir le site</h2>
                                    <p v-if="selectedSiteData" class="text-sm text-gray-500">{{ selectedSiteData.name }}</p>
                                </div>
                            </div>
                            <ChevronDownIcon :class="['h-5 w-5 text-gray-400 transition-transform', expandedSections.site ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="expandedSections.site" class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <button
                                    v-for="site in sites"
                                    :key="site.id"
                                    @click="selectedSite = site.id"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-left transition-all hover:shadow-md',
                                        selectedSite === site.id ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ site.name }}</h3>
                                            <p class="text-sm text-gray-500">{{ site.address }}, {{ site.city }}</p>
                                            <p class="text-sm font-medium mt-2" :style="{ color: settings?.primary_color }">
                                                {{ site.available_boxes_count }} box disponibles
                                            </p>
                                        </div>
                                        <div v-if="selectedSite === site.id" class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                            <CheckIcon class="h-4 w-4 text-white" />
                                        </div>
                                    </div>
                                </button>
                            </div>
                            <p v-if="errors.site" class="text-red-500 text-sm mt-2 flex items-center">
                                <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                                {{ errors.site }}
                            </p>
                        </div>
                    </section>

                    <!-- Step 2: Box Selection -->
                    <section v-if="selectedSite" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <button
                            @click="expandedSections.box = !expandedSections.box"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: selectedBox ? '#10B981' : settings?.primary_color }"
                                >
                                    <CheckIcon v-if="selectedBox" class="h-5 w-5" />
                                    <span v-else>2</span>
                                </div>
                                <div class="text-left">
                                    <h2 class="font-semibold text-gray-900">Choisir votre box</h2>
                                    <p v-if="selectedBoxData" class="text-sm text-gray-500">
                                        {{ selectedBoxData.name }} - {{ formatCurrency(selectedBoxData.current_price) }}/mois
                                    </p>
                                </div>
                            </div>
                            <ChevronDownIcon :class="['h-5 w-5 text-gray-400 transition-transform', expandedSections.box ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="expandedSections.box" class="p-6">
                            <!-- Size Filter Pills -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <button
                                    v-for="cat in sizeCategories"
                                    :key="cat.value"
                                    @click="sizeFilter = cat.value"
                                    :class="[
                                        'px-4 py-2 rounded-full text-sm font-medium transition-all',
                                        sizeFilter === cat.value
                                            ? 'text-white shadow-md'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    ]"
                                    :style="sizeFilter === cat.value ? { backgroundColor: settings?.primary_color } : {}"
                                >
                                    {{ cat.icon }} {{ cat.label }}
                                </button>
                                <button
                                    @click="showSizeGuide = !showSizeGuide"
                                    class="px-4 py-2 rounded-full text-sm font-medium text-gray-600 hover:bg-gray-100 flex items-center gap-1"
                                >
                                    <InformationCircleIcon class="h-4 w-4" />
                                    Guide
                                </button>
                            </div>

                            <!-- Size Guide -->
                            <div v-if="showSizeGuide" class="mb-4 p-4 bg-blue-50 rounded-xl">
                                <div class="grid grid-cols-5 gap-2 text-center text-sm">
                                    <div v-for="ex in sizeExamples" :key="ex.volume" class="p-2">
                                        <div class="text-2xl">{{ ex.icon }}</div>
                                        <div class="font-bold">{{ ex.volume }}m³</div>
                                        <div class="text-gray-600 text-xs">{{ ex.title }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Boxes Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-96 overflow-y-auto">
                                <button
                                    v-for="box in availableBoxes"
                                    :key="box.id"
                                    @click="selectedBox = box.id"
                                    :class="[
                                        'p-4 rounded-xl border-2 text-left transition-all hover:shadow-md relative',
                                        selectedBox === box.id ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'
                                    ]"
                                >
                                    <!-- Popular badge -->
                                    <span
                                        v-if="box.volume >= 5 && box.volume <= 10"
                                        class="absolute -top-2 -right-2 px-2 py-0.5 text-xs font-bold text-white rounded-full"
                                        :style="{ backgroundColor: settings?.primary_color }"
                                    >
                                        Populaire
                                    </span>

                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ box.name }}</h3>
                                            <p class="text-sm text-gray-500">{{ box.formatted_volume }} • {{ box.dimensions }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-bold" :style="{ color: settings?.primary_color }">
                                                {{ formatCurrency(box.current_price) }}
                                            </span>
                                            <span class="text-gray-500 text-xs block">/mois</span>
                                        </div>
                                    </div>

                                    <!-- Features -->
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <span v-if="box.climate_controlled" class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded">
                                            Climatisé
                                        </span>
                                        <span v-if="box.has_24_7_access" class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                            24/7
                                        </span>
                                        <span v-if="box.is_ground_floor" class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded">
                                            RDC
                                        </span>
                                    </div>

                                    <div v-if="selectedBox === box.id" class="absolute top-2 right-2 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                        <CheckIcon class="h-4 w-4 text-white" />
                                    </div>
                                </button>
                            </div>

                            <p v-if="availableBoxes.length === 0" class="text-center text-gray-500 py-8">
                                Aucun box disponible pour ce filtre
                            </p>
                            <p v-if="errors.box" class="text-red-500 text-sm mt-2 flex items-center">
                                <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                                {{ errors.box }}
                            </p>
                        </div>
                    </section>

                    <!-- Step 3: Date Selection -->
                    <section v-if="selectedBox" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <button
                            @click="expandedSections.date = !expandedSections.date"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: form.start_date ? '#10B981' : settings?.primary_color }"
                                >
                                    <CheckIcon v-if="form.start_date" class="h-5 w-5" />
                                    <span v-else>3</span>
                                </div>
                                <div class="text-left">
                                    <h2 class="font-semibold text-gray-900">Date de début</h2>
                                    <p v-if="form.start_date" class="text-sm text-gray-500">{{ formatDate(form.start_date) }}</p>
                                </div>
                            </div>
                            <ChevronDownIcon :class="['h-5 w-5 text-gray-400 transition-transform', expandedSections.date ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="expandedSections.date" class="p-6">
                            <div class="flex flex-wrap gap-2 mb-4">
                                <button
                                    @click="setQuickDate(0)"
                                    :class="['px-4 py-2 rounded-xl text-sm font-medium transition-all', form.start_date === minDate ? 'text-white' : 'bg-gray-100 hover:bg-gray-200']"
                                    :style="form.start_date === minDate ? { backgroundColor: settings?.primary_color } : {}"
                                >
                                    Aujourd'hui
                                </button>
                                <button
                                    @click="setQuickDate(7)"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition-all"
                                >
                                    Dans 1 semaine
                                </button>
                                <button
                                    @click="setNextMonth()"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-xl text-sm font-medium transition-all"
                                >
                                    Début mois prochain
                                </button>
                            </div>
                            <input
                                type="date"
                                v-model="form.start_date"
                                :min="minDate"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent"
                            />
                            <p v-if="errors.start_date" class="text-red-500 text-sm mt-2">{{ errors.start_date }}</p>
                        </div>
                    </section>

                    <!-- Step 4: Customer Info -->
                    <section v-if="form.start_date" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <button
                            @click="expandedSections.customer = !expandedSections.customer"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: (form.customer_first_name && form.customer_email) ? '#10B981' : settings?.primary_color }"
                                >
                                    <CheckIcon v-if="form.customer_first_name && form.customer_email" class="h-5 w-5" />
                                    <span v-else>4</span>
                                </div>
                                <div class="text-left">
                                    <h2 class="font-semibold text-gray-900">Vos coordonnées</h2>
                                    <p v-if="form.customer_first_name" class="text-sm text-gray-500">
                                        {{ form.customer_first_name }} {{ form.customer_last_name }}
                                    </p>
                                </div>
                            </div>
                            <ChevronDownIcon :class="['h-5 w-5 text-gray-400 transition-transform', expandedSections.customer ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="expandedSections.customer" class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                                    <input
                                        type="text"
                                        v-model="form.customer_first_name"
                                        class="w-full border-2 rounded-xl px-4 py-3 transition-all"
                                        :class="errors.customer_first_name ? 'border-red-500' : 'border-gray-200 focus:border-gray-300'"
                                        placeholder="Jean"
                                    />
                                    <p v-if="errors.customer_first_name" class="text-red-500 text-xs mt-1">{{ errors.customer_first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                    <input
                                        type="text"
                                        v-model="form.customer_last_name"
                                        class="w-full border-2 rounded-xl px-4 py-3 transition-all"
                                        :class="errors.customer_last_name ? 'border-red-500' : 'border-gray-200 focus:border-gray-300'"
                                        placeholder="Dupont"
                                    />
                                    <p v-if="errors.customer_last_name" class="text-red-500 text-xs mt-1">{{ errors.customer_last_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input
                                        type="email"
                                        v-model="form.customer_email"
                                        class="w-full border-2 rounded-xl px-4 py-3 transition-all"
                                        :class="errors.customer_email ? 'border-red-500' : 'border-gray-200 focus:border-gray-300'"
                                        placeholder="jean@email.com"
                                    />
                                    <p v-if="errors.customer_email" class="text-red-500 text-xs mt-1">{{ errors.customer_email }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                    <input
                                        type="tel"
                                        v-model="form.customer_phone"
                                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-gray-300 transition-all"
                                        placeholder="06 12 34 56 78"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Entreprise (optionnel)</label>
                                    <input
                                        type="text"
                                        v-model="form.customer_company"
                                        class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:border-gray-300 transition-all"
                                        placeholder="Ma Société SARL"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Step 5: Payment Method (if Stripe enabled) -->
                    <section v-if="form.customer_email && settings?.stripe_publishable_key" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <button
                            @click="expandedSections.payment = !expandedSections.payment"
                            class="w-full px-6 py-4 flex items-center justify-between bg-gray-50 hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
                                    :style="{ backgroundColor: settings?.primary_color }"
                                >
                                    5
                                </div>
                                <div class="text-left">
                                    <h2 class="font-semibold text-gray-900">Mode de paiement</h2>
                                    <p class="text-sm text-gray-500">
                                        {{ paymentMethod === 'now' ? 'Payer maintenant' : 'Payer à la signature' }}
                                    </p>
                                </div>
                            </div>
                            <ChevronDownIcon :class="['h-5 w-5 text-gray-400 transition-transform', expandedSections.payment ? 'rotate-180' : '']" />
                        </button>

                        <div v-show="expandedSections.payment" class="p-6">
                            <div class="space-y-3 mb-4">
                                <label
                                    class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                                    :class="paymentMethod === 'at_signing' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input type="radio" v-model="paymentMethod" value="at_signing" class="sr-only" />
                                    <div class="flex-1">
                                        <span class="font-medium text-gray-900">Payer à la signature</span>
                                        <p class="text-sm text-gray-500">Payez lors de la signature du contrat sur place</p>
                                    </div>
                                    <div v-if="paymentMethod === 'at_signing'" class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                        <CheckIcon class="h-4 w-4 text-white" />
                                    </div>
                                </label>

                                <label
                                    class="flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all"
                                    :class="paymentMethod === 'now' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input type="radio" v-model="paymentMethod" value="now" class="sr-only" />
                                    <div class="flex-1">
                                        <span class="font-medium text-gray-900">Payer maintenant</span>
                                        <p class="text-sm text-gray-500">Paiement sécurisé par carte bancaire</p>
                                    </div>
                                    <div v-if="paymentMethod === 'now'" class="w-6 h-6 rounded-full bg-green-500 flex items-center justify-center">
                                        <CheckIcon class="h-4 w-4 text-white" />
                                    </div>
                                </label>
                            </div>

                            <!-- Stripe Card Element -->
                            <div v-if="paymentMethod === 'now'" class="mt-4 p-4 bg-gray-50 rounded-xl">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Carte bancaire</label>
                                <div id="card-element" class="p-4 bg-white border-2 border-gray-200 rounded-xl"></div>
                                <p v-if="cardError" class="text-red-500 text-sm mt-2">{{ cardError }}</p>
                                <div class="flex items-center gap-2 mt-3 text-xs text-gray-500">
                                    <LockClosedIcon class="h-4 w-4" />
                                    Paiement sécurisé par Stripe
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Order Summary (Sticky) -->
                <div class="lg:col-span-1 mt-6 lg:mt-0">
                    <div class="sticky top-20">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                            <div class="p-6 border-b border-gray-100">
                                <h3 class="font-bold text-lg text-gray-900">Récapitulatif</h3>
                            </div>

                            <div class="p-6 space-y-4">
                                <!-- Selected Box Info -->
                                <div v-if="selectedBoxData" class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl"
                                         :style="{ backgroundColor: settings?.primary_color }">
                                        <CubeIcon class="h-6 w-6" />
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ selectedBoxData.name }}</p>
                                        <p class="text-sm text-gray-500">{{ selectedBoxData.formatted_volume }}</p>
                                    </div>
                                </div>
                                <div v-else class="text-gray-400 text-sm text-center py-4">
                                    Sélectionnez un box
                                </div>

                                <!-- Site Info -->
                                <div v-if="selectedSiteData" class="flex items-center gap-3 text-sm">
                                    <MapPinIcon class="h-5 w-5 text-gray-400" />
                                    <span class="text-gray-600">{{ selectedSiteData.name }}</span>
                                </div>

                                <!-- Date Info -->
                                <div v-if="form.start_date" class="flex items-center gap-3 text-sm">
                                    <CalendarIcon class="h-5 w-5 text-gray-400" />
                                    <span class="text-gray-600">Début : {{ formatDate(form.start_date) }}</span>
                                </div>

                                <!-- Promo Code -->
                                <div class="pt-4 border-t border-gray-100">
                                    <div v-if="promoApplied" class="flex items-center justify-between bg-green-50 text-green-700 p-3 rounded-xl">
                                        <div class="flex items-center gap-2">
                                            <TicketIcon class="h-5 w-5" />
                                            <span class="font-medium">{{ promoApplied.code }}</span>
                                        </div>
                                        <button @click="removePromo" class="text-green-600 hover:text-green-800">
                                            <XMarkIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                    <div v-else-if="showPromoInput" class="space-y-2">
                                        <div class="flex gap-2">
                                            <input
                                                type="text"
                                                v-model="promoCode"
                                                placeholder="Code promo"
                                                class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm"
                                            />
                                            <button
                                                @click="validatePromo"
                                                class="px-4 py-2 text-sm font-medium text-white rounded-lg"
                                                :style="{ backgroundColor: settings?.primary_color }"
                                            >
                                                OK
                                            </button>
                                        </div>
                                        <p v-if="promoError" class="text-red-500 text-xs">{{ promoError }}</p>
                                    </div>
                                    <button
                                        v-else
                                        @click="showPromoInput = true"
                                        class="text-sm font-medium hover:underline"
                                        :style="{ color: settings?.primary_color }"
                                    >
                                        + Ajouter un code promo
                                    </button>
                                </div>

                                <!-- Price Breakdown -->
                                <div v-if="selectedBoxData" class="pt-4 border-t border-gray-100 space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Loyer mensuel</span>
                                        <span class="font-medium">{{ formatCurrency(selectedBoxData.current_price) }}</span>
                                    </div>
                                    <div v-if="promoApplied" class="flex justify-between text-sm text-green-600">
                                        <span>Réduction</span>
                                        <span>-{{ formatCurrency(promoApplied.calculated_discount) }}</span>
                                    </div>
                                    <div v-if="depositAmount > 0" class="flex justify-between text-sm">
                                        <span class="text-gray-600">Dépôt de garantie</span>
                                        <span class="font-medium">{{ formatCurrency(depositAmount) }}</span>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold pt-3 border-t border-gray-200">
                                        <span>Total dû</span>
                                        <span :style="{ color: settings?.primary_color }">
                                            {{ formatCurrency(totalDueAtSigning) }}
                                        </span>
                                    </div>
                                    <p v-if="paymentMethod === 'at_signing'" class="text-xs text-gray-500 text-center">
                                        À payer lors de la signature du contrat
                                    </p>
                                </div>

                                <!-- Terms & Submit -->
                                <div class="pt-4 space-y-4">
                                    <label class="flex items-start gap-3 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="form.terms_accepted"
                                            class="mt-1 w-5 h-5 rounded border-gray-300"
                                        />
                                        <span class="text-sm text-gray-600">
                                            J'accepte les <a href="#" class="underline" :style="{ color: settings?.primary_color }">conditions générales</a>
                                            et la <a href="#" class="underline" :style="{ color: settings?.primary_color }">politique de confidentialité</a>
                                        </span>
                                    </label>
                                    <p v-if="errors.terms" class="text-red-500 text-xs">{{ errors.terms }}</p>

                                    <button
                                        @click="submitBooking"
                                        :disabled="processing || !isFormComplete"
                                        class="w-full py-4 rounded-xl text-white font-semibold text-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                        :style="{ backgroundColor: settings?.primary_color }"
                                    >
                                        <svg v-if="processing" class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span v-if="processing">Réservation en cours...</span>
                                        <span v-else-if="paymentMethod === 'now'">Payer {{ formatCurrency(totalDueNow) }}</span>
                                        <span v-else>Confirmer la réservation</span>
                                    </button>

                                    <p v-if="errors.general" class="text-red-500 text-sm text-center">{{ errors.general }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Trust Badges -->
                        <div class="mt-4 flex items-center justify-center gap-6 text-sm text-gray-500">
                            <span class="flex items-center gap-1">
                                <ShieldCheckIcon class="h-4 w-4 text-green-500" />
                                Sécurisé
                            </span>
                            <span class="flex items-center gap-1">
                                <ClockIcon class="h-4 w-4 text-blue-500" />
                                Sans engagement
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar for boxes list */
.max-h-96::-webkit-scrollbar {
    width: 6px;
}
.max-h-96::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}
.max-h-96::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
.max-h-96::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Mobile optimizations */
@media (max-width: 767px) {
    /* Add padding for sticky footer on mobile */
    .min-h-screen {
        padding-bottom: 120px;
    }

    /* Larger touch targets on mobile */
    button, input, select {
        min-height: 48px;
    }

    /* Better spacing for mobile forms */
    input[type="text"],
    input[type="email"],
    input[type="tel"],
    input[type="date"] {
        font-size: 16px !important; /* Prevents iOS zoom */
    }
}

/* Safe area for notch devices (iPhone X+) */
.safe-area-inset-bottom {
    padding-bottom: max(16px, env(safe-area-inset-bottom));
}

/* Apple Pay / Google Pay button styling */
#payment-request-button {
    min-height: 48px;
}

/* Pulse animation for urgency messages */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.animate-pulse {
    animation: pulse 2s ease-in-out infinite;
}

/* Smooth section transitions */
section {
    transition: all 0.3s ease-out;
}

/* Touch-friendly hover states */
@media (hover: none) {
    button:active {
        transform: scale(0.98);
    }
}
</style>
