<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    Cog6ToothIcon,
    ArrowLeftIcon,
    ClockIcon,
    BellIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
    sites: Array,
})

const form = useForm({
    is_enabled: props.settings?.is_enabled ?? true,
    max_entries_per_site: props.settings?.max_entries_per_site ?? 50,
    auto_notify: props.settings?.auto_notify ?? true,
    notification_delay_hours: props.settings?.notification_delay_hours ?? 2,
    expiry_days: props.settings?.expiry_days ?? 30,
    notification_channels: props.settings?.notification_channels ?? ['email'],
    priority_rules: props.settings?.priority_rules ?? {
        existing_customers: true,
        specific_box: true,
        flexible_criteria: false,
    },
    email_template: props.settings?.email_template ?? '',
    sms_template: props.settings?.sms_template ?? '',
})

const submit = () => {
    form.put(route('tenant.waitlist.settings.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Paramètres Liste d'attente" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('tenant.waitlist.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Paramètres Liste d'attente
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Configurer le comportement de la liste d'attente
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
                                        Activer la liste d'attente
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Permettre aux clients de s'inscrire sur la liste d'attente
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Nombre maximum d'entrées par site
                                </label>
                                <input
                                    type="number"
                                    v-model="form.max_entries_per_site"
                                    min="1"
                                    max="500"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Limite le nombre de personnes pouvant s'inscrire par site
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Durée de validité (jours)
                                </label>
                                <input
                                    type="number"
                                    v-model="form.expiry_days"
                                    min="7"
                                    max="365"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Les entrées expirent automatiquement après cette période
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <BellIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Notifications
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label class="font-medium text-gray-900 dark:text-white">
                                        Notification automatique
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Notifier automatiquement quand un box correspondant se libère
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.auto_notify" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Délai avant notification (heures)
                                </label>
                                <input
                                    type="number"
                                    v-model="form.notification_delay_hours"
                                    min="0"
                                    max="72"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Temps d'attente après qu'un box se libère avant d'envoyer la notification
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-3">
                                    Canaux de notification
                                </label>
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
                        </div>
                    </div>

                    <!-- Priority Rules -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <ClockIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Règles de priorité
                            </h3>
                        </div>

                        <div class="space-y-4">
                            <label class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        Clients existants prioritaires
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Les clients ayant déjà loué ont la priorité
                                    </p>
                                </div>
                                <input
                                    type="checkbox"
                                    v-model="form.priority_rules.existing_customers"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5"
                                />
                            </label>

                            <label class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        Demande de box spécifique
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Priorité si le client demande un box précis
                                    </p>
                                </div>
                                <input
                                    type="checkbox"
                                    v-model="form.priority_rules.specific_box"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5"
                                />
                            </label>

                            <label class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        Critères flexibles
                                    </span>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Priorité aux clients avec des critères plus larges
                                    </p>
                                </div>
                                <input
                                    type="checkbox"
                                    v-model="form.priority_rules.flexible_criteria"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-5 w-5"
                                />
                            </label>
                        </div>
                    </div>

                    <!-- Templates -->
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
                                    Modèle Email
                                </label>
                                <textarea
                                    v-model="form.email_template"
                                    rows="5"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Utilisez {customer_name}, {site_name}, {box_name}, {box_size}, {box_price}, {link} comme variables..."
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
                                    placeholder="Max 160 caractères. Variables: {customer_name}, {site_name}, {link}"
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
                            :href="route('tenant.waitlist.index')"
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
