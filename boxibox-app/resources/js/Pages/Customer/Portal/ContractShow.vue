<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('customer.portal.contracts')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-arrow-left"></i>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Contrat {{ contract.contract_number }}</h1>
                    <p class="text-gray-600 dark:text-gray-400">Details de votre contrat de location</p>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Contract Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Box Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm opacity-80">Box de stockage</div>
                                    <div class="text-3xl font-bold">{{ contract.box?.code }}</div>
                                </div>
                                <span :class="contract.status === 'active' ? 'bg-green-500' : 'bg-gray-500'" class="px-4 py-2 rounded-full font-medium">
                                    {{ contract.status === 'active' ? 'Actif' : 'Termine' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-6 grid md:grid-cols-2 gap-6">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Surface</div>
                                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ contract.box?.size_m2 }} m²</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Etage</div>
                                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ contract.box?.floor || 'RDC' }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Date de debut</div>
                                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ contract.start_date }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Loyer mensuel</div>
                                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ formatCurrency(contract.monthly_price) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Site Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">
                            <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                            Adresse du site
                        </h3>
                        <div class="space-y-2">
                            <p class="font-medium text-gray-900 dark:text-white">{{ contract.site?.name }}</p>
                            <p class="text-gray-600 dark:text-gray-400">{{ contract.site?.address }}</p>
                            <p v-if="contract.site?.phone" class="text-gray-600 dark:text-gray-400">
                                <i class="fas fa-phone mr-2"></i>{{ contract.site?.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Recent Invoices -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                                Factures
                            </h3>
                            <Link :href="route('customer.portal.invoices')" class="text-sm text-blue-600 hover:text-blue-700">
                                Voir toutes
                            </Link>
                        </div>
                        <div v-if="invoices.length > 0" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="invoice in invoices" :key="invoice.id" class="p-4 flex items-center justify-between">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-white">{{ invoice.invoice_number }}</div>
                                    <div class="text-sm text-gray-500">Echeance: {{ invoice.due_date }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ formatCurrency(invoice.total) }}</div>
                                    <span :class="invoice.status === 'paid' ? 'text-green-600' : 'text-yellow-600'" class="text-sm">
                                        {{ invoice.status === 'paid' ? 'Payee' : 'En attente' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-8 text-center text-gray-500">
                            Aucune facture
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Resume</h3>
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Depot de garantie</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(contract.deposit) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Facturation</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ contract.billing_cycle === 'monthly' ? 'Mensuelle' : 'Annuelle' }}</span>
                            </div>
                            <div v-if="contract.end_date" class="flex justify-between">
                                <span class="text-gray-500">Date de fin</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ contract.end_date }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Actions</h3>
                        <div class="space-y-2">
                            <Link
                                :href="route('customer.portal.support.index') + '?type=box_change&contract_id=' + contract.id"
                                class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-exchange-alt text-blue-600"></i>
                                Changer de box
                            </Link>
                            <Link
                                :href="route('customer.portal.support.index') + '?type=termination&contract_id=' + contract.id"
                                class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-door-open text-red-600"></i>
                                Resilier le contrat
                            </Link>
                            <Link
                                :href="route('customer.portal.support.index') + '?type=maintenance&contract_id=' + contract.id"
                                class="flex items-center gap-3 p-3 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition"
                            >
                                <i class="fas fa-tools text-orange-600"></i>
                                Signaler un probleme
                            </Link>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div v-if="payments.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Derniers paiements</h3>
                        <div class="space-y-3">
                            <div v-for="payment in payments.slice(0, 3)" :key="payment.id" class="flex justify-between text-sm">
                                <span class="text-gray-500">{{ payment.payment_date }}</span>
                                <span class="font-medium text-green-600">{{ formatCurrency(payment.amount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    contract: Object,
    invoices: Array,
    payments: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};
</script>
