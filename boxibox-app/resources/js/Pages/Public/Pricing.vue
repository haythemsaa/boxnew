<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import {
    CheckIcon,
    XMarkIcon,
    SparklesIcon,
    BuildingOffice2Icon,
    RocketLaunchIcon,
    BuildingLibraryIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: Object,
    widgets: Object,
    featureLabels: Object,
    supportLabels: Object,
})

const billingPeriod = ref('monthly')

const getPlanIcon = (slug) => {
    const icons = {
        starter: RocketLaunchIcon,
        professional: SparklesIcon,
        business: BuildingOffice2Icon,
        enterprise: BuildingLibraryIcon,
    }
    return icons[slug] || SparklesIcon
}

const formatPrice = (price) => {
    if (price === null) return 'Sur devis'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(price)
}

const getDisplayPrice = (plan) => {
    if (billingPeriod.value === 'yearly') {
        return plan.price_yearly ? Math.round(plan.price_yearly / 12) : null
    }
    return plan.price_monthly
}

const getSavings = (plan) => {
    if (!plan.price_monthly || !plan.price_yearly) return 0
    return (plan.price_monthly * 12) - plan.price_yearly
}

const mainFeatures = [
    'boxes_management',
    'customers_management',
    'contracts_management',
    'invoicing',
    'payment_reminders',
    'electronic_signature',
    'sepa_direct_debit',
    'crm_prospects',
    'advanced_analytics',
    'interactive_plan',
    'multi_site',
    'api_access',
    'booking_widget',
    'dynamic_pricing',
]

const getFeatureValue = (plan, feature) => {
    const value = plan.features[feature]
    if (typeof value === 'boolean') return value
    if (typeof value === 'number') {
        if (value === -1) return 'Illimité'
        if (value === 0) return false
        return `${value}/mois`
    }
    if (typeof value === 'string') {
        if (value === 'read') return 'Lecture'
        if (value === 'full') return 'Complet'
        if (value === 'addon') return 'Add-on'
        if (value === 'basic') return 'Basic inclus'
        if (value === 'pro') return 'Pro inclus'
    }
    return value
}
</script>

