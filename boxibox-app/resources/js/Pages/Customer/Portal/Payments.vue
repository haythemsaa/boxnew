<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Paiements</h1>
                <p class="text-gray-600 dark:text-gray-400">Historique de vos paiements</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-euro-sign text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(stats.total_paid) }}
                            </div>
                            <div class="text-sm text-gray-500">Total paye</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(stats.this_year) }}
                            </div>
                            <div class="text-sm text-gray-500">Cette annee</div>
                        </div>
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center">
                            <i class="fas fa-receipt text-purple-600 text-xl"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ stats.payment_count }}
                            </div>
                            <div class="text-sm text-gray-500">Paiements effectues</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments List -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div v-if="payments.data?.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div
                        v-for="payment in payments.data"
                        :key="payment.id"
                        class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-xl flex items-center justify-center">
                                    <i class="fas fa-check text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(payment.amount) }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ payment.invoice?.contract?.box_code }} - {{ formatDate(payment.created_at) }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Facture: {{ payment.invoice?.invoice_number }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                    <i :class="getPaymentMethodIcon(payment.method)"></i>
                                    {{ getPaymentMethodLabel(payment.method) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="p-12 text-center">
                    <i class="fas fa-credit-card text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucun paiement</h3>
                    <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore effectue de paiement.</p>
                </div>

                <!-- Pagination -->
                <div v-if="payments.links?.length > 3" class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                    <Link
                        v-for="link in payments.links"
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
import { Link } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    payments: Object,
    stats: Object,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const getPaymentMethodIcon = (method) => {
    const icons = {
        'card': 'fas fa-credit-card',
        'sepa': 'fas fa-university',
        'bank_transfer': 'fas fa-exchange-alt',
        'cash': 'fas fa-money-bill',
        'check': 'fas fa-money-check',
    };
    return icons[method] || 'fas fa-credit-card';
};

const getPaymentMethodLabel = (method) => {
    const labels = {
        'card': 'Carte bancaire',
        'sepa': 'Prelevement SEPA',
        'bank_transfer': 'Virement',
        'cash': 'Especes',
        'check': 'Cheque',
    };
    return labels[method] || method;
};
</script>
