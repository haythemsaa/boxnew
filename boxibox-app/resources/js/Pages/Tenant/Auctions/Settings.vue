<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    Cog6ToothIcon,
    ArrowLeftIcon,
    ClockIcon,
    CurrencyEuroIcon,
    DocumentTextIcon,
    BellIcon,
    ScaleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    settings: Object,
})

const form = useForm({
    is_enabled: props.settings?.is_enabled ?? false,
    days_before_first_notice: props.settings?.days_before_first_notice ?? 30,
    days_before_final_notice: props.settings?.days_before_final_notice ?? 45,
    days_before_auction: props.settings?.days_before_auction ?? 60,
    auction_duration_days: props.settings?.auction_duration_days ?? 7,
    minimum_bid_increment: props.settings?.minimum_bid_increment ?? 10,
    starting_bid_calculation: props.settings?.starting_bid_calculation ?? 'debt_percentage',
    starting_bid_percentage: props.settings?.starting_bid_percentage ?? 50,
    starting_bid_minimum: props.settings?.starting_bid_minimum ?? 50,
    auto_process: props.settings?.auto_process ?? false,
    notification_channels: props.settings?.notification_channels ?? ['email'],
    legal_text_first_notice: props.settings?.legal_text_first_notice ?? '',
    legal_text_final_notice: props.settings?.legal_text_final_notice ?? '',
    terms_and_conditions: props.settings?.terms_and_conditions ?? '',
})

const submit = () => {
    form.put(route('tenant.auctions.settings.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Paramètres Enchères" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link
                    :href="route('tenant.auctions.index')"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Paramètres Enchères (Lien Sales)
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Configurer le processus d'enchères pour les impayés
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Warning Banner -->
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <ScaleIcon class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-3 mt-0.5" />
                        <div>
                            <h4 class="font-medium text-yellow-800 dark:text-yellow-200">
                                Conformité légale
                            </h4>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                Les enchères de contenu de box (Lien Sales) sont soumises à des réglementations spécifiques.
                                Assurez-vous de consulter un conseiller juridique pour garantir la conformité avec les lois locales.
                            </p>
                        </div>
                    </div>
                </div>

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
                                        Activer les enchères
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Permet de gérer les ventes aux enchères pour les impayés
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
                                        Traitement automatique
                                    </label>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Envoyer automatiquement les avis et programmer les enchères
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.auto_process" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <ClockIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Délais du processus
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Jours avant 1er avis
                                </label>
                                <input
                                    type="number"
                                    v-model="form.days_before_first_notice"
                                    min="14"
                                    max="90"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Après X jours d'impayé
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Jours avant avis final
                                </label>
                                <input
                                    type="number"
                                    v-model="form.days_before_final_notice"
                                    min="30"
                                    max="120"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Après X jours d'impayé
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Jours avant mise aux enchères
                                </label>
                                <input
                                    type="number"
                                    v-model="form.days_before_auction"
                                    min="45"
                                    max="180"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    Après X jours d'impayé
                                </p>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Durée de l'enchère (jours)
                                </label>
                                <input
                                    type="number"
                                    v-model="form.auction_duration_days"
                                    min="1"
                                    max="30"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Bidding Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <CurrencyEuroIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Paramètres d'enchères
                            </h3>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Calcul de la mise de départ
                                </label>
                                <select
                                    v-model="form.starting_bid_calculation"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                >
                                    <option value="debt_percentage">Pourcentage de la dette</option>
                                    <option value="fixed_minimum">Montant fixe minimum</option>
                                    <option value="box_value">Valeur estimée du contenu</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div v-if="form.starting_bid_calculation === 'debt_percentage'">
                                    <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                        Pourcentage de la dette
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            v-model="form.starting_bid_percentage"
                                            min="10"
                                            max="100"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-8"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                        Mise de départ minimum
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            v-model="form.starting_bid_minimum"
                                            min="1"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-8"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                        Incrément minimum
                                    </label>
                                    <div class="relative">
                                        <input
                                            type="number"
                                            v-model="form.minimum_bid_increment"
                                            min="1"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pr-8"
                                        />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500">€</span>
                                    </div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Montant minimum pour surenchérir
                                    </p>
                                </div>
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
                                    <span class="text-gray-700 dark:text-gray-300">Email</span>
                                </label>
                                <label class="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        value="sms"
                                        v-model="form.notification_channels"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">SMS</span>
                                </label>
                                <label class="flex items-center space-x-3">
                                    <input
                                        type="checkbox"
                                        value="postal"
                                        v-model="form.notification_channels"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span class="text-gray-700 dark:text-gray-300">Courrier postal (recommandé)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Templates -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center space-x-3 mb-6">
                            <DocumentTextIcon class="w-6 h-6 text-gray-400" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                Textes légaux
                            </h3>
                        </div>

                        <div class="space-y-6">
                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Texte du 1er avis
                                </label>
                                <textarea
                                    v-model="form.legal_text_first_notice"
                                    rows="4"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Variables: {customer_name}, {box_name}, {site_name}, {debt_amount}, {due_date}, {deadline}..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Texte de l'avis final
                                </label>
                                <textarea
                                    v-model="form.legal_text_final_notice"
                                    rows="4"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Variables: {customer_name}, {box_name}, {site_name}, {debt_amount}, {auction_date}..."
                                ></textarea>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-900 dark:text-white mb-2">
                                    Conditions générales des enchères
                                </label>
                                <textarea
                                    v-model="form.terms_and_conditions"
                                    rows="6"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    placeholder="Conditions de participation, règles de paiement, récupération du contenu..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end space-x-4">
                        <Link
                            :href="route('tenant.auctions.index')"
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
