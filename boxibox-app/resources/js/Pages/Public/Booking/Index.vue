<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router, Head } from '@inertiajs/vue3'
import {
    CubeIcon,
    MapPinIcon,
    CurrencyEuroIcon,
    CalendarIcon,
    CheckIcon,
    ArrowRightIcon,
    UserIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    TicketIcon,
    ExclamationCircleIcon,
    FunnelIcon,
    ArrowsUpDownIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    InformationCircleIcon,
    SparklesIcon,
    ShieldCheckIcon,
    ClockIcon,
    TruckIcon,
    BoltIcon,
    SunIcon,
    PhotoIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon, StarIcon, CreditCardIcon, LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    settings: Object,
    tenant: Object,
    sites: Array,
    availableDates: {
        type: Array,
        default: () => [],
    },
})

const step = ref(1)
const selectedSite = ref(null)
const selectedBox = ref(null)
const promoCode = ref('')
const promoApplied = ref(null)
const promoError = ref('')
const processing = ref(false)
const success = ref(false)
const bookingResult = ref(null)
const showSizeComparison = ref(false)
const currentImageIndex = ref(0)
const selectedBoxImages = ref([])
const showImageGallery = ref(false)
const galleryImageIndex = ref(0)
const checkingAvailability = ref(false)
const availabilityStatus = ref({})

// Payment options
const paymentMethod = ref('at_signing') // 'now' or 'at_signing'
const paymentProcessing = ref(false)
const stripeLoaded = ref(false)
const cardElement = ref(null)
const stripeInstance = ref(null)
const stripeElements = ref(null)
const cardError = ref('')

// Filtering and sorting
const searchQuery = ref('')
const priceRange = ref([0, 1000])
const sizeFilter = ref('all')
const sortBy = ref('price_asc')
const showFilters = ref(false)

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
    secondary_contact_name: '',
    secondary_contact_phone: '',
    start_date: '',
    duration_type: 'month_to_month',
    planned_duration_months: null,
    notes: '',
    special_requests: '',
    needs_24h_access: false,
    needs_climate_control: false,
    needs_electricity: false,
    needs_insurance: false,
    needs_moving_help: false,
    preferred_time_slot: 'flexible',
    storage_contents: '',
    estimated_value: null,
    terms_accepted: false,
})

const errors = ref({})

// Size categories for filtering
const sizeCategories = [
    { value: 'all', label: 'Toutes tailles', min: 0, max: Infinity },
    { value: 'xs', label: 'Casier (< 2m³)', min: 0, max: 2 },
    { value: 'small', label: 'Petit (2-5m³)', min: 2, max: 5 },
    { value: 'medium', label: 'Moyen (5-10m³)', min: 5, max: 10 },
    { value: 'large', label: 'Grand (10-20m³)', min: 10, max: 20 },
    { value: 'xl', label: 'Très grand (> 20m³)', min: 20, max: Infinity },
]

// Size comparison examples
const sizeExamples = [
    { volume: 1, icon: '📦', description: '10 cartons', items: ['Affaires personnelles', 'Dossiers', 'Petits objets'] },
    { volume: 3, icon: '🛋️', description: 'Studio/Chambre', items: ['1 canapé', '1 lit simple', '10-20 cartons'] },
    { volume: 5, icon: '🏠', description: 'Appartement T2', items: ['Salon complet', '1 chambre', 'Électroménager'] },
    { volume: 10, icon: '🏘️', description: 'Appartement T3/T4', items: ['2-3 chambres', 'Salle à manger', 'Cave/Garage'] },
    { volume: 20, icon: '🏡', description: 'Maison', items: ['4+ pièces', 'Mobilier complet', 'Jardin/Terrasse'] },
    { volume: 30, icon: '🏢', description: 'Grande maison', items: ['Stock professionnel', 'Archives', 'Équipements'] },
]

const availableBoxes = computed(() => {
    if (!selectedSite.value) return []
    const site = props.sites.find(s => s.id === selectedSite.value)
    let boxes = site?.boxes || []

    // Apply search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        boxes = boxes.filter(b =>
            b.name?.toLowerCase().includes(query) ||
            b.number?.toString().includes(query)
        )
    }

    // Apply size filter
    if (sizeFilter.value !== 'all') {
        const category = sizeCategories.find(c => c.value === sizeFilter.value)
        if (category) {
            boxes = boxes.filter(b => b.volume >= category.min && b.volume < category.max)
        }
    }

    // Apply price filter
    boxes = boxes.filter(b => b.current_price >= priceRange.value[0] && b.current_price <= priceRange.value[1])

    // Apply sorting
    switch (sortBy.value) {
        case 'price_asc':
            boxes = [...boxes].sort((a, b) => a.current_price - b.current_price)
            break
        case 'price_desc':
            boxes = [...boxes].sort((a, b) => b.current_price - a.current_price)
            break
        case 'size_asc':
            boxes = [...boxes].sort((a, b) => a.volume - b.volume)
            break
        case 'size_desc':
            boxes = [...boxes].sort((a, b) => b.volume - a.volume)
            break
    }

    return boxes
})

const maxPrice = computed(() => {
    if (!selectedSite.value) return 1000
    const site = props.sites.find(s => s.id === selectedSite.value)
    const prices = site?.boxes?.map(b => b.current_price) || [1000]
    return Math.max(...prices) + 50
})

const selectedBoxData = computed(() => {
    if (!selectedBox.value) return null
    return availableBoxes.value.find(b => b.id === selectedBox.value)
})

const monthlyPrice = computed(() => {
    if (!selectedBox.value) return 0
    const box = availableBoxes.value.find(b => b.id === selectedBox.value)
    let price = box?.current_price || 0
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

const getSizeCategory = (volume) => {
    for (const cat of sizeCategories) {
        if (volume >= cat.min && volume < cat.max && cat.value !== 'all') {
            return cat.label.split(' ')[0]
        }
    }
    return 'Standard'
}

const getSizeExample = (volume) => {
    for (let i = sizeExamples.length - 1; i >= 0; i--) {
        if (volume >= sizeExamples[i].volume) {
            return sizeExamples[i]
        }
    }
    return sizeExamples[0]
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const formatVolume = (volume) => {
    return `${volume} m³`
}

const validatePromo = async () => {
    if (!promoCode.value) return

    promoError.value = ''
    const box = availableBoxes.value.find(b => b.id === selectedBox.value)

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
                monthly_price: box?.current_price || 0,
            }),
        })

        const data = await response.json()

        if (data.valid) {
            promoApplied.value = data.promo
        } else {
            promoError.value = data.message
            promoApplied.value = null
        }
    } catch (e) {
        promoError.value = 'Erreur lors de la validation du code'
    }
}

const removePromo = () => {
    promoCode.value = ''
    promoApplied.value = null
    promoError.value = ''
}

