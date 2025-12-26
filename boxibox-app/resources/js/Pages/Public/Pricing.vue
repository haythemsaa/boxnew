<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
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
    StarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    plans: {
        type: Array,
        default: () => [
            {
                id: 1,
                code: 'starter',
                name: 'Starter',
                description: 'Ideal pour demarrer votre activite',
                monthly_price: 49,
                yearly_price: 470,
                max_sites: 1,
                max_boxes: 50,
                max_users: 2,
                max_customers: 100,
                emails_per_month: 500,
                sms_per_month: 50,
                api_access: false,
                whitelabel: false,
                support_level: 'email',
                is_popular: false,
                modules: ['Gestion des boxes', 'CRM basique', 'Facturation', 'Portail client']
            },
            {
                id: 2,
                code: 'professional',
                name: 'Professional',
                description: 'Pour les operateurs en croissance',
                monthly_price: 149,
                yearly_price: 1430,
                max_sites: 3,
                max_boxes: 200,
                max_users: 10,
                max_customers: 500,
                emails_per_month: 2000,
                sms_per_month: 200,
                api_access: true,
                whitelabel: false,
                support_level: 'priority',
                is_popular: true,
                modules: ['Tout Starter +', 'Reservations en ligne', 'Paiements automatiques', 'Analytics avances', 'Multi-sites', 'IoT & Serrures']
            },
            {
                id: 3,
                code: 'business',
                name: 'Business',
                description: 'Pour les groupes et reseaux',
                monthly_price: 349,
                yearly_price: 3350,
                max_sites: null,
                max_boxes: null,
                max_users: null,
                max_customers: null,
                emails_per_month: null,
                sms_per_month: 1000,
                api_access: true,
                whitelabel: true,
                support_level: 'dedicated',
                is_popular: false,
                modules: ['Tout Professional +', 'Sites illimites', 'Marque blanche', 'API complete', 'Account manager', 'Formation personnalisee']
            },
            {
                id: 4,
                code: 'enterprise',
                name: 'Enterprise',
                description: 'Solution sur mesure',
                monthly_price: null,
                yearly_price: null,
                max_sites: null,
                max_boxes: null,
                max_users: null,
                max_customers: null,
                emails_per_month: null,
                sms_per_month: null,
                api_access: true,
                whitelabel: true,
                support_level: 'dedicated',
                is_popular: false,
                modules: ['Tout Business +', 'Deploiement dedie', 'SLA garanti', 'Integrations custom', 'Support 24/7']
            }
        ]
    },
    faqs: {
        type: Array,
        default: () => [
            { question: 'Puis-je changer de plan a tout moment ?', answer: 'Oui, vous pouvez upgrader ou downgrader votre plan a tout moment. Le changement prend effet immediatement et la facturation est proratisee.' },
            { question: 'Y a-t-il des frais caches ?', answer: 'Non, nos tarifs sont transparents. Vous payez uniquement l\'abonnement mensuel ou annuel. Les seuls frais supplementaires sont les SMS et emails au-dela des quotas inclus.' },
            { question: 'Comment fonctionne l\'essai gratuit ?', answer: 'L\'essai gratuit de 14 jours vous donne acces a toutes les fonctionnalites du plan Professional. Aucune carte bancaire requise. Vous choisissez votre plan a la fin de l\'essai.' },
            { question: 'Mes donnees sont-elles securisees ?', answer: 'Oui, nous utilisons le chiffrement SSL/TLS pour toutes les communications. Vos donnees sont hebergees en France sur des serveurs certifies ISO 27001 et conformes RGPD.' },
            { question: 'Quel support est inclus ?', answer: 'Tous les plans incluent le support par email. Le plan Professional inclut le support prioritaire avec reponse sous 4h. Le plan Business inclut un account manager dedie.' },
            { question: 'Proposez-vous une aide a la migration ?', answer: 'Oui, notre equipe vous accompagne gratuitement dans la migration de vos donnees depuis votre ancien systeme. Nous importons vos clients, contrats et historique.' }
        ]
    }
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
    if (!num) return 'Illimite'
    return new Intl.NumberFormat('fr-FR').format(num)
}

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? null : index
}

const getSupportLabel = (level) => {
    const labels = {
        'none': 'Communaute uniquement',
        'email': 'Support par email',
        'priority': 'Support prioritaire',
        'dedicated': 'Account manager dedie',
    }
    return labels[level] || level
}
</script>

