<template>
    <PublicLayout>
        <section class="py-24 bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <!-- Left Column - Value Proposition -->
                    <div>
                        <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                            Découvrez Boxibox
                            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                en action
                            </span>
                        </h1>
                        <p class="text-xl text-gray-600 mb-8">
                            Demandez une démo personnalisée et découvrez comment Boxibox peut transformer votre activité de self-storage.
                        </p>

                        <!-- Benefits -->
                        <div class="space-y-6 mb-12">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Démo personnalisée</h3>
                                    <p class="text-gray-600">30 minutes avec un expert pour voir les fonctionnalités adaptées à vos besoins.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Sans engagement</h3>
                                    <p class="text-gray-600">Aucune carte bancaire requise. Essai gratuit de 14 jours inclus.</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Migration assistée</h3>
                                    <p class="text-gray-600">Notre équipe vous accompagne pour importer vos données existantes.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Testimonial -->
                        <div class="bg-white rounded-2xl p-6 border border-gray-100">
                            <div class="flex gap-1 mb-3">
                                <svg v-for="i in 5" :key="i" class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            <p class="text-gray-700 italic mb-4">
                                "La démo m'a convaincu en 15 minutes. L'équipe a compris nos besoins et nous a montré exactement comment Boxibox pouvait nous aider."
                            </p>
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    P
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">Pierre Martin</p>
                                    <p class="text-sm text-gray-500">Stock&Box Marseille</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Demo Form -->
                    <div class="bg-white rounded-2xl shadow-xl p-8 lg:p-12">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Planifier ma démo</h2>
                        <p class="text-gray-600 mb-8">Remplissez ce formulaire, nous vous recontactons sous 24h.</p>

                        <form @submit.prevent="submitForm" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénom & Nom *</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="Jean Dupont"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email professionnel *</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="jean@monstorage.fr"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Entreprise *</label>
                                    <input
                                        v-model="form.company"
                                        type="text"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="Mon Storage"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        placeholder="+33 6 12 34 56 78"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de sites *</label>
                                    <select
                                        v-model="form.sites_count"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    >
                                        <option value="">Sélectionnez...</option>
                                        <option value="1">1 site</option>
                                        <option value="2">2-3 sites</option>
                                        <option value="5">4-10 sites</option>
                                        <option value="10">Plus de 10 sites</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de boxes *</label>
                                    <select
                                        v-model="form.boxes_count"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    >
                                        <option value="">Sélectionnez...</option>
                                        <option value="50">Moins de 50</option>
                                        <option value="100">50-100</option>
                                        <option value="250">100-250</option>
                                        <option value="500">250-500</option>
                                        <option value="1000">Plus de 500</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Logiciel actuel</label>
                                <input
                                    v-model="form.current_software"
                                    type="text"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                    placeholder="Excel, autre logiciel..."
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Message (optionnel)</label>
                                <textarea
                                    v-model="form.message"
                                    rows="3"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                                    placeholder="Précisez vos besoins ou questions..."
                                ></textarea>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="btn-primary w-full text-lg py-4"
                            >
                                <span v-if="form.processing">Envoi en cours...</span>
                                <span v-else class="flex items-center justify-center gap-2">
                                    Demander ma démo gratuite
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </span>
                            </button>

                            <p class="text-sm text-gray-500 text-center">
                                En soumettant ce formulaire, vous acceptez notre
                                <a href="#" class="text-indigo-600 hover:underline">politique de confidentialité</a>.
                            </p>
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
    sites_count: '',
    boxes_count: '',
    current_software: '',
    message: '',
})

const submitForm = () => {
    form.post('/demo', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset()
        },
    })
}
</script>