// Check box availability for a specific date
const checkBoxAvailability = async (boxId, date) => {
    if (!date) return true

    const key = `${boxId}_${date}`
    if (availabilityStatus.value[key] !== undefined) {
        return availabilityStatus.value[key]
    }

    try {
        const response = await fetch(route('public.booking.check-availability'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({
                box_id: boxId,
                start_date: date,
                tenant_id: props.tenant.id,
            }),
        })

        const data = await response.json()
        availabilityStatus.value[key] = data.available
        return data.available
    } catch (e) {
        return true // Assume available on error
    }
}

// Get availability status display
const getBoxAvailabilityClass = (boxId) => {
    const key = `${boxId}_${form.value.start_date}`
    const status = availabilityStatus.value[key]

    if (status === undefined) return 'bg-gray-100 text-gray-600'
    if (status === true) return 'bg-green-100 text-green-700'
    return 'bg-red-100 text-red-700'
}

const getBoxAvailabilityText = (boxId) => {
    const key = `${boxId}_${form.value.start_date}`
    const status = availabilityStatus.value[key]

    if (status === undefined) return 'Verifier disponibilite'
    if (status === true) return 'Disponible'
    return 'Non disponible'
}

// Open image gallery for a box
const openImageGallery = (box) => {
    if (box.images && box.images.length > 0) {
        selectedBoxImages.value = box.images
        galleryImageIndex.value = 0
        showImageGallery.value = true
    }
}

const closeImageGallery = () => {
    showImageGallery.value = false
    selectedBoxImages.value = []
}

const nextGalleryImage = () => {
    if (galleryImageIndex.value < selectedBoxImages.value.length - 1) {
        galleryImageIndex.value++
    } else {
        galleryImageIndex.value = 0
    }
}

const prevGalleryImage = () => {
    if (galleryImageIndex.value > 0) {
        galleryImageIndex.value--
    } else {
        galleryImageIndex.value = selectedBoxImages.value.length - 1
    }
}

// Get formatted date for display
const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    })
}

// Check availability when date changes
watch(() => form.value.start_date, async (newDate) => {
    if (newDate && selectedSite.value) {
        checkingAvailability.value = true
        const site = props.sites.find(s => s.id === selectedSite.value)
        const boxes = site?.boxes || []

        // Check availability for all boxes
        await Promise.all(boxes.map(box => checkBoxAvailability(box.id, newDate)))
        checkingAvailability.value = false
    }
})

const nextStep = () => {
    if (step.value === 1 && selectedBox.value) {
        step.value = 2
        window.scrollTo({ top: 0, behavior: 'smooth' })
    } else if (step.value === 2 && validateForm()) {
        step.value = 3
        window.scrollTo({ top: 0, behavior: 'smooth' })
    } else if (step.value === 3) {
        step.value = 4
        window.scrollTo({ top: 0, behavior: 'smooth' })
        // Load Stripe if payment now is selected
        if (paymentMethod.value === 'now' && props.settings?.stripe_publishable_key) {
            loadStripe()
        }
    }
}

// Load Stripe.js dynamically
const loadStripe = async () => {
    if (stripeLoaded.value) return

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

// Initialize Stripe Elements
const initStripe = () => {
    if (!props.settings?.stripe_publishable_key) return

    stripeInstance.value = window.Stripe(props.settings.stripe_publishable_key)
    stripeElements.value = stripeInstance.value.elements({
        locale: 'fr',
    })

    // Create card element with French styling
    cardElement.value = stripeElements.value.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#1f2937',
                fontFamily: 'system-ui, -apple-system, sans-serif',
                '::placeholder': {
                    color: '#9ca3af',
                },
            },
            invalid: {
                color: '#ef4444',
                iconColor: '#ef4444',
            },
        },
        hidePostalCode: true,
    })

    // Mount after a short delay to ensure DOM is ready
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

