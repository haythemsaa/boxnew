<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import PortalLayout from '@/Layouts/PortalLayout.vue'

const props = defineProps({
    categories: Object,
})

const form = useForm({
    subject: '',
    message: '',
    category: 'general',
})

const submit = () => {
    form.post(route('customer.portal.support.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Nouvelle Demande de Support" />

    <PortalLayout>
        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <Link
                        :href="route('customer.portal.support.index')"
                        class="p-2 hover:bg-gray-100 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Nouvelle Demande</h1>
                        <p class="text-gray-600">Contactez notre service support</p>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border p-6 space-y-6">
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Type de demande
                        </label>
                        <select
                            v-model="form.category"
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option v-for="(label, key) in categories" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Sujet *
                        </label>
                        <input
                            type="text"
                            v-model="form.subject"
                            placeholder="Resume de votre demande..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.subject" class="text-sm text-red-600 mt-1">
                            {{ form.errors.subject }}
                        </p>
                    </div>

                    <!-- Message -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Votre message *
                        </label>
                        <textarea
                            v-model="form.message"
                            rows="6"
                            placeholder="Decrivez votre demande en detail..."
                            class="w-full rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"
                        />
                        <p v-if="form.errors.message" class="text-sm text-red-600 mt-1">
                            {{ form.errors.message }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Plus vous nous donnez de details, plus nous pourrons vous aider rapidement.
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3">
                        <Link
                            :href="route('customer.portal.support.index')"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition"
                        >
                            Envoyer ma demande
                        </button>
                    </div>
                </form>

                <!-- Help Tips -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">Conseils</h3>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Verifiez d'abord la FAQ pour les questions frequentes</li>
                        <li>Pour les urgences, appelez notre hotline</li>
                        <li>Incluez votre numero de contrat si pertinent</li>
                        <li>Les reponses sont generalement envoyees sous 24h ouvrables</li>
                    </ul>
                </div>
            </div>
        </div>
    </PortalLayout>
</template>
