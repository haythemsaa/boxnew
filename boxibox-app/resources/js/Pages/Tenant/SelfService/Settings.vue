<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    tenant: Object,
    sites: Array,
})

const form = useForm({
    self_service_enabled: props.tenant.self_service_enabled,
    settings: {
        access_hours: props.tenant.self_service_settings?.access_hours || { start: '06:00', end: '22:00' },
        '24h_access': props.tenant.self_service_settings?.['24h_access'] || false,
        require_pin: props.tenant.self_service_settings?.require_pin ?? true,
        require_qr: props.tenant.self_service_settings?.require_qr ?? true,
        auto_generate_access_code: props.tenant.self_service_settings?.auto_generate_access_code ?? true,
        access_code_validity_hours: props.tenant.self_service_settings?.access_code_validity_hours || 24,
        allow_guest_access: props.tenant.self_service_settings?.allow_guest_access || false,
        max_guests_per_customer: props.tenant.self_service_settings?.max_guests_per_customer || 2,
        notification_on_entry: props.tenant.self_service_settings?.notification_on_entry ?? true,
        notification_on_exit: props.tenant.self_service_settings?.notification_on_exit ?? true,
    }
})

const editingSite = ref(null)
const siteForm = reactive({
    self_service_enabled: false,
    gate_system_type: '',
    gate_api_endpoint: '',
    gate_api_key: '',
    access_hours: null,
})

const submit = () => {
    form.post(route('tenant.self-service.settings.update'), {
        preserveScroll: true,
    })
}

const editSite = (site) => {
    editingSite.value = site
    siteForm.self_service_enabled = site.self_service_enabled
    siteForm.gate_system_type = site.gate_system_type || ''
    siteForm.gate_api_endpoint = site.gate_api_endpoint || ''
    siteForm.gate_api_key = site.gate_api_key || ''
    siteForm.access_hours = site.access_hours
}

const saveSite = () => {
    router.put(route('tenant.self-service.sites.settings.update', editingSite.value.id), siteForm, {
        preserveScroll: true,
        onSuccess: () => {
            editingSite.value = null
        }
    })
}

const gateSystemTypes = [
    { value: 'manual', label: 'Manuel' },
    { value: 'keypad', label: 'Clavier à code' },
    { value: 'qr_scanner', label: 'Scanner QR' },
    { value: 'smart_lock', label: 'Serrure connectée' },
    { value: 'rfid', label: 'RFID' },
]
</script>

<template>
    <Head title="Paramètres Self-Service" />

    <TenantLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Paramètres Self-Service</h2>
                    <p class="text-sm text-gray-600 mt-1">Configuration de l'accès automatique pour vos clients</p>
                </div>
                <Link
                    :href="route('tenant.self-service.index')"
                    class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                >
                    Retour
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Main Settings -->
                <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Paramètres globaux</h3>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Enable/Disable -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Activer le mode Self-Service</h4>
                                <p class="text-sm text-gray-500">Permettre aux clients d'accéder aux sites sans intervention humaine</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.self_service_enabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            </label>
                        </div>

                        <!-- Access Hours -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heures d'accès - Début</label>
                                <input
                                    type="time"
                                    v-model="form.settings.access_hours.start"
                                    :disabled="form.settings['24h_access']"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100"
                                >
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heures d'accès - Fin</label>
                                <input
                                    type="time"
                                    v-model="form.settings.access_hours.end"
                                    :disabled="form.settings['24h_access']"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent disabled:bg-gray-100"
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                id="24h_access"
                                v-model="form.settings['24h_access']"
                                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                            >
                            <label for="24h_access" class="text-sm text-gray-700">Accès 24h/24</label>
                        </div>

                        <!-- Access Methods -->
                        <div class="border-t pt-6">
                            <h4 class="font-medium text-gray-900 mb-4">Méthodes d'accès</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="require_pin"
                                        v-model="form.settings.require_pin"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="require_pin" class="text-sm text-gray-700">Code PIN (4-6 chiffres)</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="require_qr"
                                        v-model="form.settings.require_qr"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="require_qr" class="text-sm text-gray-700">QR Code</label>
                                </div>
                            </div>
                        </div>

                        <!-- Auto-generation -->
                        <div class="border-t pt-6">
                            <h4 class="font-medium text-gray-900 mb-4">Génération automatique</h4>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="auto_generate"
                                        v-model="form.settings.auto_generate_access_code"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="auto_generate" class="text-sm text-gray-700">Générer automatiquement les codes à la création du contrat</label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Validité des codes temporaires (heures)</label>
                                    <input
                                        type="number"
                                        v-model="form.settings.access_code_validity_hours"
                                        min="1"
                                        max="720"
                                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Guest Access -->
                        <div class="border-t pt-6">
                            <h4 class="font-medium text-gray-900 mb-4">Accès invités</h4>
                            <div class="space-y-4">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="allow_guest"
                                        v-model="form.settings.allow_guest_access"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="allow_guest" class="text-sm text-gray-700">Autoriser les clients à créer des codes invités</label>
                                </div>
                                <div v-if="form.settings.allow_guest_access">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre max d'invités par client</label>
                                    <input
                                        type="number"
                                        v-model="form.settings.max_guests_per_customer"
                                        min="0"
                                        max="10"
                                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="border-t pt-6">
                            <h4 class="font-medium text-gray-900 mb-4">Notifications</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="notif_entry"
                                        v-model="form.settings.notification_on_entry"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="notif_entry" class="text-sm text-gray-700">Notifier le client à chaque entrée</label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="notif_exit"
                                        v-model="form.settings.notification_on_exit"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="notif_exit" class="text-sm text-gray-700">Notifier le client à chaque sortie</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </div>
                </form>

                <!-- Sites Configuration -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-lg font-semibold text-gray-900">Configuration par site</h3>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div v-for="site in sites" :key="site.id" class="p-6">
                            <div v-if="editingSite?.id !== site.id" class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div :class="[
                                        'w-3 h-3 rounded-full',
                                        site.self_service_enabled ? 'bg-green-500' : 'bg-gray-300'
                                    ]"></div>
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ site.name }}</h4>
                                        <p class="text-sm text-gray-500">
                                            {{ site.code }} -
                                            {{ site.gate_system_type || 'Non configuré' }}
                                        </p>
                                    </div>
                                </div>
                                <button
                                    @click="editSite(site)"
                                    class="px-4 py-2 text-sm text-indigo-600 hover:bg-indigo-50 rounded-lg transition"
                                >
                                    Configurer
                                </button>
                            </div>

                            <!-- Edit Form -->
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-gray-900">{{ site.name }}</h4>
                                    <button
                                        @click="editingSite = null"
                                        class="text-gray-400 hover:text-gray-600"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="site_enabled"
                                        v-model="siteForm.self_service_enabled"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                    >
                                    <label for="site_enabled" class="text-sm text-gray-700">Self-service activé pour ce site</label>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Type de système de portail</label>
                                    <select
                                        v-model="siteForm.gate_system_type"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="">Sélectionner...</option>
                                        <option v-for="type in gateSystemTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">URL de l'API du portail</label>
                                    <input
                                        type="url"
                                        v-model="siteForm.gate_api_endpoint"
                                        placeholder="https://api.gate-system.com/v1"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Clé API du portail</label>
                                    <input
                                        type="password"
                                        v-model="siteForm.gate_api_key"
                                        placeholder="••••••••••••"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                </div>

                                <div class="flex justify-end gap-3 pt-4">
                                    <button
                                        @click="editingSite = null"
                                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        @click="saveSite"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                    >
                                        Enregistrer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
