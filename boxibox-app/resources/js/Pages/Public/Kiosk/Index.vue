<script setup>
/**
 * Kiosk Mode - Fullscreen Interface for Unmanned Self-Storage Facilities
 *
 * Features:
 * - Fullscreen touch-optimized interface
 * - Large buttons and fonts for easy touch interaction
 * - Auto-return to home after inactivity
 * - Simplified booking flow
 * - On-screen keyboard support
 * - QR code generation for booking confirmation
 */
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import {
    CubeIcon,
    MapPinIcon,
    CalendarIcon,
    CheckIcon,
    UserIcon,
    ArrowRightIcon,
    ArrowLeftIcon,
    HomeIcon,
    XMarkIcon,
    ClockIcon,
    ShieldCheckIcon,
    PhoneIcon,
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon, LockClosedIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    settings: Object,
    tenant: Object,
    sites: Array,
})

// Screen management
const currentScreen = ref('home') // home, site, box, date, customer, payment, confirm, success
const isFullscreen = ref(false)
const inactivityTimeout = ref(null)
const INACTIVITY_DELAY = 120000 // 2 minutes

// Selection state
const selectedSite = ref(null)
const selectedBox = ref(null)
const sizeFilter = ref('all')

// Form state
const form = ref({
    customer_first_name: '',
    customer_last_name: '',
    customer_email: '',
    customer_phone: '',
    start_date: '',
    terms_accepted: false,
})

const errors = ref({})
const processing = ref(false)
const bookingResult = ref(null)

// Virtual keyboard
const showKeyboard = ref(false)
const activeInput = ref(null)
const keyboardType = ref('text') // text, email, phone, date

// Size categories
const sizeCategories = [
    { value: 'all', label: 'Tous', icon: '📦', color: 'gray' },
    { value: 'small', label: 'Petit', icon: '📦', color: 'blue', min: 0, max: 5 },
    { value: 'medium', label: 'Moyen', icon: '🏠', color: 'green', min: 5, max: 15 },
    { value: 'large', label: 'Grand', icon: '🏢', color: 'purple', min: 15, max: Infinity },
]

// Computed
const availableBoxes = computed(() => {
    if (!selectedSite.value) return []
    const site = props.sites.find(s => s.id === selectedSite.value)
    let boxes = site?.boxes?.filter(b => b.status === 'available') || []

    if (sizeFilter.value !== 'all') {
        const category = sizeCategories.find(c => c.value === sizeFilter.value)
        if (category) {
            boxes = boxes.filter(b => b.volume >= category.min && b.volume < category.max)
        }
    }

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

const minDate = computed(() => {
    return new Date().toISOString().split('T')[0]
})

const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
    })
})

// Helpers
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

// Navigation
const goToScreen = (screen) => {
    currentScreen.value = screen
    resetInactivityTimer()
}

const goBack = () => {
    const flow = ['home', 'site', 'box', 'date', 'customer', 'confirm']
    const currentIndex = flow.indexOf(currentScreen.value)
    if (currentIndex > 0) {
        currentScreen.value = flow[currentIndex - 1]
    }
    resetInactivityTimer()
}

const goHome = () => {
    // Reset everything
    currentScreen.value = 'home'
    selectedSite.value = null
    selectedBox.value = null
    sizeFilter.value = 'all'
    form.value = {
        customer_first_name: '',
        customer_last_name: '',
        customer_email: '',
        customer_phone: '',
        start_date: '',
        terms_accepted: false,
    }
    errors.value = {}
    bookingResult.value = null
    showKeyboard.value = false
}

// Fullscreen management
const toggleFullscreen = async () => {
    try {
        if (!document.fullscreenElement) {
            await document.documentElement.requestFullscreen()
            isFullscreen.value = true
        } else {
            await document.exitFullscreen()
            isFullscreen.value = false
        }
    } catch (e) {
        console.error('Fullscreen error:', e)
    }
}

