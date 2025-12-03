<script setup>
import { ref, computed, watch } from 'vue'
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
} from '@heroicons/vue/24/outline'
import { CheckCircleIcon } from '@heroicons/vue/24/solid'

const props = defineProps({
    settings: Object,
    tenant: Object,
    sites: Array,
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
    notes: '',
    terms_accepted: false,
})

const errors = ref({})

const availableBoxes = computed(() => {
    if (!selectedSite.value) return []
    const site = props.sites.find(s => s.id === selectedSite.value)
    return site?.boxes || []
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

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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

const nextStep = () => {
    if (step.value === 1 && selectedBox.value) {
        step.value = 2
    } else if (step.value === 2 && validateForm()) {
        step.value = 3
    }
}

const prevStep = () => {
    if (step.value > 1) step.value--
}

const validateForm = () => {
    errors.value = {}

    if (!form.value.customer_first_name) errors.value.customer_first_name = 'Prénom requis'
    if (!form.value.customer_last_name) errors.value.customer_last_name = 'Nom requis'
    if (!form.value.customer_email) errors.value.customer_email = 'Email requis'
    if (!form.value.start_date) errors.value.start_date = 'Date de début requise'

    return Object.keys(errors.value).length === 0
}

const submitBooking = async () => {
    if (!form.value.terms_accepted) {
        errors.value.terms_accepted = 'Vous devez accepter les conditions'
        return
    }

    processing.value = true
    errors.value = {}

    try {
        const response = await fetch(route('public.booking.store'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                tenant_id: props.tenant.id,
                site_id: selectedSite.value,
                box_id: selectedBox.value,
                ...form.value,
                promo_code: promoApplied.value?.code,
                source: 'website',
            }),
        })

        const data = await response.json()

        if (data.success) {
            success.value = true
            bookingResult.value = data.booking
        } else {
            errors.value = data.errors || {}
        }
    } catch (e) {
        errors.value.general = 'Une erreur est survenue'
    } finally {
        processing.value = false
    }
}

// Set minimum date to today
const minDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})
</script>

