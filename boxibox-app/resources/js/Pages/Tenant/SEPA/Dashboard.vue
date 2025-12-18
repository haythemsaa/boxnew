<template>
    <TenantLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Prelevements SEPA</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gerez les mandats et prelevements automatiques</p>
                </div>
                <button
                    @click="showSetupModal = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    <i class="fas fa-plus mr-2"></i>Nouveau mandat
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.active_mandates }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Mandats actifs</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/50 flex items-center justify-center">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.pending_mandates }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">En attente</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                            <i class="fas fa-euro-sign text-blue-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_collected) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Total preleve</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center">
                            <i class="fas fa-hourglass-half text-purple-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.pending_payments) }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">En cours</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.failed_payments_30d }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Echoues (30j)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Mandates List -->
                <div class="lg:col-span-2">
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Mandats SEPA</h2>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div
                                v-for="mandate in mandates.data"
                                :key="mandate.id"
                                class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div :class="[
                                            'w-10 h-10 rounded-full flex items-center justify-center',
                                            mandate.status === 'active' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-gray-100 dark:bg-gray-700'
                                        ]">
                                            <i :class="[
                                                'fas',
                                                mandate.status === 'active' ? 'fa-check text-green-600' : 'fa-clock text-gray-500'
                                            ]"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ mandate.customer?.first_name }} {{ mandate.customer?.last_name }}
                                                <span v-if="mandate.customer?.company_name" class="text-gray-500">
                                                    ({{ mandate.customer.company_name }})
                                                </span>
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                RUM: {{ mandate.rum || mandate.gocardless_mandate_id }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <span :class="['px-2 py-1 rounded-full text-xs font-medium', getStatusClass(mandate.status)]">
                                                {{ getStatusLabel(mandate.status) }}
                                            </span>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {{ formatCurrency(mandate.total_collected || 0) }} preleve
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <Link
                                                :href="route('tenant.sepa.show', mandate.id)"
                                                class="p-2 text-blue-600 hover:text-blue-700"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </Link>
                                            <button
                                                v-if="mandate.status === 'active'"
                                                @click="cancelMandate(mandate)"
                                                class="p-2 text-red-600 hover:text-red-700"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="mandates.data.length === 0" class="p-8 text-center">
                                <i class="fas fa-file-invoice text-4xl text-gray-400 mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400">Aucun mandat SEPA</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <div v-if="mandates.last_page > 1" class="p-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex justify-center gap-2">
                                <Link
                                    v-for="page in mandates.last_page"
                                    :key="page"
                                    :href="route('tenant.sepa.index', { page })"
                                    :class="[
                                        'px-3 py-1 rounded',
                                        page === mandates.current_page
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    ]"
                                >
                                    {{ page }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Payments -->
                <div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Prelevements recents</h2>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-[500px] overflow-y-auto">
                            <div
                                v-for="payment in recentPayments"
                                :key="payment.id"
                                class="p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ payment.customer?.first_name }} {{ payment.customer?.last_name }}
                                    </span>
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(payment.amount) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ payment.invoice?.invoice_number || 'N/A' }}
                                    </span>
                                    <span :class="['px-2 py-0.5 rounded-full text-xs', getPaymentStatusClass(payment.status)]">
                                        {{ getPaymentStatusLabel(payment.status) }}
                                    </span>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    {{ formatDate(payment.created_at) }}
                                </div>
                            </div>
                            <div v-if="recentPayments.length === 0" class="p-8 text-center">
                                <i class="fas fa-history text-3xl text-gray-400 mb-2"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Aucun prelevement</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mt-4 p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-3">Actions rapides</h3>
                        <div class="space-y-2">
                            <button
                                @click="showBulkChargeModal = true"
                                class="w-full px-4 py-2 text-left bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                            >
                                <i class="fas fa-bolt mr-2 text-yellow-500"></i>
                                Prelevement groupé
                            </button>
                            <Link
                                :href="route('tenant.invoices.index', { status: 'overdue' })"
                                class="block w-full px-4 py-2 text-left bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300"
                            >
                                <i class="fas fa-file-invoice mr-2 text-red-500"></i>
                                Factures impayees
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Setup Mandate Modal -->
        <Teleport to="body">
            <div v-if="showSetupModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Nouveau mandat SEPA</h2>
                            <button @click="showSetupModal = false" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Client</label>
                            <select
                                v-model="setupForm.customer_id"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option value="">Selectionnez un client...</option>
                                <!-- This would be populated with customers -->
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Le client sera redirige vers GoCardless pour signer le mandat</p>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/30 p-4 rounded-lg">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                                <div class="text-sm text-blue-700 dark:text-blue-300">
                                    <p class="font-medium">Comment ca marche ?</p>
                                    <ol class="list-decimal list-inside mt-2 space-y-1">
                                        <li>Le client est redirige vers une page securisee GoCardless</li>
                                        <li>Il saisit ses coordonnees bancaires</li>
                                        <li>Le mandat est automatiquement active</li>
                                        <li>Vous pouvez prelever les factures automatiquement</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <button
                            @click="showSetupModal = false"
                            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800"
                        >
                            Annuler
                        </button>
                        <button
                            @click="initiateSetup"
                            :disabled="!setupForm.customer_id || loading"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                        >
                            <i class="fas fa-external-link-alt mr-2"></i>Creer le mandat
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    mandates: Object,
    stats: Object,
    recentPayments: Array,
});

