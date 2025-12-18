<template>
    <SuperAdminLayout :title="`Abonnement - ${tenant.name}`">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('superadmin.tenants.show', tenant.id)" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                        <i class="fas fa-arrow-left mr-2"></i>Retour au tenant
                    </Link>
                    <h2 class="text-3xl font-bold text-gray-900">Gestion de l'Abonnement</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ tenant.name }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Abonnement actuel -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Current Subscription Card -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-xl font-bold mb-4">Abonnement Actuel</h3>

                            <div v-if="currentSubscription">
                                <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                    <div>
                                        <h4 class="text-2xl font-bold text-gray-900">{{ currentSubscription.plan.name }}</h4>
                                        <p class="text-gray-600">{{ currentSubscription.plan.description }}</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-3xl font-bold text-blue-600">{{ currentSubscription.price }}€</div>
                                        <div class="text-sm text-gray-500">{{ currentSubscription.billing_cycle === 'monthly' ? '/mois' : '/an' }}</div>
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Statut</span>
                                        <div class="mt-1">
                                            <span class="px-3 py-1 rounded-full text-sm font-semibold"
                                                :class="{
                                                    'bg-green-100 text-green-800': currentSubscription.status === 'active',
                                                    'bg-blue-100 text-blue-800': currentSubscription.status === 'trial',
                                                    'bg-red-100 text-red-800': currentSubscription.status === 'past_due',
                                                    'bg-gray-100 text-gray-800': currentSubscription.status === 'suspended',
                                                }">
                                                {{ currentSubscription.status }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-sm text-gray-600">Cycle de facturation</span>
                                        <div class="mt-1 font-semibold">{{ currentSubscription.billing_cycle === 'monthly' ? 'Mensuel' : 'Annuel' }}</div>
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div class="grid grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <span class="text-sm text-gray-600">Début</span>
                                        <div class="mt-1 font-semibold">{{ formatDate(currentSubscription.starts_at) }}</div>
                                    </div>
                                    <div v-if="currentSubscription.trial_ends_at">
                                        <span class="text-sm text-gray-600">Fin essai</span>
                                        <div class="mt-1 font-semibold text-orange-600">{{ formatDate(currentSubscription.trial_ends_at) }}</div>
                                    </div>
                                    <div v-if="currentSubscription.ends_at">
                                        <span class="text-sm text-gray-600">Expiration</span>
                                        <div class="mt-1 font-semibold">{{ formatDate(currentSubscription.ends_at) }}</div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex gap-2 mt-6">
                                    <button @click="showChangePlanModal = true" class="btn btn-primary">
                                        <i class="fas fa-exchange-alt mr-2"></i>Changer de Plan
                                    </button>
                                    <button v-if="currentSubscription.status === 'active'" @click="suspendSubscription"
                                        class="btn bg-orange-600 text-white hover:bg-orange-700">
                                        <i class="fas fa-pause mr-2"></i>Suspendre
                                    </button>
                                    <button v-if="currentSubscription.status === 'suspended'" @click="reactivateSubscription"
                                        class="btn bg-green-600 text-white hover:bg-green-700">
                                        <i class="fas fa-play mr-2"></i>Réactiver
                                    </button>
                                </div>
                            </div>

                            <div v-else class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-4xl mb-4"></i>
                                <p>Aucun abonnement actif</p>
                                <button @click="showChangePlanModal = true" class="btn btn-primary mt-4">
                                    Créer un Abonnement
                                </button>
                            </div>
                        </div>

                        <!-- Factures Plateforme -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-bold">Factures Plateforme</h3>
                                <button @click="showCreateInvoiceModal = true" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus mr-2"></i>Créer une Facture
                                </button>
                            </div>

                            <div v-if="platformInvoices.data.length > 0" class="space-y-3">
                                <div v-for="invoice in platformInvoices.data" :key="invoice.id"
                                    class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <div class="font-semibold">{{ invoice.invoice_number }}</div>
                                            <div class="text-sm text-gray-600">{{ formatDate(invoice.issue_date) }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-bold">{{ invoice.total_amount }}€</div>
                                            <span class="px-2 py-1 rounded-full text-xs"
                                                :class="{
                                                    'bg-green-100 text-green-800': invoice.status === 'paid',
                                                    'bg-yellow-100 text-yellow-800': invoice.status === 'pending',
                                                    'bg-red-100 text-red-800': invoice.is_overdue,
                                                }">
                                                {{ invoice.status }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-gray-500">
                                Aucune facture
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Plans disponibles -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-bold mb-4">Plans Disponibles</h3>
                            <div class="space-y-3">
                                <div v-for="plan in plans" :key="plan.id"
                                    class="border rounded-lg p-3 hover:border-blue-500 cursor-pointer"
                                    :class="currentSubscription?.plan_id === plan.id ? 'border-blue-500 bg-blue-50' : ''">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-semibold">{{ plan.name }}</div>
                                            <div class="text-xs text-gray-600 mt-1">
                                                {{ plan.max_sites || '∞' }} sites · {{ plan.max_boxes || '∞' }} boxes
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold text-blue-600">{{ plan.monthly_price }}€</div>
                                            <div class="text-xs text-gray-500">/mois</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Historique -->
                        <div class="bg-white rounded-lg shadow-lg p-6">
                            <h3 class="text-lg font-bold mb-4">Historique</h3>
                            <div v-if="subscriptionHistory.length > 0" class="space-y-3">
                                <div v-for="sub in subscriptionHistory" :key="sub.id" class="text-sm border-l-2 pl-3"
                                    :class="sub.status === 'active' ? 'border-green-500' : 'border-gray-300'">
                                    <div class="font-semibold">{{ sub.plan.name }}</div>
                                    <div class="text-gray-600">{{ formatDate(sub.starts_at) }}</div>
                                    <span class="px-2 py-1 rounded text-xs" :class="`bg-${getStatusColor(sub.status)}-100 text-${getStatusColor(sub.status)}-800`">
                                        {{ sub.status }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">Aucun historique</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Changer Plan -->
        <div v-if="showChangePlanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
                <h3 class="text-xl font-bold mb-4">Changer de Plan</h3>
                <form @submit.prevent="changePlan">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Plan</label>
                            <select v-model="planForm.plan_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Sélectionner un plan</option>
                                <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                    {{ plan.name }} - {{ plan.monthly_price }}€/mois
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cycle de facturation</label>
                            <select v-model="planForm.billing_cycle" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="monthly">Mensuel</option>
                                <option value="yearly">Annuel</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Jours d'essai (optionnel)</label>
                            <input v-model.number="planForm.trial_days" type="number" min="0" max="90"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showChangePlanModal = false" class="btn btn-secondary">Annuler</button>
                        <button type="submit" class="btn btn-primary">Confirmer</button>
                    </div>
                </form>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    tenant: Object,
    currentSubscription: Object,
    plans: Array,
    platformInvoices: Object,
    subscriptionHistory: Array,
});

const showChangePlanModal = ref(false);
const showCreateInvoiceModal = ref(false);

const planForm = ref({
    plan_id: null,
    billing_cycle: 'monthly',
    trial_days: 0,
});

const changePlan = () => {
    router.post(route('superadmin.tenant-management.subscription.change', props.tenant.id), planForm.value, {
        onSuccess: () => {
            showChangePlanModal.value = false;
            planForm.value = { plan_id: null, billing_cycle: 'monthly', trial_days: 0 };
        }
    });
};

const suspendSubscription = () => {
    if (confirm('Suspendre l\'abonnement de ce tenant ?')) {
        router.post(route('superadmin.tenant-management.subscription.suspend', props.tenant.id));
    }
};

const reactivateSubscription = () => {
    if (confirm('Réactiver l\'abonnement de ce tenant ?')) {
        router.post(route('superadmin.tenant-management.subscription.reactivate', props.tenant.id));
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR');
};

const getStatusColor = (status) => {
    const colors = {
        'active': 'green',
        'trial': 'blue',
        'past_due': 'red',
        'suspended': 'gray',
        'cancelled': 'gray',
    };
    return colors[status] || 'gray';
};
</script>
