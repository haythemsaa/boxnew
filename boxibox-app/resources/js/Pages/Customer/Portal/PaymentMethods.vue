<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Moyens de paiement</h1>
                    <p class="text-gray-600 dark:text-gray-400">Gerez vos cartes et modes de paiement</p>
                </div>
                <button @click="showAddModal = true" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus"></i>
                    Ajouter un moyen de paiement
                </button>
            </div>

            <!-- Payment Methods List -->
            <div class="grid md:grid-cols-2 gap-4">
                <div
                    v-for="method in methods"
                    :key="method.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div :class="getMethodIconClass(method.type)" class="w-14 h-14 rounded-xl flex items-center justify-center">
                                <i :class="getMethodIcon(method.type)" class="text-2xl"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                    {{ method.brand || 'Carte' }} **** {{ method.last_four }}
                                    <span v-if="method.is_default" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-xs rounded-full">
                                        Par defaut
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500">Expire: {{ method.expiry }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="!method.is_default"
                                @click="setDefault(method)"
                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg"
                                title="Definir par defaut"
                            >
                                <i class="fas fa-star"></i>
                            </button>
                            <button
                                @click="removeMethod(method)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg"
                                title="Supprimer"
                            >
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="methods.length === 0" class="md:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-12 text-center">
                    <i class="fas fa-credit-card text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucun moyen de paiement</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">Ajoutez une carte pour faciliter vos paiements.</p>
                    <button @click="showAddModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter une carte
                    </button>
                </div>
            </div>

            <!-- SEPA Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        <i class="fas fa-university mr-2 text-green-600"></i>
                        Prelevement SEPA
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        Configurez un prelevement automatique pour ne plus vous soucier des echeances.
                    </p>
                    <div class="flex items-center gap-4 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">Prelevement SEPA actif</div>
                            <div class="text-sm text-gray-500">IBAN: FR76 **** **** **** 1234</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="bg-blue-50 dark:bg-blue-900/30 rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Paiement securise</h3>
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            Vos informations de paiement sont chiffrees et securisees. Nous ne stockons jamais vos numeros de carte complets.
                            Tous les paiements sont conformes aux normes PCI DSS.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Payment Method Modal -->
        <div v-if="showAddModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-xl max-w-md w-full p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Ajouter un moyen de paiement</h3>
                <form @submit.prevent="addMethod" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                        <select class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option>Carte bancaire</option>
                            <option>IBAN (prelevement SEPA)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numero de carte</label>
                        <input type="text" placeholder="1234 5678 9012 3456" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date d'expiration</label>
                            <input type="text" placeholder="MM/AA" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">CVV</label>
                            <input type="text" placeholder="123" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="default" class="rounded border-gray-300" />
                        <label for="default" class="text-sm text-gray-700 dark:text-gray-300">Definir comme moyen de paiement par defaut</label>
                    </div>
                    <div class="flex justify-end gap-2 pt-4">
                        <button type="button" @click="showAddModal = false" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            Annuler
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { ref } from 'vue';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    methods: Array,
});

const showAddModal = ref(false);

const getMethodIcon = (type) => {
    return type === 'sepa' ? 'fas fa-university text-green-600' : 'fas fa-credit-card text-blue-600';
};

const getMethodIconClass = (type) => {
    return type === 'sepa' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-blue-100 dark:bg-blue-900/50';
};

const setDefault = (method) => {
    // API call to set default
    console.log('Set default:', method.id);
};

const removeMethod = (method) => {
    if (confirm('Etes-vous sur de vouloir supprimer ce moyen de paiement ?')) {
        // API call to remove
        console.log('Remove:', method.id);
    }
};

const addMethod = () => {
    // API call to add
    showAddModal.value = false;
};
</script>
