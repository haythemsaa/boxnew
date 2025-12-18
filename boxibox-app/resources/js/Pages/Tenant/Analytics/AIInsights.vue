<template>
    <TenantLayout>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                    <span class="text-3xl">🤖</span>
                    Intelligence Artificielle & Insights
                </h1>
                <p class="text-gray-600 mt-1">
                    Analyses prédictives et recommandations pour optimiser votre business
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Churn Risk -->
                <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Clients à risque</p>
                            <p class="text-3xl font-bold text-red-600">{{ stats.churn_at_risk }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">⚠️</span>
                        </div>
                    </div>
                    <Link
                        href="/tenant/analytics/ai/churn"
                        class="mt-4 text-sm text-red-600 hover:text-red-700 flex items-center gap-1"
                    >
                        Voir les prédictions de churn →
                    </Link>
                </div>

                <!-- Upsell Opportunities -->
                <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Opportunités upsell</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.upsell_opportunities }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">📈</span>
                        </div>
                    </div>
                    <Link
                        href="/tenant/analytics/ai/upsell"
                        class="mt-4 text-sm text-green-600 hover:text-green-700 flex items-center gap-1"
                    >
                        Voir les opportunités →
                    </Link>
                </div>

                <!-- Pricing Optimizations -->
                <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Prix à optimiser</p>
                            <p class="text-3xl font-bold text-blue-600">{{ stats.pricing_optimizations }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">💰</span>
                        </div>
                    </div>
                    <Link
                        href="/tenant/analytics/ai/pricing"
                        class="mt-4 text-sm text-blue-600 hover:text-blue-700 flex items-center gap-1"
                    >
                        Optimiser les prix →
                    </Link>
                </div>

                <!-- Hot Leads -->
                <div class="bg-white rounded-xl shadow-sm border p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Leads chauds</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.hot_leads }}</p>
                        </div>
                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                            <span class="text-2xl">🔥</span>
                        </div>
                    </div>
                    <Link
                        href="/tenant/analytics/ai/leads"
                        class="mt-4 text-sm text-orange-600 hover:text-orange-700 flex items-center gap-1"
                    >
                        Voir les leads chauds →
                    </Link>
                </div>
            </div>

            <!-- Main Features Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Churn Prediction Card -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-gradient-to-r from-red-500 to-red-600 p-4">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <span>🎯</span> Prédiction de Churn
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">
                            Identifiez les clients susceptibles de résilier leur contrat et agissez avant qu'il ne soit trop tard.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700 mb-4">
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Score de risque 0-100 par client
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Facteurs de risque identifiés
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Actions de rétention suggérées
                            </li>
                        </ul>
                        <Link
                            href="/tenant/analytics/ai/churn"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                        >
                            Analyser le churn
                        </Link>
                    </div>
                </div>

                <!-- Pricing Optimization Card -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <span>💎</span> Optimisation des Prix
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">
                            Maximisez vos revenus avec des prix optimisés basés sur la demande, la saisonnalité et la concurrence.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700 mb-4">
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Prix optimal par box
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Ajustement saisonnier automatique
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Impact revenus estimé
                            </li>
                        </ul>
                        <Link
                            href="/tenant/analytics/ai/pricing"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            Optimiser les prix
                        </Link>
                    </div>
                </div>

                <!-- Upsell Opportunities Card -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <span>📊</span> Opportunités d'Upsell
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">
                            Découvrez quels clients sont prêts pour une montée en gamme ou des services additionnels.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700 mb-4">
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Score d'upsell par client
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Recommandations personnalisées
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Revenus additionnels estimés
                            </li>
                        </ul>
                        <Link
                            href="/tenant/analytics/ai/upsell"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                        >
                            Voir les opportunités
                        </Link>
                    </div>
                </div>

                <!-- Occupation Forecast Card -->
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4">
                        <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                            <span>📅</span> Prévision d'Occupation
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">
                            Anticipez votre taux d'occupation sur les 3 prochains mois pour planifier vos actions commerciales.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-700 mb-4">
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Prévisions journalières
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Détection de tendances
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-green-500">✓</span>
                                Intervalles de confiance
                            </li>
                        </ul>
                        <Link
                            href="/tenant/analytics/ai/forecast"
                            class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
                        >
                            Voir les prévisions
                        </Link>
                    </div>
                </div>
            </div>

            <!-- AI Accuracy Notice -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl">💡</span>
                    <div>
                        <h4 class="font-medium text-yellow-800">Note sur la précision</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Les prédictions IA s'améliorent avec le temps et les données. Plus vous utilisez BoxiBox, plus les recommandations seront précises.
                            La précision actuelle est estimée à <strong>85-90%</strong> pour les prédictions de churn et <strong>80-85%</strong> pour l'optimisation des prix.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<style scoped>
/* Primary colors */
.bg-primary {
    background-color: #8FBD56;
}
.text-primary {
    color: #8FBD56;
}
</style>
