<template>
    <SuperAdminLayout title="Gestion des Credits Tenants">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Credits Email & SMS</h2>
                        <p class="mt-1 text-sm text-gray-600">Gerez les quotas et credits de tous les tenants</p>
                    </div>
                    <Link :href="route('superadmin.credits.packs')" class="btn btn-primary">
                        <i class="fas fa-box mr-2"></i>
                        Gerer les packs
                    </Link>
                </div>

                <!-- Stats globales -->
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-envelope text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600">Emails envoyés (mois)</p>
                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(stats.total_emails_sent_today) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-sms text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600">SMS envoyes (mois)</p>
                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(stats.total_sms_sent_today) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-coins text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600">Credits email dispo</p>
                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(stats.total_email_credits) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-orange-100 text-orange-600">
                                <i class="fas fa-coins text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600">Credits SMS dispo</p>
                                <p class="text-xl font-bold text-gray-900">{{ formatNumber(stats.total_sms_credits) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center">
                            <div class="p-2 rounded-full bg-red-100 text-red-600">
                                <i class="fas fa-exclamation-triangle text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-xs text-gray-600">Quotas depasses</p>
                                <p class="text-xl font-bold text-gray-900">{{ stats.tenants_over_quota }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-lg shadow p-4 mb-6">
                    <div class="flex flex-wrap gap-4 items-center">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" v-model="searchQuery" placeholder="Rechercher un tenant..."
                                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                @keyup.enter="applyFilters">
                        </div>
                        <select v-model="selectedPlan" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les plans</option>
                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                {{ plan.name }}
                            </option>
                        </select>
                        <button @click="applyFilters" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                    </div>
                </div>

                <!-- Liste des tenants -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tenant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Plan
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Emails (usage)
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    SMS (usage)
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Credits
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                            {{ tenant.name.charAt(0) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ tenant.name }}</div>
                                            <div class="text-sm text-gray-500">{{ tenant.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="tenant.subscription_plan" class="px-2 py-1 rounded-full text-xs font-semibold"
                                        :class="`bg-${tenant.subscription_plan.badge_color || 'gray'}-100 text-${tenant.subscription_plan.badge_color || 'gray'}-800`">
                                        {{ tenant.subscription_plan.name }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">Aucun</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="text-sm font-medium">
                                            {{ formatNumber(tenant.usage?.emails_sent || 0) }} / {{ formatNumber(tenant.usage?.emails_quota || 0) }}
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div class="h-2 rounded-full"
                                                :class="getProgressColor(tenant.usage?.emails_percent || 0)"
                                                :style="{ width: Math.min(tenant.usage?.emails_percent || 0, 100) + '%' }">
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">{{ tenant.usage?.emails_percent || 0 }}%</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-center">
                                        <div class="text-sm font-medium">
                                            {{ formatNumber(tenant.usage?.sms_sent || 0) }} / {{ formatNumber(tenant.usage?.sms_quota || 0) }}
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div class="h-2 rounded-full"
                                                :class="getProgressColor(tenant.usage?.sms_percent || 0)"
                                                :style="{ width: Math.min(tenant.usage?.sms_percent || 0, 100) + '%' }">
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">{{ tenant.usage?.sms_percent || 0 }}%</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center space-x-4 text-sm">
                                        <span class="text-blue-600">
                                            <i class="fas fa-envelope mr-1"></i>{{ formatNumber(tenant.credits?.emails || 0) }}
                                        </span>
                                        <span class="text-green-600">
                                            <i class="fas fa-sms mr-1"></i>{{ formatNumber(tenant.credits?.sms || 0) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <button @click="openAddCreditsModal(tenant)"
                                            class="text-blue-600 hover:text-blue-900" title="Ajouter des credits">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                        <Link :href="route('superadmin.credits.show', tenant.id)"
                                            class="text-purple-600 hover:text-purple-900" title="Details">
                                            <i class="fas fa-eye"></i>
                                        </Link>
                                        <button @click="resetUsage(tenant)"
                                            class="text-orange-600 hover:text-orange-900" title="Reset compteurs">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Affichage de {{ tenants.from }} a {{ tenants.to }} sur {{ tenants.total }} tenants
                            </div>
                            <div class="flex space-x-2">
                                <Link v-for="link in tenants.links" :key="link.label"
                                    :href="link.url || '#'"
                                    class="px-3 py-2 rounded text-sm"
                                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                                    v-html="link.label">
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Ajouter credits -->
                <div v-if="showAddCreditsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-semibold mb-4">
                            Ajouter des credits a {{ selectedTenant?.name }}
                        </h3>

                        <form @submit.prevent="submitAddCredits">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                    <select v-model="creditsForm.type" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="email">Email</option>
                                        <option value="sms">SMS</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Methode</label>
                                    <select v-model="addMethod"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="manual">Credits manuels</option>
                                        <option value="pack">Pack predifini</option>
                                    </select>
                                </div>

                                <div v-if="addMethod === 'manual'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de credits</label>
                                    <input type="number" v-model="creditsForm.credits" required min="1"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div v-if="addMethod === 'pack'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pack</label>
                                    <select v-model="packForm.pack_id" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option v-for="pack in filteredPacks" :key="pack.id" :value="pack.id">
                                            {{ pack.name }} - {{ pack.credits }} credits ({{ pack.price }}EUR)
                                        </option>
                                    </select>
                                </div>

                                <div v-if="addMethod === 'pack'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mode de paiement</label>
                                    <select v-model="packForm.payment_method" required
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                        <option value="manual">Paiement manuel</option>
                                        <option value="promo">Promotion</option>
                                        <option value="gift">Cadeau</option>
                                    </select>
                                </div>

                                <div v-if="addMethod === 'manual'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date d'expiration (optionnel)</label>
                                    <input type="date" v-model="creditsForm.expires_at"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>

                                <div v-if="addMethod === 'manual'">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Raison</label>
                                    <input type="text" v-model="creditsForm.reason"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                        placeholder="ex: Compensation, Bonus...">
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
    tenants: Object,
    plans: Array,
    creditPacks: Array,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const selectedPlan = ref(props.filters?.plan_id || '');
const showAddCreditsModal = ref(false);
const selectedTenant = ref(null);
const addMethod = ref('manual');

const creditsForm = useForm({
    type: 'email',
    credits: 100,
    reason: '',
    expires_at: null,
});

const packForm = useForm({
    pack_id: null,
    payment_method: 'manual',
});

const filteredPacks = computed(() => {
    return props.creditPacks.filter(p => p.type === creditsForm.type);
});

const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('fr-FR').format(num);
};

const getProgressColor = (percent) => {
    if (percent >= 90) return 'bg-red-500';
    if (percent >= 70) return 'bg-orange-500';
    if (percent >= 50) return 'bg-yellow-500';
    return 'bg-green-500';
};

const applyFilters = () => {
    router.get(route('superadmin.credits.index'), {
        search: searchQuery.value,
        plan_id: selectedPlan.value,
    }, { preserveState: true });
};

const openAddCreditsModal = (tenant) => {
    selectedTenant.value = tenant;
    showAddCreditsModal.value = true;
    creditsForm.reset();
    packForm.reset();
    addMethod.value = 'manual';
};

const submitAddCredits = () => {
    if (addMethod.value === 'manual') {
        creditsForm.post(route('superadmin.credits.add', selectedTenant.value.id), {
            onSuccess: () => {
                showAddCreditsModal.value = false;
            }
        });
    } else {
        packForm.post(route('superadmin.credits.add-pack', selectedTenant.value.id), {
            onSuccess: () => {
                showAddCreditsModal.value = false;
            }
        });
    }
};

const resetUsage = (tenant) => {
    if (confirm(`Reinitialiser les compteurs d'usage pour ${tenant.name} ?`)) {
        router.post(route('superadmin.credits.reset', tenant.id));
    }
};
</script>