<template>
    <Head title="Tarifs - Boxibox" />

    <PublicLayout>
        <!-- Hero Section -->
        <section class="relative pt-32 pb-20 bg-gradient-to-b from-slate-900 via-slate-900 to-indigo-950 overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-[url('/images/grid.svg')] opacity-20"></div>
                <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
            </div>

            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <span class="px-4 py-1.5 bg-green-500/20 text-green-400 rounded-full text-sm font-medium mb-6 inline-block">
                    14 jours d'essai gratuit
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6">
                    Des tarifs simples et
                    <span class="bg-gradient-to-r from-indigo-400 to-purple-400 bg-clip-text text-transparent">transparents</span>
                </h1>
                <p class="text-xl text-slate-300 max-w-2xl mx-auto mb-10">
                    Choisissez le plan adapte a votre centre de self-stockage. Evolutif, sans engagement, sans frais caches.
                </p>

                <!-- Billing Toggle -->
                <div class="inline-flex items-center gap-4 p-1.5 bg-slate-800/50 rounded-xl backdrop-blur-sm">
                    <button
                        @click="billingPeriod = 'monthly'"
                        :class="[
                            'px-6 py-2.5 rounded-lg font-medium transition-all',
                            billingPeriod === 'monthly'
                                ? 'bg-white text-slate-900 shadow-lg'
                                : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Mensuel
                    </button>
                    <button
                        @click="billingPeriod = 'yearly'"
                        :class="[
                            'px-6 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2',
                            billingPeriod === 'yearly'
                                ? 'bg-white text-slate-900 shadow-lg'
                                : 'text-slate-400 hover:text-white'
                        ]"
                    >
                        Annuel
                        <span class="px-2 py-0.5 bg-green-500 text-white text-xs rounded-full font-bold">
                            -20%
                        </span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Pricing Cards -->
        <section class="py-20 bg-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-4">
                    <div
                        v-for="plan in plans"
                        :key="plan.id"
                        :class="[
                            plan.is_popular
                                ? 'ring-2 ring-indigo-500 lg:scale-105 bg-white shadow-2xl shadow-indigo-500/20'
                                : 'bg-white shadow-lg',
                            'relative rounded-2xl p-8 flex flex-col'
                        ]"
                    >
                        <!-- Popular Badge -->
                        <div v-if="plan.is_popular" class="absolute -top-4 left-1/2 -translate-x-1/2">
                            <span class="px-4 py-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-1">
                                <StarIcon class="w-4 h-4" />
                                Le plus populaire
                            </span>
                        </div>

                        <!-- Plan Header -->
                        <div class="text-center mb-6">
                            <div :class="[
                                plan.is_popular ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-600',
                                'inline-flex p-3 rounded-xl mb-4'
                            ]">
                                <component :is="getPlanIcon(plan.code)" class="h-8 w-8" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-900">{{ plan.name }}</h3>
                            <p class="mt-2 text-sm text-slate-500">{{ plan.description }}</p>
                        </div>

                        <!-- Price -->
                        <div class="text-center mb-6">
                            <div class="flex items-baseline justify-center gap-1">
                                <span class="text-5xl font-bold text-slate-900">
                                    {{ plan.monthly_price ? formatPrice(getDisplayPrice(plan)) : '' }}
                                </span>
                                <span v-if="plan.monthly_price" class="text-slate-500">/mois</span>
                                <span v-else class="text-2xl font-bold text-slate-900">Sur devis</span>
                            </div>
                            <p v-if="billingPeriod === 'yearly' && getSavings(plan) > 0" class="mt-2 text-sm text-green-600 font-medium">
                                Economisez {{ formatPrice(getSavings(plan)) }}/an
                            </p>
                            <p v-if="billingPeriod === 'yearly' && plan.yearly_price" class="mt-1 text-xs text-slate-400">
                                Facture {{ formatPrice(plan.yearly_price) }}/an
                            </p>
                        </div>

                        <!-- Limits -->
                        <div class="mb-6 p-4 bg-slate-50 rounded-xl space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <CubeIcon class="h-4 w-4" />
                                    <span>Boxes</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ plan.max_boxes ? `${formatNumber(plan.max_boxes)}` : 'Illimite' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <MapPinIcon class="h-4 w-4" />
                                    <span>Sites</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ plan.max_sites ? plan.max_sites : 'Illimite' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <UsersIcon class="h-4 w-4" />
                                    <span>Utilisateurs</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ plan.max_users ? plan.max_users : 'Illimite' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <UserGroupIcon class="h-4 w-4" />
                                    <span>Clients</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ plan.max_customers ? formatNumber(plan.max_customers) : 'Illimite' }}
                                </span>
                            </div>
                        </div>

                        <!-- Quotas -->
                        <div class="mb-6 p-4 bg-slate-50 rounded-xl space-y-3">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Quotas inclus</p>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <EnvelopeIcon class="h-4 w-4" />
                                    <span>Emails/mois</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ formatNumber(plan.emails_per_month) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <DevicePhoneMobileIcon class="h-4 w-4" />
                                    <span>SMS/mois</span>
                                </div>
                                <span class="text-slate-900 font-semibold">
                                    {{ formatNumber(plan.sms_per_month) }}
                                </span>
                            </div>
                        </div>

                        <!-- CTA -->
                        <Link
                            :href="plan.code === 'enterprise' ? '/contact' : `/register?plan=${plan.code}`"
                            :class="[
                                plan.is_popular
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white shadow-lg shadow-indigo-500/25'
                                    : 'bg-slate-900 hover:bg-slate-800 text-white',
                                'w-full py-3.5 px-4 rounded-xl font-semibold text-center transition-all mb-6'
                            ]"
                        >
                            {{ plan.code === 'enterprise' ? 'Contacter les ventes' : 'Essai gratuit 14 jours' }}
                        </Link>

                        <!-- Features List -->
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-700 mb-4">
                                Fonctionnalites incluses :
                            </p>
                            <ul class="space-y-2.5">
                                <li class="flex items-start gap-2 text-sm">
                                    <CheckIcon class="h-5 w-5 text-green-500 flex-shrink-0" />
                                    <span class="text-slate-600">{{ getSupportLabel(plan.support_level) }}</span>
                                </li>
                                <li v-for="module in plan.modules.slice(0, 6)" :key="module" class="flex items-start gap-2 text-sm">
                                    <CheckIcon class="h-5 w-5 text-green-500 flex-shrink-0" />
                                    <span class="text-slate-600">{{ module }}</span>
                                </li>
                                <li v-if="plan.modules.length > 6" class="flex items-start gap-2 text-sm">
                                    <CheckIcon class="h-5 w-5 text-green-500 flex-shrink-0" />
                                    <span class="text-slate-600">+ {{ plan.modules.length - 6 }} autres modules</span>
                                </li>
                                <li v-if="plan.api_access" class="flex items-start gap-2 text-sm">
                                    <CheckIcon class="h-5 w-5 text-indigo-500 flex-shrink-0" />
                                    <span class="text-indigo-600 font-medium">Acces API complet</span>
                                </li>
                                <li v-if="plan.whitelabel" class="flex items-start gap-2 text-sm">
                                    <CheckIcon class="h-5 w-5 text-indigo-500 flex-shrink-0" />
                                    <span class="text-indigo-600 font-medium">Marque blanche</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comparison Section -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">Comparez les fonctionnalites</h2>
                    <p class="text-slate-600">Decouvrez ce qui est inclus dans chaque plan</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="text-left py-4 px-4 font-semibold text-slate-900">Fonctionnalites</th>
                                <th class="text-center py-4 px-4 font-semibold text-slate-900">Starter</th>
                                <th class="text-center py-4 px-4 font-semibold text-indigo-600 bg-indigo-50 rounded-t-xl">Professional</th>
                                <th class="text-center py-4 px-4 font-semibold text-slate-900">Business</th>
                                <th class="text-center py-4 px-4 font-semibold text-slate-900">Enterprise</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Gestion des boxes</td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">CRM & Clients</td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Facturation automatique</td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Reservations en ligne</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Paiements automatiques</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">IoT & Serrures connectees</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Analytics avances</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">API complete</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Marque blanche</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4 text-slate-600">Account manager dedie</td>
                                <td class="text-center py-4 px-4"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4 bg-indigo-50/50"><XMarkIcon class="w-5 h-5 text-slate-300 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                                <td class="text-center py-4 px-4"><CheckIcon class="w-5 h-5 text-green-500 mx-auto" /></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-20 bg-slate-50">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">Questions frequentes</h2>
                    <p class="text-slate-600">Tout ce que vous devez savoir sur nos tarifs</p>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(faq, index) in faqs"
                        :key="index"
                        class="bg-white rounded-xl shadow-sm overflow-hidden"
                    >
                        <button
                            @click="toggleFaq(index)"
                            class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-slate-50 transition-colors"
                        >
                            <h3 class="text-lg font-semibold text-slate-900">{{ faq.question }}</h3>
                            <ChevronDownIcon
                                :class="[
                                    openFaq === index ? 'rotate-180' : '',
                                    'h-5 w-5 text-slate-400 transition-transform'
                                ]"
                            />
                        </button>
                        <div v-show="openFaq === index" class="px-6 pb-5">
                            <p class="text-slate-600 leading-relaxed">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-gradient-to-br from-indigo-600 via-purple-600 to-indigo-800 relative overflow-hidden">
            <div class="absolute inset-0">
                <div class="absolute inset-0 bg-[url('/images/grid.svg')] opacity-10"></div>
            </div>

            <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Pret a digitaliser votre centre ?
                </h2>
                <p class="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
                    Rejoignez les centaines d'operateurs qui font confiance a Boxibox pour gerer leur activite de self-stockage.
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link href="/register" class="px-8 py-4 bg-white text-indigo-600 font-semibold rounded-xl hover:bg-indigo-50 transition-colors shadow-xl">
                        Commencer gratuitement
                    </Link>
                    <Link href="/demo" class="px-8 py-4 border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/10 transition-colors">
                        Demander une demo
                    </Link>
                </div>
                <p class="mt-8 text-indigo-200 text-sm">
                    14 jours d'essai gratuit - Sans carte bancaire - Configuration en 5 minutes
                </p>
            </div>
        </section>
    </PublicLayout>
</template>
