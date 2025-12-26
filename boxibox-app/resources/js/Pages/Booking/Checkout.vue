<template>
    <PublicLayout title="Finaliser la Réservation">
        <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('booking.show', box.id)"
                    class="text-blue-600 hover:text-blue-800 mb-4 inline-block"
                >
                    ← Retour aux détails
                </Link>
                <h1 class="text-3xl font-bold text-gray-900">Finaliser Votre Réservation</h1>
                <p class="text-gray-600 mt-2">Encore quelques informations pour réserver votre box</p>
            </div>

            <!-- Error display -->
            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="text-red-800 font-medium mb-2">Veuillez corriger les erreurs suivantes:</h3>
                <ul class="list-disc list-inside text-red-600 text-sm">
                    <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                </ul>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form -->
                <div class="lg:col-span-2">
                    <form @submit.prevent="submit" class="bg-white rounded-lg shadow-md p-8">
                        <!-- Customer Type -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Type de Client</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="cursor-pointer">
                                    <input
                                        v-model="form.customer_type"
                                        type="radio"
                                        value="individual"
                                        class="sr-only peer"
                                    />
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                        <p class="font-semibold text-gray-900">Particulier</p>
                                        <p class="text-sm text-gray-600">Stockage personnel</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input
                                        v-model="form.customer_type"
                                        type="radio"
                                        value="company"
                                        class="sr-only peer"
                                    />
                                    <div class="border-2 border-gray-200 rounded-lg p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                        <p class="font-semibold text-gray-900">Entreprise</p>
                                        <p class="text-sm text-gray-600">Stockage professionnel</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Individual Fields -->
                        <div v-if="form.customer_type === 'individual'" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations Personnelles</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                                    <input
                                        v-model="form.first_name"
                                        type="text"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.first_name }"
                                    />
                                    <p v-if="form.errors.first_name" class="text-sm text-red-600 mt-1">{{ form.errors.first_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                    <input
                                        v-model="form.last_name"
                                        type="text"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.last_name }"
                                    />
                                    <p v-if="form.errors.last_name" class="text-sm text-red-600 mt-1">{{ form.errors.last_name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Company Fields -->
                        <div v-if="form.customer_type === 'company'" class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations Entreprise</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Raison Sociale *</label>
                                    <input
                                        v-model="form.company_name"
                                        type="text"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.company_name }"
                                    />
                                    <p v-if="form.errors.company_name" class="text-sm text-red-600 mt-1">{{ form.errors.company_name }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">N° SIRET</label>
                                        <input
                                            v-model="form.company_registration"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">N° TVA</label>
                                        <input
                                            v-model="form.vat_number"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Coordonnées</h2>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.email }"
                                        />
                                        <p v-if="form.errors.email" class="text-sm text-red-600 mt-1">{{ form.errors.email }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone *</label>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.phone }"
                                        />
                                        <p v-if="form.errors.phone" class="text-sm text-red-600 mt-1">{{ form.errors.phone }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Adresse</h2>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Adresse *</label>
                                    <input
                                        v-model="form.address"
                                        type="text"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        :class="{ 'border-red-500': form.errors.address }"
                                    />
                                    <p v-if="form.errors.address" class="text-sm text-red-600 mt-1">{{ form.errors.address }}</p>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ville *</label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.city }"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Code Postal *</label>
                                        <input
                                            v-model="form.postal_code"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.postal_code }"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pays *</label>
                                        <input
                                            v-model="form.country"
                                            type="text"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.country }"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Details -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Détails de la Location</h2>
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Date d'entrée *</label>
                                        <input
                                            v-model="form.start_date"
                                            type="date"
                                            :min="today"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="{ 'border-red-500': form.errors.start_date }"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Fréquence de facturation *</label>
                                        <select
                                            v-model="form.billing_frequency"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        >
                                            <option value="monthly">Mensuelle</option>
                                            <option value="quarterly">Trimestrielle</option>
                                            <option value="yearly">Annuelle</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement *</label>
                                    <select
                                        v-model="form.payment_method"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="bank_transfer">Virement bancaire</option>
                                        <option value="credit_card">Carte bancaire</option>
                                        <option value="direct_debit">Prélèvement automatique</option>
                                    </select>
                                </div>
                                <div class="flex items-center">
                                    <input
                                        v-model="form.auto_renew"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label class="ml-2 text-sm text-gray-700">Renouveler automatiquement mon contrat</label>
                                </div>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mb-8">
                            <div class="flex items-start">
                                <input
                                    v-model="form.agree_terms"
                                    type="checkbox"
                                    class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    :class="{ 'border-red-500': form.errors.agree_terms }"
                                />
                                <label class="ml-2 text-sm text-gray-700">
                                    J'accepte les <a href="#" class="text-blue-600 hover:underline">Conditions Générales</a> et la <a href="#" class="text-blue-600 hover:underline">Politique de Confidentialité</a> *
                                </label>
                            </div>
                            <p v-if="form.errors.agree_terms" class="text-sm text-red-600 mt-1">{{ form.errors.agree_terms }}</p>
                        </div>

                        <!-- Submit -->
                        <div>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                            >
                                <span v-if="form.processing" class="flex items-center justify-center">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Traitement en cours...
                                </span>
                                <span v-else>Confirmer la Réservation</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                        <h2 class="text-xl font-semibold text-gray-900 mb-4">Récapitulatif</h2>

                        <div class="mb-4">
                            <h3 class="font-medium text-gray-900">Box {{ box.number || box.name }}</h3>
                            <p class="text-sm text-gray-600">{{ box.site?.name }}</p>
                            <p class="text-sm text-gray-500">{{ box.site?.city }}</p>
                        </div>

                        <div class="border-t border-b py-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Prix mensuel:</span>
                                <span class="font-medium">{{ formatPrice(pricing.monthly_price) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Dépôt de garantie:</span>
                                <span class="font-medium">{{ formatPrice(pricing.deposit_amount) }}€</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Premier mois:</span>
                                <span class="font-medium">{{ formatPrice(pricing.first_month_rent) }}€</span>
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">Total à payer:</span>
                                <span class="text-2xl font-bold text-blue-600">{{ formatPrice(pricing.total_due) }}€</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-2 text-sm text-gray-600">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Réservation en ligne sécurisée</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Confirmation instantanée</span>
                            </div>
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>Sans frais cachés</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const props = defineProps({
    box: Object,
    pricing: Object,
});

const formatPrice = (value) => {
    if (value === null || value === undefined) return '0.00';
    return parseFloat(value).toFixed(2);
};

const today = computed(() => {
    const date = new Date();
    return date.toISOString().split('T')[0];
});

const form = useForm({
    customer_type: 'individual',
    first_name: '',
    last_name: '',
    company_name: '',
    company_registration: '',
    vat_number: '',
    email: '',
    phone: '',
    mobile: '',
    address: '',
    city: '',
    postal_code: '',
    country: 'France',
    start_date: today.value,
    billing_frequency: 'monthly',
    payment_method: 'bank_transfer',
    auto_renew: false,
    agree_terms: false,
});

const submit = () => {
    // Transform agree_terms to boolean before sending
    form.agree_terms = form.agree_terms ? true : false;

    form.post(route('booking.store', props.box.id), {
        preserveScroll: true,
        onError: (errors) => {
            console.log('Form errors:', errors);
            // Scroll to top to show errors
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });
};
</script>
