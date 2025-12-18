<template>
    <SuperAdminLayout :title="`Credits - ${tenant.name}`">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <Link :href="route('superadmin.credits.index')" class="text-blue-600 hover:text-blue-800 text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Retour a la liste
                    </Link>
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-3xl font-bold text-gray-900">{{ tenant.name }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ tenant.email }}</p>
                            <span v-if="tenant.subscription_plan" class="mt-2 inline-block px-3 py-1 rounded-full text-sm font-semibold"
                                :class="`bg-${tenant.subscription_plan.badge_color || 'gray'}-100 text-${tenant.subscription_plan.badge_color || 'gray'}-800`">
                                Plan: {{ tenant.subscription_plan.name }}
                            </span>
                        </div>
                        <div class="flex space-x-3">
                            <button @click="showChangePlanModal = true"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                <i class="fas fa-exchange-alt mr-2"></i>Changer de plan
                            </button>
                            <button @click="showAddCreditsModal = true"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Ajouter des credits
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Usage actuel -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Email -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-envelope text-blue-500 mr-2"></i>Emails
                            </h3>
                            <span class="text-2xl font-bold text-blue-600">
                                {{ currentUsage.email_usage_percent }}%
                            </span>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Quota mensuel</span>
                                    <span>{{ formatNumber(currentUsage.emails_sent) }} / {{ formatNumber(currentUsage.emails_quota) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all"
                                        :class="getProgressColor(currentUsage.email_usage_percent)"
                                        :style="{ width: Math.min(currentUsage.email_usage_percent, 100) + '%' }">
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-center pt-4 border-t">
                                <div>
                                    <p class="text-2xl font-bold text-gray-900">{{ formatNumber(getTotalEmailCredits) }}</p>
                                    <p class="text-sm text-gray-600">Credits disponibles</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-green-600">{{ currentUsage.email_open_rate }}%</p>
                                    <p class="text-sm text-gray-600">Taux d'ouverture</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SMS -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-sms text-green-500 mr-2"></i>SMS
                            </h3>
                            <span class="text-2xl font-bold text-green-600">
                                {{ currentUsage.sms_usage_percent }}%
                            </span>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Quota mensuel</span>
                                    <span>{{ formatNumber(currentUsage.sms_sent) }} / {{ formatNumber(currentUsage.sms_quota) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="h-3 rounded-full transition-all"
                                        :class="getProgressColor(currentUsage.sms_usage_percent)"
                                        :style="{ width: Math.min(currentUsage.sms_usage_percent, 100) + '%' }">
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-center pt-4 border-t">
                                <div>
                                    <p class="text-2xl font-bold text-gray-900">{{ formatNumber(getTotalSmsCredits) }}</p>
                                    <p class="text-sm text-gray-600">Credits disponibles</p>
                                </div>
                                <div>
                                    <p class="text-2xl font-bold text-green-600">{{ currentUsage.sms_delivery_rate }}%</p>
                                    <p class="text-sm text-gray-600">Taux de delivrabilite</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Credits actifs -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-coins text-yellow-500 mr-2"></i>Credits actifs
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits restants</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Achetes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expire le</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="credit in activeCredits" :key="credit.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="credit.type === 'email' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                            {{ credit.type === 'email' ? 'Email' : 'SMS' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-semibold text-gray-900">{{ formatNumber(credit.credits_remaining) }}</span>
                                        <span class="text-gray-500"> / {{ formatNumber(credit.credits_purchased) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ formatDate(credit.purchased_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600">{{ getPaymentMethodLabel(credit.payment_method) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="credit.expires_at" class="text-sm"
                                            :class="isExpiringSoon(credit.expires_at) ? 'text-orange-600 font-semibold' : 'text-gray-600'">
                                            {{ formatDate(credit.expires_at) }}
                                        </span>
                                        <span v-else class="text-sm text-green-600">Jamais</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <button @click="revokeCredit(credit)"
                                            class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="activeCredits.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        Aucun credit actif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historique usage -->
                <div class="bg-white rounded-lg shadow mb-8">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-chart-line text-purple-500 mr-2"></i>Historique de consommation (12 mois)
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Periode</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Emails</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">SMS</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Depuis credits</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="usage in usageHistory" :key="usage.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatPeriod(usage.period_start) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="font-medium">{{ formatNumber(usage.emails_sent) }}</span>
                                        <span class="text-gray-500 text-sm"> / {{ formatNumber(usage.emails_quota) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="font-medium">{{ formatNumber(usage.sms_sent) }}</span>
                                        <span class="text-gray-500 text-sm"> / {{ formatNumber(usage.sms_quota) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">
                                        {{ formatNumber(usage.emails_from_credits) }} / {{ formatNumber(usage.sms_from_credits) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Historique credits -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-history text-gray-500 mr-2"></i>Historique des credits
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="credit in creditHistory" :key="credit.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ formatDate(credit.purchased_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="credit.type === 'email' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                                            {{ credit.type === 'email' ? 'Email' : 'SMS' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        {{ formatNumber(credit.credits_purchased) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ credit.amount_paid > 0 ? credit.amount_paid + ' ' + credit.currency : 'Gratuit' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        {{ getPaymentMethodLabel(credit.payment_method) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                            :class="getStatusClass(credit.status)">
                                            {{ getStatusLabel(credit.status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Changer Plan -->
                <div v-if="showChangePlanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Changer le plan de {{ tenant.name }}</h3>
                        <form @submit.prevent="submitChangePlan">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau plan</label>
                                <select v-model="changePlanForm.plan_id" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option v-for="pack in creditPacks" :key="pack.id" :value="pack.id">
                                        {{ pack.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="showChangePlanModal = false"
                                    class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">
                                    Annuler
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                                    Changer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Modal Ajouter Credits -->
                <div v-if="showAddCreditsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-semibold mb-4">Ajouter des credits</h3>
                        <form @submit.prevent="submitAddCredits">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select v-model="addCreditsForm.type" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="email">Email</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de credits</label>
                                    <input type="number" v-model="addCreditsForm.credits" required min="1"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration</label>
                                    <input type="date" v-model="addCreditsForm.expires_at"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Raison</label>
                                    <input type="text" v-model="addCreditsForm.reason"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="ex: Compensation...">
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" @click="showAddCreditsModal = false"
                                    class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-50">
                                    Annuler
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    tenant: Object,
    currentUsage: Object,
    usageHistory: Array,
    activeCredits: Array,
    creditHistory: Array,
    creditPacks: Array,
});

const showChangePlanModal = ref(false);
const showAddCreditsModal = ref(false);

const changePlanForm = useForm({
    plan_id: props.tenant.current_plan_id,
});

const addCreditsForm = useForm({
    type: 'email',
    credits: 100,
    expires_at: null,
    reason: '',
});

const getTotalEmailCredits = computed(() => {
    return props.activeCredits
        .filter(c => c.type === 'email')
        .reduce((sum, c) => sum + c.credits_remaining, 0);
});

const getTotalSmsCredits = computed(() => {
    return props.activeCredits
        .filter(c => c.type === 'sms')
        .reduce((sum, c) => sum + c.credits_remaining, 0);
});

const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('fr-FR').format(num);
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('fr-FR');
};

const formatPeriod = (date) => {
    if (!date) return '-';
    const d = new Date(date);
    return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
};

const getProgressColor = (percent) => {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-orange-500';
    if (percent >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
};

const getPaymentMethodLabel = (method) => {
    const labels = {
        manual: 'Manuel',
        stripe: 'Stripe',
        promo: 'Promotion',
        gift: 'Cadeau',
    };
    return labels[method] || method;
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-green-100 text-green-800',
        expired: 'bg-gray-100 text-gray-800',
        depleted: 'bg-orange-100 text-orange-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Actif',
        expired: 'Expire',
        depleted: 'Epuise',
    };
    return labels[status] || status;
};

const isExpiringSoon = (date) => {
    if (!date) return false;
    const expiresAt = new Date(date);
    const now = new Date();
    const diffDays = (expiresAt - now) / (1000 * 60 * 60 * 24);
    return diffDays <= 30 && diffDays > 0;
};

const submitChangePlan = () => {
    changePlanForm.post(route('superadmin.credits.change-plan', props.tenant.id), {
        onSuccess: () => {
            showChangePlanModal.value = false;
        }
    });
};

const submitAddCredits = () => {
    addCreditsForm.post(route('superadmin.credits.add', props.tenant.id), {
        onSuccess: () => {
            showAddCreditsModal.value = false;
            addCreditsForm.reset();
        }
    });
};

const revokeCredit = (credit) => {
    if (confirm('Revoquer ces credits ?')) {
        router.post(route('superadmin.credits.revoke', credit.id));
    }
};
</script>
