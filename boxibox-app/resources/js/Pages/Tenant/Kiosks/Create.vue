<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, ComputerDesktopIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: Array,
});

const form = useForm({
    name: '',
    site_id: '',
    location_description: '',
    language: 'fr',
    allow_new_rentals: true,
    allow_payments: true,
    allow_access_code_generation: true,
    show_prices: true,
    require_id_verification: false,
    primary_color: '#2563eb',
    secondary_color: '#1e40af',
    welcome_message: 'Bienvenue ! Comment pouvons-nous vous aider ?',
    idle_timeout_seconds: 120,
    enable_screensaver: true,
});

const submit = () => {
    form.post(route('tenant.kiosks.store'));
};

const languageOptions = [
    { value: 'fr', label: 'Francais' },
    { value: 'en', label: 'English' },
    { value: 'nl', label: 'Nederlands' },
    { value: 'de', label: 'Deutsch' },
];
</script>

<template>
    <TenantLayout title="Nouveau kiosque">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('tenant.kiosks.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau kiosque</h1>
                    <p class="text-gray-600 dark:text-gray-400">Configurez une nouvelle borne self-service</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <ComputerDesktopIcon class="w-5 h-5" />
                        Informations generales
                    </h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nom du kiosque *
                            </label>
                            <input v-model="form.name" type="text" class="input" placeholder="Ex: Borne Accueil" required />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Site *
                            </label>
                            <select v-model="form.site_id" class="input" required>
                                <option value="">Selectionner un site</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">{{ form.errors.site_id }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Emplacement
                            </label>
                            <input v-model="form.location_description" type="text" class="input" placeholder="Ex: Hall d'entree, pres de la reception" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Langue par defaut
                            </label>
                            <select v-model="form.language" class="input">
                                <option v-for="lang in languageOptions" :key="lang.value" :value="lang.value">
                                    {{ lang.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Fonctionnalites</h2>

                    <div class="space-y-3">
                        <label class="flex items-center gap-3">
                            <input v-model="form.allow_new_rentals" type="checkbox" class="checkbox" />
                            <span class="text-gray-700 dark:text-gray-300">Permettre les nouvelles locations</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input v-model="form.allow_payments" type="checkbox" class="checkbox" />
                            <span class="text-gray-700 dark:text-gray-300">Permettre les paiements</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input v-model="form.allow_access_code_generation" type="checkbox" class="checkbox" />
                            <span class="text-gray-700 dark:text-gray-300">Generation de codes d'acces</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input v-model="form.show_prices" type="checkbox" class="checkbox" />
                            <span class="text-gray-700 dark:text-gray-300">Afficher les prix</span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input v-model="form.require_id_verification" type="checkbox" class="checkbox" />
                            <span class="text-gray-700 dark:text-gray-300">Exiger verification d'identite</span>
                        </label>
                    </div>
                </div>

                <!-- Appearance -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Apparence</h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Couleur principale
                            </label>
                            <div class="flex items-center gap-2">
                                <input v-model="form.primary_color" type="color" class="w-10 h-10 rounded cursor-pointer" />
                                <input v-model="form.primary_color" type="text" class="input flex-1" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Couleur secondaire
                            </label>
                            <div class="flex items-center gap-2">
                                <input v-model="form.secondary_color" type="color" class="w-10 h-10 rounded cursor-pointer" />
                                <input v-model="form.secondary_color" type="text" class="input flex-1" />
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Message d'accueil
                            </label>
                            <textarea v-model="form.welcome_message" class="input" rows="2" placeholder="Message affiche sur l'ecran d'accueil"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Behavior -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Comportement</h2>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Delai d'inactivite (secondes)
                            </label>
                            <input v-model.number="form.idle_timeout_seconds" type="number" min="30" max="600" class="input" />
                            <p class="mt-1 text-xs text-gray-500">30 a 600 secondes</p>
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center gap-3">
                                <input v-model="form.enable_screensaver" type="checkbox" class="checkbox" />
                                <span class="text-gray-700 dark:text-gray-300">Activer l'ecran de veille</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('tenant.kiosks.index')" class="btn-secondary">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        <span v-if="form.processing">Creation...</span>
                        <span v-else>Creer le kiosque</span>
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.checkbox { @apply w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium; }
</style>
