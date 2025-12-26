<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, Cog6ToothIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    settings: Object,
    sites: Array,
});

const form = useForm({
    site_id: props.settings?.site_id || null,
    is_enabled: props.settings?.is_enabled ?? false,
    merchant_id: props.settings?.merchant_id || '',
    place_id: props.settings?.place_id || '',
    available_days: props.settings?.available_days || [1, 2, 3, 4, 5, 6],
    opening_time: props.settings?.opening_time || '09:00',
    closing_time: props.settings?.closing_time || '18:00',
    slot_duration_minutes: props.settings?.slot_duration_minutes || 30,
    max_advance_days: props.settings?.max_advance_days || 30,
    min_advance_hours: props.settings?.min_advance_hours || 2,
    auto_confirm: props.settings?.auto_confirm ?? true,
    require_deposit: props.settings?.require_deposit ?? false,
    deposit_amount: props.settings?.deposit_amount || 0,
    notify_on_booking: props.settings?.notify_on_booking ?? true,
    send_customer_confirmation: props.settings?.send_customer_confirmation ?? true,
    send_reminder: props.settings?.send_reminder ?? true,
    reminder_hours_before: props.settings?.reminder_hours_before || 24,
});

const daysOfWeek = [
    { value: 1, label: 'Lundi' },
    { value: 2, label: 'Mardi' },
    { value: 3, label: 'Mercredi' },
    { value: 4, label: 'Jeudi' },
    { value: 5, label: 'Vendredi' },
    { value: 6, label: 'Samedi' },
    { value: 0, label: 'Dimanche' },
];

const toggleDay = (day) => {
    const index = form.available_days.indexOf(day);
    if (index > -1) {
        form.available_days.splice(index, 1);
    } else {
        form.available_days.push(day);
    }
};

const submit = () => {
    form.post(route('tenant.google-reserve.settings.update'));
};
</script>

<template>
    <TenantLayout title="Paramètres Google Reserve">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('tenant.google-reserve.index')" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                    <ArrowLeftIcon class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Paramètres Google Reserve</h1>
                    <p class="text-gray-600 dark:text-gray-400">Configurez l'intégration avec Google Reserve</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Activation -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Activer Google Reserve</h3>
                            <p class="text-sm text-gray-500">Permettre les réservations via Google Maps et Search</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Identifiants Google -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <Cog6ToothIcon class="w-5 h-5" />
                        Identifiants Google
                    </h3>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                            <select v-model="form.site_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option :value="null">Tous les sites</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Merchant ID</label>
                            <input type="text" v-model="form.merchant_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" placeholder="Votre ID marchand Google">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Place ID</label>
                            <input type="text" v-model="form.place_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" placeholder="ChIJ...">
                            <p class="text-xs text-gray-500 mt-1">L'identifiant de votre établissement sur Google Maps</p>
                        </div>
                    </div>
                </div>

                <!-- Disponibilités -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Disponibilités</h3>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jours d'ouverture</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="day in daysOfWeek"
                                :key="day.value"
                                type="button"
                                @click="toggleDay(day.value)"
                                :class="[
                                    'px-4 py-2 rounded-lg text-sm font-medium transition',
                                    form.available_days.includes(day.value)
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                ]"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heure d'ouverture</label>
                            <input type="time" v-model="form.opening_time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heure de fermeture</label>
                            <input type="time" v-model="form.closing_time" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Durée créneau (min)</label>
                            <select v-model="form.slot_duration_minutes" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option :value="15">15 minutes</option>
                                <option :value="30">30 minutes</option>
                                <option :value="45">45 minutes</option>
                                <option :value="60">1 heure</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Réservation max (jours)</label>
                            <input type="number" v-model="form.max_advance_days" min="1" max="90" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Délai min (heures)</label>
                            <input type="number" v-model="form.min_advance_hours" min="0" max="72" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        </div>
                    </div>
                </div>

                <!-- Confirmation & Paiement -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Confirmation & Acompte</h3>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.auto_confirm" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Confirmer automatiquement les réservations</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.require_deposit" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Demander un acompte</span>
                        </label>
                    </div>

                    <div v-if="form.require_deposit">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Montant de l'acompte (€)</label>
                        <input type="number" v-model="form.deposit_amount" min="0" step="0.01" class="w-full md:w-48 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                    </div>
                </div>

                <!-- Notifications -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Notifications</h3>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.notify_on_booking" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Recevoir une notification à chaque réservation</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.send_customer_confirmation" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Envoyer une confirmation au client</span>
                        </label>
                        <label class="flex items-center gap-3">
                            <input type="checkbox" v-model="form.send_reminder" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-700 dark:text-gray-300">Envoyer un rappel au client</span>
                        </label>
                    </div>

                    <div v-if="form.send_reminder">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rappel (heures avant)</label>
                        <select v-model="form.reminder_hours_before" class="w-full md:w-48 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option :value="1">1 heure</option>
                            <option :value="2">2 heures</option>
                            <option :value="4">4 heures</option>
                            <option :value="12">12 heures</option>
                            <option :value="24">24 heures</option>
                            <option :value="48">48 heures</option>
                        </select>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-3">
                    <Link :href="route('tenant.google-reserve.index')" class="btn-secondary">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 transition; }
</style>
