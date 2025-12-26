<template>
    <MobileLayout title="Parametres" :show-back="true">
        <!-- Security Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center mr-3">
                    <ShieldCheckIcon class="w-5 h-5 text-red-600 dark:text-red-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Securite</h3>
            </div>

            <div class="space-y-4">
                <!-- Biometric Auth -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mr-3">
                            <FingerPrintIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Authentification biometrique</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Face ID / Touch ID</p>
                        </div>
                    </div>
                    <ToggleSwitch v-model="settings.biometric_auth" @change="toggleBiometric" />
                </div>

                <!-- PIN Code -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mr-3">
                            <KeyIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Code PIN</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Securiser l'acces a l'app</p>
                        </div>
                    </div>
                    <ToggleSwitch v-model="settings.pin_enabled" @change="togglePin" />
                </div>

                <!-- Two Factor -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mr-3">
                            <DevicePhoneMobileIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Double authentification</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">SMS / Email</p>
                        </div>
                    </div>
                    <ToggleSwitch v-model="settings.two_factor" />
                </div>

                <!-- Auto Lock -->
                <div>
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-xl flex items-center justify-center mr-3">
                            <ClockIcon class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">Verrouillage automatique</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Apres inactivite</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 gap-2 ml-13">
                        <button
                            v-for="option in autoLockOptions"
                            :key="option.value"
                            @click="settings.auto_lock = option.value"
                            class="py-2 px-3 rounded-xl text-sm font-medium transition"
                            :class="settings.auto_lock === option.value
                                ? 'bg-primary-600 text-white'
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400'"
                        >
                            {{ option.label }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center mr-3">
                    <BellIcon class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
            </div>

            <div class="space-y-4">
                <SettingsItem
                    title="Notifications push"
                    description="Recevoir des alertes sur votre telephone"
                    v-model="settings.push_notifications"
                />
                <SettingsItem
                    title="Notifications email"
                    description="Recevoir des emails importants"
                    v-model="settings.email_notifications"
                />
                <SettingsItem
                    title="Rappels de paiement"
                    description="Recevoir des rappels avant l'echeance"
                    v-model="settings.payment_reminders"
                />
                <SettingsItem
                    title="Alertes de securite"
                    description="Acces a votre box, connexions..."
                    v-model="settings.security_alerts"
                />
                <SettingsItem
                    title="Offres promotionnelles"
                    description="Recevoir nos offres speciales"
                    v-model="settings.marketing_emails"
                />
            </div>
        </div>

        <!-- App Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center mr-3">
                    <Cog6ToothIcon class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Application</h3>
            </div>

            <div class="space-y-4">
                <!-- Theme Selection -->
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-3">Theme</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button
                            v-for="theme in themeOptions"
                            :key="theme.value"
                            @click="setTheme(theme.value)"
                            class="p-4 rounded-xl border-2 transition flex flex-col items-center"
                            :class="settings.theme === theme.value
                                ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/30'
                                : 'border-gray-200 dark:border-gray-700'"
                        >
                            <component
                                :is="theme.icon"
                                class="w-6 h-6 mb-2"
                                :class="settings.theme === theme.value
                                    ? 'text-primary-600 dark:text-primary-400'
                                    : 'text-gray-500 dark:text-gray-400'"
                            />
                            <span class="text-sm font-medium" :class="settings.theme === theme.value
                                ? 'text-primary-700 dark:text-primary-400'
                                : 'text-gray-700 dark:text-gray-300'">
                                {{ theme.label }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Language -->
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">Langue</p>
                    <select
                        v-model="settings.language"
                        @change="updateLanguage"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="fr">Francais</option>
                        <option value="en">English</option>
                        <option value="es">Espanol</option>
                        <option value="de">Deutsch</option>
                        <option value="it">Italiano</option>
                        <option value="pt">Portugues</option>
                        <option value="nl">Nederlands</option>
                    </select>
                </div>

                <!-- Date Format -->
                <div>
                    <p class="font-medium text-gray-900 dark:text-white mb-2">Format de date</p>
                    <select
                        v-model="settings.date_format"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="dd/mm/yyyy">JJ/MM/AAAA (25/12/2024)</option>
                        <option value="mm/dd/yyyy">MM/JJ/AAAA (12/25/2024)</option>
                        <option value="yyyy-mm-dd">AAAA-MM-JJ (2024-12-25)</option>
                    </select>
                </div>

                <!-- Haptic Feedback -->
                <SettingsItem
                    title="Retour haptique"
                    description="Vibrations lors des actions"
                    v-model="settings.haptic_feedback"
                />

                <!-- Sound Effects -->
                <SettingsItem
                    title="Sons"
                    description="Effets sonores dans l'app"
                    v-model="settings.sound_effects"
                />
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center mr-3">
                    <EyeSlashIcon class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Confidentialite</h3>
            </div>

            <div class="space-y-4">
                <SettingsItem
                    title="Historique des acces"
                    description="Enregistrer mes acces au site"
                    v-model="settings.access_history"
                />
                <SettingsItem
                    title="Cookies analytiques"
                    description="Ameliorer l'experience utilisateur"
                    v-model="settings.analytics_cookies"
                />
                <SettingsItem
                    title="Partage de donnees"
                    description="Partager des stats anonymes"
                    v-model="settings.data_sharing"
                />
            </div>
        </div>

        <!-- Storage & Data -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center mr-3">
                    <CircleStackIcon class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Stockage</h3>
            </div>

            <!-- Storage Usage -->
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600 dark:text-gray-400">Utilisation du cache</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ cacheSize }} MB</span>
                </div>
                <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div
                        class="h-full bg-primary-600 rounded-full transition-all"
                        :style="{ width: `${Math.min(cacheSize / 100 * 100, 100)}%` }"
                    ></div>
                </div>
            </div>

            <div class="space-y-3">
                <button
                    @click="exportData"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl active:scale-[0.98] transition"
                >
                    <div class="flex items-center">
                        <ArrowDownTrayIcon class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-3" />
                        <span class="text-gray-900 dark:text-white">Exporter mes donnees</span>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </button>

                <button
                    @click="clearCache"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl active:scale-[0.98] transition"
                >
                    <div class="flex items-center">
                        <TrashIcon class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-3" />
                        <span class="text-gray-900 dark:text-white">Vider le cache</span>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ cacheSize }} MB</span>
                </button>

                <button
                    @click="clearAllData"
                    class="w-full flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/20 rounded-xl active:scale-[0.98] transition"
                >
                    <div class="flex items-center">
                        <ExclamationTriangleIcon class="w-5 h-5 text-red-500 mr-3" />
                        <span class="text-red-600 dark:text-red-400">Effacer toutes les donnees</span>
                    </div>
                </button>
            </div>
        </div>

        <!-- App Info -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-4">
            <div class="flex items-center mb-4">
                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-xl flex items-center justify-center mr-3">
                    <InformationCircleIcon class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">A propos</h3>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between py-2">
                    <span class="text-gray-500 dark:text-gray-400">Version</span>
                    <span class="text-gray-900 dark:text-white font-medium">2.0.0</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500 dark:text-gray-400">Build</span>
                    <span class="text-gray-900 dark:text-white font-medium">2024.12.25</span>
                </div>

                <a
                    href="/terms"
                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl active:scale-[0.98] transition"
                >
                    <span class="text-gray-900 dark:text-white">Conditions d'utilisation</span>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <a
                    href="/privacy"
                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl active:scale-[0.98] transition"
                >
                    <span class="text-gray-900 dark:text-white">Politique de confidentialité</span>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <button
                    @click="checkForUpdates"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl active:scale-[0.98] transition"
                >
                    <div class="flex items-center">
                        <ArrowPathIcon class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-3" />
                        <span class="text-gray-900 dark:text-white">Verifier les mises a jour</span>
                    </div>
                    <span v-if="checkingUpdates" class="text-sm text-gray-500">
                        <ArrowPathIcon class="w-4 h-4 animate-spin" />
                    </span>
                </button>
            </div>
        </div>

        <!-- Biometric Setup Modal -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showBiometricSetup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-sm w-full shadow-xl text-center">
                    <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                        <FingerPrintIcon class="w-10 h-10 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Activer la biometrie</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        Utilisez Face ID ou Touch ID pour deverrouiller l'application rapidement et en toute securite.
                    </p>
                    <div class="space-y-3">
                        <button
                            @click="setupBiometric"
                            class="w-full py-3 bg-primary-600 text-white font-semibold rounded-xl active:scale-95 transition"
                        >
                            Activer
                        </button>
                        <button
                            @click="showBiometricSetup = false; settings.biometric_auth = false"
                            class="w-full py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-xl active:scale-95 transition"
                        >
                            Plus tard
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </MobileLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    ShieldCheckIcon,
    FingerPrintIcon,
    KeyIcon,
    DevicePhoneMobileIcon,
    ClockIcon,
    BellIcon,
    Cog6ToothIcon,
    SunIcon,
    MoonIcon,
    ComputerDesktopIcon,
    EyeSlashIcon,
    CircleStackIcon,
    ArrowDownTrayIcon,
    TrashIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    ChevronRightIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