<template>
    <Head :title="`Réservation - ${settings?.company_name || tenant.name}`" />

    <div class="min-h-screen" :style="{ backgroundColor: settings?.primary_color + '10' }">
        <!-- Header -->
        <header class="py-6 px-4" :style="{ backgroundColor: settings?.primary_color }">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl font-bold text-white">{{ settings?.company_name || tenant.name }}</h1>
                <p v-if="settings?.welcome_message" class="text-white/80 mt-2">{{ settings.welcome_message }}</p>
            </div>
        </header>

        <!-- Success Message -->
        <div v-if="success" class="max-w-2xl mx-auto px-4 py-12">
            <div class="bg-white rounded-2xl shadow-xl p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <CheckCircleIcon class="h-12 w-12 text-green-600" />
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Réservation confirmée !</h2>
                <p class="text-gray-600 mb-6">
                    Votre réservation <strong>{{ bookingResult?.booking_number }}</strong> a été enregistrée.
                    Vous recevrez un email de confirmation à <strong>{{ form.customer_email }}</strong>.
                </p>
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Prix mensuel:</span>
                            <span class="ml-2 font-medium">{{ formatCurrency(bookingResult?.monthly_price) }}</span>
                        </div>
                        <div v-if="bookingResult?.deposit_amount > 0">
                            <span class="text-gray-500">Acompte:</span>
                            <span class="ml-2 font-medium">{{ formatCurrency(bookingResult?.deposit_amount) }}</span>
                        </div>
                    </div>
                </div>
                <a
                    :href="`/book/status/${bookingResult?.uuid}`"
                    class="inline-flex items-center px-6 py-3 rounded-xl text-white font-medium transition-colors"
                    :style="{ backgroundColor: settings?.primary_color }"
                >
                    Suivre ma réservation
                </a>
            </div>
        </div>

        <!-- Booking Form -->
        <div v-else class="max-w-4xl mx-auto px-4 py-8">
            <!-- Progress Steps -->
            <div class="mb-8">
                <div class="flex items-center justify-center space-x-4">
                    <div
                        v-for="s in 3"
                        :key="s"
                        class="flex items-center"
                    >
                        <div
                            :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center font-bold transition-colors',
                                step >= s ? 'text-white' : 'bg-gray-200 text-gray-500'
                            ]"
                            :style="step >= s ? { backgroundColor: settings?.primary_color } : {}"
                        >
                            <CheckIcon v-if="step > s" class="h-5 w-5" />
                            <span v-else>{{ s }}</span>
                        </div>
                        <div
                            v-if="s < 3"
                            :class="['w-16 h-1 mx-2', step > s ? 'bg-green-500' : 'bg-gray-200']"
                        ></div>
                    </div>
                </div>
                <div class="flex justify-center mt-2 text-sm text-gray-600">
                    <span class="px-4">Choisir un box</span>
                    <span class="px-4">Vos informations</span>
                    <span class="px-4">Confirmation</span>
                </div>
            </div>

            <!-- Step 1: Choose Box -->
            <div v-if="step === 1" class="space-y-6">
                <!-- Site Selection -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <MapPinIcon class="inline h-5 w-5 mr-2" />
                        Choisissez un site
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button
                            v-for="site in sites"
                            :key="site.id"
                            @click="selectedSite = site.id; selectedBox = null"
                            :class="[
                                'p-4 rounded-xl border-2 text-left transition-all',
                                selectedSite === site.id
                                    ? 'border-current shadow-lg'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                            :style="selectedSite === site.id ? { borderColor: settings?.primary_color, color: settings?.primary_color } : {}"
                        >
                            <h3 class="font-semibold text-gray-900">{{ site.name }}</h3>
                            <p class="text-sm text-gray-600">{{ site.address }}, {{ site.city }}</p>
                            <p class="text-sm mt-2">
                                <span class="font-medium" :style="{ color: settings?.primary_color }">
                                    {{ site.available_boxes_count }} boxes disponibles
                                </span>
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Box Selection -->
                <div v-if="selectedSite && availableBoxes.length > 0" class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <CubeIcon class="inline h-5 w-5 mr-2" />
                        Choisissez un box
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <button
                            v-for="box in availableBoxes"
                            :key="box.id"
                            @click="selectedBox = box.id"
                            :class="[
                                'p-4 rounded-xl border-2 text-left transition-all',
                                selectedBox === box.id
                                    ? 'border-current shadow-lg'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                            :style="selectedBox === box.id ? { borderColor: settings?.primary_color } : {}"
                        >
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-semibold text-gray-900">{{ box.name }}</h3>
                                <span
                                    class="text-lg font-bold"
                                    :style="{ color: settings?.primary_color }"
                                >
                                    {{ formatCurrency(box.current_price) }}/mois
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">{{ box.formatted_volume }}</p>
                            <p class="text-sm text-gray-500">{{ box.dimensions }}</p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-if="box.climate_controlled" class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Climatisé</span>
                                <span v-if="box.has_electricity" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">Électricité</span>
                                <span v-if="box.has_24_7_access" class="text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded">Accès 24/7</span>
                            </div>
                        </button>
                    </div>
                </div>

                <div v-if="selectedBox" class="flex justify-end">
                    <button
                        @click="nextStep"
                        class="px-6 py-3 rounded-xl text-white font-medium flex items-center transition-colors"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        Continuer
                        <ArrowRightIcon class="h-5 w-5 ml-2" />
                    </button>
                </div>
            </div>

            <!-- Step 2: Customer Info -->
            <div v-if="step === 2" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">
                        <UserIcon class="inline h-5 w-5 mr-2" />
                        Vos informations
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                            <input
                                type="text"
                                v-model="form.customer_first_name"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                                :class="errors.customer_first_name ? 'border-red-500' : ''"
                                :style="{ '--tw-ring-color': settings?.primary_color }"
                            />
                            <p v-if="errors.customer_first_name" class="text-red-500 text-sm mt-1">{{ errors.customer_first_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input
                                type="text"
                                v-model="form.customer_last_name"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                                :class="errors.customer_last_name ? 'border-red-500' : ''"
                            />
                            <p v-if="errors.customer_last_name" class="text-red-500 text-sm mt-1">{{ errors.customer_last_name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input
                                type="email"
                                v-model="form.customer_email"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                                :class="errors.customer_email ? 'border-red-500' : ''"
                            />
                            <p v-if="errors.customer_email" class="text-red-500 text-sm mt-1">{{ errors.customer_email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                            <input
                                type="tel"
                                v-model="form.customer_phone"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                            <input
                                type="text"
                                v-model="form.customer_address"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Code postal</label>
                            <input
                                type="text"
                                v-model="form.customer_postal_code"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ville</label>
                            <input
                                type="text"
                                v-model="form.customer_city"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Entreprise (optionnel)</label>
                            <input
                                type="text"
                                v-model="form.customer_company"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Date de début *</h3>
                        <input
                            type="date"
                            v-model="form.start_date"
                            :min="minDate"
                            class="w-full md:w-auto border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            :class="errors.start_date ? 'border-red-500' : ''"
                        />
                        <p v-if="errors.start_date" class="text-red-500 text-sm mt-1">{{ errors.start_date }}</p>
                    </div>

                    <div class="mt-6 pt-6 border-t">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Notes (optionnel)</h3>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:border-transparent"
                            placeholder="Informations complémentaires..."
                        ></textarea>
                    </div>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="prevStep"
                        class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                    >
                        Retour
                    </button>
                    <button
                        @click="nextStep"
                        class="px-6 py-3 rounded-xl text-white font-medium flex items-center transition-colors"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        Continuer
                        <ArrowRightIcon class="h-5 w-5 ml-2" />
                    </button>
                </div>
            </div>

            <!-- Step 3: Confirmation -->
            <div v-if="step === 3" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Récapitulatif</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Box sélectionné</h3>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="font-semibold text-gray-900">
                                    {{ availableBoxes.find(b => b.id === selectedBox)?.name }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ sites.find(s => s.id === selectedSite)?.name }}
                                </p>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Client</h3>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <p class="font-semibold text-gray-900">
                                    {{ form.customer_first_name }} {{ form.customer_last_name }}
                                </p>
                                <p class="text-sm text-gray-600">{{ form.customer_email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Promo Code -->
                    <div v-if="settings?.allow_promo_codes" class="mt-6 pt-6 border-t">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Code promo</h3>
                        <div v-if="!promoApplied" class="flex items-center space-x-2">
                            <input
                                type="text"
                                v-model="promoCode"
                                class="flex-1 border border-gray-300 rounded-xl px-4 py-2 uppercase"
                                placeholder="Entrez votre code"
                            />
                            <button
                                @click="validatePromo"
                                class="px-4 py-2 rounded-xl text-white font-medium"
                                :style="{ backgroundColor: settings?.primary_color }"
                            >
                                Appliquer
                            </button>
                        </div>
                        <div v-else class="flex items-center justify-between bg-green-50 rounded-xl p-4">
                            <div class="flex items-center">
                                <TicketIcon class="h-5 w-5 text-green-600 mr-2" />
                                <span class="font-medium text-green-800">{{ promoApplied.code }}</span>
                                <span class="text-green-600 ml-2">-{{ promoApplied.discount_label }}</span>
                            </div>
                            <button @click="removePromo" class="text-red-600 hover:text-red-700">Retirer</button>
                        </div>
                        <p v-if="promoError" class="text-red-500 text-sm mt-1">{{ promoError }}</p>
                    </div>

                    <!-- Pricing -->
                    <div class="mt-6 pt-6 border-t">
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>Prix mensuel</span>
                                <span>{{ formatCurrency(availableBoxes.find(b => b.id === selectedBox)?.current_price) }}</span>
                            </div>
                            <div v-if="promoApplied" class="flex justify-between text-green-600">
                                <span>Réduction ({{ promoApplied.discount_label }})</span>
                                <span>-{{ formatCurrency(promoApplied.calculated_discount) }}</span>
                            </div>
                            <div v-if="depositAmount > 0" class="flex justify-between text-gray-600">
                                <span>Acompte</span>
                                <span>{{ formatCurrency(depositAmount) }}</span>
                            </div>
                            <div class="flex justify-between text-xl font-bold text-gray-900 pt-2 border-t">
                                <span>Total mensuel</span>
                                <span :style="{ color: settings?.primary_color }">{{ formatCurrency(monthlyPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="mt-6 pt-6 border-t">
                        <label class="flex items-start">
                            <input
                                type="checkbox"
                                v-model="form.terms_accepted"
                                class="h-5 w-5 rounded border-gray-300 mt-0.5"
                                :style="{ color: settings?.primary_color }"
                            />
                            <span class="ml-3 text-sm text-gray-600">
                                J'accepte les <a href="#" class="underline">conditions générales</a> de location *
                            </span>
                        </label>
                        <p v-if="errors.terms_accepted" class="text-red-500 text-sm mt-1">{{ errors.terms_accepted }}</p>
                    </div>

                    <p v-if="errors.general" class="mt-4 text-red-500 text-center">{{ errors.general }}</p>
                </div>

                <div class="flex justify-between">
                    <button
                        @click="prevStep"
                        class="px-6 py-3 rounded-xl border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                    >
                        Retour
                    </button>
                    <button
                        @click="submitBooking"
                        :disabled="processing"
                        class="px-8 py-3 rounded-xl text-white font-medium flex items-center transition-colors disabled:opacity-50"
                        :style="{ backgroundColor: settings?.primary_color }"
                    >
                        <span v-if="processing">Envoi en cours...</span>
                        <span v-else>Confirmer la réservation</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="py-6 px-4 text-center text-gray-500 text-sm">
            <p v-if="settings?.contact_email || settings?.contact_phone">
                Contact:
                <a v-if="settings?.contact_email" :href="`mailto:${settings.contact_email}`" class="underline">{{ settings.contact_email }}</a>
                <span v-if="settings?.contact_email && settings?.contact_phone"> | </span>
                <a v-if="settings?.contact_phone" :href="`tel:${settings.contact_phone}`" class="underline">{{ settings.contact_phone }}</a>
            </p>
            <p class="mt-2">Powered by Boxibox</p>
        </footer>
    </div>
</template>
