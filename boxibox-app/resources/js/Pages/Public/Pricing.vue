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
    ChevronDownIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    CubeIcon,
    UsersIcon,
    MapPinIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: Array,
    faqs: Array,
})

const billingPeriod = ref('monthly')
const openFaq = ref(null)

const getPlanIcon = (code) => {
    const icons = {
        starter: RocketLaunchIcon,
        professional: SparklesIcon,
        business: BuildingOffice2Icon,
        enterprise: BuildingLibraryIcon,
    }
    return icons[code] || SparklesIcon
}

const getPlanColor = (code) => {
    const colors = {
        starter: 'blue',
        professional: 'purple',
        business: 'orange',
        enterprise: 'indigo',
    }
    return colors[code] || 'purple'
}

const formatPrice = (price) => {
    if (price === null || price === undefined) return 'Sur devis'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(price)
}

const getDisplayPrice = (plan) => {
    if (billingPeriod.value === 'yearly') {
        return plan.yearly_price ? Math.round(plan.yearly_price / 12) : plan.monthly_price
    }
    return plan.monthly_price
}

const getSavings = (plan) => {
    if (!plan.monthly_price || !plan.yearly_price) return 0
    return (plan.monthly_price * 12) - plan.yearly_price
}

const formatNumber = (num) => {
    if (!num) return 'Illimité'
    return new Intl.NumberFormat('fr-FR').format(num)
}

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index
}

