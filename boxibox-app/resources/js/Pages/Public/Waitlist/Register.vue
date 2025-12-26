<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import {
    ClockIcon,
    CheckCircleIcon,
    MapPinIcon,
    CubeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    site: Object,
    boxes: Array,
    sizeOptions: Array,
})

const form = useForm({
    site_id: props.site?.id || '',
    box_id: '',
    customer_first_name: '',
    customer_last_name: '',
    customer_email: '',
    customer_phone: '',
    min_size: '',
    max_size: '',
    max_price: '',
    notes: '',
    accept_terms: false,
})

const submitted = ref(false)

const submit = () => {
    form.post(route('public.waitlist.store'), {
        onSuccess: () => {
            submitted.value = true
        }
    })
}
</script>

<template>
    <Head title="Liste d'attente" />

    <PublicLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="submitted" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center mx-auto mb-6">
                        <CheckCircleIcon class="w-10 h-10 text-green-600 dark:text-green-400" />
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                        Inscription confirmée !
                    </h2>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Vous êtes maintenant inscrit sur notre liste d'attente.
                        Nous vous contacterons dès qu'un box correspondant à vos critères sera disponible.
                    </p>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Un email de confirmation a été envoyé à <strong class="text-gray-900 dark:text-white">{{ form.customer_email }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Registration Form -->
                <template v-else>
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-full flex items-center justify-center mx-auto mb-4">
                            <ClockIcon class="w-8 h-8 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Liste d'attente
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400">
                            Inscrivez-vous pour être notifié dès qu'un box se libère
                        </p>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Site Info -->
                            <div v-if="site" class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 flex items-center space-x-3">
                                <MapPinIcon class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ site.name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ site.address }}</p>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div class="space-y-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    Vos informations
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Prénom *
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.customer_first_name"
                                            required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <p v-if="form.errors.customer_first_name" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.customer_first_name }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Nom *
                                        </label>
                                        <input
                                            type="text"
                                            v-model="form.customer_last_name"
                                            required
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        />
                                        <p v-if="form.errors.customer_last_name" class="text-red-500 text-sm mt-1">
                                            {{ form.errors.customer_last_name }}
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Email *
                                    </label>
                                    <input
                                        type="email"
                                        v-model="form.customer_email"
                                        required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <p v-if="form.errors.customer_email" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.customer_email }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Téléphone
                                    </label>
                                    <input
                                        type="tel"
                                        v-model="form.customer_phone"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                            </div>

                            <!-- Preferences -->
                            <div class="space-y-4">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    Vos critères
                                </h3>

                                <div v-if="boxes && boxes.length > 0">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Box spécifique (optionnel)
                                    </label>
                                    <select
                                        v-model="form.box_id"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">Tous les box disponibles</option>
                                        <option v-for="box in boxes" :key="box.id" :value="box.id">
                                            Box {{ box.name }} - {{ box.size }} m²
                                        </option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Taille minimum (m²)
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.min_size"
                                            min="1"
                                            step="0.5"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Ex: 3"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Taille maximum (m²)
                                        </label>
                                        <input
                                            type="number"
                                            v-model="form.max_size"
                                            min="1"
                                            step="0.5"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                            placeholder="Ex: 10"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Budget maximum (€/mois)
                                    </label>
                                    <input
                                        type="number"
                                        v-model="form.max_price"
                                        min="1"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Ex: 100"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Commentaires ou besoins spécifiques
                                    </label>
                                    <textarea
                                        v-model="form.notes"
                                        rows="3"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        placeholder="Par exemple: accès 24h/24, climatisé, au rez-de-chaussée..."
                                    ></textarea>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="space-y-4">
                                <label class="flex items-start space-x-3">
                                    <input
                                        type="checkbox"
                                        v-model="form.accept_terms"
                                        required
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 mt-1"
                                    />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        J'accepte de recevoir des notifications par email lorsqu'un box correspondant à mes critères devient disponible.
                                        Je peux me désinscrire à tout moment.
                                    </span>
                                </label>
                                <p v-if="form.errors.accept_terms" class="text-red-500 text-sm">
                                    {{ form.errors.accept_terms }}
                                </p>
                            </div>

                            <!-- Submit -->
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-3 px-6 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                            >
                                {{ form.processing ? 'Inscription en cours...' : 'S\'inscrire sur la liste d\'attente' }}
                            </button>
                        </form>
                    </div>

                    <!-- Info -->
                    <div class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
                        <p>
                            Votre inscription est gratuite et sans engagement.
                            Nous vous contacterons uniquement lorsqu'un box correspondant à vos critères sera disponible.
                        </p>
                    </div>
                </template>
            </div>
        </div>
    </PublicLayout>
</template>
