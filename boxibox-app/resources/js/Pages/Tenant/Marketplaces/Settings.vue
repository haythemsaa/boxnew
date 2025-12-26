<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import { ArrowLeftIcon, Cog6ToothIcon, CheckCircleIcon, XCircleIcon, ArrowPathIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    integrations: Array,
    platforms: Object,
});

const selectedPlatform = ref(null);
const showDeleteModal = ref(false);
const integrationToDelete = ref(null);

const form = useForm({
    platform: '',
    is_active: true,
    platform_account_id: '',
    api_key: '',
    api_secret: '',
    auto_sync_inventory: true,
    auto_sync_prices: true,
    auto_accept_leads: false,
    sync_interval_minutes: 60,
    price_markup_percent: 0,
    commission_percent: 0,
    lead_cost: 0,
});

const selectPlatform = (platform) => {
    selectedPlatform.value = platform;

    // Check if we have existing integration for this platform
    const existing = props.integrations?.find(i => i.platform === platform);

    if (existing) {
        form.platform = existing.platform;
        form.is_active = existing.is_active;
        form.platform_account_id = existing.platform_account_id || '';
        form.api_key = existing.api_key || '';
        form.api_secret = existing.api_secret || '';
        form.auto_sync_inventory = existing.auto_sync_inventory ?? true;
        form.auto_sync_prices = existing.auto_sync_prices ?? true;
        form.auto_accept_leads = existing.auto_accept_leads ?? false;
        form.sync_interval_minutes = existing.sync_interval_minutes || 60;
        form.price_markup_percent = existing.price_markup_percent || 0;
        form.commission_percent = existing.commission_percent || 0;
        form.lead_cost = existing.lead_cost || 0;
    } else {
        form.reset();
        form.platform = platform;
    }
};

const saveIntegration = () => {
    form.post(route('tenant.marketplaces.integrations.save'), {
        onSuccess: () => {
            selectedPlatform.value = null;
        }
    });
};

const testConnection = (integration) => {
    router.post(route('tenant.marketplaces.integrations.test', integration.id));
};

const syncInventory = (integration) => {
    router.post(route('tenant.marketplaces.integrations.sync', integration.id));
};

const confirmDelete = (integration) => {
    integrationToDelete.value = integration;
    showDeleteModal.value = true;
};

const deleteIntegration = () => {
    if (integrationToDelete.value) {
        router.delete(route('tenant.marketplaces.integrations.delete', integrationToDelete.value.id), {
            onSuccess: () => {
                showDeleteModal.value = false;
                integrationToDelete.value = null;
            }
        });
    }
};

const getIntegrationForPlatform = (platform) => {
    return props.integrations?.find(i => i.platform === platform);
};

const platformList = computed(() => {
    return Object.entries(props.platforms || {}).map(([key, data]) => ({
        key,
        ...data
    }));
});
</script>