// Process payment with Stripe
const processPayment = async () => {
    if (!stripeInstance.value || !cardElement.value) {
        cardError.value = 'Erreur de chargement du formulaire de paiement'
        return null
    }

    paymentProcessing.value = true
    cardError.value = ''

    try {
        // Create payment intent on server
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
            throw new Error('Erreur lors de la creation du paiement')
        }

        // Confirm payment with Stripe
        const { error, paymentIntent } = await stripeInstance.value.confirmCardPayment(
            intentData.client_secret,
            {
                payment_method: {
                    card: cardElement.value,
                    billing_details: {
                        name: `${form.value.customer_first_name} ${form.value.customer_last_name}`,
                        email: form.value.customer_email,
                        phone: form.value.customer_phone || undefined,
                        address: {
                            line1: form.value.customer_address || undefined,
                            city: form.value.customer_city || undefined,
                            postal_code: form.value.customer_postal_code || undefined,
                            country: form.value.customer_country || 'FR',
                        },
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

// Calculate total due now (deposit + first month if paying now)
const totalDueNow = computed(() => {
    if (paymentMethod.value === 'at_signing') return 0
    return monthlyPrice.value + depositAmount.value
})

const prevStep = () => {
    if (step.value > 1) {
        step.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const validateForm = () => {
    errors.value = {}

    if (!form.value.customer_first_name) errors.value.customer_first_name = 'Prenom requis'
    if (!form.value.customer_last_name) errors.value.customer_last_name = 'Nom requis'
    if (!form.value.customer_email) errors.value.customer_email = 'Email requis'
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.customer_email)) {
        errors.value.customer_email = 'Email invalide'
    }
    if (!form.value.start_date) errors.value.start_date = 'Date de debut requise'
    if (form.value.customer_phone && !/^[\d\s+()-]{10,}$/.test(form.value.customer_phone)) {
        errors.value.customer_phone = 'Numero de telephone invalide'
    }

    return Object.keys(errors.value).length === 0
}

const submitBooking = async () => {
    if (!form.value.terms_accepted) {
        errors.value.terms_accepted = 'Vous devez accepter les conditions'
        return
    }

    processing.value = true
    errors.value = {}

    let paymentIntentId = null

    // Process payment if paying now
    if (paymentMethod.value === 'now' && props.settings?.stripe_publishable_key) {
        paymentIntentId = await processPayment()
        if (!paymentIntentId) {
            processing.value = false
            return // Payment failed
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
                source: 'website',
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

// Reset filters when site changes
watch(selectedSite, () => {
    selectedBox.value = null
    searchQuery.value = ''
    sizeFilter.value = 'all'
    priceRange.value = [0, maxPrice.value]
})

// Set minimum date to today
const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

// Initialize price range when mounted
onMounted(() => {
    if (props.sites.length === 1) {
        selectedSite.value = props.sites[0].id
    }
})
</script>

<template>
    <Head :title="`Reservation - ${settings?.company_name || tenant.name}`">
        <meta name="description" :content="`Reservez votre box de stockage chez ${settings?.company_name || tenant.name}. Location flexible, securisee et accessible.`" />
        <meta property="og:title" :content="`Reservation - ${settings?.company_name || tenant.name}`" />
        <meta property="og:description" content="Reservez votre espace de stockage en ligne en quelques clics." />
        <meta property="og:type" content="website" />
    </Head>

    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <!-- Header with gradient -->
        <header class="relative overflow-hidden py-8 px-4" :style="{ background: `linear-gradient(135deg, ${settings?.primary_color || '#3B82F6'} 0%, ${settings?.primary_color || '#3B82F6'}dd 100%)` }">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="absolute inset-0" style="background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;);"></div>
            <div class="max-w-4xl mx-auto text-center relative z-10">
                <h1 class="text-3xl md:text-4xl font-bold text-white drop-shadow-lg">{{ settings?.company_name || tenant.name }}</h1>
                <p v-if="settings?.welcome_message" class="text-white/90 mt-3 text-lg">{{ settings.welcome_message }}</p>
                <div class="flex flex-wrap justify-center gap-4 mt-6">
                    <div class="flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white text-sm">
                        <ShieldCheckIcon class="h-5 w-5 mr-2" />
                        Securise 24/7
                    </div>
                    <div class="flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white text-sm">
                        <ClockIcon class="h-5 w-5 mr-2" />
                        Acces flexible
                    </div>
                    <div class="flex items-center bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-white text-sm">
                        <SparklesIcon class="h-5 w-5 mr-2" />
                        Sans engagement
                    </div>
                </div>
            </div>
        </header>

        <!-- Success Message -->
        <div v-if="success" class="max-w-2xl mx-auto px-4 py-12">
            <div class="bg-white rounded-3xl shadow-2xl p-8 text-center transform animate-fade-in">
                <div class="w-24 h-24 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                    <CheckCircleIcon class="h-14 w-14 text-white" />
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Reservation confirmee !</h2>
                <p class="text-gray-600 mb-6 text-lg">
                    Votre reservation <strong class="text-gray-900">{{ bookingResult?.booking_number }}</strong> a ete enregistree.
                    <br />Vous recevrez un email de confirmation a <strong>{{ form.customer_email }}</strong>.
                </p>
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-2xl p-6 mb-6">
                    <div class="grid grid-cols-2 gap-6 text-left">
                        <div>
                            <span class="text-gray-500 text-sm">Prix mensuel</span>
                            <p class="font-bold text-2xl" :style="{ color: settings?.primary_color }">{{ formatCurrency(bookingResult?.monthly_price) }}</p>
                        </div>
                        <div v-if="bookingResult?.deposit_amount > 0">
                            <span class="text-gray-500 text-sm">Acompte</span>
                            <p class="font-bold text-2xl text-gray-900">{{ formatCurrency(bookingResult?.deposit_amount) }}</p>
                        </div>
                    </div>
                </div>
                <a
                    :href="`/book/status/${bookingResult?.uuid}`"
                    class="inline-flex items-center px-8 py-4 rounded-2xl text-white font-semibold text-lg transition-all transform hover:scale-105 shadow-lg"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Suivre ma reservation
                    <ArrowRightIcon class="h-5 w-5 ml-2" />
                </a>
            </div>
        </div>

        <!-- Booking Form -->
        <div v-else class="max-w-5xl mx-auto px-4 py-8">
            <!-- Progress Steps -->
            <div class="mb-10">
                <div class="flex items-center justify-center">
                    <template v-for="s in (settings?.stripe_publishable_key ? 4 : 3)" :key="s">
                        <div class="flex items-center">
                            <div
                                :class="[
                                    'w-10 h-10 md:w-12 md:h-12 rounded-2xl flex items-center justify-center font-bold text-base md:text-lg transition-all duration-300 shadow-md',
                                    step >= s ? 'text-white scale-110' : 'bg-white text-gray-400 border-2 border-gray-200'
                                ]"
                                :style="step >= s ? { backgroundColor: settings?.primary_color } : {}"
                            >
                                <CheckIcon v-if="step > s" class="h-5 w-5 md:h-6 md:w-6" />
                                <span v-else>{{ s }}</span>
                            </div>
                            <div
                                v-if="s < (settings?.stripe_publishable_key ? 4 : 3)"
                                :class="['w-12 md:w-24 h-1.5 mx-1 md:mx-2 rounded-full transition-all duration-500', step > s ? 'bg-green-500' : 'bg-gray-200']"
                            ></div>
                        </div>
                    </template>
                </div>
                <div class="flex justify-center mt-4 text-xs md:text-sm font-medium flex-wrap gap-1">
                    <span :class="['px-2 md:px-4 transition-colors', step >= 1 ? 'text-gray-900' : 'text-gray-400']">Box</span>
                    <span :class="['px-2 md:px-4 transition-colors', step >= 2 ? 'text-gray-900' : 'text-gray-400']">Informations</span>
                    <span :class="['px-2 md:px-4 transition-colors', step >= 3 ? 'text-gray-900' : 'text-gray-400']">Recapitulatif</span>
                    <span v-if="settings?.stripe_publishable_key" :class="['px-2 md:px-4 transition-colors', step >= 4 ? 'text-gray-900' : 'text-gray-400']">Paiement</span>
                </div>
            </div>

            <!-- Step 1: Choose Box -->
            <div v-if="step === 1" class="space-y-6">
                <!-- Site Selection -->
                <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <MapPinIcon class="h-7 w-7 mr-3" :style="{ color: settings?.primary_color }" />
                        Choisissez votre site
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            v-for="site in sites"
                            :key="site.id"
                            @click="selectedSite = site.id; selectedBox = null"
                            :class="[
                                'group p-5 rounded-2xl border-2 text-left transition-all duration-300 hover:shadow-lg',
                                selectedSite === site.id
                                    ? 'shadow-xl scale-[1.02]'
                                    : 'border-gray-200 hover:border-gray-300 bg-white'
                            ]"
                            :style="selectedSite === site.id ? { borderColor: settings?.primary_color, backgroundColor: settings?.primary_color + '08' } : {}"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg group-hover:text-gray-700">{{ site.name }}</h3>
                                    <p class="text-gray-500 mt-1">{{ site.address }}, {{ site.city }}</p>
                                </div>
                                <div
                                    v-if="selectedSite === site.id"
                                    class="w-8 h-8 rounded-full flex items-center justify-center"
                                    :style="{ backgroundColor: settings?.primary_color }"
                                >
                                    <CheckIcon class="h-5 w-5 text-white" />
                                </div>
                            </div>
                            <div class="mt-4 flex items-center">
                                <CubeIcon class="h-5 w-5 mr-2" :style="{ color: settings?.primary_color }" />
                                <span class="font-semibold" :style="{ color: settings?.primary_color }">
                                    {{ site.available_boxes_count }} box{{ site.available_boxes_count > 1 ? 's' : '' }} disponible{{ site.available_boxes_count > 1 ? 's' : '' }}
                                </span>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Quick Date Selection -->
                <div v-if="selectedSite" class="bg-white rounded-3xl shadow-xl p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                        <CalendarIcon class="h-6 w-6 mr-3" :style="{ color: settings?.primary_color }" />
                        Quand souhaitez-vous commencer ?
                    </h2>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex gap-2">
                            <button
                                @click="form.start_date = minDate"
                                :class="[
                                    'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                    form.start_date === minDate ? 'text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                                :style="form.start_date === minDate ? { backgroundColor: settings?.primary_color } : {}"
                            >
                                Aujourd'hui
                            </button>
                            <button
                                @click="form.start_date = new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0]"
                                :class="[
                                    'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                    form.start_date === new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0] ? 'text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                                :style="form.start_date === new Date(new Date().setDate(new Date().getDate() + 7)).toISOString().split('T')[0] ? { backgroundColor: settings?.primary_color } : {}"
                            >
                                Dans 1 semaine
                            </button>
                            <button
                                @click="form.start_date = new Date(new Date().setMonth(new Date().getMonth() + 1, 1)).toISOString().split('T')[0]"
                                :class="[
                                    'px-4 py-2 rounded-xl text-sm font-medium transition-all',
                                    form.start_date === new Date(new Date().setMonth(new Date().getMonth() + 1, 1)).toISOString().split('T')[0] ? 'text-white shadow-md' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                ]"
                                :style="form.start_date === new Date(new Date().setMonth(new Date().getMonth() + 1, 1)).toISOString().split('T')[0] ? { backgroundColor: settings?.primary_color } : {}"
                            >
                                Debut mois prochain
                            </button>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <input
                                type="date"
                                v-model="form.start_date"
                                :min="minDate"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent transition-all"
                            />
                        </div>
                        <div v-if="form.start_date" class="text-sm text-gray-600">
                            <span class="font-medium" :style="{ color: settings?.primary_color }">{{ formatDate(form.start_date) }}</span>
                        </div>
                    </div>
                    <div v-if="checkingAvailability" class="mt-3 flex items-center text-sm text-gray-500">
                        <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Verification des disponibilites...
                    </div>
                </div>

                <!-- Size Guide Button -->
                <div v-if="selectedSite" class="flex justify-center">
                    <button
                        @click="showSizeComparison = !showSizeComparison"
                        class="flex items-center gap-2 px-6 py-3 bg-white rounded-full shadow-md hover:shadow-lg transition-all text-gray-700 font-medium"
                    >
                        <InformationCircleIcon class="h-5 w-5" />
                        {{ showSizeComparison ? 'Masquer' : 'Afficher' }} le guide des tailles
                    </button>
                </div>

                <!-- Size Comparison Guide -->
                <div v-if="showSizeComparison && selectedSite" class="bg-white rounded-3xl shadow-xl p-6 md:p-8 transition-all">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <SparklesIcon class="h-6 w-6 mr-2" :style="{ color: settings?.primary_color }" />
                        Guide des tailles - Que pouvez-vous stocker ?
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                        <div
                            v-for="example in sizeExamples"
                            :key="example.volume"
                            class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-4 text-center hover:shadow-md transition-all"
                        >
                            <div class="text-4xl mb-2">{{ example.icon }}</div>
                            <div class="font-bold text-gray-900">{{ example.volume }}m³</div>
                            <div class="text-sm text-gray-600 font-medium">{{ example.description }}</div>
                            <ul class="text-xs text-gray-500 mt-2 space-y-1">
                                <li v-for="item in example.items" :key="item">{{ item }}</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Box Selection with Filters -->
                <div v-if="selectedSite && availableBoxes.length > 0" class="bg-white rounded-3xl shadow-xl p-6 md:p-8">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 gap-4">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <CubeIcon class="h-7 w-7 mr-3" :style="{ color: settings?.primary_color }" />
                            Choisissez votre box
                        </h2>

                        <!-- Search and Filters Toggle -->
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <MagnifyingGlassIcon class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="text"
                                    v-model="searchQuery"
                                    placeholder="Rechercher..."
                                    class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:border-transparent w-48"
                                    :style="{ '--tw-ring-color': settings?.primary_color }"
                                />
                            </div>
                            <button
                                @click="showFilters = !showFilters"
                                :class="['flex items-center gap-2 px-4 py-2 rounded-xl border transition-all', showFilters ? 'bg-gray-100 border-gray-300' : 'border-gray-200 hover:border-gray-300']"
                            >
                                <FunnelIcon class="h-5 w-5" />
                                Filtres
                            </button>
                        </div>
                    </div>

                    <!-- Expanded Filters -->
                    <div v-if="showFilters" class="mb-6 p-4 bg-gray-50 rounded-2xl space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Size Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Taille</label>
                                <select
                                    v-model="sizeFilter"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2 focus:ring-2"
                                >
                                    <option v-for="cat in sizeCategories" :key="cat.value" :value="cat.value">
                                        {{ cat.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Sort -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Trier par</label>
                                <select
                                    v-model="sortBy"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2 focus:ring-2"
                                >
                                    <option value="price_asc">Prix croissant</option>
                                    <option value="price_desc">Prix decroissant</option>
                                    <option value="size_asc">Taille croissante</option>
                                    <option value="size_desc">Taille decroissante</option>
                                </select>
                            </div>

                            <!-- Results count -->
                            <div class="flex items-end">
                                <p class="text-gray-600">
                                    <span class="font-bold text-2xl" :style="{ color: settings?.primary_color }">{{ availableBoxes.length }}</span>
                                    box{{ availableBoxes.length > 1 ? 's' : '' }} trouve{{ availableBoxes.length > 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Boxes Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            v-for="box in availableBoxes"
                            :key="box.id"
                            @click="selectedBox = box.id"
                            :class="[
                                'group p-5 rounded-2xl border-2 text-left transition-all duration-300 hover:shadow-lg relative overflow-hidden',
                                selectedBox === box.id
                                    ? 'shadow-xl scale-[1.02]'
                                    : 'border-gray-200 hover:border-gray-300 bg-white'
                            ]"
                            :style="selectedBox === box.id ? { borderColor: settings?.primary_color, backgroundColor: settings?.primary_color + '08' } : {}"
                        >
                            <!-- Popular badge -->
                            <div
                                v-if="box.volume >= 5 && box.volume <= 10"
                                class="absolute -right-8 top-4 transform rotate-45 text-xs font-bold text-white px-10 py-1"
                                :style="{ backgroundColor: settings?.primary_color }"
                            >
                                Populaire
                            </div>

                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">{{ box.name }}</h3>
                                    <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full mt-1">
                                        {{ getSizeCategory(box.volume) }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="text-2xl font-bold"
                                        :style="{ color: settings?.primary_color }"
                                    >
                                        {{ formatCurrency(box.current_price) }}
                                    </span>
                                    <span class="text-gray-500 text-sm">/mois</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-gray-600 mb-3">
                                <span class="flex items-center">
                                    <CubeIcon class="h-4 w-4 mr-1" />
                                    {{ box.formatted_volume }}
                                </span>
                                <span class="text-sm">{{ box.dimensions }}</span>
                            </div>

                            <!-- Size example -->
                            <div class="text-sm text-gray-500 mb-3 flex items-center">
                                <span class="mr-1">{{ getSizeExample(box.volume).icon }}</span>
                                {{ getSizeExample(box.volume).description }}
                            </div>

                            <!-- Features -->
                            <div class="flex flex-wrap gap-2">
                                <span v-if="box.climate_controlled" class="flex items-center text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-lg">
                                    <SunIcon class="h-3 w-3 mr-1" />
                                    Climatise
                                </span>
                                <span v-if="box.has_electricity" class="flex items-center text-xs bg-yellow-50 text-yellow-700 px-2 py-1 rounded-lg">
                                    <BoltIcon class="h-3 w-3 mr-1" />
                                    Electricite
                                </span>
                                <span v-if="box.has_24_7_access" class="flex items-center text-xs bg-green-50 text-green-700 px-2 py-1 rounded-lg">
                                    <ClockIcon class="h-3 w-3 mr-1" />
                                    24/7
                                </span>
                                <span v-if="box.is_ground_floor" class="flex items-center text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded-lg">
                                    RDC
                                </span>
                            </div>

                            <!-- Availability indicator & Image gallery button -->
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
                                <span
                                    :class="['text-xs px-2 py-1 rounded-full font-medium flex items-center', getBoxAvailabilityClass(box.id)]"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full mr-1.5"
                                        :class="availabilityStatus[`${box.id}_${form.start_date}`] === true ? 'bg-green-500' : availabilityStatus[`${box.id}_${form.start_date}`] === false ? 'bg-red-500' : 'bg-gray-400'"
                                    ></span>
                                    {{ getBoxAvailabilityText(box.id) }}
                                </span>
                                <button
                                    v-if="box.images && box.images.length > 0"
                                    @click.stop="openImageGallery(box)"
                                    class="flex items-center text-xs text-gray-500 hover:text-gray-700 transition-colors"
                                >
                                    <PhotoIcon class="h-4 w-4 mr-1" />
                                    {{ box.images.length }} photo{{ box.images.length > 1 ? 's' : '' }}
                                </button>
                            </div>

                            <!-- Selection indicator -->
                            <div
                                v-if="selectedBox === box.id"
                                class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center"
                                :style="{ backgroundColor: settings?.primary_color }"
                            >
                                <CheckIcon class="h-5 w-5 text-white" />
                            </div>
                        </button>
                    </div>

                    <!-- Empty state -->
                    <div v-if="availableBoxes.length === 0" class="text-center py-12">
                        <CubeIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun box trouve</h3>
                        <p class="text-gray-500">Essayez de modifier vos filtres</p>
                        <button
                            @click="sizeFilter = 'all'; searchQuery = ''"
                            class="mt-4 text-sm font-medium hover:underline"
                            :style="{ color: settings?.primary_color }"
                        >
                            Reinitialiser les filtres
                        </button>
                    </div>
                </div>

                <!-- Continue Button -->
                <div v-if="selectedBox" class="flex justify-end">
                    <button
                        @click="nextStep"
                        class="group px-8 py-4 rounded-2xl text-white font-semibold text-lg flex items-center transition-all transform hover:scale-105 shadow-lg"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        Continuer
                        <ArrowRightIcon class="h-5 w-5 ml-2 group-hover:translate-x-1 transition-transform" />
                    </button>
                </div>
            </div>

            <!-- Step 2: Customer Info -->
            <div v-if="step === 2" class="space-y-6">
                <!-- Selected Box Summary -->
                <div v-if="selectedBoxData" class="bg-gradient-to-r from-gray-900 to-gray-800 rounded-3xl shadow-xl p-6 text-white">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <p class="text-gray-400 text-sm">Box selectionne</p>
                            <h3 class="text-xl font-bold">{{ selectedBoxData.name }}</h3>
                            <p class="text-gray-300">{{ sites.find(s => s.id === selectedSite)?.name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-gray-400 text-sm">Prix mensuel</p>
                            <p class="text-3xl font-bold" :style="{ color: settings?.primary_color }">{{ formatCurrency(selectedBoxData.current_price) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <UserIcon class="h-7 w-7 mr-3" :style="{ color: settings?.primary_color }" />
                        Vos informations
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Prenom *</label>
                            <input
                                type="text"
                                v-model="form.customer_first_name"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                :class="errors.customer_first_name ? 'border-red-500' : ''"
                                placeholder="Jean"
                            />
                            <p v-if="errors.customer_first_name" class="text-red-500 text-sm mt-1 flex items-center">
                                <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                                {{ errors.customer_first_name }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                            <input
                                type="text"
                                v-model="form.customer_last_name"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                :class="errors.customer_last_name ? 'border-red-500' : ''"
                                placeholder="Dupont"
                            />
                            <p v-if="errors.customer_last_name" class="text-red-500 text-sm mt-1">{{ errors.customer_last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                            <input
                                type="email"
                                v-model="form.customer_email"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                :class="errors.customer_email ? 'border-red-500' : ''"
                                placeholder="jean.dupont@email.com"
                            />
                            <p v-if="errors.customer_email" class="text-red-500 text-sm mt-1">{{ errors.customer_email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Telephone</label>
                            <input
                                type="tel"
                                v-model="form.customer_phone"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                :class="errors.customer_phone ? 'border-red-500' : ''"
                                placeholder="06 12 34 56 78"
                            />
                            <p v-if="errors.customer_phone" class="text-red-500 text-sm mt-1">{{ errors.customer_phone }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Adresse</label>
                            <input
                                type="text"
                                v-model="form.customer_address"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                placeholder="123 rue de la Paix"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Code postal</label>
                            <input
                                type="text"
                                v-model="form.customer_postal_code"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                placeholder="75001"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ville</label>
                            <input
                                type="text"
                                v-model="form.customer_city"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                placeholder="Paris"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Entreprise (optionnel)</label>
                            <input
                                type="text"
                                v-model="form.customer_company"
                                class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                placeholder="Ma Societe SARL"
                            />
                        </div>
                    </div>

                    <!-- Duration Section -->
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <CalendarIcon class="h-6 w-6 mr-2" :style="{ color: settings?.primary_color }" />
                            Duree de location
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Date de debut *</label>
                                <input
                                    type="date"
                                    v-model="form.start_date"
                                    :min="minDate"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                    :class="errors.start_date ? 'border-red-500' : ''"
                                />
                                <p v-if="errors.start_date" class="text-red-500 text-sm mt-1">{{ errors.start_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Type de location</label>
                                <select
                                    v-model="form.duration_type"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                >
                                    <option value="month_to_month">Mois par mois (flexible)</option>
                                    <option value="fixed_term">Duree determinee</option>
                                </select>
                            </div>
                            <div v-if="form.duration_type === 'fixed_term'">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Duree prevue</label>
                                <select
                                    v-model="form.planned_duration_months"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                >
                                    <option :value="null">Selectionner...</option>
                                    <option :value="1">1 mois</option>
                                    <option :value="3">3 mois (-5%)</option>
                                    <option :value="6">6 mois (-10%)</option>
                                    <option :value="12">12 mois (-15%)</option>
                                    <option :value="24">24 mois (-20%)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Special Needs Section -->
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Options et services</h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div
                                @click="form.needs_24h_access = !form.needs_24h_access"
                                class="flex items-center p-4 border-2 rounded-2xl cursor-pointer hover:bg-gray-50 transition-all"
                                :class="form.needs_24h_access ? 'border-teal-500 bg-teal-50' : 'border-gray-200'"
                            >
                                <span
                                    class="w-6 h-6 border-2 rounded-lg mr-3 flex items-center justify-center transition-all flex-shrink-0"
                                    :class="form.needs_24h_access ? 'bg-teal-500 border-teal-500' : 'border-gray-300'"
                                >
                                    <CheckIcon v-if="form.needs_24h_access" class="h-4 w-4 text-white" />
                                </span>
                                <div>
                                    <ClockIcon class="h-5 w-5 text-gray-400 mb-1" />
                                    <span class="text-sm font-medium">Acces 24h/24</span>
                                </div>
                            </div>
                            <div
                                @click="form.needs_climate_control = !form.needs_climate_control"
                                class="flex items-center p-4 border-2 rounded-2xl cursor-pointer hover:bg-gray-50 transition-all"
                                :class="form.needs_climate_control ? 'border-teal-500 bg-teal-50' : 'border-gray-200'"
                            >
                                <span
                                    class="w-6 h-6 border-2 rounded-lg mr-3 flex items-center justify-center transition-all flex-shrink-0"
                                    :class="form.needs_climate_control ? 'bg-teal-500 border-teal-500' : 'border-gray-300'"
                                >
                                    <CheckIcon v-if="form.needs_climate_control" class="h-4 w-4 text-white" />
                                </span>
                                <div>
                                    <SunIcon class="h-5 w-5 text-gray-400 mb-1" />
                                    <span class="text-sm font-medium">Climatisation</span>
                                </div>
                            </div>
                            <div
                                @click="form.needs_electricity = !form.needs_electricity"
                                class="flex items-center p-4 border-2 rounded-2xl cursor-pointer hover:bg-gray-50 transition-all"
                                :class="form.needs_electricity ? 'border-teal-500 bg-teal-50' : 'border-gray-200'"
                            >
                                <span
                                    class="w-6 h-6 border-2 rounded-lg mr-3 flex items-center justify-center transition-all flex-shrink-0"
                                    :class="form.needs_electricity ? 'bg-teal-500 border-teal-500' : 'border-gray-300'"
                                >
                                    <CheckIcon v-if="form.needs_electricity" class="h-4 w-4 text-white" />
                                </span>
                                <div>
                                    <BoltIcon class="h-5 w-5 text-gray-400 mb-1" />
                                    <span class="text-sm font-medium">Electricite</span>
                                </div>
                            </div>
                            <div
                                @click="form.needs_insurance = !form.needs_insurance"
                                class="flex items-center p-4 border-2 rounded-2xl cursor-pointer hover:bg-gray-50 transition-all"
                                :class="form.needs_insurance ? 'border-teal-500 bg-teal-50' : 'border-gray-200'"
                            >
                                <span
                                    class="w-6 h-6 border-2 rounded-lg mr-3 flex items-center justify-center transition-all flex-shrink-0"
                                    :class="form.needs_insurance ? 'bg-teal-500 border-teal-500' : 'border-gray-300'"
                                >
                                    <CheckIcon v-if="form.needs_insurance" class="h-4 w-4 text-white" />
                                </span>
                                <div>
                                    <ShieldCheckIcon class="h-5 w-5 text-gray-400 mb-1" />
                                    <span class="text-sm font-medium">Assurance</span>
                                </div>
                            </div>
                            <div
                                @click="form.needs_moving_help = !form.needs_moving_help"
                                class="flex items-center p-4 border-2 rounded-2xl cursor-pointer hover:bg-gray-50 transition-all"
                                :class="form.needs_moving_help ? 'border-teal-500 bg-teal-50' : 'border-gray-200'"
                            >
                                <span
                                    class="w-6 h-6 border-2 rounded-lg mr-3 flex items-center justify-center transition-all flex-shrink-0"
                                    :class="form.needs_moving_help ? 'bg-teal-500 border-teal-500' : 'border-gray-300'"
                                >
                                    <CheckIcon v-if="form.needs_moving_help" class="h-4 w-4 text-white" />
                                </span>
                                <div>
                                    <TruckIcon class="h-5 w-5 text-gray-400 mb-1" />
                                    <span class="text-sm font-medium">Demenagement</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Storage Contents -->
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <h3 class="text-xl font-bold text-gray-900 mb-6">Informations complementaires</h3>
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Que souhaitez-vous stocker ?</label>
                                <textarea
                                    v-model="form.storage_contents"
                                    rows="2"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                    placeholder="Meubles, cartons, archives professionnelles..."
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Demandes particulieres</label>
                                <textarea
                                    v-model="form.special_requests"
                                    rows="2"
                                    class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:border-transparent transition-all"
                                    placeholder="Toute information ou demande specifique..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="prevStep"
                        class="px-6 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-all flex items-center"
                    >
                        <ChevronLeftIcon class="h-5 w-5 mr-2" />
                        Retour
                    </button>
                    <button
                        @click="nextStep"
                        class="group px-8 py-4 rounded-2xl text-white font-semibold text-lg flex items-center transition-all transform hover:scale-105 shadow-lg"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        Continuer
                        <ArrowRightIcon class="h-5 w-5 ml-2 group-hover:translate-x-1 transition-transform" />
                    </button>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-if="step === 3" class="space-y-6">
                <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <CheckCircleIcon class="h-7 w-7 mr-3" :style="{ color: settings?.primary_color }" />
                        Recapitulatif de votre reservation
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5">
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wide">Box selectionne</h3>
                            <p class="font-bold text-xl text-gray-900">
                                {{ availableBoxes.find(b => b.id === selectedBox)?.name }}
                            </p>
                            <p class="text-gray-600 mt-1">
                                {{ sites.find(s => s.id === selectedSite)?.name }}
                            </p>
                            <p class="text-gray-500 text-sm mt-2">
                                {{ availableBoxes.find(b => b.id === selectedBox)?.formatted_volume }} - {{ availableBoxes.find(b => b.id === selectedBox)?.dimensions }}
                            </p>
                        </div>
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5">
                            <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wide">Vos coordonnees</h3>
                            <p class="font-bold text-xl text-gray-900">
                                {{ form.customer_first_name }} {{ form.customer_last_name }}
                            </p>
                            <p class="text-gray-600 mt-1">{{ form.customer_email }}</p>
                            <p v-if="form.customer_phone" class="text-gray-500 text-sm mt-1">{{ form.customer_phone }}</p>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div v-if="settings?.allow_promo_codes" class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-500 mb-3 uppercase tracking-wide">Code promo</h3>
                        <div v-if="!promoApplied" class="flex items-center gap-3">
                            <input
                                type="text"
                                v-model="promoCode"
                                class="flex-1 border-2 border-gray-200 rounded-xl px-4 py-3 uppercase font-mono"
                                placeholder="Entrez votre code"
                            />
                            <button
                                @click="validatePromo"
                                class="px-6 py-3 rounded-xl text-white font-semibold transition-all hover:opacity-90"
                                :style="{ backgroundColor: settings?.primary_color }"
                            >
                                Appliquer
                            </button>
                        </div>
                        <div v-else class="flex items-center justify-between bg-green-50 border-2 border-green-200 rounded-2xl p-4">
                            <div class="flex items-center">
                                <TicketIcon class="h-6 w-6 text-green-600 mr-3" />
                                <div>
                                    <span class="font-bold text-green-800">{{ promoApplied.code }}</span>
                                    <span class="text-green-600 ml-2">-{{ promoApplied.discount_label }}</span>
                                </div>
                            </div>
                            <button @click="removePromo" class="text-red-600 hover:text-red-700 font-medium">
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="promoError" class="text-red-500 text-sm mt-2 flex items-center">
                            <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                            {{ promoError }}
                        </p>
                    </div>

                    <!-- Pricing -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Prix mensuel</span>
                                <span class="font-medium">{{ formatCurrency(availableBoxes.find(b => b.id === selectedBox)?.current_price) }}</span>
                            </div>
                            <div v-if="promoApplied" class="flex justify-between text-green-600">
                                <span>Reduction ({{ promoApplied.discount_label }})</span>
                                <span class="font-medium">-{{ formatCurrency(promoApplied.calculated_discount) }}</span>
                            </div>
                            <div v-if="depositAmount > 0" class="flex justify-between text-gray-600">
                                <span>Acompte a la reservation</span>
                                <span class="font-medium">{{ formatCurrency(depositAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-2xl font-bold text-gray-900 pt-4 border-t border-gray-200">
                                <span>Total mensuel</span>
                                <span :style="{ color: settings?.primary_color }">{{ formatCurrency(monthlyPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Selection (if Stripe enabled) -->
                    <div v-if="settings?.stripe_publishable_key" class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <CurrencyEuroIcon class="h-6 w-6 mr-2" :style="{ color: settings?.primary_color }" />
                            Comment souhaitez-vous payer ?
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Pay Now -->
                            <button
                                type="button"
                                @click="paymentMethod = 'now'"
                                :class="[
                                    'p-5 rounded-2xl border-2 text-left transition-all hover:shadow-lg',
                                    paymentMethod === 'now' ? 'shadow-lg' : 'border-gray-200'
                                ]"
                                :style="paymentMethod === 'now' ? { borderColor: settings?.primary_color, backgroundColor: settings?.primary_color + '08' } : {}"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center mb-2">
                                            <CreditCardIcon class="h-6 w-6 mr-2" :style="{ color: settings?.primary_color }" />
                                            <span class="font-bold text-gray-900">Payer maintenant</span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            Reglez votre premier mois et l'acompte par carte bancaire. Reservation confirmee immediatement.
                                        </p>
                                        <p class="mt-3 font-bold text-lg" :style="{ color: settings?.primary_color }">
                                            {{ formatCurrency(monthlyPrice + depositAmount) }}
                                        </p>
                                    </div>
                                    <div
                                        v-if="paymentMethod === 'now'"
                                        class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
                                        :style="{ backgroundColor: settings?.primary_color }"
                                    >
                                        <CheckIcon class="h-4 w-4 text-white" />
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center text-xs text-gray-500">
                                    <LockClosedIcon class="h-4 w-4 mr-1" />
                                    Paiement securise par Stripe
                                </div>
                            </button>

                            <!-- Pay at Signing -->
                            <button
                                type="button"
                                @click="paymentMethod = 'at_signing'"
                                :class="[
                                    'p-5 rounded-2xl border-2 text-left transition-all hover:shadow-lg',
                                    paymentMethod === 'at_signing' ? 'shadow-lg' : 'border-gray-200'
                                ]"
                                :style="paymentMethod === 'at_signing' ? { borderColor: settings?.primary_color, backgroundColor: settings?.primary_color + '08' } : {}"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center mb-2">
                                            <CalendarIcon class="h-6 w-6 mr-2" :style="{ color: settings?.primary_color }" />
                                            <span class="font-bold text-gray-900">Payer a la signature</span>
                                        </div>
                                        <p class="text-sm text-gray-600">
                                            Reservez maintenant, payez lors de la signature du contrat sur site ou par virement.
                                        </p>
                                        <p class="mt-3 font-bold text-lg text-gray-700">
                                            0 € maintenant
                                        </p>
                                    </div>
                                    <div
                                        v-if="paymentMethod === 'at_signing'"
                                        class="w-6 h-6 rounded-full flex items-center justify-center flex-shrink-0"
                                        :style="{ backgroundColor: settings?.primary_color }"
                                    >
                                        <CheckIcon class="h-4 w-4 text-white" />
                                    </div>
                                </div>
                                <div class="mt-3 flex items-center text-xs text-gray-500">
                                    <ClockIcon class="h-4 w-4 mr-1" />
                                    Paiement differe
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <label class="flex items-start cursor-pointer group">
                            <input
                                type="checkbox"
                                v-model="form.terms_accepted"
                                class="h-5 w-5 rounded border-gray-300 mt-0.5"
                            />
                            <span class="ml-3 text-gray-600 group-hover:text-gray-900 transition-colors">
                                J'accepte les <a href="#" class="underline font-medium" :style="{ color: settings?.primary_color }">conditions generales</a> de location et la <a href="#" class="underline font-medium" :style="{ color: settings?.primary_color }">politique de confidentialite</a> *
                            </span>
                        </label>
                        <p v-if="errors.terms_accepted" class="text-red-500 text-sm mt-2 flex items-center">
                            <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                            {{ errors.terms_accepted }}
                        </p>
                    </div>

                    <p v-if="errors.general" class="mt-4 text-red-500 text-center bg-red-50 rounded-xl p-4">{{ errors.general }}</p>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="prevStep"
                        class="px-6 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-all flex items-center"
                    >
                        <ChevronLeftIcon class="h-5 w-5 mr-2" />
                        Retour
                    </button>
                    <!-- If paying now and Stripe enabled, go to step 4 -->
                    <button
                        v-if="settings?.stripe_publishable_key && paymentMethod === 'now'"
                        @click="nextStep"
                        :disabled="!form.terms_accepted"
                        class="group px-10 py-4 rounded-2xl text-white font-bold text-lg flex items-center transition-all transform hover:scale-105 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        <span class="flex items-center">
                            Proceder au paiement
                            <CreditCardIcon class="h-6 w-6 ml-2" />
                        </span>
                    </button>
                    <!-- Otherwise, submit booking directly -->
                    <button
                        v-else
                        @click="submitBooking"
                        :disabled="processing || !form.terms_accepted"
                        class="group px-10 py-4 rounded-2xl text-white font-bold text-lg flex items-center transition-all transform hover:scale-105 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        <span v-if="processing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Traitement...
                        </span>
                        <span v-else class="flex items-center">
                            Confirmer la reservation
                            <CheckCircleIcon class="h-6 w-6 ml-2" />
                        </span>
                    </button>
                </div>
            </div>

            <!-- Step 4: Payment (if paying now) -->
            <div v-if="step === 4" class="space-y-6">
                <div class="bg-white rounded-3xl shadow-xl p-6 md:p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
                        <CreditCardIcon class="h-7 w-7 mr-3" :style="{ color: settings?.primary_color }" />
                        Paiement securise
                    </h2>

                    <!-- Payment Summary -->
                    <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-5 mb-6">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Premier mois de location</span>
                            <span class="font-medium">{{ formatCurrency(monthlyPrice) }}</span>
                        </div>
                        <div v-if="depositAmount > 0" class="flex justify-between items-center mb-3">
                            <span class="text-gray-600">Depot de garantie</span>
                            <span class="font-medium">{{ formatCurrency(depositAmount) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                            <span class="font-bold text-lg text-gray-900">Total a payer</span>
                            <span class="font-bold text-2xl" :style="{ color: settings?.primary_color }">
                                {{ formatCurrency(totalDueNow) }}
                            </span>
                        </div>
                    </div>

                    <!-- Stripe Card Element -->
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">
                            Informations de carte bancaire
                        </label>
                        <div
                            id="card-element"
                            class="border-2 border-gray-200 rounded-xl p-4 bg-white focus-within:border-blue-500 transition-colors"
                        >
                            <!-- Stripe Elements will mount here -->
                            <div v-if="!stripeLoaded" class="flex items-center justify-center py-4 text-gray-400">
                                <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Chargement du formulaire de paiement...
                            </div>
                        </div>
                        <p v-if="cardError" class="text-red-500 text-sm mt-2 flex items-center">
                            <ExclamationCircleIcon class="h-4 w-4 mr-1" />
                            {{ cardError }}
                        </p>
                    </div>

                    <!-- Security badges -->
                    <div class="flex items-center justify-center gap-6 text-gray-400 text-sm mb-6">
                        <div class="flex items-center">
                            <LockClosedIcon class="h-5 w-5 mr-1" />
                            SSL Securise
                        </div>
                        <div class="flex items-center">
                            <ShieldCheckIcon class="h-5 w-5 mr-1" />
                            Stripe
                        </div>
                        <div class="flex items-center">
                            <span class="font-bold">VISA</span>
                            <span class="ml-2 font-bold">MC</span>
                        </div>
                    </div>

                    <p v-if="errors.general" class="mt-4 text-red-500 text-center bg-red-50 rounded-xl p-4">{{ errors.general }}</p>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="prevStep"
                        class="px-6 py-3 rounded-xl border-2 border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-all flex items-center"
                    >
                        <ChevronLeftIcon class="h-5 w-5 mr-2" />
                        Retour
                    </button>
                    <button
                        @click="submitBooking"
                        :disabled="processing || paymentProcessing || !stripeLoaded"
                        class="group px-10 py-4 rounded-2xl text-white font-bold text-lg flex items-center transition-all transform hover:scale-105 shadow-xl disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        <span v-if="processing || paymentProcessing" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ paymentProcessing ? 'Paiement en cours...' : 'Traitement...' }}
                        </span>
                        <span v-else class="flex items-center">
                            <LockClosedIcon class="h-5 w-5 mr-2" />
                            Payer {{ formatCurrency(totalDueNow) }}
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Image Gallery Modal -->
        <Teleport to="body">
            <div
                v-if="showImageGallery"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm"
                @click.self="closeImageGallery"
            >
                <button
                    @click="closeImageGallery"
                    class="absolute top-4 right-4 text-white hover:text-gray-300 z-10"
                >
                    <XMarkIcon class="h-8 w-8" />
                </button>

                <button
                    @click="prevGalleryImage"
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black/30 hover:bg-black/50 rounded-full p-2 transition-all"
                >
                    <ChevronLeftIcon class="h-8 w-8" />
                </button>

                <div class="max-w-4xl max-h-[80vh] relative">
                    <img
                        :src="selectedBoxImages[galleryImageIndex]?.url || selectedBoxImages[galleryImageIndex]"
                        class="max-h-[80vh] max-w-full object-contain rounded-lg"
                        :alt="`Image ${galleryImageIndex + 1}`"
                    />
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-black/60 text-white px-4 py-2 rounded-full text-sm">
                        {{ galleryImageIndex + 1 }} / {{ selectedBoxImages.length }}
                    </div>
                </div>

                <button
                    @click="nextGalleryImage"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-white hover:text-gray-300 bg-black/30 hover:bg-black/50 rounded-full p-2 transition-all"
                >
                    <ChevronRightIcon class="h-8 w-8" />
                </button>

                <!-- Thumbnail strip -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 mt-4">
                    <button
                        v-for="(img, idx) in selectedBoxImages"
                        :key="idx"
                        @click="galleryImageIndex = idx"
                        :class="[
                            'w-16 h-12 rounded-lg overflow-hidden border-2 transition-all',
                            idx === galleryImageIndex ? 'border-white scale-110' : 'border-transparent opacity-60 hover:opacity-100'
                        ]"
                    >
                        <img :src="img.url || img" class="w-full h-full object-cover" />
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Footer -->
        <footer class="py-8 px-4 text-center bg-gray-900 text-gray-400 mt-12">
            <div class="max-w-4xl mx-auto">
                <p v-if="settings?.contact_email || settings?.contact_phone" class="mb-4">
                    <span class="text-gray-500">Besoin d'aide ?</span>
                    <a v-if="settings?.contact_email" :href="`mailto:${settings.contact_email}`" class="ml-2 text-white hover:underline">{{ settings.contact_email }}</a>
                    <span v-if="settings?.contact_email && settings?.contact_phone" class="mx-2">|</span>
                    <a v-if="settings?.contact_phone" :href="`tel:${settings.contact_phone}`" class="text-white hover:underline">{{ settings.contact_phone }}</a>
                </p>
                <p class="text-sm">
                    Powered by <span class="font-semibold text-white">Boxibox</span>
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