<template>
    <Head title="Tarifs - Boxibox" />

    <div class="min-h-screen bg-gradient-to-b from-gray-900 via-gray-900 to-gray-800">
        <!-- Header -->
        <header class="border-b border-gray-800">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="h-10 w-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl flex items-center justify-center">
                            <span class="text-white font-bold text-lg">B</span>
                        </div>
                        <span class="text-xl font-bold text-white">Boxibox</span>
                    </Link>
                    <div class="flex items-center gap-4">
                        <Link href="/login" class="text-gray-300 hover:text-white transition-colors">
                            Connexion
                        </Link>
                        <Link href="/register" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors">
                            Essai gratuit
                        </Link>
                    </div>
                </div>
            </nav>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <!-- Hero -->
            <div class="text-center mb-16">
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">
                    Des tarifs simples et transparents
                </h1>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    Choisissez le plan adapté à votre centre de self-stockage. Évoluez à tout moment.
                </p>

                <!-- Billing Toggle -->
                <div class="mt-8 flex items-center justify-center gap-4">
                    <span :class="billingPeriod === 'monthly' ? 'text-white' : 'text-gray-400'" class="text-sm font-medium">
                        Mensuel
                    </span>
                    <button
                        @click="billingPeriod = billingPeriod === 'monthly' ? 'yearly' : 'monthly'"
                        :class="billingPeriod === 'yearly' ? 'bg-purple-600' : 'bg-gray-700'"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200"
                    >
                        <span
                            :class="billingPeriod === 'yearly' ? 'translate-x-5' : 'translate-x-0'"
                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 mt-0.5 ml-0.5"
                        />
                    </button>
                    <span :class="billingPeriod === 'yearly' ? 'text-white' : 'text-gray-400'" class="text-sm font-medium">
                        Annuel
                        <span class="ml-1 px-2 py-0.5 bg-green-500/20 text-green-400 text-xs rounded-full">
                            -2 mois
                        </span>
                    </span>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <div
                    v-for="(plan, slug) in plans"
                    :key="slug"
                    :class="[
                        plan.popular ? 'ring-2 ring-purple-500 scale-105' : 'ring-1 ring-gray-700',
                        'relative bg-gray-800 rounded-2xl p-8 flex flex-col'
                    ]"
                >
                    <!-- Popular Badge -->
                    <div v-if="plan.popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="px-4 py-1 bg-purple-600 text-white text-sm font-medium rounded-full">
                            Le plus populaire
                        </span>
                    </div>

                    <!-- Plan Header -->
                    <div class="text-center mb-6">
                        <div :class="[
                            plan.popular ? 'bg-purple-600/20 text-purple-400' : 'bg-gray-700/50 text-gray-400',
                            'inline-flex p-3 rounded-xl mb-4'
                        ]">
                            <component :is="getPlanIcon(slug)" class="h-8 w-8" />
                        </div>
                        <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                        <p class="mt-2 text-sm text-gray-400">{{ plan.description }}</p>
                    </div>

                    <!-- Price -->
                    <div class="text-center mb-6">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold text-white">
                                {{ getDisplayPrice(plan) !== null ? formatPrice(getDisplayPrice(plan)) : 'Sur devis' }}
                            </span>
                            <span v-if="getDisplayPrice(plan) !== null" class="text-gray-400">/mois</span>
                        </div>
                        <p v-if="billingPeriod === 'yearly' && getSavings(plan) > 0" class="mt-1 text-sm text-green-400">
                            Économisez {{ formatPrice(getSavings(plan)) }}/an
                        </p>
                        <p v-if="plan.price_yearly && billingPeriod === 'yearly'" class="mt-1 text-xs text-gray-500">
                            Facturé {{ formatPrice(plan.price_yearly) }}/an
                        </p>
                    </div>

                    <!-- Limits -->
                    <div class="mb-6 p-4 bg-gray-900/50 rounded-xl space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Boxes</span>
                            <span class="text-white font-medium">
                                {{ plan.max_units === -1 ? 'Illimité' : `≤ ${plan.max_units}` }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Sites</span>
                            <span class="text-white font-medium">
                                {{ plan.max_sites === -1 ? 'Illimité' : plan.max_sites }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-400">Utilisateurs</span>
                            <span class="text-white font-medium">
                                {{ plan.max_users === -1 ? 'Illimité' : plan.max_users }}
                            </span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <Link
                        :href="plan.price_monthly ? `/register?plan=${slug}` : '/contact'"
                        :class="[
                            plan.popular
                                ? 'bg-purple-600 hover:bg-purple-700 text-white'
                                : 'bg-gray-700 hover:bg-gray-600 text-white',
                            'w-full py-3 px-4 rounded-xl font-medium text-center transition-colors mb-6'
                        ]"
                    >
                        {{ plan.price_monthly ? 'Commencer' : 'Nous contacter' }}
                    </Link>

                    <!-- Features List -->
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-300 mb-4">Inclus :</p>
                        <ul class="space-y-3">
                            <li class="flex items-start gap-3 text-sm">
                                <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                <span class="text-gray-300">{{ supportLabels[plan.support] }}</span>
                            </li>
                            <li v-for="feature in mainFeatures.slice(0, 8)" :key="feature" class="flex items-start gap-3 text-sm">
                                <template v-if="getFeatureValue(plan, feature)">
                                    <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                    <span class="text-gray-300">
                                        {{ featureLabels[feature] }}
                                        <span v-if="typeof getFeatureValue(plan, feature) === 'string'" class="text-gray-500">
                                            ({{ getFeatureValue(plan, feature) }})
                                        </span>
                                    </span>
                                </template>
                                <template v-else>
                                    <XMarkIcon class="h-5 w-5 text-gray-600 flex-shrink-0" />
                                    <span class="text-gray-500">{{ featureLabels[feature] }}</span>
                                </template>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Widget Addons Section -->
            <div class="mt-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white mb-4">Widget de Réservation</h2>
                    <p class="text-gray-400 max-w-2xl mx-auto">
                        Permettez à vos clients de réserver directement depuis votre site web, 24h/24 et 7j/7
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                    <div
                        v-for="(widget, slug) in widgets"
                        :key="slug"
                        class="bg-gray-800 rounded-2xl p-8 ring-1 ring-gray-700"
                    >
                        <h3 class="text-xl font-bold text-white mb-2">{{ widget.name }}</h3>
                        <p class="text-sm text-gray-400 mb-4">{{ widget.description }}</p>

                        <div class="flex items-baseline gap-1 mb-6">
                            <span class="text-3xl font-bold text-white">{{ formatPrice(widget.price_monthly) }}</span>
                            <span class="text-gray-400">/mois</span>
                        </div>

                        <div v-if="widget.included_in?.length" class="mb-4 p-3 bg-green-500/10 rounded-lg">
                            <p class="text-sm text-green-400">
                                Inclus dans : {{ widget.included_in.map(p => plans[p]?.name).join(', ') }}
                            </p>
                        </div>

                        <ul class="space-y-3">
                            <li
                                v-for="(value, feature) in widget.features"
                                :key="feature"
                                class="flex items-start gap-3 text-sm"
                            >
                                <template v-if="value">
                                    <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                    <span class="text-gray-300">{{ featureLabels[feature] }}</span>
                                </template>
                                <template v-else>
                                    <XMarkIcon class="h-5 w-5 text-gray-600 flex-shrink-0" />
                                    <span class="text-gray-500">{{ featureLabels[feature] }}</span>
                                </template>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white mb-4">Questions fréquentes</h2>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">
                    <div class="bg-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-medium text-white mb-2">Puis-je changer de plan à tout moment ?</h3>
                        <p class="text-gray-400">Oui, vous pouvez upgrader ou downgrader votre plan à tout moment. Le changement est effectif immédiatement et la facturation est ajustée au prorata.</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-medium text-white mb-2">Y a-t-il un engagement ?</h3>
                        <p class="text-gray-400">Non, tous nos plans sont sans engagement. Vous pouvez résilier à tout moment sans frais.</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-medium text-white mb-2">Proposez-vous un essai gratuit ?</h3>
                        <p class="text-gray-400">Oui, nous offrons 14 jours d'essai gratuit sur tous les plans, sans carte bancaire requise.</p>
                    </div>
                    <div class="bg-gray-800 rounded-xl p-6">
                        <h3 class="text-lg font-medium text-white mb-2">Que se passe-t-il si je dépasse mes limites ?</h3>
                        <p class="text-gray-400">Nous vous préviendrons à l'approche de vos limites. Vous pourrez alors choisir d'upgrader votre plan ou de rester sur votre plan actuel.</p>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-24 text-center">
                <div class="bg-gradient-to-r from-purple-600/20 to-indigo-600/20 rounded-2xl p-12 ring-1 ring-purple-500/30">
                    <h2 class="text-3xl font-bold text-white mb-4">Prêt à digitaliser votre centre ?</h2>
                    <p class="text-gray-400 mb-8 max-w-xl mx-auto">
                        Rejoignez les centaines d'opérateurs qui font confiance à Boxibox pour gérer leur activité.
                    </p>
                    <div class="flex items-center justify-center gap-4">
                        <Link href="/register" class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition-colors">
                            Commencer gratuitement
                        </Link>
                        <Link href="/contact" class="px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-xl transition-colors">
                            Demander une démo
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-800 mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">B</span>
                        </div>
                        <span class="text-lg font-bold text-white">Boxibox</span>
                    </div>
                    <p class="text-gray-500 text-sm">© 2024 Boxibox. Tous droits réservés.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
