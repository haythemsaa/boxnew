<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    Cog6ToothIcon,
    ArrowLeftIcon,
    ClockIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    StarIcon,
    LinkIcon,
    BellIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
    sites: Array,
})

const form = useForm({
    is_enabled: props.settings?.is_enabled ?? true,
    auto_request_enabled: props.settings?.auto_request_enabled ?? true,
    trigger_event: props.settings?.trigger_event ?? 'contract_end',
    delay_days: props.settings?.delay_days ?? 7,
    reminder_enabled: props.settings?.reminder_enabled ?? true,
    reminder_delay_days: props.settings?.reminder_delay_days ?? 3,
    max_reminders: props.settings?.max_reminders ?? 2,
    notification_channels: props.settings?.notification_channels ?? ['email'],
    google_review_url: props.settings?.google_review_url ?? '',
    facebook_review_url: props.settings?.facebook_review_url ?? '',
    trustpilot_url: props.settings?.trustpilot_url ?? '',
    minimum_rating_for_redirect: props.settings?.minimum_rating_for_redirect ?? 4,
    email_subject: props.settings?.email_subject ?? 'Votre avis compte pour nous !',
    email_template: props.settings?.email_template ?? '',
    sms_template: props.settings?.sms_template ?? '',
    nps_enabled: props.settings?.nps_enabled ?? true,
    nps_question: props.settings?.nps_question ?? 'Sur une échelle de 0 à 10, quelle est la probabilité que vous recommandiez nos services à un ami ou collègue ?',
})

const submit = () => {
    form.put(route('tenant.reviews.settings.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Paramètres Avis" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('tenant.reviews.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Paramètres Demandes d'avis
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Configurer les demandes automatiques d'avis clients
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- General Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <Cog6ToothIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Paramètres généraux
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="font-medium text-gray-900 dark:text-white">
                                        Activer les demandes d'avis
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Permet d'envoyer des demandes d'avis aux clients
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="font-medium text-gray-900 dark:text-white">
                                        Demandes automatiques
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Envoyer automatiquement après un événement
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.auto_request_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Trigger Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <ClockIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Déclenchement
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Événement déclencheur
                                </label>
                                <select
                                    v-model="form.trigger_event"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="contract_end">Fin de contrat</option>
                                    <option value="first_payment">Après premier paiement</option>
                                    <option value="contract_renewal">Renouvellement de contrat</option>
                                    <option value="support_ticket_closed">Ticket support résolu</option>
                                    <option value="manual">Manuel uniquement</option>
                                </select>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Délai après l'événement (jours)
                                </label>
                                <input
                                    type="number"
                                    v-model="form.delay_days"
                                    min="0"
                                    max="30"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Nombre de jours à attendre avant d'envoyer la demande
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Reminder Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <BellIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Relances
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="font-medium text-gray-900 dark:text-white">
                                        Activer les relances
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Envoyer des rappels si le client n'a pas répondu
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.reminder_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                        Délai entre relances (jours)
                                    </label>
                                    <input
                                        type="number"
                                        v-model="form.reminder_delay_days"
                                        min="1"
                                        max="14"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                        Nombre max de relances
                                    </label>
                                    <input
                                        type="number"
                                        v-model="form.max_reminders"
                                        min="0"
                                        max="5"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Channels -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <EnvelopeIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Canaux de notification
                            </h3>
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    value="email"
                                    v-model="form.notification_channels"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <EnvelopeIcon class="w-5 h-5 text-gray-400" />
                                <span class="text-gray-700 dark:text-gray-300">Email</span>
                            </label>
                            <label class="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    value="sms"
                                    v-model="form.notification_channels"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <DevicePhoneMobileIcon class="w-5 h-5 text-gray-400" />
                                <span class="text-gray-700 dark:text-gray-300">SMS</span>
                            </label>
                        </div>
                    </div>

                    <!-- Review Platforms -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <LinkIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Plateformes d'avis
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    URL Google Reviews
                                </label>
                                <input
                                    type="url"
                                    v-model="form.google_review_url"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="https://g.page/r/..."
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Lien direct vers votre page d'avis Google
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    URL Facebook Reviews
                                </label>
                                <input
                                    type="url"
                                    v-model="form.facebook_review_url"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="https://www.facebook.com/..."
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    URL Trustpilot
                                </label>
                                <input
                                    type="url"
                                    v-model="form.trustpilot_url"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="https://www.trustpilot.com/review/..."
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Note minimum pour redirection externe
                                </label>
                                <div class="flex items-center space-x-2">
                                    <input
                                        type="range"
                                        v-model="form.minimum_rating_for_redirect"
                                        min="1"
                                        max="5"
                                        class="flex-1"
                                    />
                                    <div class="flex items-center space-x-1 min-w-[80px]">
                                        <span class="font-bold text-lg text-gray-900 dark:text-white">{{ form.minimum_rating_for_redirect }}</span>
                                        <StarIcon class="w-5 h-5 text-yellow-500 fill-current" />
                                    </div>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Les clients avec une note &ge; {{ form.minimum_rating_for_redirect }} seront redirigés vers Google/Facebook
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- NPS Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <StarIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Net Promoter Score (NPS)
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="font-medium text-gray-900 dark:text-white">
                                        Activer le NPS
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Collecter le score NPS en plus de l'avis
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.nps_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Question NPS
                                </label>
                                <textarea
                                    v-model="form.nps_question"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Email Template -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <EnvelopeIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Modèles de messages
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Sujet de l'email
                                </label>
                                <input
                                    type="text"
                                    v-model="form.email_subject"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Modèle Email
                                </label>
                                <textarea
                                    v-model="form.email_template"
                                    rows="6"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Variables: {customer_name}, {site_name}, {review_link}, {company_name}..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Modèle SMS
                                </label>
                                <textarea
                                    v-model="form.sms_template"
                                    rows="3"
                                    maxlength="160"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Max 160 caractères. Variables: {customer_name}, {review_link}"
                                ></textarea>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ form.sms_template?.length || 0 }}/160 caractères
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end space-x-4">
                        <Link
                            :href="route('tenant.reviews.index')"
                            class="px-6 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
