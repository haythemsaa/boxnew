<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, ComputerDesktopIcon, TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    kiosk: Object,
    sites: Array,
});

const form = useForm({
    name: props.kiosk.name || '',
    site_id: props.kiosk.site_id || '',
    location_description: props.kiosk.location_description || '',
    is_active: props.kiosk.is_active ?? true,
    language: props.kiosk.language || 'fr',
    allow_new_rentals: props.kiosk.allow_new_rentals ?? true,
    allow_payments: props.kiosk.allow_payments ?? true,
    allow_access_code_generation: props.kiosk.allow_access_code_generation ?? true,
    show_prices: props.kiosk.show_prices ?? true,
    require_id_verification: props.kiosk.require_id_verification ?? false,
    primary_color: props.kiosk.primary_color || '#2563eb',
    secondary_color: props.kiosk.secondary_color || '#1e40af',
    welcome_message: props.kiosk.welcome_message || '',
    idle_timeout_seconds: props.kiosk.idle_timeout_seconds || 120,
    enable_screensaver: props.kiosk.enable_screensaver ?? true,
    admin_pin: '',
});

const showDeleteModal = ref(false);

const submit = () => {
    form.put(route('tenant.kiosks.update', props.kiosk.id));
};

const regenerateCode = () => {
    if (confirm('Regenerer le code d\'appairage ? L\'ancien code ne fonctionnera plus.')) {
        router.post(route('tenant.kiosks.regenerate-code', props.kiosk.id));
    }
};

const deleteKiosk = () => {
    router.delete(route('tenant.kiosks.destroy', props.kiosk.id));
};

const languageOptions = [
    { value: 'fr', label: 'Francais' },
    { value: 'en', label: 'English' },
    { value: 'nl', label: 'Nederlands' },
    { value: 'de', label: 'Deutsch' },
];

const getStatusColor = (status) => {
    const colors = {
        online: 'bg-green-100 text-green-800',
        offline: 'bg-red-100 text-red-800',
        maintenance: 'bg-yellow-100 text-yellow-800',
        idle: 'bg-blue-100 text-blue-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        online: 'En ligne',
        offline: 'Hors ligne',
        maintenance: 'Maintenance',
        idle: 'Inactif',
    };
    return labels[status] || status;
};
</script>

<template>
    <TenantLayout :title="`Modifier ${kiosk.name}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('tenant.kiosks.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ kiosk.name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <span :class="['px-2 py-0.5 rounded-full text-xs font-medium', getStatusColor(kiosk.status)]">
                                {{ getStatusLabel(kiosk.status) }}
                            </span>
                            <span class="text-sm text-gray-500">Code: {{ kiosk.device_code }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="regenerateCode" class="btn-secondary flex items-center gap-1">
                        <ArrowPathIcon class="w-4 h-4" />
                        Nouveau code
                    </button>
                    <button @click="showDeleteModal = true" class="btn-danger flex items-center gap-1">
                        <TrashIcon class="w-4 h-4" />
                        Supprimer
                    </button>
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
                            <input v-model="form.name" type="text" class="input" required />
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
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Emplacement
                            </label>
                            <input v-model="form.location_description" type="text" class="input" />
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

                        <div class="flex items-center">
                            <label class="flex items-center gap-3">
                                <input v-model="form.is_active" type="checkbox" class="checkbox" />
                                <span class="text-gray-700 dark:text-gray-300">Kiosque actif</span>
                            </label>
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
                            <textarea v-model="form.welcome_message" class="input" rows="2"></textarea>
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
                        </div>

                        <div class="flex items-center">
                            <label class="flex items-center gap-3">
                                <input v-model="form.enable_screensaver" type="checkbox" class="checkbox" />
                                <span class="text-gray-700 dark:text-gray-300">Activer l'ecran de veille</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Security -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Securite</h2>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            PIN administrateur (4-8 chiffres)
                        </label>
                        <input v-model="form.admin_pin" type="password" class="input max-w-xs" placeholder="Laisser vide pour ne pas changer" minlength="4" maxlength="8" />
                        <p class="mt-1 text-xs text-gray-500">Utilisé pour accéder aux parametres sur la borne</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('tenant.kiosks.index')" class="btn-secondary">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing" class="btn-primary">
                        <span v-if="form.processing">Enregistrement...</span>
                        <span v-else>Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Supprimer le kiosque ?</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Cette action est irreversible. Toutes les donnees de sessions seront conservees pour les statistiques.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="btn-secondary">Annuler</button>
                    <button @click="deleteKiosk" class="btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.checkbox { @apply w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium; }
.btn-danger { @apply px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium; }
</style>
