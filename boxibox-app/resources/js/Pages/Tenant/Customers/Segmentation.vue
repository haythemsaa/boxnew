<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    segmentStats: Object,
    customers: Object,
    currentSegment: String,
})

const recalculating = ref(false)
const showSegmentModal = ref(false)
const selectedSegment = ref(null)

const filterBySegment = (segment) => {
    router.get(route('tenant.customer-segmentation.index'), {
        segment: segment || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

const recalculateScores = () => {
    recalculating.value = true
    router.post(route('tenant.customer-segmentation.recalculate'), {}, {
        onFinish: () => {
            recalculating.value = false
        }
    })
}

const openSegmentDetails = (segment) => {
    selectedSegment.value = {
        key: segment,
        ...props.segmentStats.segments[segment],
    }
    showSegmentModal.value = true
}

const getSegmentRecommendations = (segment) => {
    const recommendations = {
        champions: [
            'Récompenser leur fidélité avec des offres exclusives',
            'Les inviter à parrainer de nouveaux clients',
            'Proposer des services premium ou upgrades',
        ],
        loyal: [
            'Maintenir la relation avec des communications personnalisées',
            'Proposer des programmes de fidélité',
        ],
        potential_loyalist: [
            'Encourager des interactions plus fréquentes',
            'Proposer des offres de fidélisation',
        ],
        new_customer: [
            'Mettre en place un parcours d\'onboarding',
            'Proposer une offre de bienvenue',
        ],
        promising: [
            'Encourager à augmenter la fréquence',
            'Proposer des services complémentaires',
        ],
        need_attention: [
            'Contacter rapidement pour comprendre la baisse',
            'Proposer une offre de reconquête',
        ],
        about_to_sleep: [
            'Envoyer une campagne de réactivation',
            'Proposer une offre limitée dans le temps',
        ],
        at_risk: [
            'Action urgente - contacter personnellement',
            'Comprendre les raisons du désengagement',
        ],
        cant_lose: [
            'Intervention immédiate du manager',
            'Offre de reconquête personnalisée',
        ],
        hibernating: [
            'Campagne de réactivation par email',
            'Offre spéciale "Vous nous manquez"',
        ],
        lost: [
            'Tentative finale de réactivation',
            'Enquête de satisfaction',
        ],
    }
    return recommendations[segment] || []
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const getColorClasses = (color) => {
    const colors = {
        emerald: { bg: 'bg-emerald-100', text: 'text-emerald-700', border: 'border-emerald-200' },
        blue: { bg: 'bg-blue-100', text: 'text-blue-700', border: 'border-blue-200' },
        cyan: { bg: 'bg-cyan-100', text: 'text-cyan-700', border: 'border-cyan-200' },
        violet: { bg: 'bg-violet-100', text: 'text-violet-700', border: 'border-violet-200' },
        indigo: { bg: 'bg-indigo-100', text: 'text-indigo-700', border: 'border-indigo-200' },
        amber: { bg: 'bg-amber-100', text: 'text-amber-700', border: 'border-amber-200' },
        orange: { bg: 'bg-orange-100', text: 'text-orange-700', border: 'border-orange-200' },
        red: { bg: 'bg-red-100', text: 'text-red-700', border: 'border-red-200' },
        rose: { bg: 'bg-rose-100', text: 'text-rose-700', border: 'border-rose-200' },
        gray: { bg: 'bg-gray-100', text: 'text-gray-700', border: 'border-gray-200' },
        slate: { bg: 'bg-slate-100', text: 'text-slate-700', border: 'border-slate-200' },
    }
    return colors[color] || colors.gray
}

const sortedSegments = computed(() => {
    if (!props.segmentStats?.segments) return []
    return Object.entries(props.segmentStats.segments)
        .sort((a, b) => b[1].count - a[1].count)
        .map(([key, data]) => ({ key, ...data }))
})

const totalValue = computed(() => {
    if (!props.segmentStats?.segments) return 0
    return Object.values(props.segmentStats.segments).reduce((sum, seg) => sum + seg.value, 0)
})
</script>

<template>
    <TenantLayout title="Segmentation Client">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-indigo-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Segmentation Client RFM</h1>
                                <p class="text-gray-500 mt-1">Analysez vos clients par Récence, Fréquence et Montant</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-0 flex gap-3 animate-fade-in-up" style="animation-delay: 0.1s">
                        <Link
                            :href="route('tenant.customer-segmentation.credit-scores')"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-all duration-200"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Credit Scoring
                        </Link>
                        <button
                            @click="recalculateScores"
                            :disabled="recalculating"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-lg shadow-indigo-500/25 disabled:opacity-50"
                        >
                            <svg v-if="recalculating" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ recalculating ? 'Calcul...' : 'Recalculer' }}
                        </button>
                    </div>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Total Clients</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1">{{ segmentStats?.total_customers || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.15s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Valeur Totale Annuelle</p>
                                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ formatCurrency(totalValue) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Champions</p>
                                <p class="text-2xl font-bold text-purple-600 mt-1">{{ segmentStats?.segments?.champions?.count || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segment Distribution -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 animate-fade-in-up" style="animation-delay: 0.25s">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Distribution des Segments</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                        <button
                            @click="filterBySegment(null)"
                            :class="[
                                'p-3 rounded-xl border-2 text-left transition-all',
                                !currentSegment
                                    ? 'border-indigo-500 bg-indigo-50'
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-xs font-medium text-gray-500">Tous</p>
                            <p class="text-lg font-bold text-gray-900">{{ segmentStats?.total_customers || 0 }}</p>
                        </button>

                        <button
                            v-for="segment in sortedSegments"
                            :key="segment.key"
                            @click="filterBySegment(segment.key)"
                            :class="[
                                'p-3 rounded-xl border-2 text-left transition-all',
                                currentSegment === segment.key
                                    ? `${getColorClasses(segment.color).border} ${getColorClasses(segment.color).bg}`
                                    : 'border-gray-200 hover:border-gray-300'
                            ]"
                        >
                            <p class="text-xs font-medium text-gray-500 truncate">{{ segment.label }}</p>
                            <div class="flex items-baseline gap-1">
                                <p class="text-lg font-bold" :class="getColorClasses(segment.color).text">{{ segment.count }}</p>
                                <p class="text-xs text-gray-400">({{ segment.percentage }}%)</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Segment Actions -->
                <div v-if="currentSegment" class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 mb-6 animate-fade-in-up" style="animation-delay: 0.3s">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">
                                Segment: {{ segmentStats?.segments[currentSegment]?.label }}
                            </h3>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ segmentStats?.segments[currentSegment]?.count }} clients -
                                {{ formatCurrency(segmentStats?.segments[currentSegment]?.value) }} de valeur annuelle
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-white rounded-lg border border-indigo-200 hover:bg-indigo-50">
                                Exporter
                            </button>
                            <button class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-white rounded-lg border border-indigo-200 hover:bg-indigo-50">
                                Campagne Email
                            </button>
                            <button class="px-3 py-1.5 text-sm font-medium text-indigo-700 bg-white rounded-lg border border-indigo-200 hover:bg-indigo-50">
                                Campagne SMS
                            </button>
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Recommandations:</p>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <li v-for="(rec, idx) in getSegmentRecommendations(currentSegment)" :key="idx" class="flex items-start gap-2">
                                <svg class="w-4 h-4 text-indigo-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ rec }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Customer Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.35s">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Clients {{ currentSegment ? `- ${segmentStats?.segments[currentSegment]?.label}` : '' }}
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Client</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">Segment</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">Score RFM</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">R</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">F</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">M</th>
                                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase">Contrats</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-if="!customers.data || customers.data.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <p class="text-gray-500">Aucun client trouvé</p>
                                        <p class="text-gray-400 text-sm mt-1">Cliquez sur "Recalculer" pour générer les scores RFM</p>
                                    </td>
                                </tr>
                                <tr
                                    v-else
                                    v-for="customer in customers.data"
                                    :key="customer.id"
                                    class="hover:bg-gray-50/50 transition-colors"
                                >
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold text-white"
                                                :class="customer.type === 'company' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'"
                                            >
                                                {{ customer.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ customer.name }}</p>
                                                <p class="text-xs text-gray-500">{{ customer.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            :class="[getColorClasses(customer.segment_color).bg, getColorClasses(customer.segment_color).text]"
                                            class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium"
                                        >
                                            {{ customer.segment_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-lg font-bold text-gray-900">{{ customer.rfm_total }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold"
                                            :class="customer.rfm_recency >= 4 ? 'bg-emerald-100 text-emerald-700' : customer.rfm_recency >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ customer.rfm_recency }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold"
                                            :class="customer.rfm_frequency >= 4 ? 'bg-emerald-100 text-emerald-700' : customer.rfm_frequency >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ customer.rfm_frequency }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-bold"
                                            :class="customer.rfm_monetary >= 4 ? 'bg-emerald-100 text-emerald-700' : customer.rfm_monetary >= 3 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'"
                                        >
                                            {{ customer.rfm_monetary }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm text-gray-600">{{ customer.active_contracts }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <Link
                                            :href="route('tenant.customer-segmentation.show', customer.id)"
                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors inline-flex"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                            </svg>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="customers.links && customers.links.length > 3"
                        class="bg-gray-50/50 px-6 py-4 flex items-center justify-between border-t border-gray-100"
                    >
                        <p class="text-sm text-gray-600">
                            {{ customers.from }} - {{ customers.to }} sur {{ customers.total }}
                        </p>
                        <nav class="flex gap-1">
                            <template v-for="(link, index) in customers.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-200 hover:bg-gray-50',
                                        'px-3 py-2 text-sm font-medium rounded-lg',
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>

                <!-- RFM Legend -->
                <div class="mt-6 bg-gray-50 rounded-2xl p-6 animate-fade-in-up" style="animation-delay: 0.4s">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">Légende RFM</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                        <div>
                            <p class="font-medium text-gray-900">R - Récence</p>
                            <p class="text-gray-600">Nombre de jours depuis le dernier paiement. Plus c'est récent, plus le score est élevé.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">F - Fréquence</p>
                            <p class="text-gray-600">Nombre de paiements sur les 12 derniers mois. Plus il y a de paiements, plus le score est élevé.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">M - Montant</p>
                            <p class="text-gray-600">Total des paiements sur les 12 derniers mois. Plus le montant est élevé, plus le score est élevé.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