const getSupportLabel = (level) => {
    const labels = {
        'none': 'Communauté uniquement',
        'email': 'Support par email',
        'priority': 'Support prioritaire',
        'dedicated': 'Account manager dédié',
    }
    return labels[level] || level
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
                    <div class="hidden md:flex items-center gap-8">
                        <Link href="/" class="text-gray-400 hover:text-white transition-colors">Accueil</Link>
                        <Link href="/features" class="text-gray-400 hover:text-white transition-colors">Fonctionnalités</Link>
                        <Link href="/pricing" class="text-white font-medium">Tarifs</Link>
                        <Link href="/contact" class="text-gray-400 hover:text-white transition-colors">Contact</Link>
                    </div>
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
                <span class="px-4 py-1.5 bg-purple-500/20 text-purple-400 rounded-full text-sm font-medium mb-6 inline-block">
                    14 jours d'essai gratuit
                </span>
                <h1 class="text-4xl sm:text-5xl font-bold text-white mb-4">
                    Des tarifs simples et transparents
                </h1>
                <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                    Choisissez le plan adapté à votre centre de self-stockage. Évoluez à tout moment sans frais cachés.
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
                            -20%
                        </span>
                    </span>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                <div
                    v-for="plan in plans"
                    :key="plan.id"
                    :class="[
                        plan.is_popular ? 'ring-2 ring-purple-500 lg:scale-105' : 'ring-1 ring-gray-700',
                        'relative bg-gray-800 rounded-2xl p-8 flex flex-col'
                    ]"
                >
                    <!-- Popular Badge -->
                    <div v-if="plan.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                        <span class="px-4 py-1 bg-purple-600 text-white text-sm font-medium rounded-full">
                            Le plus populaire
                        </span>
                    </div>

                    <!-- Plan Header -->
                    <div class="text-center mb-6">
                        <div :class="[
                            plan.is_popular ? 'bg-purple-600/20 text-purple-400' : 'bg-gray-700/50 text-gray-400',
                            'inline-flex p-3 rounded-xl mb-4'
                        ]">
                            <component :is="getPlanIcon(plan.code)" class="h-8 w-8" />
                        </div>
                        <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                        <p class="mt-2 text-sm text-gray-400">{{ plan.description }}</p>
                    </div>

                    <!-- Price -->
                    <div class="text-center mb-6">
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-bold text-white">
                                {{ formatPrice(getDisplayPrice(plan)) }}
                            </span>
                            <span class="text-gray-400">/mois</span>
                        </div>
                        <p v-if="billingPeriod === 'yearly' && getSavings(plan) > 0" class="mt-1 text-sm text-green-400">
                            Économisez {{ formatPrice(getSavings(plan)) }}/an
                        </p>
                        <p v-if="billingPeriod === 'yearly'" class="mt-1 text-xs text-gray-500">
                            Facturé {{ formatPrice(plan.yearly_price) }}/an
                        </p>
                    </div>

                    <!-- Limits -->
                    <div class="mb-6 p-4 bg-gray-900/50 rounded-xl space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <CubeIcon class="h-4 w-4" />
                                <span>Boxes</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ plan.max_boxes ? `≤ ${formatNumber(plan.max_boxes)}` : 'Illimité' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <MapPinIcon class="h-4 w-4" />
                                <span>Sites</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ plan.max_sites ? plan.max_sites : 'Illimité' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <UsersIcon class="h-4 w-4" />
                                <span>Utilisateurs</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ plan.max_users ? plan.max_users : 'Illimité' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <UserGroupIcon class="h-4 w-4" />
                                <span>Clients</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ plan.max_customers ? formatNumber(plan.max_customers) : 'Illimité' }}
                            </span>
                        </div>
                    </div>

                    <!-- Quotas -->
                    <div class="mb-6 p-4 bg-gray-900/50 rounded-xl space-y-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Quotas inclus</p>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <EnvelopeIcon class="h-4 w-4" />
                                <span>Emails/mois</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ formatNumber(plan.emails_per_month) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 text-gray-400">
                                <DevicePhoneMobileIcon class="h-4 w-4" />
                                <span>SMS/mois</span>
                            </div>
                            <span class="text-white font-medium">
                                {{ formatNumber(plan.sms_per_month) }}
                            </span>
                        </div>
                    </div>

                    <!-- CTA -->
                    <Link
                        :href="`/register?plan=${plan.code}`"
                        :class="[
                            plan.is_popular
                                ? 'bg-purple-600 hover:bg-purple-700 text-white'
                                : 'bg-gray-700 hover:bg-gray-600 text-white',
                            'w-full py-3 px-4 rounded-xl font-medium text-center transition-colors mb-6'
                        ]"
                    >
                        Essai gratuit 14 jours
                    </Link>

                    <!-- Features List -->
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-300 mb-4">
                            {{ plan.modules_count }} modules inclus :
                        </p>
                        <ul class="space-y-2.5">
                            <li class="flex items-start gap-2 text-sm">
                                <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                <span class="text-gray-300">{{ getSupportLabel(plan.support_level) }}</span>
                            </li>
                            <li v-for="module in plan.modules.slice(0, 6)" :key="module" class="flex items-start gap-2 text-sm">
                                <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                <span class="text-gray-300">{{ module }}</span>
                            </li>
                            <li v-if="plan.modules.length > 6" class="flex items-start gap-2 text-sm">
                                <CheckIcon class="h-5 w-5 text-green-400 flex-shrink-0" />
                                <span class="text-gray-300">+ {{ plan.modules.length - 6 }} autres modules</span>
                            </li>
                            <li v-if="plan.api_access" class="flex items-start gap-2 text-sm">
                                <CheckIcon class="h-5 w-5 text-purple-400 flex-shrink-0" />
                                <span class="text-purple-300">Accès API complet</span>
                            </li>
                            <li v-if="plan.whitelabel" class="flex items-start gap-2 text-sm">
                                <CheckIcon class="h-5 w-5 text-purple-400 flex-shrink-0" />
                                <span class="text-purple-300">Marque blanche</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Enterprise CTA -->
            <div class="mt-16 bg-gradient-to-r from-gray-800 to-gray-800/50 rounded-2xl p-8 ring-1 ring-gray-700">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <h3 class="text-2xl font-bold text-white mb-2">Besoin d'une solution sur mesure ?</h3>
                        <p class="text-gray-400">Contactez notre équipe commerciale pour un devis personnalisé et une démonstration dédiée.</p>
                    </div>
                    <Link href="/contact" class="px-8 py-3 bg-white hover:bg-gray-100 text-gray-900 font-medium rounded-xl transition-colors whitespace-nowrap">
                        Contacter les ventes
                    </Link>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="mt-24">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-white mb-4">Questions fréquentes</h2>
                    <p class="text-gray-400">Tout ce que vous devez savoir sur nos tarifs</p>
                </div>

                <div class="max-w-3xl mx-auto space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-gray-800 rounded-xl overflow-hidden ring-1 ring-gray-700"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-4 flex items-center justify-between text-left"
                        >
                            <h3 class="text-lg font-medium text-white">{{ faq.question }}</h3>
                            <ChevronDownIcon
                                :class="[
                                    openFaq === index ? 'rotate-180' : '',
                                    'h-5 w-5 text-gray-400 transition-transform'
                                ]"
                            />
                        </button>
                        <div
                            v-show="openFaq === index"
                            class="px-6 pb-4"
                        >
                            <p class="text-gray-400">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="mt-24 text-center">
                <div class="bg-gradient-to-r from-purple-600/20 to-indigo-600/20 rounded-2xl p-12 ring-1 ring-purple-500/30">
                    <h2 class="text-3xl font-bold text-white mb-4">Prêt à digitaliser votre centre ?</h2>
                    <p class="text-gray-400 mb-8 max-w-xl mx-auto">
                        Rejoignez les centaines d'opérateurs qui font confiance à Boxibox pour gérer leur activité de self-stockage.
                    </p>
                    <div class="flex items-center justify-center gap-4 flex-wrap">
                        <Link href="/register" class="px-8 py-3 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition-colors">
                            Commencer gratuitement
                        </Link>
                        <Link href="/demo" class="px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white font-medium rounded-xl transition-colors">
                            Demander une démo
                        </Link>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-800 mt-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2">
                        <div class="h-8 w-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold">B</span>
                        </div>
                        <span class="text-lg font-bold text-white">Boxibox</span>
                    </div>
                    <div class="flex items-center gap-6 text-sm text-gray-400">
                        <Link href="/legal" class="hover:text-white transition-colors">Mentions légales</Link>
                        <Link href="/privacy" class="hover:text-white transition-colors">Confidentialité</Link>
                        <Link href="/terms" class="hover:text-white transition-colors">CGV</Link>
                    </div>
                    <p class="text-gray-500 text-sm">© 2025 Boxibox. Tous droits réservés.</p>
                </div>
            </div>
        </footer>
    </div>
</template>
