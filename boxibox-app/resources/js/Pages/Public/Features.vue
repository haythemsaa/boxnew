<template>
    <PublicLayout>
        <!-- Hero Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <h1 class="text-4xl sm:text-5xl font-bold text-gray-900 mb-6">
                        Des fonctionnalités
                        <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                            puissantes
                        </span>
                    </h1>
                    <p class="text-xl text-gray-600">
                        Tout ce dont vous avez besoin pour gérer votre activité de self-storage, de la réservation à la facturation.
                    </p>
                </div>
            </div>
        </section>

        <!-- Feature Categories -->
        <section class="py-24 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div v-for="(category, catIndex) in featureCategories" :key="catIndex" class="mb-24 last:mb-0">
                    <!-- Category Header -->
                    <div class="flex items-center gap-4 mb-12">
                        <div class="w-14 h-14 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl flex items-center justify-center">
                            <component :is="getCategoryIcon(category.icon)" class="w-7 h-7 text-indigo-600" />
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">{{ category.category }}</h2>
                        </div>
                    </div>

                    <!-- Features Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div
                            v-for="(feature, fIndex) in category.features"
                            :key="fIndex"
                            class="bg-gradient-to-br from-gray-50 to-white rounded-2xl p-8 border border-gray-100 hover:border-indigo-200 hover:shadow-lg transition-all duration-300"
                        >
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ feature.title }}</h3>
                            <p class="text-gray-600">{{ feature.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Integrations Section -->
        <section class="py-24 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Intégrations natives</h2>
                    <p class="text-xl text-gray-600">Connectez Boxibox à vos outils préférés</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                    <div
                        v-for="integration in integrations"
                        :key="integration.name"
                        class="bg-white rounded-xl p-6 text-center border border-gray-100 hover:border-indigo-200 hover:shadow-md transition-all"
                    >
                        <div class="w-12 h-12 bg-gray-100 rounded-lg mx-auto mb-3 flex items-center justify-center">
                            <span class="text-2xl">{{ integration.icon }}</span>
                        </div>
                        <p class="font-medium text-gray-700 text-sm">{{ integration.name }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison Table -->
        <section class="py-24 bg-white">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">Pourquoi choisir Boxibox ?</h2>
                    <p class="text-xl text-gray-600">Comparez nos fonctionnalités avec la concurrence</p>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="text-left py-4 px-6 font-semibold text-gray-900">Fonctionnalité</th>
                                <th class="text-center py-4 px-6 font-semibold text-indigo-600">Boxibox</th>
                                <th class="text-center py-4 px-6 font-semibold text-gray-500">Autres</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="comparison in comparisons" :key="comparison.feature">
                                <td class="py-4 px-6 text-gray-700">{{ comparison.feature }}</td>
                                <td class="py-4 px-6 text-center">
                                    <svg v-if="comparison.boxibox" class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <svg v-else class="w-6 h-6 text-gray-300 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <svg v-if="comparison.others" class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <span v-else class="text-gray-400 text-sm">Partiel</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-gradient-to-br from-indigo-600 to-purple-700">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Prêt à découvrir toutes nos fonctionnalités ?
                </h2>
                <p class="text-xl text-indigo-100 mb-8">
                    Demandez une démo personnalisée pour voir Boxibox en action.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/demo" class="bg-white text-indigo-600 hover:bg-gray-100 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Demander une démo
                    </Link>
                    <Link href="/pricing" class="border-2 border-white text-white hover:bg-white/10 font-semibold px-8 py-4 rounded-xl transition-colors">
                        Voir les tarifs
                    </Link>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { h } from 'vue'

const props = defineProps({
    featureCategories: Array,
})

const integrations = [
    { name: 'Stripe', icon: '💳' },
    { name: 'SEPA', icon: '🏦' },
    { name: 'Nuki', icon: '🔐' },
    { name: 'Zapier', icon: '⚡' },
    { name: 'Google', icon: '🔍' },
    { name: 'Mailchimp', icon: '📧' },
    { name: 'Twilio', icon: '📱' },
    { name: 'QuickBooks', icon: '📊' },
    { name: 'Slack', icon: '💬' },
    { name: 'Webhook', icon: '🔗' },
    { name: 'API REST', icon: '🚀' },
    { name: 'Excel', icon: '📑' },
]

const comparisons = [
    { feature: 'Plans interactifs SVG', boxibox: true, others: false },
    { feature: 'Multi-sites illimités', boxibox: true, others: false },
    { feature: 'CRM & Marketing intégré', boxibox: true, others: false },
    { feature: 'Dynamic Pricing AI', boxibox: true, others: false },
    { feature: 'Prélèvements SEPA', boxibox: true, others: true },
    { feature: 'Smart Locks Integration', boxibox: true, others: false },
    { feature: 'API complète', boxibox: true, others: true },
    { feature: 'Support français 24/7', boxibox: true, others: false },
    { feature: 'RGPD compliant', boxibox: true, others: true },
    { feature: 'Mode Kiosk', boxibox: true, others: false },
]

const categoryIcons = {
    'cog': {
        render() {
            return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-7 h-7' }, [
                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' })
            ])
        }
    },
    'megaphone': {
        render() {
            return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-7 h-7' }, [
                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' })
            ])
        }
    },
    'banknotes': {
        render() {
            return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-7 h-7' }, [
                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z' })
            ])
        }
    },
    'shield-check': {
        render() {
            return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-7 h-7' }, [
                h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' })
            ])
        }
    },
}

const getCategoryIcon = (name) => {
    return categoryIcons[name] || categoryIcons['cog']
}
</script>
