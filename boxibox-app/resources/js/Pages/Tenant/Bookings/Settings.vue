<script setup>
import { ref } from 'vue'
import { router, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    Cog6ToothIcon,
    CheckIcon,
    ClipboardDocumentIcon,
    GlobeAltIcon,
    CurrencyEuroIcon,
    DocumentTextIcon,
    BellIcon,
    PaintBrushIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
    booking_url: String,
})

const form = useForm({
    is_enabled: props.settings?.is_enabled || false,
    booking_url_slug: props.settings?.booking_url_slug || '',
    company_name: props.settings?.company_name || '',
    primary_color: props.settings?.primary_color || '#3B82F6',
    secondary_color: props.settings?.secondary_color || '#1E40AF',
    welcome_message: props.settings?.welcome_message || '',
    terms_conditions: props.settings?.terms_conditions || '',
    require_deposit: props.settings?.require_deposit || false,
    deposit_amount: props.settings?.deposit_amount || 0,
    deposit_percentage: props.settings?.deposit_percentage || 0,
    min_rental_days: props.settings?.min_rental_days || 30,
    max_advance_booking_days: props.settings?.max_advance_booking_days || 90,
    auto_confirm: props.settings?.auto_confirm || false,
    require_id_verification: props.settings?.require_id_verification || true,
    allow_promo_codes: props.settings?.allow_promo_codes || true,
    contact_email: props.settings?.contact_email || '',
    contact_phone: props.settings?.contact_phone || '',
    settings_id: props.settings?.id,
})

const copied = ref(false)

const submit = () => {
    form.post(route('tenant.bookings.settings.update'), {
        preserveScroll: true,
    })
}

const copyUrl = () => {
    navigator.clipboard.writeText(props.booking_url)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
}
</script>

<template>
    <TenantLayout title="Paramètres réservation">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-teal-600 via-cyan-600 to-teal-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-4xl mx-auto relative z-10">
                <Link
                    :href="route('tenant.bookings.index')"
                    class="inline-flex items-center text-teal-100 hover:text-white mb-4 transition-colors"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                    Retour aux réservations
                </Link>

                <div class="flex items-center space-x-4">
                    <Cog6ToothIcon class="h-10 w-10 text-white" />
                    <div>
                        <h1 class="text-3xl font-bold text-white">Paramètres de réservation</h1>
                        <p class="mt-1 text-teal-100">Configurez votre page de réservation en ligne</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-8 relative z-20">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Enable/Disable -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Activer la réservation en ligne</h3>
                            <p class="text-sm text-gray-500">Permettre aux clients de réserver en ligne</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_enabled" class="sr-only peer">
                            <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-teal-600"></div>
                        </label>
                    </div>
                </div>

                <!-- URL -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <GlobeAltIcon class="h-5 w-5 mr-2 text-teal-600" />
                        URL de réservation
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Slug personnalisé</label>
                            <input
                                type="text"
                                v-model="form.booking_url_slug"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="mon-entreprise"
                            />
                            <p class="text-sm text-gray-500 mt-1">URL: {{ booking_url }}</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <input
                                type="text"
                                :value="booking_url"
                                readonly
                                class="flex-1 bg-gray-50 border border-gray-300 rounded-xl px-4 py-2 text-gray-600"
                            />
                            <button
                                type="button"
                                @click="copyUrl"
                                class="px-4 py-2 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors flex items-center"
                            >
                                <ClipboardDocumentIcon v-if="!copied" class="h-5 w-5" />
                                <CheckIcon v-else class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Branding -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <PaintBrushIcon class="h-5 w-5 mr-2 text-teal-600" />
                        Personnalisation
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'entreprise</label>
                            <input
                                type="text"
                                v-model="form.company_name"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Couleur principale</label>
                            <div class="flex items-center space-x-2">
                                <input
                                    type="color"
                                    v-model="form.primary_color"
                                    class="w-12 h-10 rounded border border-gray-300"
                                />
                                <input
                                    type="text"
                                    v-model="form.primary_color"
                                    class="flex-1 border border-gray-300 rounded-xl px-4 py-2"
                                />
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Message d'accueil</label>
                            <textarea
                                v-model="form.welcome_message"
                                rows="3"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                placeholder="Bienvenue sur notre page de réservation..."
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Deposit Settings -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <CurrencyEuroIcon class="h-5 w-5 mr-2 text-teal-600" />
                        Acompte
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.require_deposit"
                                id="require_deposit"
                                class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label for="require_deposit" class="ml-2 text-sm text-gray-700">Demander un acompte</label>
                        </div>
                        <div v-if="form.require_deposit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Montant fixe (€)</label>
                                <input
                                    type="number"
                                    v-model="form.deposit_amount"
                                    step="0.01"
                                    min="0"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ou pourcentage (%)</label>
                                <input
                                    type="number"
                                    v-model="form.deposit_percentage"
                                    step="0.1"
                                    min="0"
                                    max="100"
                                    class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Rules -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <DocumentTextIcon class="h-5 w-5 mr-2 text-teal-600" />
                        Règles de réservation
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Durée minimale (jours)</label>
                            <input
                                type="number"
                                v-model="form.min_rental_days"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Réservation max à l'avance (jours)</label>
                            <input
                                type="number"
                                v-model="form.max_advance_booking_days"
                                min="1"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.auto_confirm"
                                id="auto_confirm"
                                class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label for="auto_confirm" class="ml-2 text-sm text-gray-700">Confirmation automatique</label>
                        </div>
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.require_id_verification"
                                id="require_id"
                                class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label for="require_id" class="ml-2 text-sm text-gray-700">Vérification d'identité requise</label>
                        </div>
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.allow_promo_codes"
                                id="allow_promo"
                                class="h-4 w-4 text-teal-600 focus:ring-teal-500 border-gray-300 rounded"
                            />
                            <label for="allow_promo" class="ml-2 text-sm text-gray-700">Autoriser les codes promo</label>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <BellIcon class="h-5 w-5 mr-2 text-teal-600" />
                        Contact
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email de contact</label>
                            <input
                                type="email"
                                v-model="form.contact_email"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone de contact</label>
                            <input
                                type="tel"
                                v-model="form.contact_phone"
                                class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                </div>

                <!-- Terms -->
                <div class="bg-white rounded-2xl shadow-xl p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <DocumentTextIcon class="h-5 w-5 mr-2 text-teal-600" />
                        Conditions générales
                    </h3>
                    <textarea
                        v-model="form.terms_conditions"
                        rows="6"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        placeholder="Vos conditions générales de location..."
                    ></textarea>
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-3 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition-colors disabled:opacity-50 flex items-center"
                    >
                        <CheckIcon class="h-5 w-5 mr-2" />
                        Enregistrer les paramètres
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>
