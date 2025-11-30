<template>
    <MobileLayout title="Parametres" :show-back="true">
        <!-- Notifications Settings -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notifications</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Notifications push</p>
                        <p class="text-sm text-gray-500">Recevoir des alertes sur votre telephone</p>
                    </div>
                    <button
                        @click="toggleSetting('push_notifications')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.push_notifications ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.push_notifications ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Notifications email</p>
                        <p class="text-sm text-gray-500">Recevoir des emails importants</p>
                    </div>
                    <button
                        @click="toggleSetting('email_notifications')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.email_notifications ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.email_notifications ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Rappels de paiement</p>
                        <p class="text-sm text-gray-500">Recevoir des rappels avant l'echeance</p>
                    </div>
                    <button
                        @click="toggleSetting('payment_reminders')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.payment_reminders ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.payment_reminders ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Alertes de securite</p>
                        <p class="text-sm text-gray-500">Acces a votre box, connexions...</p>
                    </div>
                    <button
                        @click="toggleSetting('security_alerts')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.security_alerts ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.security_alerts ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Offres promotionnelles</p>
                        <p class="text-sm text-gray-500">Recevoir nos offres speciales</p>
                    </div>
                    <button
                        @click="toggleSetting('marketing_emails')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.marketing_emails ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.marketing_emails ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- App Settings -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Application</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Mode sombre</p>
                        <p class="text-sm text-gray-500">Theme de l'application</p>
                    </div>
                    <button
                        @click="toggleSetting('dark_mode')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.dark_mode ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.dark_mode ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="font-medium text-gray-900">Langue</p>
                            <p class="text-sm text-gray-500">Langue de l'interface</p>
                        </div>
                    </div>
                    <select
                        v-model="settings.language"
                        @change="updateLanguage"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white"
                    >
                        <option value="fr">Francais</option>
                        <option value="en">English</option>
                        <option value="es">Espanol</option>
                        <option value="de">Deutsch</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="font-medium text-gray-900">Format de date</p>
                            <p class="text-sm text-gray-500">Affichage des dates</p>
                        </div>
                    </div>
                    <select
                        v-model="settings.date_format"
                        @change="updateDateFormat"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white"
                    >
                        <option value="dd/mm/yyyy">JJ/MM/AAAA</option>
                        <option value="mm/dd/yyyy">MM/JJ/AAAA</option>
                        <option value="yyyy-mm-dd">AAAA-MM-JJ</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Confidentialite</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Historique des acces</p>
                        <p class="text-sm text-gray-500">Enregistrer mes acces au site</p>
                    </div>
                    <button
                        @click="toggleSetting('access_history')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.access_history ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.access_history ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-gray-900">Cookies analytiques</p>
                        <p class="text-sm text-gray-500">Ameliorer l'experience utilisateur</p>
                    </div>
                    <button
                        @click="toggleSetting('analytics_cookies')"
                        class="relative w-12 h-6 rounded-full transition-colors"
                        :class="settings.analytics_cookies ? 'bg-primary-600' : 'bg-gray-300'"
                    >
                        <span
                            class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                            :class="settings.analytics_cookies ? 'translate-x-6' : ''"
                        ></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Data Management -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Donnees</h3>

            <div class="space-y-3">
                <button
                    @click="exportData"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <div class="flex items-center">
                        <ArrowDownTrayIcon class="w-5 h-5 text-gray-400 mr-3" />
                        <span class="text-gray-900">Exporter mes donnees</span>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </button>

                <button
                    @click="clearCache"
                    class="w-full flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <div class="flex items-center">
                        <TrashIcon class="w-5 h-5 text-gray-400 mr-3" />
                        <span class="text-gray-900">Vider le cache</span>
                    </div>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </button>
            </div>
        </div>

        <!-- App Info -->
        <div class="bg-white rounded-2xl shadow-sm p-5 mb-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">A propos</h3>

            <div class="space-y-3">
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Version</span>
                    <span class="text-gray-900">1.0.0</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-gray-500">Build</span>
                    <span class="text-gray-900">2024.11.28</span>
                </div>

                <a
                    href="#"
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <span class="text-gray-900">Conditions d'utilisation</span>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <a
                    href="#"
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <span class="text-gray-900">Politique de confidentialite</span>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>

                <a
                    href="#"
                    class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100"
                >
                    <span class="text-gray-900">Licences open source</span>
                    <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                </a>
            </div>
        </div>
    </MobileLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import MobileLayout from '@/Layouts/MobileLayout.vue'
import {
    ArrowDownTrayIcon,
    TrashIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    userSettings: Object,
})

const settings = reactive({
    push_notifications: props.userSettings?.push_notifications ?? true,
    email_notifications: props.userSettings?.email_notifications ?? true,
    payment_reminders: props.userSettings?.payment_reminders ?? true,
    security_alerts: props.userSettings?.security_alerts ?? true,
    marketing_emails: props.userSettings?.marketing_emails ?? false,
    dark_mode: props.userSettings?.dark_mode ?? false,
    language: props.userSettings?.language ?? 'fr',
    date_format: props.userSettings?.date_format ?? 'dd/mm/yyyy',
    access_history: props.userSettings?.access_history ?? true,
    analytics_cookies: props.userSettings?.analytics_cookies ?? true,
})

const toggleSetting = (key) => {
    settings[key] = !settings[key]
    saveSettings()
}

const updateLanguage = () => {
    saveSettings()
}

const updateDateFormat = () => {
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

const clearCache = () => {
    if ('caches' in window) {
        caches.keys().then((names) => {
            names.forEach((name) => {
                caches.delete(name)
            })
        })
        alert('Cache vide avec succes')
    }
}
</script>