const showSetupModal = ref(false);
const showBulkChargeModal = ref(false);
const loading = ref(false);

const setupForm = ref({
    customer_id: '',
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const getStatusClass = (status) => {
    const classes = {
        'active': 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        'pending_submission': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        'submitted': 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
        'failed': 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        'cancelled': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status] || classes['pending'];
};

const getStatusLabel = (status) => {
    const labels = {
        'active': 'Actif',
        'pending': 'En attente',
        'pending_submission': 'En attente',
        'submitted': 'Soumis',
        'failed': 'Echoue',
        'cancelled': 'Annule',
        'expired': 'Expire',
    };
    return labels[status] || status;
};

const getPaymentStatusClass = (status) => {
    const classes = {
        'paid_out': 'bg-green-100 text-green-700',
        'confirmed': 'bg-blue-100 text-blue-700',
        'submitted': 'bg-yellow-100 text-yellow-700',
        'pending_submission': 'bg-yellow-100 text-yellow-700',
        'failed': 'bg-red-100 text-red-700',
        'cancelled': 'bg-gray-100 text-gray-700',
    };
    return classes[status] || 'bg-gray-100 text-gray-700';
};

const getPaymentStatusLabel = (status) => {
    const labels = {
        'paid_out': 'Paye',
        'confirmed': 'Confirme',
        'submitted': 'Soumis',
        'pending_submission': 'En attente',
        'failed': 'Echoue',
        'cancelled': 'Annule',
    };
    return labels[status] || status;
};

const initiateSetup = async () => {
    if (!setupForm.value.customer_id) return;
    loading.value = true;

    try {
        const response = await fetch(route('tenant.sepa.setup-mandate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
            body: JSON.stringify(setupForm.value),
        });

        const data = await response.json();

        if (data.success && data.redirect_url) {
            window.location.href = data.redirect_url;
        } else {
            alert(data.error || 'Erreur lors de la creation du mandat');
        }
    } finally {
        loading.value = false;
    }
};

const cancelMandate = async (mandate) => {
    if (!confirm('Annuler ce mandat SEPA ? Cette action est irreversible.')) return;

    try {
        const response = await fetch(route('tenant.sepa.mandates.cancel', mandate.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            router.reload();
        } else {
            alert(data.error || 'Erreur lors de l\'annulation');
        }
    } catch (error) {
        alert('Erreur de connexion');
    }
};
</script>