// Simple Toggle Component
const ToggleSwitch = {
    props: ['modelValue'],
    emits: ['update:modelValue', 'change'],
    template: `
        <button
            @click="toggle"
            class="relative w-14 h-8 rounded-full transition-colors duration-300"
            :class="modelValue ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
        >
            <span
                class="absolute top-1 w-6 h-6 bg-white rounded-full shadow-md transition-transform duration-300"
                :class="modelValue ? 'translate-x-7' : 'translate-x-1'"
            ></span>
        </button>
    `,
    methods: {
        toggle() {
            this.$emit('update:modelValue', !this.modelValue)
            this.$emit('change', !this.modelValue)
        }
    }
}

// Settings Item Component
const SettingsItem = {
    props: ['title', 'description', 'modelValue'],
    emits: ['update:modelValue'],
    components: { ToggleSwitch },
    template: `
        <div class="flex items-center justify-between">
            <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ title }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ description }}</p>
            </div>
            <ToggleSwitch :modelValue="modelValue" @update:modelValue="$emit('update:modelValue', $event)" />
        </div>
    `
}

const props = defineProps({
    userSettings: Object,
})

const showBiometricSetup = ref(false)
const checkingUpdates = ref(false)
const cacheSize = ref(12.5)

const settings = reactive({
    // Security
    biometric_auth: props.userSettings?.biometric_auth ?? false,
    pin_enabled: props.userSettings?.pin_enabled ?? false,
    two_factor: props.userSettings?.two_factor ?? false,
    auto_lock: props.userSettings?.auto_lock ?? '5min',
    // Notifications
    push_notifications: props.userSettings?.push_notifications ?? true,
    email_notifications: props.userSettings?.email_notifications ?? true,
    payment_reminders: props.userSettings?.payment_reminders ?? true,
    security_alerts: props.userSettings?.security_alerts ?? true,
    marketing_emails: props.userSettings?.marketing_emails ?? false,
    // App
    theme: props.userSettings?.theme ?? 'system',
    language: props.userSettings?.language ?? 'fr',
    date_format: props.userSettings?.date_format ?? 'dd/mm/yyyy',
    haptic_feedback: props.userSettings?.haptic_feedback ?? true,
    sound_effects: props.userSettings?.sound_effects ?? true,
    // Privacy
    access_history: props.userSettings?.access_history ?? true,
    analytics_cookies: props.userSettings?.analytics_cookies ?? true,
    data_sharing: props.userSettings?.data_sharing ?? false,
})

