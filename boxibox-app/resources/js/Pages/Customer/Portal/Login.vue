<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-600 via-purple-600 to-indigo-700 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo & Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-4">
                    <i class="fas fa-box text-3xl text-blue-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-white">Mon Espace Client</h1>
                <p class="text-blue-100 mt-2">Connectez-vous pour acceder a vos services</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <form @submit.prevent="submit">
                    <!-- Email -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            Adresse email
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition"
                            placeholder="votre@email.com"
                            required
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-gray-400"></i>
                            Mot de passe
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition pr-12"
                                placeholder="********"
                                required
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember & Forgot -->
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="ml-2 text-sm text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-700">
                            Mot de passe oublie ?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-purple-700 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Connexion en cours...
                        </span>
                        <span v-else>
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Se connecter
                        </span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">ou</span>
                    </div>
                </div>

                <!-- Help Links -->
                <div class="text-center space-y-3">
                    <p class="text-sm text-gray-600">
                        Pas encore client ?
                        <a href="/" class="text-blue-600 hover:text-blue-700 font-medium">
                            Decouvrir nos offres
                        </a>
                    </p>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-headset mr-1"></i>
                        Besoin d'aide ? Appelez le
                        <a href="tel:+33123456789" class="font-medium text-gray-700">01 23 45 67 89</a>
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-blue-100 text-sm">
                <p>&copy; {{ new Date().getFullYear() }} BoxiBox - Tous droits reserves</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('customer.portal.login.submit'), {
        onFinish: () => {
            form.password = '';
        },
    });
};
</script>
