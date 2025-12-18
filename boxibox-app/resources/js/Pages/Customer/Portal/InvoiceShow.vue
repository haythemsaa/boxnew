<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link :href="route('customer.portal.invoices')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                        <i class="fas fa-arrow-left"></i>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</h1>
                        <p class="text-gray-600 dark:text-gray-400">Details de la facture</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button @click="downloadInvoice" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                        <i class="fas fa-download mr-2"></i>
                        Telecharger
                    </button>
                    <button v-if="invoice.status === 'pending'" @click="showPayModal = true" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-credit-card mr-2"></i>
                        Payer maintenant
                    </button>
                </div>
            </div>

            <!-- Invoice Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <!-- Invoice Header -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ invoice.invoice_number }}</div>
                            <div class="text-gray-500">
                                <span v-if="invoice.contract">
                                    {{ invoice.contract.box_code }} - {{ invoice.contract.site_name }}
                                </span>
                            </div>
                        </div>
                        <span :class="statusClass" class="px-4 py-2 rounded-full font-semibold text-lg">
                            {{ statusLabel }}
                        </span>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="p-6 grid md:grid-cols-3 gap-6 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Date d'emission</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ invoice.issue_date || '-' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Date d'echeance</div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ invoice.due_date || '-' }}</div>
                    </div>
                    <div v-if="invoice.paid_at">
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Date de paiement</div>
                        <div class="font-semibold text-green-600">{{ invoice.paid_at }}</div>
                    </div>
                </div>

                <!-- Invoice Items -->
                <div class="p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Detail de la facture</h3>
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-200 dark:border-gray-700">
                                <th class="pb-3">Description</th>
                                <th class="pb-3 text-center">Quantite</th>
                                <th class="pb-3 text-right">Prix unitaire</th>
                                <th class="pb-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in invoice.items" :key="item.description" class="text-gray-900 dark:text-white">
                                <td class="py-3">{{ item.description }}</td>
                                <td class="py-3 text-center">{{ item.quantity }}</td>
                                <td class="py-3 text-right">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="py-3 text-right font-medium">{{ formatCurrency(item.total) }}</td>
                            </tr>
                            <tr v-if="!invoice.items?.length">
                                <td class="py-3">Location box - {{ invoice.contract?.box_code }}</td>
                                <td class="py-3 text-center">1</td>
                                <td class="py-3 text-right">{{ formatCurrency(invoice.subtotal) }}</td>
                                <td class="py-3 text-right font-medium">{{ formatCurrency(invoice.subtotal) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals -->
                <div class="p-6 bg-gray-50 dark:bg-gray-700/50">
                    <div class="max-w-xs ml-auto space-y-2">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Sous-total HT</span>
                            <span>{{ formatCurrency(invoice.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>TVA (20%)</span>
                            <span>{{ formatCurrency(invoice.tax) }}</span>
                        </div>
                        <div class="flex justify-between text-xl font-bold text-gray-900 dark:text-white border-t border-gray-300 dark:border-gray-600 pt-2">
                            <span>Total TTC</span>
                            <span>{{ formatCurrency(invoice.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments for this invoice -->
            <div v-if="payments.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-receipt mr-2 text-green-600"></i>
                        Paiements recus
                    </h3>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="payment in payments" :key="payment.amount" class="p-4 flex items-center justify-between">
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(payment.amount) }}</div>
                            <div class="text-sm text-gray-500">{{ payment.payment_method }}</div>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ payment.payment_date }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pay Modal -->
        <div v-if="showPayModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Payer la facture</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Montant a payer: <strong class="text-gray-900 dark:text-white">{{ formatCurrency(invoice.total) }}</strong>
                </p>
                <div class="space-y-3 mb-6">
                    <button class="w-full p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-left flex items-center gap-4">
                        <i class="fas fa-credit-card text-blue-600 text-xl"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Carte bancaire</div>
                            <div class="text-sm text-gray-500">Visa, Mastercard, CB</div>
                        </div>
                    </button>
                    <button class="w-full p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-left flex items-center gap-4">
                        <i class="fas fa-university text-green-600 text-xl"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Virement bancaire</div>
                            <div class="text-sm text-gray-500">IBAN: FR76 XXXX XXXX XXXX</div>
                        </div>
                    </button>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="showPayModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                        Annuler
                    </button>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    invoice: Object,
    payments: Array,
});

const showPayModal = ref(false);

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const isOverdue = computed(() => {
    return props.invoice.status === 'pending' && new Date(props.invoice.due_date) < new Date();
});

const statusClass = computed(() => {
    if (props.invoice.status === 'paid') return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
    if (isOverdue.value) return 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300';
    return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
});

const statusLabel = computed(() => {
    if (props.invoice.status === 'paid') return 'Payee';
    if (isOverdue.value) return 'En retard';
    return 'En attente';
});

const downloadInvoice = () => {
    window.location.href = route('customer.portal.invoice.download', props.invoice.id);
};
</script>
