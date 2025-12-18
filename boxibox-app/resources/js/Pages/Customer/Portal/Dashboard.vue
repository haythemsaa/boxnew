<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-6 text-white">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold">Bonjour, {{ customer.name }} !</h1>
                        <p class="text-blue-100 mt-1">Bienvenue sur votre espace client</p>
                    </div>
                    <div class="flex gap-3">
                        <Link
                            :href="route('customer.portal.support.index')"
                            class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition"
                        >
                            <i class="fas fa-headset mr-2"></i>
                            Contacter le support
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-file-contract text-blue-600 dark:text-blue-400 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_contracts }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Contrats actifs</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-euro-sign text-green-600 dark:text-green-400 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_monthly) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Loyer mensuel</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div :class="stats.pending_invoices > 0 ? 'bg-orange-100 dark:bg-orange-900/50' : 'bg-gray-100 dark:bg-gray-700'" class="w-12 h-12 rounded-xl flex items-center justify-center">
                            <i :class="stats.pending_invoices > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500'" class="fas fa-file-invoice text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending_invoices }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Factures en attente</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div :class="stats.pending_amount > 0 ? 'bg-red-100 dark:bg-red-900/50' : 'bg-gray-100 dark:bg-gray-700'" class="w-12 h-12 rounded-xl flex items-center justify-center">
                            <i :class="stats.pending_amount > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500'" class="fas fa-wallet text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.pending_amount) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Montant dû</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Contracts Section -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Active Contracts -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-box mr-2 text-blue-600"></i>
                                Mes boxes de stockage
                            </h2>
                            <Link
                                :href="route('customer.portal.contracts')"
                                class="text-sm text-blue-600 hover:text-blue-700"
                            >
                                Voir tout
                            </Link>
                        </div>
                        <div v-if="contracts.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="contract in contracts"
                                :key="contract.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-purple-500 rounded-xl flex items-center justify-center text-white font-bold">
                                            {{ contract.box_code }}
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                                Box {{ contract.box_code }}
                                            </h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <i class="fas fa-map-marker-alt mr-1"></i>
                                                {{ contract.site_name }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Depuis le {{ contract.start_date }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-gray-900 dark:text-white">
                                            {{ formatCurrency(contract.monthly_price) }}/mois
                                        </div>
                                        <span
                                            :class="getStatusClass(contract.status)"
                                            class="px-2 py-1 text-xs rounded-full"
                                        >
                                            {{ getStatusLabel(contract.status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-box-open text-3xl mb-3"></i>
                            <p>Aucun contrat actif</p>
                        </div>
                    </div>

                    <!-- Recent Invoices -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h2 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-file-invoice mr-2 text-green-600"></i>
                                Dernières factures
                            </h2>
                            <Link
                                :href="route('customer.portal.invoices')"
                                class="text-sm text-blue-600 hover:text-blue-700"
                            >
                                Voir tout
                            </Link>
                        </div>
                        <div v-if="invoices.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="invoice in invoices"
                                :key="invoice.id"
                                class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            >
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">
                                        {{ invoice.invoice_number }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Échéance: {{ invoice.due_date }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <div class="font-semibold text-gray-900 dark:text-white">
                                            {{ formatCurrency(invoice.total) }}
                                        </div>
                                        <span
                                            :class="getInvoiceStatusClass(invoice)"
                                            class="px-2 py-0.5 text-xs rounded-full"
                                        >
                                            {{ getInvoiceStatusLabel(invoice) }}
                                        </span>
                                    </div>
                                    <Link
                                        v-if="invoice.status === 'pending'"
                                        :href="route('customer.portal.invoice', invoice.id)"
                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700"
                                    >
                                        Payer
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-gray-500 dark:text-gray-400">
                            <i class="fas fa-check-circle text-3xl mb-3 text-green-500"></i>
                            <p>Aucune facture</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Actions rapides</h3>
                        <div class="space-y-2">
                            <Link
                                v-if="stats.pending_invoices > 0"
                                :href="route('customer.portal.invoices', { status: 'pending' })"
                                class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/50 transition"
                            >
                                <i class="fas fa-credit-card"></i>
                                <span>Payer mes factures</span>
                            </Link>
                            <button
                                @click="showRequestModal = true; requestType = 'maintenance'"
                                class="w-full flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                            >
                                <i class="fas fa-tools"></i>
                                <span>Signaler un problème</span>
                            </button>
                            <button
                                @click="showRequestModal = true; requestType = 'box_change'"
                                class="w-full flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                            >
                                <i class="fas fa-exchange-alt"></i>
                                <span>Changer de box</span>
                            </button>
                            <Link
                                :href="route('customer.portal.support.index')"
                                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                            >
                                <i class="fas fa-question-circle"></i>
                                <span>Aide & Support</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Access Codes -->
                    <div v-if="accessCodes.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-key mr-2 text-yellow-500"></i>
                            Mes codes d'accès
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="code in accessCodes"
                                :key="code.box_code"
                                class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">{{ code.site_name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">Box {{ code.box_code }}</div>
                                    </div>
                                    <button
                                        @click="showAccessCode(code)"
                                        class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200"
                                    >
                                        <i class="fas fa-eye mr-1"></i>
                                        Voir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Info -->
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/30 dark:to-purple-900/30 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Besoin d'aide ?</h3>
                        <div class="space-y-2 text-sm">
                            <a href="tel:+33123456789" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-blue-600">
                                <i class="fas fa-phone w-5"></i>
                                01 23 45 67 89
                            </a>
                            <a href="mailto:support@boxibox.fr" class="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-blue-600">
                                <i class="fas fa-envelope w-5"></i>
                                support@boxibox.fr
                            </a>
                            <p class="text-gray-500 dark:text-gray-400 pt-2">
                                Lun-Ven: 9h-18h<br>
                                Sam: 9h-12h
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Modal -->
        <div v-if="showRequestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-lg">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ requestType === 'maintenance' ? 'Signaler un problème' : requestType === 'box_change' ? 'Demande de changement de box' : 'Nouvelle demande' }}
                    </h3>
                    <button @click="showRequestModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="submitRequest" class="p-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Contrat concerné
                        </label>
                        <select
                            v-model="requestForm.contract_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="">Sélectionnez un contrat</option>
                            <option v-for="c in contracts" :key="c.id" :value="c.id">
                                Box {{ c.box_code }} - {{ c.site_name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Sujet *
                        </label>
                        <input
                            v-model="requestForm.subject"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description *
                        </label>
                        <textarea
                            v-model="requestForm.description"
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            @click="showRequestModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        >
                            Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    customer: Object,
    contracts: Array,
    invoices: Array,
    stats: Object,
    accessCodes: Array,
    settings: Object,
});

const showRequestModal = ref(false);
const requestType = ref('general');
const requestForm = ref({
    contract_id: '',
    subject: '',
    description: '',
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0);
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        terminated: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status] || classes.pending;
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        pending: 'En attente',
        terminated: 'Terminé',
    };
    return labels[status] || status;
};

const getInvoiceStatusClass = (invoice) => {
    if (invoice.is_overdue) {
        return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
    }
    const classes = {
        paid: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
    };
    return classes[invoice.status] || 'bg-gray-100 text-gray-700';
};

const getInvoiceStatusLabel = (invoice) => {
    if (invoice.is_overdue) return 'En retard';
    const labels = {
        paid: 'Payée',
        pending: 'En attente',
    };
    return labels[invoice.status] || invoice.status;
};

const showAccessCode = (code) => {
    alert(`Code d'accès pour ${code.site_name}\nBox: ${code.box_code}\nCode: ${code.access_code}\n\nGardez ce code confidentiel.`);
};

const submitRequest = () => {
    router.post(route('customer.portal.submit-request'), {
        type: requestType.value,
        ...requestForm.value,
    }, {
        onSuccess: () => {
            showRequestModal.value = false;
            requestForm.value = { contract_id: '', subject: '', description: '' };
        }
    });
};
</script>
