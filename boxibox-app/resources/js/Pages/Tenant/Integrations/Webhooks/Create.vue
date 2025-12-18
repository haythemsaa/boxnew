<template>
    <TenantLayout title="Nouveau Webhook">
        <div class="p-6 max-w-3xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <Link
                    href="/tenant/integrations/webhooks"
                    class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Retour aux webhooks
                </Link>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="text-3xl">🔗</span>
                    Nouveau Webhook
                </h1>
                <p class="text-gray-600 mt-1">
                    Configurez un endpoint pour recevoir les evenements BoxiBox
                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Info -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-xl">📝</span>
                        Informations generales
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nom du webhook *
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Ex: Synchronisation CRM"
                                class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-300': errors.name }"
                            />
                            <p v-if="errors.name" class="mt-1 text-sm text-red-500">{{ errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                URL de destination *
                            </label>
                            <input
                                v-model="form.url"
                                type="url"
                                required
                                placeholder="https://votre-api.com/webhook"
                                class="w-full px-4 py-2.5 border rounded-lg font-mono text-sm focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-300': errors.url }"
                            />
                            <p v-if="errors.url" class="mt-1 text-sm text-red-500">{{ errors.url }}</p>
                            <p class="mt-1 text-xs text-gray-500">L'URL doit etre accessible publiquement et supporter HTTPS</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Description (optionnel)
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                placeholder="Decrivez l'usage de ce webhook..."
                                class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Events Selection -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-xl">📡</span>
                        Evenements a ecouter
                    </h3>

                    <div class="mb-4">
                        <label class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100">
                            <input
                                type="checkbox"
                                v-model="selectAll"
                                @change="toggleSelectAll"
                                class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                            />
                            <span class="font-medium text-gray-900">Tous les evenements</span>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <div v-for="(events, category) in eventCategories" :key="category">
                            <h4 class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-2">
                                <span>{{ getCategoryIcon(category) }}</span>
                                {{ getCategoryLabel(category) }}
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                <label
                                    v-for="event in events"
                                    :key="event.value"
                                    class="flex items-center gap-3 p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors"
                                    :class="{ 'border-primary-300 bg-primary-50': form.events.includes(event.value) }"
                                >
                                    <input
                                        type="checkbox"
                                        :value="event.value"
                                        v-model="form.events"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    />
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">{{ event.label }}</span>
                                        <p class="text-xs text-gray-500">{{ event.description }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <p v-if="errors.events" class="mt-2 text-sm text-red-500">{{ errors.events }}</p>
                </div>

                <!-- Security -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-xl">🔐</span>
                        Securite
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Secret de signature
                            </label>
                            <div class="flex gap-2">
                                <input
                                    v-model="form.secret"
                                    type="text"
                                    readonly
                                    class="flex-1 px-4 py-2.5 border rounded-lg bg-gray-50 font-mono text-sm"
                                />
                                <button
                                    type="button"
                                    @click="generateSecret"
                                    class="px-4 py-2.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium"
                                >
                                    Regenerer
                                </button>
                                <button
                                    type="button"
                                    @click="copySecret"
                                    class="px-4 py-2.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                Utilisez ce secret pour verifier l'authenticite des requetes (signature HMAC-SHA256)
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Headers personnalises (optionnel)
                            </label>
                            <div class="space-y-2">
                                <div v-for="(header, index) in form.headers" :key="index" class="flex gap-2">
                                    <input
                                        v-model="header.key"
                                        type="text"
                                        placeholder="Header-Name"
                                        class="flex-1 px-3 py-2 border rounded-lg text-sm"
                                    />
                                    <input
                                        v-model="header.value"
                                        type="text"
                                        placeholder="Valeur"
                                        class="flex-1 px-3 py-2 border rounded-lg text-sm"
                                    />
                                    <button
                                        type="button"
                                        @click="removeHeader(index)"
                                        class="px-3 py-2 text-red-500 hover:bg-red-50 rounded-lg"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="addHeader"
                                class="mt-2 text-sm text-primary-600 hover:text-primary-700 font-medium"
                            >
                                + Ajouter un header
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-xl">⚙️</span>
                        Parametres avances
                    </h3>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <span class="font-medium text-gray-900">Webhook actif</span>
                                <p class="text-sm text-gray-500">Activer immediatement ce webhook</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Timeout (secondes)
                                </label>
                                <input
                                    v-model.number="form.timeout"
                                    type="number"
                                    min="5"
                                    max="60"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre de tentatives
                                </label>
                                <input
                                    v-model.number="form.retry_count"
                                    type="number"
                                    min="0"
                                    max="5"
                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        href="/tenant/integrations/webhooks"
                        class="px-6 py-2.5 border rounded-lg font-medium hover:bg-gray-50 transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="px-6 py-2.5 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition-colors disabled:opacity-50"
                    >
                        {{ submitting ? 'Creation...' : 'Creer le webhook' }}
                    </button>
                </div>
            </form>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = reactive({
    name: '',
    url: '',
    description: '',
    secret: generateRandomSecret(),
    events: [],
    headers: [],
    is_active: true,
    timeout: 30,
    retry_count: 3,
});

const submitting = ref(false);
const selectAll = ref(false);

const eventCategories = {
    contracts: [
        { value: 'contract.created', label: 'Contrat cree', description: 'Nouveau contrat signe' },
        { value: 'contract.updated', label: 'Contrat modifie', description: 'Mise a jour d\'un contrat' },
        { value: 'contract.terminated', label: 'Contrat resilie', description: 'Fin d\'un contrat' },
        { value: 'contract.renewed', label: 'Contrat renouvele', description: 'Renouvellement automatique' },
    ],
    customers: [
        { value: 'customer.created', label: 'Client cree', description: 'Nouveau client enregistre' },
        { value: 'customer.updated', label: 'Client modifie', description: 'Mise a jour des infos client' },
        { value: 'customer.deleted', label: 'Client supprime', description: 'Suppression d\'un client' },
    ],
    invoices: [
        { value: 'invoice.created', label: 'Facture creee', description: 'Nouvelle facture generee' },
        { value: 'invoice.paid', label: 'Facture payee', description: 'Paiement recu' },
        { value: 'invoice.overdue', label: 'Facture en retard', description: 'Depassement de l\'echeance' },
        { value: 'invoice.cancelled', label: 'Facture annulee', description: 'Annulation d\'une facture' },
    ],
    payments: [
        { value: 'payment.received', label: 'Paiement recu', description: 'Nouveau paiement enregistre' },
        { value: 'payment.failed', label: 'Paiement echoue', description: 'Echec du prelevement' },
        { value: 'payment.refunded', label: 'Remboursement', description: 'Remboursement effectue' },
    ],
    boxes: [
        { value: 'box.reserved', label: 'Box reserve', description: 'Reservation d\'un box' },
        { value: 'box.released', label: 'Box libere', description: 'Liberation d\'un box' },
        { value: 'box.maintenance', label: 'Box en maintenance', description: 'Mise en maintenance' },
    ],
    bookings: [
        { value: 'booking.created', label: 'Reservation creee', description: 'Nouvelle reservation' },
        { value: 'booking.confirmed', label: 'Reservation confirmee', description: 'Confirmation de reservation' },
        { value: 'booking.cancelled', label: 'Reservation annulee', description: 'Annulation' },
    ],
};

const allEvents = computed(() => {
    return Object.values(eventCategories).flat().map(e => e.value);
});

function generateRandomSecret() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = 'whsec_';
    for (let i = 0; i < 32; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function generateSecret() {
    form.secret = generateRandomSecret();
}

function copySecret() {
    navigator.clipboard.writeText(form.secret);
    alert('Secret copie dans le presse-papier');
}

function toggleSelectAll() {
    if (selectAll.value) {
        form.events = [...allEvents.value];
    } else {
        form.events = [];
    }
}

function getCategoryIcon(category) {
    const icons = {
        contracts: '📄',
        customers: '👥',
        invoices: '💰',
        payments: '💳',
        boxes: '📦',
        bookings: '📅',
    };
    return icons[category] || '📋';
}

function getCategoryLabel(category) {
    const labels = {
        contracts: 'Contrats',
        customers: 'Clients',
        invoices: 'Factures',
        payments: 'Paiements',
        boxes: 'Boxes',
        bookings: 'Reservations',
    };
    return labels[category] || category;
}

function addHeader() {
    form.headers.push({ key: '', value: '' });
}

function removeHeader(index) {
    form.headers.splice(index, 1);
}

function submit() {
    submitting.value = true;

    // Filter out empty headers
    const headers = form.headers
        .filter(h => h.key && h.value)
        .reduce((acc, h) => ({ ...acc, [h.key]: h.value }), {});

    router.post('/tenant/integrations/webhooks', {
        ...form,
        headers: Object.keys(headers).length > 0 ? headers : null,
    }, {
        onFinish: () => {
            submitting.value = false;
        },
    });
}
</script>

<style scoped>
.bg-primary-600 {
    background-color: #8FBD56;
}
.bg-primary-700 {
    background-color: #7aa74a;
}
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
.peer-checked\:bg-primary-600:checked ~ .peer {
    background-color: #8FBD56;
}
.bg-primary-50 {
    background-color: rgba(143, 189, 86, 0.1);
}
.border-primary-300 {
    border-color: rgba(143, 189, 86, 0.5);
}
</style>