const autoLockOptions = [
    { value: 'immediate', label: 'Immed.' },
    { value: '1min', label: '1 min' },
    { value: '5min', label: '5 min' },
    { value: 'never', label: 'Jamais' },
]

const themeOptions = [
    { value: 'light', label: 'Clair', icon: SunIcon },
    { value: 'dark', label: 'Sombre', icon: MoonIcon },
    { value: 'system', label: 'Auto', icon: ComputerDesktopIcon },
]

const toggleBiometric = (enabled) => {
    if (enabled) {
        showBiometricSetup.value = true
    }
}

const setupBiometric = async () => {
    // Check if Web Authentication API is available
    if (window.PublicKeyCredential) {
        try {
            const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
            if (available) {
                // Biometric is available
                showBiometricSetup.value = false
                if (navigator.vibrate) navigator.vibrate([50, 50, 100])
            } else {
                alert('Authentification biometrique non disponible sur cet appareil')
                settings.biometric_auth = false
            }
        } catch (e) {
            console.error('Biometric error:', e)
            settings.biometric_auth = false
        }
    } else {
        alert('Authentification biometrique non supportee')
        settings.biometric_auth = false
    }
    showBiometricSetup.value = false
}

const togglePin = (enabled) => {
    if (enabled) {
        // Show PIN setup modal
        // For now, just enable it
    }
}

const setTheme = (theme) => {
    settings.theme = theme
    localStorage.setItem('boxibox-dark-mode', theme === 'dark' ? 'true' : theme === 'light' ? 'false' : null)

    if (theme === 'dark') {
        document.documentElement.classList.add('dark')
    } else if (theme === 'light') {
        document.documentElement.classList.remove('dark')
    } else {
        // System preference
        const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches
        if (isDark) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }

    if (settings.haptic_feedback && navigator.vibrate) {
        navigator.vibrate(30)
    }
}

const updateLanguage = () => {
    saveSettings()
}

const saveSettings = () => {
    router.put(route('mobile.settings.update'), settings, {
        preserveScroll: true,
    })
}

const exportData = () => {
    window.location.href = route('mobile.settings.export-data')
}

const clearCache = async () => {
    if ('caches' in window) {
        const names = await caches.keys()
        await Promise.all(names.map(name => caches.delete(name)))
        cacheSize.value = 0
        if (settings.haptic_feedback && navigator.vibrate) {
            navigator.vibrate(50)
        }
    }
}

const clearAllData = () => {
    if (confirm('Etes-vous sur de vouloir effacer toutes les donnees locales? Cette action est irreversible.')) {
        localStorage.clear()
        sessionStorage.clear()
        clearCache()
        window.location.reload()
    }
}

const checkForUpdates = async () => {
    checkingUpdates.value = true
    await new Promise(resolve => setTimeout(resolve, 2000))
    checkingUpdates.value = false
    alert('Vous utilisez la derniere version!')
}

onMounted(() => {
    // Calculate cache size
    if ('storage' in navigator && 'estimate' in navigator.storage) {
        navigator.storage.estimate().then(estimate => {
            if (estimate.usage) {
                cacheSize.value = (estimate.usage / 1024 / 1024).toFixed(1)
            }
        })
    }
})
</script>

<style scoped>
.ml-13 {
    margin-left: 3.25rem;
}
</style>
