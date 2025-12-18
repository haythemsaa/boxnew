<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Factures</h1>
                    <p class="text-gray-600 dark:text-gray-400">Consultez et payez vos factures</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                    <div class="text-sm text-gray-500">Total factures</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-green-600">{{ stats.paid }}</div>
                    <div class="text-sm text-gray-500">Payées</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
                    <div class="text-sm text-gray-500">En attente</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-red-600">{{ stats.overdue }}</div>
                    <div class="text-sm text-gray-500">En retard</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-2">
                    <button
                        @click="filterStatus('')"
                        :class="!filters.status ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    >
                        Toutes
                    </button>
                    <button
                        @click="filterStatus('pending')"
                        :class="filters.status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    >
                        En attente
                    </button>
                    <button
                        @click="filterStatus('paid')"
                        :class="filters.status === 'paid' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition"
                    >
                        Payées
                    </button>
                </div>
            </div>

            <!-- Invoices List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="invoices.data?.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="invoice in invoices.data"
                        :key="invoice.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div :class="getStatusIconClass(invoice)" class="w-12 h-12 rounded-xl flex items-center justify-center">
                                    <i :class="getStatusIcon(invoice)" class="text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ invoice.invoice_number }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ invoice.contract?.box_code }} - {{ invoice.contract?.site_name }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Échéance: {{ formatDate(invoice.due_date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <div class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(invoice.total) }}
                                    </div>
                                    <span :class="getStatusBadgeClass(invoice)" class="px-2 py-1 text-xs rounded-full">
                                        {{ getStatusLabel(invoice) }}
                                    </span>
                                </div>
                                <div class="flex gap-2">
                                    <Link
                                        :href="route('customer.portal.invoice', invoice.id)"
                                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg"
                                        title="Voir"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                    <button
                                        @click="downloadInvoice(invoice)"
                                        class="p-2 text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                                        title="Télécharger"
                                    >
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <Link
                                        v-if="invoice.status === 'pending'"
                                        :href="route('customer.portal.invoice', invoice.id)"
                                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"
                                    >
                                        Payer
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="p-12 text-center">
                    <i class="fas fa-file-invoice text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucune facture</h3>
                    <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore de factures.</p>
                </div>

                <!-- Pagination -->
                <div v-if="invoices.links?.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                    <Link
                        v-for="link in invoices.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        v-html="link.label"
                        :class="[
                            'px-3 py-1 rounded-lg text-sm',
                            link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                            !link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-500 hover:text-white'
                        ]"
                    />
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const isOverdue = (invoice) => {
    return invoice.status === 'pending' && new Date(invoice.due_date) < new Date();
};

const getStatusIcon = (invoice) => {
    if (invoice.status === 'paid') return 'fas fa-check text-green-600';
    if (isOverdue(invoice)) return 'fas fa-exclamation-triangle text-red-600';
    return 'fas fa-clock text-yellow-600';
};

const getStatusIconClass = (invoice) => {
    if (invoice.status === 'paid') return 'bg-green-100 dark:bg-green-900/50';
    if (isOverdue(invoice)) return 'bg-red-100 dark:bg-red-900/50';
    return 'bg-yellow-100 dark:bg-yellow-900/50';
};

const getStatusBadgeClass = (invoice) => {
    if (invoice.status === 'paid') return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
    if (isOverdue(invoice)) return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
};

const getStatusLabel = (invoice) => {
    if (invoice.status === 'paid') return 'Payée';
    if (isOverdue(invoice)) return 'En retard';
    return 'En attente';
};

const filterStatus = (status) => {
    router.get(route('customer.portal.invoices'), { status }, { preserveState: true });
};

const downloadInvoice = (invoice) => {
    window.location.href = route('customer.portal.invoice.download', invoice.id);
};
</script>