// Inactivity timer
const resetInactivityTimer = () => {
    if (inactivityTimeout.value) {
        clearTimeout(inactivityTimeout.value)
    }

    if (currentScreen.value !== 'home' && currentScreen.value !== 'success') {
        inactivityTimeout.value = setTimeout(() => {
            goHome()
        }, INACTIVITY_DELAY)
    }
}

// Virtual keyboard
const openKeyboard = (inputName, type = 'text') => {
    activeInput.value = inputName
    keyboardType.value = type
    showKeyboard.value = true
    resetInactivityTimer()
}

const closeKeyboard = () => {
    showKeyboard.value = false
    activeInput.value = null
}

const onKeyPress = (key) => {
    resetInactivityTimer()

    if (!activeInput.value) return

    if (key === 'backspace') {
        form.value[activeInput.value] = form.value[activeInput.value].slice(0, -1)
    } else if (key === 'space') {
        form.value[activeInput.value] += ' '
    } else if (key === 'clear') {
        form.value[activeInput.value] = ''
    } else if (key === 'done') {
        closeKeyboard()
    } else {
        form.value[activeInput.value] += key
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
    if (!form.value.customer_phone) errors.value.customer_phone = 'Téléphone requis'
    if (!form.value.start_date) errors.value.start_date = 'Date requise'

    return Object.keys(errors.value).length === 0
}

// Submit booking
const submitBooking = async () => {
    if (!form.value.terms_accepted) {
        errors.value.terms = 'Acceptez les conditions'
        return
    }

    processing.value = true
    errors.value = {}

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
                source: 'kiosk',
                payment_method: 'at_signing',
            }),
        })

        const data = await response.json()

        if (data.success) {
            bookingResult.value = data.booking
            currentScreen.value = 'success'

            // Auto return home after 30 seconds on success
            setTimeout(() => {
                goHome()
            }, 30000)
        } else {
            errors.value = data.errors || { general: 'Erreur lors de la réservation' }
        }
    } catch (e) {
        errors.value.general = 'Erreur de connexion'
    } finally {
        processing.value = false
    }
}

// Quick date selection
const selectToday = () => {
    form.value.start_date = minDate.value
    resetInactivityTimer()
}

const selectTomorrow = () => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    form.value.start_date = tomorrow.toISOString().split('T')[0]
    resetInactivityTimer()
}

const selectNextWeek = () => {
    const nextWeek = new Date()
    nextWeek.setDate(nextWeek.getDate() + 7)
    form.value.start_date = nextWeek.toISOString().split('T')[0]
    resetInactivityTimer()
}

// Event listeners
const handleActivity = () => {
    resetInactivityTimer()
}

onMounted(() => {
    // Set default date to today
    form.value.start_date = minDate.value

    // Add activity listeners
    document.addEventListener('touchstart', handleActivity)
    document.addEventListener('click', handleActivity)
    document.addEventListener('keydown', handleActivity)

    // Check fullscreen state
    document.addEventListener('fullscreenchange', () => {
        isFullscreen.value = !!document.fullscreenElement
    })

    // Auto-fullscreen if on touch device
    if ('ontouchstart' in window) {
        // Request fullscreen on first touch
        const requestFS = () => {
            toggleFullscreen()
            document.removeEventListener('touchstart', requestFS)
        }
        document.addEventListener('touchstart', requestFS, { once: true })
    }
})

onUnmounted(() => {
    if (inactivityTimeout.value) {
        clearTimeout(inactivityTimeout.value)
    }
    document.removeEventListener('touchstart', handleActivity)
    document.removeEventListener('click', handleActivity)
    document.removeEventListener('keydown', handleActivity)
})
</script>

