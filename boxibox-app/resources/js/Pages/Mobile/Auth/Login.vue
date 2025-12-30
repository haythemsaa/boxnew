<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-primary-900 to-indigo-900 flex flex-col relative overflow-hidden">
        <!-- Animated Background Elements -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <!-- Floating orbs -->
            <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-primary-400/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>

            <!-- Grid pattern overlay -->
            <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.02)_1px,transparent_1px)] bg-[size:50px_50px]"></div>
        </div>

        <!-- Header with Logo -->
        <div class="flex-shrink-0 pt-16 pb-10 px-6 text-center relative z-10">
            <!-- Animated Logo Container -->
            <div class="relative inline-block">
                <!-- Glow effect behind logo -->
                <div class="absolute inset-0 bg-gradient-to-r from-primary-400 to-indigo-400 rounded-3xl blur-xl opacity-50 animate-pulse"></div>

                <!-- Logo Box with Glass Effect -->
                <div class="relative w-24 h-24 bg-white/10 backdrop-blur-2xl rounded-3xl flex items-center justify-center mx-auto shadow-2xl border border-white/20 transform hover:scale-105 transition-transform duration-300">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent rounded-3xl"></div>
                    <img
                        src="/images/icons/icon-192x192.png"
                        alt="Boxibox"
                        class="w-14 h-14 relative z-10"
                        @error="handleLogoError"
                    />
                </div>
            </div>

            <!-- Brand Name with Gradient -->
            <h1 class="text-4xl font-extrabold mt-6 bg-gradient-to-r from-white via-primary-200 to-indigo-200 bg-clip-text text-transparent">
                Boxibox
            </h1>
            <p class="text-primary-300/80 mt-3 text-base font-light tracking-wide">Votre espace de stockage intelligent</p>

            <!-- Decorative line -->
            <div class="w-16 h-1 bg-gradient-to-r from-primary-400 to-indigo-400 rounded-full mx-auto mt-4"></div>
        </div>

        <!-- Login Form Card -->
        <div class="flex-1 relative z-10">
            <!-- Glass Card Effect -->
            <div class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl rounded-t-[3rem] px-8 pt-10 pb-8 shadow-2xl min-h-full border-t border-white/20">
                <!-- Welcome Text -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bienvenue</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">Connectez-vous pour acceder a votre espace</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Email Field -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 ml-1">
                            Adresse email
                        </label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-primary-50 dark:bg-primary-900/30 rounded-xl transition-all group-focus-within:bg-primary-100 dark:group-focus-within:bg-primary-800/40">
                                <EnvelopeIcon class="w-5 h-5 text-primary-500 dark:text-primary-400" />
                            </div>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                placeholder="votre@email.com"
                                class="w-full pl-16 pr-4 py-4 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-100 dark:border-gray-700 rounded-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-primary-500 dark:focus:border-primary-400 transition-all duration-300 text-base"
                                :class="{
                                    'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/20': form.errors.email,
                                    'border-green-400 dark:border-green-500': form.email && !form.errors.email && isValidEmail
                                }"
                            />
                            <!-- Validation checkmark -->
                            <div v-if="form.email && isValidEmail && !form.errors.email" class="absolute right-4 top-1/2 -translate-y-1/2">
                                <CheckCircleIcon class="w-6 h-6 text-green-500 animate-scale-in" />
                            </div>
                        </div>
                        <Transition name="slide-fade">
                            <p v-if="form.errors.email" class="mt-2 text-sm text-red-500 dark:text-red-400 flex items-center ml-1">
                                <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                                {{ form.errors.email }}
                            </p>
                        </Transition>
                    </div>

                    <!-- Password Field -->
                    <div class="group">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 ml-1">
                            Mot de passe
                        </label>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 p-2 bg-primary-50 dark:bg-primary-900/30 rounded-xl transition-all group-focus-within:bg-primary-100 dark:group-focus-within:bg-primary-800/40">
                                <LockClosedIcon class="w-5 h-5 text-primary-500 dark:text-primary-400" />
                            </div>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="Entrez votre mot de passe"
                                class="w-full pl-16 pr-14 py-4 bg-gray-50 dark:bg-gray-800/50 border-2 border-gray-100 dark:border-gray-700 rounded-2xl text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 focus:border-primary-500 dark:focus:border-primary-400 transition-all duration-300 text-base"
                                :class="{ 'border-red-400 dark:border-red-500 bg-red-50 dark:bg-red-900/20': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <EyeIcon v-if="!showPassword" class="w-5 h-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors" />
                                <EyeSlashIcon v-else class="w-5 h-5 text-primary-500" />
                            </button>
                        </div>
                        <Transition name="slide-fade">
                            <p v-if="form.errors.password" class="mt-2 text-sm text-red-500 dark:text-red-400 flex items-center ml-1">
                                <ExclamationCircleIcon class="w-4 h-4 mr-1" />
                                {{ form.errors.password }}
                            </p>
                        </Transition>
                    </div>

                    <!-- Remember & Forgot Password -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center cursor-pointer group">
                            <div class="relative">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="sr-only peer"
                                />
                                <div class="w-6 h-6 border-2 border-gray-300 dark:border-gray-600 rounded-lg peer-checked:bg-primary-500 peer-checked:border-primary-500 transition-all duration-200 flex items-center justify-center">
                                    <CheckIcon v-if="form.remember" class="w-4 h-4 text-white animate-scale-in" />
                                </div>
                            </div>
                            <span class="ml-3 text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition-colors">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                            Mot de passe oublie ?
                        </a>
                    </div>

                    <!-- Error Alert -->
                    <Transition name="slide-fade">
                        <div v-if="form.errors.auth" class="p-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 rounded-xl flex items-start space-x-3">
                            <ExclamationCircleIcon class="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
                            <div>
                                <p class="text-sm font-medium text-red-700 dark:text-red-400">Erreur de connexion</p>
                                <p class="text-sm text-red-600 dark:text-red-300 mt-1">{{ form.errors.auth }}</p>
                            </div>
                        </div>
                    </Transition>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="relative w-full py-4 bg-gradient-to-r from-primary-600 via-primary-500 to-indigo-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-primary-500/30 disabled:opacity-60 disabled:cursor-not-allowed overflow-hidden group transition-all duration-300 hover:shadow-2xl hover:shadow-primary-500/40 active:scale-[0.98]"
                    >
                        <!-- Animated background shine -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>

                        <!-- Button content -->
                        <span class="relative flex items-center justify-center">
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ form.processing ? 'Connexion en cours...' : 'Se connecter' }}</span>
                            <ArrowRightIcon v-if="!form.processing" class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                        </span>
                    </button>
                </form>

                <!-- Biometric Login -->
                <div v-if="biometricAvailable" class="mt-8">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200 dark:border-gray-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-4 bg-white dark:bg-gray-900 text-gray-400 font-medium">ou continuer avec</span>
                        </div>
                    </div>

                    <button
                        @click="loginWithBiometric"
                        class="w-full mt-6 py-4 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-2xl font-semibold border-2 border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-300 flex items-center justify-center group"
                    >
                        <div class="p-2 bg-gradient-to-br from-primary-500 to-indigo-500 rounded-xl mr-3 group-hover:scale-110 transition-transform">
                            <FingerPrintIcon class="w-5 h-5 text-white" />
                        </div>
                        <span>Connexion biometrique</span>
                    </button>
                </div>

                <!-- Footer -->
                <div class="mt-10 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Pas encore client ?
                        <a href="#" class="font-bold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 ml-1 transition-colors">
                            Contactez-nous
                        </a>
                    </p>
                </div>

                <!-- App Version & Branding -->
                <div class="mt-8 text-center space-y-2">
                    <div class="flex items-center justify-center space-x-2">
                        <div class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-xs text-gray-400">Connexion securisee</span>
                    </div>
                    <p class="text-xs text-gray-400">Boxibox Mobile v1.0.0</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
    EnvelopeIcon,
    LockClosedIcon,
    EyeIcon,
    EyeSlashIcon,
    FingerPrintIcon,
    CheckIcon,
    CheckCircleIcon,
    ExclamationCircleIcon,
    ArrowRightIcon,
} from '@heroicons/vue/24/outline'

const showPassword = ref(false)
const biometricAvailable = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

// Email validation
const isValidEmail = computed(() => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(form.email)
})

const handleLogoError = (e) => {
    // Fallback if image doesn't load - hide the broken image
    e.target.style.display = 'none'
}

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

<style scoped>
/* Transition animations */
.slide-fade-enter-active {
    transition: all 0.3s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateY(-10px);
    opacity: 0;
}

/* Scale in animation for checkmarks */
@keyframes scale-in {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-scale-in {
    animation: scale-in 0.3s ease-out;
}

/* Smooth input focus states */
input:focus {
    outline: none;
}

/* Custom scrollbar for the form area if needed */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.3);
    border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.5);
}
</style>
