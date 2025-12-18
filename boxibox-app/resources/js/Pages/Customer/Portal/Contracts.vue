<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Mes Contrats</h1>
                <p class="text-gray-600 dark:text-gray-400">Gérez vos contrats de location</p>
            </div>

            <!-- Contracts Grid -->
            <div v-if="contracts.length > 0" class="grid md:grid-cols-2 gap-6">
                <div
                    v-for="contract in contracts"
                    :key="contract.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm opacity-80">Box de stockage</div>
                                <div class="text-2xl font-bold">{{ contract.box_code }}</div>
                            </div>
                            <span
                                :class="contract.status === 'active' ? 'bg-green-500' : 'bg-gray-500'"
                                class="px-3 py-1 rounded-full text-sm font-medium"
                            >
                                {{ contract.status === 'active' ? 'Actif' : 'Terminé' }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Site</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ contract.site_name }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Surface</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ contract.box_size }} m²</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Début</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ contract.start_date }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Loyer mensuel</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(contract.monthly_price) }}</div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Adresse</div>
                            <div class="text-gray-900 dark:text-white">{{ contract.site_address }}</div>
                        </div>

                        <!-- Recent Invoices -->
                        <div v-if="contract.recent_invoices?.length > 0">
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">Dernières factures</div>
                            <div class="space-y-1">
                                <div
                                    v-for="invoice in contract.recent_invoices"
                                    :key="invoice.id"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-gray-700 dark:text-gray-300">{{ invoice.invoice_number }}</span>
                                    <span :class="invoice.status === 'paid' ? 'text-green-600' : 'text-yellow-600'">
                                        {{ formatCurrency(invoice.total) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-between">
                        <Link
                            :href="route('customer.portal.contract', contract.id)"
                            class="px-4 py-2 text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                            <i class="fas fa-eye mr-1"></i>
                            Voir détails
                        </Link>
                        <div v-if="contract.status === 'active'" class="flex gap-2">
                            <button
                                @click="requestBoxChange(contract)"
                                class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                            >
                                <i class="fas fa-exchange-alt mr-1"></i>
                                Changer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <i class="fas fa-file-contract text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucun contrat</h3>
                <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas encore de contrat de location.</p>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    contracts: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const requestBoxChange = (contract) => {
    router.get(route('customer.portal.contract.change-box', contract.id));
};
</script>
