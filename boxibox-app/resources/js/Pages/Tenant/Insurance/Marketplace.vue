<template>
    <TenantLayout title="Marketplace Assurance">
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Marketplace Assurance</h1>
                    <p class="text-gray-600 dark:text-gray-400">Comparez et proposez les meilleures assurances a vos clients</p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Clients assures</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">124</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Commission mensuelle</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">890 EUR</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Taux de souscription</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">78%</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Partenaires</p>
                                <p class="text-2xl font-bold text-gray-900 dark:text-white">5</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insurance Providers -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Partenaires Assurance</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div v-for="provider in providers" :key="provider.id"
                                 class="border border-gray-200 dark:border-gray-700 rounded-xl p-6 hover:border-indigo-500 hover:shadow-lg transition cursor-pointer"
                                 :class="provider.is_active ? 'bg-indigo-50 dark:bg-indigo-900/20 border-indigo-300' : ''">
                                <div class="flex items-start justify-between mb-4">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                        <span class="text-2xl font-bold text-gray-400">{{ provider.name.charAt(0) }}</span>
                                    </div>
                                    <span v-if="provider.is_active" class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Actif</span>
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">{{ provider.name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ provider.description }}</p>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Prix de base</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ provider.base_price }} EUR/mois</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Commission</span>
                                        <span class="font-medium text-green-600">{{ provider.commission }}%</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Couverture max</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(provider.max_coverage) }}</span>
                                    </div>
                                </div>
                                <button class="mt-4 w-full py-2 border border-indigo-600 text-indigo-600 rounded-lg hover:bg-indigo-600 hover:text-white transition text-sm font-medium">
                                    {{ provider.is_active ? 'Configurer' : 'Activer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Insurance Plans -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Plans Proposes aux Clients</h2>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                            + Creer un plan
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Partenaire</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Prix/mois</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Couverture</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Souscriptions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="plan in plans" :key="plan.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="getPlanColor(plan.tier)">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ plan.name }}</p>
                                                <p class="text-xs text-gray-500">{{ plan.code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ plan.provider }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">{{ plan.price }} EUR</td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">{{ formatCurrency(plan.coverage) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ plan.subscriptions }}</td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                              :class="plan.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400'">
                                            {{ plan.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Modifier</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import TenantLayout from '@/Layouts/TenantLayout.vue';

const providers = [
    { id: 1, name: 'AXA Assurance', description: 'Leader europeen de l\'assurance', base_price: 9.90, commission: 15, max_coverage: 50000, is_active: true },
    { id: 2, name: 'Allianz', description: 'Assurance multirisques professionnelle', base_price: 11.90, commission: 12, max_coverage: 75000, is_active: true },
    { id: 3, name: 'MAIF', description: 'Assurance mutualiste responsable', base_price: 8.50, commission: 10, max_coverage: 30000, is_active: false },
    { id: 4, name: 'Generali', description: 'Solutions d\'assurance sur mesure', base_price: 10.90, commission: 14, max_coverage: 60000, is_active: false },
    { id: 5, name: 'Groupama', description: 'Assurance proche de vous', base_price: 7.90, commission: 11, max_coverage: 25000, is_active: false },
];

const plans = [
    { id: 1, name: 'Essentiel', code: 'INS-ESS', provider: 'AXA', price: 9.90, coverage: 10000, subscriptions: 45, is_active: true, tier: 'basic' },
    { id: 2, name: 'Confort', code: 'INS-CFT', provider: 'AXA', price: 14.90, coverage: 25000, subscriptions: 52, is_active: true, tier: 'standard' },
    { id: 3, name: 'Premium', code: 'INS-PRM', provider: 'Allianz', price: 24.90, coverage: 50000, subscriptions: 27, is_active: true, tier: 'premium' },
    { id: 4, name: 'Pro Max', code: 'INS-MAX', provider: 'Allianz', price: 39.90, coverage: 75000, subscriptions: 12, is_active: false, tier: 'premium' },
];

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(value);
};

const getPlanColor = (tier) => {
    const colors = {
        basic: 'bg-gray-500',
        standard: 'bg-blue-500',
        premium: 'bg-purple-500',
    };
    return colors[tier] || 'bg-gray-500';
};
</script>
