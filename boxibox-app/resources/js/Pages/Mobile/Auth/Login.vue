<template>
    <div class="min-h-screen bg-gradient-to-br from-primary-600 via-primary-700 to-indigo-800 flex flex-col">
        <!-- Header -->
        <div class="flex-shrink-0 pt-12 pb-8 px-6 text-center">
            <div class="w-20 h-20 bg-white/20 backdrop-blur-xl rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                <CubeIcon class="w-10 h-10 text-white" />
            </div>
            <h1 class="text-3xl font-bold text-white">Boxibox</h1>
            <p class="text-primary-200 mt-2">Votre espace client</p>
        </div>

        <!-- Login Form -->
        <div class="flex-1 bg-white dark:bg-gray-900 rounded-t-[2.5rem] px-6 pt-8 pb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Connexion</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-8">Accédez à votre espace de stockage</p>

            <form @submit.prevent="submit" class="space-y-5">
                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email
                    </label>
                    <div class="relative">
                        <EnvelopeIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="votre@email.com"
                            class="w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.email }"
                        />
                    </div>
                    <p v-if="form.errors.email" class="mt-2 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Mot de passe
                    </label>
                    <div class="relative">
                        <LockClosedIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full pl-12 pr-12 py-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                            :class="{ 'border-red-500': form.errors.password }"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 p-1"
                        >
                            <EyeIcon v-if="!showPassword" class="w-5 h-5 text-gray-400" />
                            <EyeSlashIcon v-else class="w-5 h-5 text-gray-400" />
                        </button>
                    </div>
                    <p v-if="form.errors.password" class="mt-2 text-sm text-red-600">{{ form.errors.password }}</p>
                </div>

                <!-- Remember & Forgot -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="w-5 h-5 rounded-lg border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <span class="ml-3 text-sm text-gray-600 dark:text-gray-400">Se souvenir</span>
                    </label>
                    <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                        Mot de passe oublié ?
                    </a>
                </div>

                <!-- Error message -->
                <div v-if="form.errors.auth" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-2xl">
                    <p class="text-sm text-red-600 dark:text-red-400">{{ form.errors.auth }}</p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-2xl font-semibold shadow-lg shadow-primary-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center justify-center"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ form.processing ? 'Connexion...' : 'Se connecter' }}
                </button>
            </form>

            <!-- Biometric login option -->
            <div v-if="biometricAvailable" class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white dark:bg-gray-900 text-gray-500">ou</span>
                    </div>
                </div>
                <button
                    @click="loginWithBiometric"
                    class="w-full mt-6 py-4 bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-2xl font-semibold border border-gray-200 dark:border-gray-700 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all flex items-center justify-center"
                >
                    <FingerPrintIcon class="w-6 h-6 mr-3" />
                    Connexion biométrique
                </button>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Pas encore client ?
                    <a href="#" class="font-medium text-primary-600 hover:text-primary-700">Contactez-nous</a>
                </p>
            </div>

            <!-- App info -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-400">Boxibox Mobile v1.0.0</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    CubeIcon,
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    FingerPrintIcon,
} from '@heroicons/vue/24/outline'

const showPassword = ref(false)
const biometricAvailable = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('mobile.login.submit'), {
        onError: (errors) => {
            if (errors.email || errors.password) {
                form.errors.auth = errors.email || errors.password
            }
        },
    })
}

const loginWithBiometric = async () => {
    // Would implement WebAuthn biometric login
    alert('Biometric login coming soon!')
}

onMounted(() => {
    // Check if biometric is available
    if (window.PublicKeyCredential) {
        PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
            .then(available => {
                biometricAvailable.value = available
            })
    }
})
</script>
