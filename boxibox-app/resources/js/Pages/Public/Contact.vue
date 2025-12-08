<template>
    <PublicLayout>
        <section class="py-24 bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
                    <!-- Contact Info -->
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-6">
                            Contactez-nous
                        </h1>
                        <p class="text-xl text-gray-600 mb-12">
                            Une question ? Notre équipe est là pour vous aider. Réponse garantie sous 24h.
                        </p>

                        <div class="space-y-8">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Email</h3>
                                    <p class="text-gray-600">contact@boxibox.fr</p>
                                    <p class="text-gray-600">support@boxibox.fr</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Téléphone</h3>
                                    <p class="text-gray-600">+33 1 23 45 67 89</p>
                                    <p class="text-sm text-gray-500">Lun-Ven 9h-18h</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Adresse</h3>
                                    <p class="text-gray-600">123 Avenue de l'Innovation</p>
                                    <p class="text-gray-600">75008 Paris, France</p>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Quick Links -->
                        <div class="mt-12 p-6 bg-white rounded-2xl border border-gray-100">
                            <h3 class="font-semibold text-gray-900 mb-4">Questions fréquentes</h3>
                            <ul class="space-y-3">
                                <li>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        Comment fonctionne l'essai gratuit ?
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        Puis-je migrer mes données ?
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="text-indigo-600 hover:text-indigo-700 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        Quelle formation est incluse ?
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 lg:p-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-6">Envoyez-nous un message</h2>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="Jean Dupont"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="jean@example.com"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Entreprise</label>
                                    <input
                                        v-model="form.company"
                                        type="text"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="Ma Société"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="+33 6 12 34 56 78"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type de demande *</label>
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                >
                                    <option value="">Sélectionnez...</option>
                                    <option value="demo">Demande de démonstration</option>
                                    <option value="support">Support technique</option>
                                    <option value="partnership">Partenariat</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                                <textarea
                                    v-model="form.message"
                                    required
                                    rows="5"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                                    placeholder="Comment pouvons-nous vous aider ?"
                                ></textarea>
                            </div>

                            <div class="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    v-model="form.privacy"
                                    required
                                    class="mt-1 w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                />
                                <label class="text-sm text-gray-600">
                                    J'accepte que mes données soient traitées conformément à la
                                    <a href="#" class="text-indigo-600 hover:underline">politique de confidentialité</a>.
                                </label>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn-primary w-full"
                            >
                                <span v-if="form.processing">Envoi en cours...</span>
                                <span v-else>Envoyer le message</span>
                            </button>
                        </form>

                        <!-- Success Message -->
                        <div v-if="$page.props.flash?.success" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <p class="text-green-700">{{ $page.props.flash.success }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'

const form = useForm({
    name: '',
    email: '',
    company: '',
    phone: '',
    type: '',
    message: '',
    privacy: false,
})

const submitForm = () => {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>
