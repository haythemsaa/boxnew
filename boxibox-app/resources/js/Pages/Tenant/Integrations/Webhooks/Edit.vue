<template>
    <TenantLayout :title="`Modifier - ${webhook.name}`">
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
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <span class="text-3xl">✏️</span>
                            Modifier le webhook
                        </h1>
                        <p class="text-gray-600 mt-1">{{ webhook.name }}</p>
                    </div>
                    <button
                        @click="showDeleteModal = true"
                        class="px-4 py-2 text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-xl border p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">{{ webhook.successful_calls || 0 }}</p>
                    <p class="text-sm text-gray-500">Appels reussis</p>
                </div>
                <div class="bg-white rounded-xl border p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ webhook.failed_calls || 0 }}</p>
                    <p class="text-sm text-gray-500">Echecs</p>
                </div>
                <div class="bg-white rounded-xl border p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900">{{ webhook.average_response_time || 0 }}ms</p>
                    <p class="text-sm text-gray-500">Temps moyen</p>
                </div>
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
                                class="w-full px-4 py-2.5 border rounded-lg font-mono text-sm focus:ring-primary-500 focus:border-primary-500"
                                :class="{ 'border-red-300': errors.url }"
                            />
                            <p v-if="errors.url" class="mt-1 text-sm text-red-500">{{ errors.url }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                v-model="form.description"
                                rows="2"
                                class="w-full px-4 py-2.5 border rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Events Selection -->
                <div class="bg-white rounded-xl border p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-xl">📡</span>
                        Evenements
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
                                    :value="showSecret ? form.secret : '••••••••••••••••••••••••••••••'"
                                    type="text"
                                    readonly
                                    class="flex-1 px-4 py-2.5 border rounded-lg bg-gray-50 font-mono text-sm"
                                />
                                <button
                                    type="button"
                                    @click="showSecret = !showSecret"
                                    class="px-4 py-2.5 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    <svg v-if="!showSecret" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    @click="regenerateSecret"
                                    class="px-4 py-2.5 bg-amber-100 text-amber-700 rounded-lg hover:bg-amber-200 transition-colors text-sm font-medium"
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
                            <p class="mt-1 text-xs text-amber-600">
                                ⚠️ La regeneration invalidera les anciennes signatures
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Headers personnalises
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
                                <p class="text-sm text-gray-500">Activer/desactiver ce webhook</p>
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
                        {{ submitting ? 'Enregistrement...' : 'Enregistrer' }}
                    </button>
                </div>
            </form>

            <!-- Delete Modal -->
            <Teleport to="body">
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-2xl max-w-md w-full p-6">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-3xl">🗑️</span>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">Supprimer ce webhook ?</h3>
                            <p class="text-gray-500 mb-6">
                                Cette action est irreversible. Toutes les donnees associees seront supprimees.
                            </p>
                            <div class="flex gap-3">
                                <button
                                    @click="showDeleteModal = false"
                                    class="flex-1 px-4 py-2.5 border rounded-lg font-medium hover:bg-gray-50"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="deleteWebhook"
                                    :disabled="deleting"
                                    class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 disabled:opacity-50"
                                >
                                    {{ deleting ? 'Suppression...' : 'Supprimer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    webhook: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
});

// Parse headers from object to array
const parseHeaders = (headers) => {
    if (!headers || typeof headers !== 'object') return [];
    return Object.entries(headers).map(([key, value]) => ({ key, value }));
};

const form = reactive({
    name: props.webhook.name,
    url: props.webhook.url,
    description: props.webhook.description || '',
    secret: props.webhook.secret,
    events: props.webhook.events || [],
    headers: parseHeaders(props.webhook.headers),
    is_active: props.webhook.is_active,
    timeout: props.webhook.timeout || 30,
    retry_count: props.webhook.retry_count || 3,
});

const submitting = ref(false);
const deleting = ref(false);
const showDeleteModal = ref(false);
const showSecret = ref(false);
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

function regenerateSecret() {
    if (confirm('Regenerer le secret ? Les anciennes signatures ne seront plus valides.')) {
        form.secret = generateRandomSecret();
        showSecret.value = true;
    }
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

    const headers = form.headers
        .filter(h => h.key && h.value)
        .reduce((acc, h) => ({ ...acc, [h.key]: h.value }), {});

    router.put(`/tenant/integrations/webhooks/${props.webhook.id}`, {
        ...form,
        headers: Object.keys(headers).length > 0 ? headers : null,
    }, {
        onFinish: () => {
            submitting.value = false;
        },
    });
}

function deleteWebhook() {
    deleting.value = true;
    router.delete(`/tenant/integrations/webhooks/${props.webhook.id}`, {
        onFinish: () => {
            deleting.value = false;
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
.bg-primary-50 {
    background-color: rgba(143, 189, 86, 0.1);
}
.border-primary-300 {
    border-color: rgba(143, 189, 86, 0.5);
}
</style>
