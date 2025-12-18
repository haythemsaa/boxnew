<template>
    <CustomerPortalLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demandes de Changement</h1>
                    <p class="text-gray-600 dark:text-gray-400">Historique de vos demandes de changement de box</p>
                </div>
                <Link
                    :href="route('customer.portal.contracts')"
                    class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux contrats
                </Link>
            </div>

            <!-- Requests List -->
            <div v-if="requests.length > 0" class="space-y-4">
                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                >
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div :class="[
                                    'w-10 h-10 rounded-full flex items-center justify-center',
                                    request.type === 'box_upgrade' ? 'bg-green-100 dark:bg-green-900/50' : 'bg-amber-100 dark:bg-amber-900/50'
                                ]">
                                    <i :class="[
                                        'fas',
                                        request.type === 'box_upgrade' ? 'fa-arrow-up text-green-600' : 'fa-arrow-down text-amber-600'
                                    ]"></i>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ request.type === 'box_upgrade' ? 'Upgrade' : 'Downgrade' }} de box
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Contrat {{ request.contract_number }}
                                    </div>
                                </div>
                            </div>
                            <span :class="[
                                'px-3 py-1 rounded-full text-sm font-medium',
                                getStatusClass(request.status)
                            ]">
                                {{ getStatusLabel(request.status) }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Ancien box</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ request.old_box }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Nouveau box</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ request.new_box }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Date effective</div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ request.effective_date }}</div>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">Montant prorata</div>
                                <div :class="[
                                    'font-medium',
                                    request.net_amount > 0 ? 'text-amber-600' : 'text-green-600'
                                ]">
                                    {{ request.net_amount > 0 ? '+' : '' }}{{ formatCurrency(request.net_amount) }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Demande cree le {{ request.created_at }}
                                <span v-if="request.processed_at"> | Traitee le {{ request.processed_at }}</span>
                            </div>
                            <button
                                v-if="request.status === 'pending'"
                                @click="cancelRequest(request)"
                                class="text-red-600 hover:text-red-700 text-sm font-medium"
                            >
                                <i class="fas fa-times mr-1"></i>
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <i class="fas fa-exchange-alt text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aucune demande</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Vous n'avez pas encore effectue de demande de changement de box.</p>
                <Link
                    :href="route('customer.portal.contracts')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
                    <i class="fas fa-file-contract mr-2"></i>
                    Voir mes contrats
                </Link>
            </div>
        </div>
    </CustomerPortalLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue';

const props = defineProps({
    requests: Array,
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0);
};

const getStatusClass = (status) => {
    const classes = {
        'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300',
        'approved': 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300',
        'rejected': 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300',
        'cancelled': 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[status] || classes['pending'];
};

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'En attente',
        'approved': 'Approuve',
        'rejected': 'Refuse',
        'cancelled': 'Annule',
    };
    return labels[status] || status;
};

const cancelRequest = async (request) => {
    if (!confirm('Etes-vous sur de vouloir annuler cette demande ?')) return;

    try {
        const response = await fetch(route('customer.portal.box-change-request.cancel', request.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (response.ok && data.success) {
            window.location.reload();
        } else {
            alert(data.error || 'Erreur lors de l\'annulation');
        }
    } catch (error) {
        console.error('Cancel error:', error);
        alert('Erreur de connexion');
    }
};
</script>