<template>
    <TenantLayout title="Parametres Marketplaces">
        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('tenant.marketplaces.index')" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <ArrowLeftIcon class="w-5 h-5 text-gray-500" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parametres des Marketplaces</h1>
                    <p class="text-gray-600 dark:text-gray-400">Configurez vos integrations avec les plateformes de listing</p>
                </div>
            </div>

            <!-- Active Integrations -->
            <div v-if="integrations?.length" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Integrations actives</h2>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="integration in integrations" :key="integration.id" class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                    <span class="text-xl">{{ platforms[integration.platform]?.icon || '🔗' }}</span>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ platforms[integration.platform]?.name || integration.platform }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span v-if="integration.is_active" class="flex items-center gap-1 text-sm text-green-600">
                                            <CheckCircleIcon class="w-4 h-4" />
                                            Actif
                                        </span>
                                        <span v-else class="flex items-center gap-1 text-sm text-gray-500">
                                            <XCircleIcon class="w-4 h-4" />
                                            Inactif
                                        </span>
                                        <span v-if="integration.last_sync_at" class="text-sm text-gray-500">
                                            Derniere sync: {{ new Date(integration.last_sync_at).toLocaleDateString('fr-FR') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="testConnection(integration)" class="btn-sm btn-secondary" title="Tester la connexion">
                                    <CheckCircleIcon class="w-4 h-4" />
                                </button>
                                <button @click="syncInventory(integration)" class="btn-sm btn-secondary" title="Synchroniser">
                                    <ArrowPathIcon class="w-4 h-4" />
                                </button>
                                <button @click="selectPlatform(integration.platform)" class="btn-sm btn-primary">
                                    <Cog6ToothIcon class="w-4 h-4" />
                                    Configurer
                                </button>
                                <button @click="confirmDelete(integration)" class="btn-sm btn-danger" title="Supprimer">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Platforms -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">Plateformes disponibles</h2>
                </div>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
                    <div v-for="platform in platformList" :key="platform.key"
                         @click="selectPlatform(platform.key)"
                         class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:border-blue-500 cursor-pointer transition group">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl">{{ platform.icon || '🔗' }}</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600">
                                    {{ platform.name }}
                                </p>
                                <span v-if="getIntegrationForPlatform(platform.key)" class="text-xs text-green-600">Configure</span>
                                <span v-else class="text-xs text-gray-500">Non configure</span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500">{{ platform.description || 'Plateforme de listing' }}</p>
                    </div>
                </div>
            </div>

            <!-- Configuration Modal -->
            <div v-if="selectedPlatform" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 overflow-y-auto py-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-2xl w-full mx-4 shadow-xl">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Configurer {{ platforms[selectedPlatform]?.name || selectedPlatform }}
                        </h3>
                        <button @click="selectedPlatform = null" class="text-gray-500 hover:text-gray-700">
                            <XCircleIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <form @submit.prevent="saveIntegration" class="p-6 space-y-6">
                        <!-- Status -->
                        <div>
                            <label class="flex items-center gap-3">
                                <input v-model="form.is_active" type="checkbox" class="checkbox" />
                                <span class="font-medium text-gray-700 dark:text-gray-300">Integration active</span>
                            </label>
                        </div>

                        <!-- API Credentials -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Identifiants API</h4>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    ID de compte
                                </label>
                                <input v-model="form.platform_account_id" type="text" class="input" placeholder="Votre ID de compte sur la plateforme" />
                            </div>

                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Cle API
                                    </label>
                                    <input v-model="form.api_key" type="password" class="input" placeholder="Cle API" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Secret API
                                    </label>
                                    <input v-model="form.api_secret" type="password" class="input" placeholder="Secret API" />
                                </div>
                            </div>
                        </div>

                        <!-- Sync Settings -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Synchronisation</h4>

                            <div class="space-y-3">
                                <label class="flex items-center gap-3">
                                    <input v-model="form.auto_sync_inventory" type="checkbox" class="checkbox" />
                                    <span class="text-gray-700 dark:text-gray-300">Synchroniser automatiquement l'inventaire</span>
                                </label>

                                <label class="flex items-center gap-3">
                                    <input v-model="form.auto_sync_prices" type="checkbox" class="checkbox" />
                                    <span class="text-gray-700 dark:text-gray-300">Synchroniser automatiquement les prix</span>
                                </label>

                                <label class="flex items-center gap-3">
                                    <input v-model="form.auto_accept_leads" type="checkbox" class="checkbox" />
                                    <span class="text-gray-700 dark:text-gray-300">Accepter automatiquement les leads</span>
                                </label>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Intervalle de synchronisation (minutes)
                                </label>
                                <input v-model.number="form.sync_interval_minutes" type="number" min="15" max="1440" class="input max-w-xs" />
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">Tarification</h4>

                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Majoration prix (%)
                                    </label>
                                    <input v-model.number="form.price_markup_percent" type="number" min="-50" max="100" class="input" />
                                    <p class="mt-1 text-xs text-gray-500">Ajustement des prix affiches</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Commission plateforme (%)
                                    </label>
                                    <input v-model.number="form.commission_percent" type="number" min="0" max="100" class="input" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Cout par lead (EUR)
                                    </label>
                                    <input v-model.number="form.lead_cost" type="number" min="0" step="0.01" class="input" />
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" @click="selectedPlatform = null" class="btn-secondary">
                                Annuler
                            </button>
                            <button type="submit" :disabled="form.processing" class="btn-primary">
                                <span v-if="form.processing">Enregistrement...</span>
                                <span v-else>Enregistrer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Modal -->
            <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        Supprimer l'integration ?
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Cette action est irreversible. Les leads et statistiques seront conserves.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button @click="showDeleteModal = false" class="btn-secondary">Annuler</button>
                        <button @click="deleteIntegration" class="btn-danger">Supprimer</button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500; }
.checkbox { @apply w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500; }
.btn-sm { @apply px-3 py-1.5 text-sm rounded-lg font-medium transition inline-flex items-center gap-1; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 font-medium; }
.btn-secondary { @apply px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 font-medium; }
.btn-danger { @apply px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium; }
</style>