<template>
    <Head :title="`Kiosk - ${settings?.company_name || tenant.name}`" />

    <div
        class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 text-white select-none overflow-hidden"
        :style="{ '--primary-color': settings?.primary_color || '#3B82F6' }"
    >
        <!-- Header Bar -->
        <header class="fixed top-0 left-0 right-0 z-50 bg-black/30 backdrop-blur-lg px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button
                        v-if="currentScreen !== 'home' && currentScreen !== 'success'"
                        @click="goBack"
                        class="p-3 rounded-xl bg-white/10 hover:bg-white/20 transition-all active:scale-95"
                    >
                        <ArrowLeftIcon class="h-8 w-8" />
                    </button>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center"
                            :style="{ backgroundColor: settings?.primary_color }"
                        >
                            <CubeIcon class="h-7 w-7 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold">{{ settings?.company_name || tenant.name }}</h1>
                            <p class="text-sm text-white/60">Borne de réservation</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-white/60">{{ new Date().toLocaleDateString('fr-FR') }}</p>
                        <p class="text-lg font-semibold">{{ new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}</p>
                    </div>
                    <button
                        v-if="currentScreen !== 'home'"
                        @click="goHome"
                        class="p-3 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-400 transition-all active:scale-95"
                    >
                        <HomeIcon class="h-8 w-8" />
                    </button>
                    <button
                        @click="toggleFullscreen"
                        class="p-3 rounded-xl bg-white/10 hover:bg-white/20 transition-all active:scale-95"
                    >
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!isFullscreen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V5m0 0H5m4 0L4 10m11-1V5m0 0h4m-4 0l5 5M9 15v4m0 0H5m4 0l-5-5m11 5v-4m0 0h4m-4 0l5-5" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="pt-24 pb-8 px-6 min-h-screen flex flex-col">

            <!-- HOME SCREEN -->
            <div v-if="currentScreen === 'home'" class="flex-1 flex flex-col items-center justify-center text-center">
                <div class="mb-12 animate-pulse">
                    <div
                        class="w-32 h-32 rounded-3xl flex items-center justify-center mx-auto mb-8 shadow-2xl"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        <CubeIcon class="h-20 w-20 text-white" />
                    </div>
                    <h1 class="text-5xl font-bold mb-4">Bienvenue</h1>
                    <p class="text-2xl text-white/70">Réservez votre box de stockage en quelques clics</p>
                </div>

                <button
                    @click="goToScreen('site')"
                    class="group px-16 py-8 rounded-3xl text-white text-3xl font-bold transition-all transform hover:scale-105 active:scale-95 shadow-2xl flex items-center gap-4"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Commencer
                    <ArrowRightIcon class="h-10 w-10 group-hover:translate-x-2 transition-transform" />
                </button>

                <div class="mt-16 flex items-center gap-8 text-white/50 text-lg">
                    <span class="flex items-center gap-2">
                        <ShieldCheckIcon class="h-6 w-6" />
                        Sécurisé 24/7
                    </span>
                    <span class="flex items-center gap-2">
                        <ClockIcon class="h-6 w-6" />
                        Accès flexible
                    </span>
                    <span class="flex items-center gap-2">
                        <LockClosedIcon class="h-6 w-6" />
                        Sans engagement
                    </span>
                </div>
            </div>

            <!-- SITE SELECTION -->
            <div v-else-if="currentScreen === 'site'" class="flex-1 flex flex-col">
                <div class="text-center mb-8">
                    <h2 class="text-4xl font-bold mb-2">Choisissez votre site</h2>
                    <p class="text-xl text-white/60">Sélectionnez l'emplacement qui vous convient</p>
                </div>

                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto w-full">
                    <button
                        v-for="site in sites"
                        :key="site.id"
                        @click="selectedSite = site.id; goToScreen('box')"
                        class="p-8 rounded-3xl border-2 text-left transition-all hover:scale-[1.02] active:scale-[0.98] bg-white/5 border-white/10 hover:border-white/30 hover:bg-white/10"
                    >
                        <div class="flex items-start gap-4">
                            <div
                                class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0"
                                :style="{ backgroundColor: settings?.primary_color }"
                            >
                                <MapPinIcon class="h-8 w-8 text-white" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold mb-1">{{ site.name }}</h3>
                                <p class="text-white/60 text-lg">{{ site.address }}, {{ site.city }}</p>
                                <p class="text-xl font-semibold mt-4" :style="{ color: settings?.primary_color }">
                                    {{ site.available_boxes_count }} box disponibles
                                </p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- BOX SELECTION -->
            <div v-else-if="currentScreen === 'box'" class="flex-1 flex flex-col">
                <div class="text-center mb-6">
                    <h2 class="text-4xl font-bold mb-2">Choisissez votre box</h2>
                    <p class="text-xl text-white/60">{{ selectedSiteData?.name }}</p>
                </div>

                <!-- Size Filter -->
                <div class="flex justify-center gap-4 mb-6">
                    <button
                        v-for="cat in sizeCategories"
                        :key="cat.value"
                        @click="sizeFilter = cat.value"
                        :class="[
                            'px-8 py-4 rounded-2xl text-xl font-semibold transition-all active:scale-95',
                            sizeFilter === cat.value
                                ? 'text-white shadow-lg'
                                : 'bg-white/10 text-white/70 hover:bg-white/20'
                        ]"
                        :style="sizeFilter === cat.value ? { backgroundColor: settings?.primary_color } : {}"
                    >
                        {{ cat.icon }} {{ cat.label }}
                    </button>
                </div>

                <!-- Boxes Grid -->
                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-w-6xl mx-auto">
                        <button
                            v-for="box in availableBoxes"
                            :key="box.id"
                            @click="selectedBox = box.id; goToScreen('date')"
                            class="p-6 rounded-2xl border-2 text-left transition-all hover:scale-[1.02] active:scale-[0.98] bg-white/5 border-white/10 hover:border-white/30 hover:bg-white/10"
                        >
                            <div class="text-center">
                                <div class="text-3xl mb-2">
                                    {{ box.volume < 5 ? '📦' : box.volume < 15 ? '🏠' : '🏢' }}
                                </div>
                                <h3 class="text-xl font-bold mb-1">{{ box.name }}</h3>
                                <p class="text-white/60">{{ box.formatted_volume }}</p>
                                <p class="text-2xl font-bold mt-3" :style="{ color: settings?.primary_color }">
                                    {{ formatCurrency(box.current_price) }}
                                    <span class="text-sm text-white/50">/mois</span>
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <p v-if="availableBoxes.length === 0" class="text-center text-white/50 text-xl py-12">
                    Aucun box disponible pour ce filtre
                </p>
            </div>

            <!-- DATE SELECTION -->
            <div v-else-if="currentScreen === 'date'" class="flex-1 flex flex-col items-center justify-center">
                <div class="text-center mb-8">
                    <h2 class="text-4xl font-bold mb-2">Date de début</h2>
                    <p class="text-xl text-white/60">Quand souhaitez-vous commencer ?</p>
                </div>

                <div class="grid grid-cols-3 gap-6 mb-8 max-w-3xl">
                    <button
                        @click="selectToday"
                        :class="[
                            'p-8 rounded-3xl text-center transition-all active:scale-95',
                            form.start_date === minDate
                                ? 'text-white shadow-lg'
                                : 'bg-white/10 hover:bg-white/20'
                        ]"
                        :style="form.start_date === minDate ? { backgroundColor: settings?.primary_color } : {}"
                    >
                        <CalendarIcon class="h-12 w-12 mx-auto mb-3" />
                        <p class="text-2xl font-bold">Aujourd'hui</p>
                        <p class="text-white/60">{{ todayFormatted }}</p>
                    </button>

                    <button
                        @click="selectTomorrow"
                        class="p-8 rounded-3xl bg-white/10 hover:bg-white/20 text-center transition-all active:scale-95"
                    >
                        <CalendarIcon class="h-12 w-12 mx-auto mb-3" />
                        <p class="text-2xl font-bold">Demain</p>
                    </button>

                    <button
                        @click="selectNextWeek"
                        class="p-8 rounded-3xl bg-white/10 hover:bg-white/20 text-center transition-all active:scale-95"
                    >
                        <CalendarIcon class="h-12 w-12 mx-auto mb-3" />
                        <p class="text-2xl font-bold">Dans 1 semaine</p>
                    </button>
                </div>

                <div class="w-full max-w-md">
                    <label class="block text-center text-white/60 mb-3">Ou choisissez une autre date :</label>
                    <input
                        type="date"
                        v-model="form.start_date"
                        :min="minDate"
                        class="w-full px-6 py-4 rounded-2xl bg-white/10 border-2 border-white/20 text-white text-xl text-center focus:outline-none focus:border-white/50"
                    />
                </div>

                <button
                    v-if="form.start_date"
                    @click="goToScreen('customer')"
                    class="mt-8 px-12 py-6 rounded-2xl text-white text-2xl font-bold flex items-center gap-3 transition-all hover:scale-105 active:scale-95 shadow-lg"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Continuer
                    <ArrowRightIcon class="h-8 w-8" />
                </button>
            </div>

            <!-- CUSTOMER INFO -->
            <div v-else-if="currentScreen === 'customer'" class="flex-1 flex flex-col items-center">
                <div class="text-center mb-8">
                    <h2 class="text-4xl font-bold mb-2">Vos coordonnées</h2>
                    <p class="text-xl text-white/60">Entrez vos informations</p>
                </div>

                <div class="w-full max-w-2xl space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white/60 mb-2 text-lg">Prénom *</label>
                            <input
                                type="text"
                                v-model="form.customer_first_name"
                                @focus="openKeyboard('customer_first_name', 'text')"
                                class="w-full px-6 py-4 rounded-2xl bg-white/10 border-2 text-white text-xl focus:outline-none transition-all"
                                :class="errors.customer_first_name ? 'border-red-500' : 'border-white/20 focus:border-white/50'"
                                placeholder="Jean"
                            />
                            <p v-if="errors.customer_first_name" class="text-red-400 mt-1">{{ errors.customer_first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-white/60 mb-2 text-lg">Nom *</label>
                            <input
                                type="text"
                                v-model="form.customer_last_name"
                                @focus="openKeyboard('customer_last_name', 'text')"
                                class="w-full px-6 py-4 rounded-2xl bg-white/10 border-2 text-white text-xl focus:outline-none transition-all"
                                :class="errors.customer_last_name ? 'border-red-500' : 'border-white/20 focus:border-white/50'"
                                placeholder="Dupont"
                            />
                            <p v-if="errors.customer_last_name" class="text-red-400 mt-1">{{ errors.customer_last_name }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-white/60 mb-2 text-lg">Email *</label>
                        <input
                            type="email"
                            v-model="form.customer_email"
                            @focus="openKeyboard('customer_email', 'email')"
                            class="w-full px-6 py-4 rounded-2xl bg-white/10 border-2 text-white text-xl focus:outline-none transition-all"
                            :class="errors.customer_email ? 'border-red-500' : 'border-white/20 focus:border-white/50'"
                            placeholder="jean.dupont@email.com"
                        />
                        <p v-if="errors.customer_email" class="text-red-400 mt-1">{{ errors.customer_email }}</p>
                    </div>

                    <div>
                        <label class="block text-white/60 mb-2 text-lg">Téléphone *</label>
                        <input
                            type="tel"
                            v-model="form.customer_phone"
                            @focus="openKeyboard('customer_phone', 'phone')"
                            class="w-full px-6 py-4 rounded-2xl bg-white/10 border-2 text-white text-xl focus:outline-none transition-all"
                            :class="errors.customer_phone ? 'border-red-500' : 'border-white/20 focus:border-white/50'"
                            placeholder="06 12 34 56 78"
                        />
                        <p v-if="errors.customer_phone" class="text-red-400 mt-1">{{ errors.customer_phone }}</p>
                    </div>
                </div>

                <button
                    @click="validateForm() && goToScreen('confirm')"
                    class="mt-8 px-12 py-6 rounded-2xl text-white text-2xl font-bold flex items-center gap-3 transition-all hover:scale-105 active:scale-95 shadow-lg"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Continuer
                    <ArrowRightIcon class="h-8 w-8" />
                </button>
            </div>

            <!-- CONFIRMATION -->
            <div v-else-if="currentScreen === 'confirm'" class="flex-1 flex flex-col items-center justify-center">
                <div class="text-center mb-8">
                    <h2 class="text-4xl font-bold mb-2">Confirmez votre réservation</h2>
                    <p class="text-xl text-white/60">Vérifiez les détails avant de confirmer</p>
                </div>

                <div class="w-full max-w-2xl bg-white/10 rounded-3xl p-8 space-y-6">
                    <!-- Box Info -->
                    <div class="flex items-center gap-4 pb-6 border-b border-white/10">
                        <div
                            class="w-16 h-16 rounded-2xl flex items-center justify-center"
                            :style="{ backgroundColor: settings?.primary_color }"
                        >
                            <CubeIcon class="h-8 w-8 text-white" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-2xl font-bold">{{ selectedBoxData?.name }}</h3>
                            <p class="text-white/60">{{ selectedSiteData?.name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold" :style="{ color: settings?.primary_color }">
                                {{ formatCurrency(selectedBoxData?.current_price) }}
                            </p>
                            <p class="text-white/60">/mois</p>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="grid grid-cols-2 gap-6 text-lg">
                        <div>
                            <p class="text-white/60">Date de début</p>
                            <p class="font-semibold">{{ formatDate(form.start_date) }}</p>
                        </div>
                        <div>
                            <p class="text-white/60">Client</p>
                            <p class="font-semibold">{{ form.customer_first_name }} {{ form.customer_last_name }}</p>
                        </div>
                        <div>
                            <p class="text-white/60">Email</p>
                            <p class="font-semibold">{{ form.customer_email }}</p>
                        </div>
                        <div>
                            <p class="text-white/60">Téléphone</p>
                            <p class="font-semibold">{{ form.customer_phone }}</p>
                        </div>
                    </div>

                    <!-- Terms -->
                    <label class="flex items-start gap-4 pt-6 border-t border-white/10 cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.terms_accepted"
                            class="w-8 h-8 rounded mt-1"
                        />
                        <span class="text-white/70">
                            J'accepte les conditions générales de location et la politique de confidentialité
                        </span>
                    </label>
                    <p v-if="errors.terms" class="text-red-400">{{ errors.terms }}</p>
                    <p v-if="errors.general" class="text-red-400">{{ errors.general }}</p>
                </div>

                <button
                    @click="submitBooking"
                    :disabled="processing"
                    class="mt-8 px-16 py-8 rounded-3xl text-white text-3xl font-bold flex items-center gap-4 transition-all hover:scale-105 active:scale-95 shadow-2xl disabled:opacity-50 disabled:cursor-not-allowed"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    <svg v-if="processing" class="animate-spin h-8 w-8" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="processing">Réservation en cours...</span>
                    <span v-else>Confirmer la réservation</span>
                </button>
            </div>

            <!-- SUCCESS -->
            <div v-else-if="currentScreen === 'success'" class="flex-1 flex flex-col items-center justify-center text-center">
                <div class="mb-8 animate-bounce">
                    <div class="w-32 h-32 bg-green-500 rounded-full flex items-center justify-center mx-auto shadow-2xl">
                        <CheckCircleIcon class="h-20 w-20 text-white" />
                    </div>
                </div>

                <h2 class="text-5xl font-bold mb-4">Réservation confirmée !</h2>

                <div class="bg-white/10 rounded-3xl p-8 max-w-xl mb-8">
                    <p class="text-2xl mb-4">
                        Numéro de réservation :
                        <span class="font-bold block text-4xl mt-2" :style="{ color: settings?.primary_color }">
                            {{ bookingResult?.booking_number }}
                        </span>
                    </p>
                    <p class="text-xl text-white/70">
                        Un email de confirmation a été envoyé à<br>
                        <strong>{{ form.customer_email }}</strong>
                    </p>
                </div>

                <div class="bg-white/10 rounded-2xl p-6 mb-8">
                    <p class="text-lg text-white/70 mb-2">Prochaine étape :</p>
                    <p class="text-xl">
                        Rendez-vous à l'accueil avec une pièce d'identité pour finaliser votre contrat
                    </p>
                </div>

                <button
                    @click="goHome"
                    class="px-12 py-6 rounded-2xl text-white text-2xl font-bold flex items-center gap-3 transition-all hover:scale-105 active:scale-95 shadow-lg"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    <HomeIcon class="h-8 w-8" />
                    Retour à l'accueil
                </button>

                <p class="mt-8 text-white/50">
                    Retour automatique à l'accueil dans 30 secondes
                </p>
            </div>
        </main>

        <!-- Virtual Keyboard Overlay -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-full"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-full"
        >
            <div v-if="showKeyboard" class="fixed bottom-0 left-0 right-0 bg-gray-800 p-4 shadow-2xl z-50">
                <div class="max-w-4xl mx-auto">
                    <!-- Close button -->
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-white/60">Clavier virtuel</span>
                        <button @click="closeKeyboard" class="p-2 rounded-lg bg-white/10 hover:bg-white/20">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <!-- Keyboard layout -->
                    <div v-if="keyboardType === 'phone'" class="grid grid-cols-3 gap-2">
                        <button v-for="key in ['1','2','3','4','5','6','7','8','9',' ','0','backspace']" :key="key"
                            @click="onKeyPress(key)"
                            class="py-6 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-2xl font-semibold transition-all"
                        >
                            {{ key === 'backspace' ? '⌫' : key === ' ' ? '' : key }}
                        </button>
                    </div>
                    <div v-else class="space-y-2">
                        <div class="flex gap-1 justify-center">
                            <button v-for="key in ['1','2','3','4','5','6','7','8','9','0']" :key="key"
                                @click="onKeyPress(key)"
                                class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-xl font-semibold transition-all"
                            >{{ key }}</button>
                        </div>
                        <div class="flex gap-1 justify-center">
                            <button v-for="key in ['a','z','e','r','t','y','u','i','o','p']" :key="key"
                                @click="onKeyPress(key)"
                                class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-xl font-semibold transition-all uppercase"
                            >{{ key }}</button>
                        </div>
                        <div class="flex gap-1 justify-center">
                            <button v-for="key in ['q','s','d','f','g','h','j','k','l','m']" :key="key"
                                @click="onKeyPress(key)"
                                class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-xl font-semibold transition-all uppercase"
                            >{{ key }}</button>
                        </div>
                        <div class="flex gap-1 justify-center">
                            <button v-for="key in ['w','x','c','v','b','n']" :key="key"
                                @click="onKeyPress(key)"
                                class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 active:bg-white/30 text-xl font-semibold transition-all uppercase"
                            >{{ key }}</button>
                            <button @click="onKeyPress('backspace')" class="w-20 h-14 rounded-xl bg-red-500/30 hover:bg-red-500/50 text-xl transition-all">⌫</button>
                        </div>
                        <div class="flex gap-1 justify-center">
                            <button v-if="keyboardType === 'email'" @click="onKeyPress('@')" class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 text-xl">@</button>
                            <button @click="onKeyPress('space')" class="flex-1 h-14 rounded-xl bg-white/10 hover:bg-white/20 text-xl">Espace</button>
                            <button v-if="keyboardType === 'email'" @click="onKeyPress('.')" class="w-14 h-14 rounded-xl bg-white/10 hover:bg-white/20 text-xl">.</button>
                            <button @click="onKeyPress('done')" class="w-24 h-14 rounded-xl bg-green-500/50 hover:bg-green-500/70 text-xl font-semibold transition-all">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Hide scrollbar but allow scrolling */
.overflow-y-auto {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.overflow-y-auto::-webkit-scrollbar {
    display: none;
}

/* Animation for home screen */
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
.animate-pulse {
    animation: pulse 3s ease-in-out infinite;
}
</style>
