<template>
    <SuperAdminLayout title="Gestion des Plans d'Abonnement">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900">Plans d'Abonnement</h2>
                        <p class="mt-1 text-sm text-gray-600">Gérez les plans et tarifs de la plateforme</p>
                    </div>
                    <Link :href="route('superadmin.plans.create')" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Créer un Plan
                    </Link>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-layer-group text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Plans Actifs</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.active_plans }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-building text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Plans</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_plans }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Abonnements</p>
                                <p class="text-2xl font-bold text-gray-900">{{ stats.total_subscriptions }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plans Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="plan in plans" :key="plan.id" class="bg-white rounded-lg shadow-lg overflow-hidden border-2"
                        :class="plan.is_popular ? 'border-purple-500' : 'border-transparent'">

                        <!-- Popular Badge -->
                        <div v-if="plan.is_popular" class="bg-purple-500 text-white text-center py-2 text-sm font-semibold">
                            <i class="fas fa-star mr-1"></i> POPULAIRE
                        </div>

                        <div class="p-6">
                            <!-- Header -->
                            <div class="text-center mb-6">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                    :class="`bg-${plan.badge_color}-100 text-${plan.badge_color}-800`">
                                    {{ plan.name }}
                                </span>
                                <div class="mt-4">
                                    <span class="text-4xl font-bold text-gray-900">{{ plan.monthly_price }}€</span>
                                    <span class="text-gray-600">/mois</span>
                                </div>
                                <p class="text-sm text-gray-500 mt-2">
                                    {{ plan.yearly_price }}€/an (économie {{ plan.yearly_discount }}%)
                                </p>
                            </div>

                            <!-- Description -->
                            <p class="text-sm text-gray-600 mb-4">{{ plan.description }}</p>

                            <!-- Limites -->
                            <div class="space-y-2 mb-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Sites</span>
                                    <span class="font-semibold">{{ plan.max_sites || '∞' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Boxes</span>
                                    <span class="font-semibold">{{ plan.max_boxes || '∞' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Utilisateurs</span>
                                    <span class="font-semibold">{{ plan.max_users || '∞' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tenants</span>
                                    <span class="font-semibold text-blue-600">{{ plan.tenant_count }}</span>
                                </div>
                            </div>

                            <!-- Quotas Email/SMS -->
                            <div class="border-t pt-4 mb-4 space-y-2 text-sm">
                                <p class="text-xs font-semibold text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-1"></i> Quotas Communications
                                </p>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Emails/mois</span>
                                    <span class="font-semibold">{{ formatNumber(plan.emails_per_month) || '0' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SMS/mois</span>
                                    <span class="font-semibold">{{ formatNumber(plan.sms_per_month) || '0' }}</span>
                                </div>
                                <div class="flex gap-2 mt-2">
                                    <span v-if="plan.custom_email_provider_allowed"
                                        class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                                        Email custom
                                    </span>
                                    <span v-if="plan.custom_sms_provider_allowed"
                                        class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">
                                        SMS custom
                                    </span>
                                    <span v-if="plan.api_access"
                                        class="px-2 py-0.5 bg-purple-100 text-purple-700 rounded text-xs">
                                        API
                                    </span>
                                </div>
                            </div>

                            <!-- Modules inclus -->
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-700 mb-2">
                                    {{ plan.included_modules_list.length }} modules inclus
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="module in plan.included_modules_list.slice(0, 3)" :key="module.id"
                                        class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                        <i :class="module.icon" class="mr-1"></i>
                                        {{ module.name }}
                                    </span>
                                    <span v-if="plan.included_modules_list.length > 3"
                                        class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
                                        +{{ plan.included_modules_list.length - 3 }}
                                    </span>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="space-y-2">
                                <Link :href="route('superadmin.plans.edit', plan.id)"
                                    class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                    <i class="fas fa-edit mr-2"></i>Modifier
                                </Link>

                                <div class="flex gap-2">
                                    <button @click="togglePlan(plan)"
                                        class="flex-1 px-3 py-2 text-sm rounded-lg"
                                        :class="plan.is_active ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200'">
                                        {{ plan.is_active ? 'Désactiver' : 'Activer' }}
                                    </button>

                                    <button @click="duplicatePlan(plan)"
                                        class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                                        <i class="fas fa-copy mr-1"></i>Dupliquer
                                    </button>
                                </div>

                                <button v-if="plan.tenant_count === 0" @click="deletePlan(plan)"
                                    class="w-full px-3 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 text-sm">
                                    <i class="fas fa-trash mr-2"></i>Supprimer
                                </button>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="px-6 py-3 bg-gray-50 border-t">
                            <div class="flex items-center justify-between text-xs">
                                <span :class="plan.is_active ? 'text-green-600' : 'text-gray-400'">
                                    <i class="fas fa-circle mr-1"></i>
                                    {{ plan.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <span class="text-gray-500">Ordre: {{ plan.sort_order }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    plans: Array,
    modules: Array,
    stats: Object,
});

const togglePlan = (plan) => {
    if (confirm(`Voulez-vous vraiment ${plan.is_active ? 'désactiver' : 'activer'} le plan ${plan.name} ?`)) {
        router.post(route('superadmin.plans.toggle', plan.id));
    }
};

const duplicatePlan = (plan) => {
    if (confirm(`Dupliquer le plan ${plan.name} ?`)) {
        router.post(route('superadmin.plans.duplicate', plan.id));
    }
};

const deletePlan = (plan) => {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le plan ${plan.name} ? Cette action est irréversible.`)) {
        router.delete(route('superadmin.plans.destroy', plan.id));
    }
};

const formatNumber = (num) => {
    if (!num) return '0';
    return new Intl.NumberFormat('fr-FR').format(num);
};
</script>
