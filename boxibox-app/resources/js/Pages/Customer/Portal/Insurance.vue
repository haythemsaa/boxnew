<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    contracts: Array,
    plans: Array,
    policies: Array,
    claims: Array,
    stats: Object,
})

const activeTab = ref('overview') // 'overview', 'plans', 'claims'
const showSubscribeModal = ref(false)
const showClaimModal = ref(false)
const showCancelModal = ref(false)
const selectedContract = ref(null)
const selectedPolicy = ref(null)

const subscribeForm = useForm({
    contract_id: null,
    plan_id: null,
    declared_value: null,
    items_description: '',
})

const claimForm = useForm({
    policy_id: null,
    incident_date: '',
    incident_type: 'damage',
    description: '',
    items_damaged: [],
    estimated_damage: null,
})

const cancelForm = useForm({
    reason: '',
})

const openSubscribeModal = (contract) => {
    selectedContract.value = contract
    subscribeForm.contract_id = contract.id
    subscribeForm.plan_id = props.plans.find(p => p.is_default)?.id || props.plans[0]?.id
    showSubscribeModal.value = true
}

const submitSubscription = () => {
    subscribeForm.post(route('customer.portal.insurance.subscribe'), {
        preserveScroll: true,
        onSuccess: () => {
            showSubscribeModal.value = false
            subscribeForm.reset()
        },
    })
}

const openClaimModal = (policy) => {
    selectedPolicy.value = policy
    claimForm.policy_id = policy.id
    claimForm.incident_date = new Date().toISOString().split('T')[0]
    showClaimModal.value = true
}

const submitClaim = () => {
    claimForm.post(route('customer.portal.insurance.claim'), {
        preserveScroll: true,
        onSuccess: () => {
            showClaimModal.value = false
            claimForm.reset()
        },
    })
}

const openCancelModal = (policy) => {
    selectedPolicy.value = policy
    showCancelModal.value = true
}

const submitCancel = () => {
    cancelForm.post(route('customer.portal.insurance.cancel', selectedPolicy.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCancelModal.value = false
            cancelForm.reset()
        },
    })
}

const selectedPlan = computed(() => {
    return props.plans.find(p => p.id === subscribeForm.plan_id)
})

const formatPrice = (price) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price || 0)
}

const incidentTypeLabels = {
    theft: 'Vol',
    damage: 'Dommage',
    fire: 'Incendie',
    water: 'Dégât des eaux',
    other: 'Autre',
}

const claimStatusLabels = {
    pending: 'En attente',
    under_review: 'En cours d\'examen',
    approved: 'Approuvé',
    rejected: 'Refusé',
    paid: 'Indemnisé',
}

const claimStatusColors = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    under_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
}
</script>

<template>
    <Head title="Assurance & Protection" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Assurance & Protection</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Protégez vos biens stockés avec nos formules d'assurance
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ stats.insured_contracts }}/{{ stats.total_contracts }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Box assurés</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatPrice(stats.total_coverage) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Couverture totale</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ formatPrice(stats.monthly_premium) }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Prime mensuelle</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ stats.active_claims }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sinistres en cours</div>
                    </div>
                </div>

                <!-- Benefits Banner -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-2xl p-6 mb-8 text-white">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-white/20 rounded-xl">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold mb-2">Pourquoi souscrire une assurance ?</h2>
                            <ul class="space-y-1 text-indigo-100">
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Protection contre le vol, l'incendie et les dégâts des eaux
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Indemnisation rapide en cas de sinistre
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    Résiliation possible à tout moment
                                </li>
                                <li class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    À partir de 5,90€/mois
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-gray-700">
                    <button
                        @click="activeTab = 'overview'"
                        :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                            activeTab === 'overview'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]"
                    >
                        Mes contrats
                    </button>
                    <button
                        @click="activeTab = 'plans'"
                        :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                            activeTab === 'plans'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]"
                    >
                        Formules disponibles
                    </button>
                    <button
                        @click="activeTab = 'claims'"
                        :class="[
                            'px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors',
                            activeTab === 'claims'
                                ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'
                        ]"
                    >
                        Mes sinistres
                    </button>
                </div>

                <!-- Tab: Overview (Contracts with Insurance) -->
                <div v-if="activeTab === 'overview'" class="space-y-4">
                    <div v-for="contract in contracts" :key="contract.id"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                        <div class="p-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg">
                                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ contract.site_name }} - Box {{ contract.box_name }}</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Contrat N° {{ contract.contract_number }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Insurance Status -->
                                <div v-if="contract.has_insurance" class="flex items-center gap-4">
                                    <div class="text-right">
                                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-sm rounded-full font-medium">
                                            Assuré
                                        </span>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {{ contract.insurance.plan_name }}
                                        </p>
                                    </div>
                                </div>
                                <div v-else>
                                    <button
                                        @click="openSubscribeModal(contract)"
                                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors"
                                    >
                                        Souscrire une assurance
                                    </button>
                                </div>
                            </div>

                            <!-- Insurance Details (if insured) -->
                            <div v-if="contract.has_insurance" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Couverture</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ formatPrice(contract.insurance.coverage_amount) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Prime mensuelle</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ formatPrice(contract.insurance.premium_monthly) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Franchise</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ formatPrice(contract.insurance.deductible) }}</p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Depuis le</span>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ contract.insurance.start_date }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        @click="openClaimModal(contract.insurance)"
                                        class="px-3 py-1.5 text-sm bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-lg hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors"
                                    >
                                        Déclarer un sinistre
                                    </button>
                                    <button
                                        @click="openCancelModal(contract.insurance)"
                                        class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                    >
                                        Résilier
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="contracts.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun contrat actif</h3>
                        <p class="text-gray-500 dark:text-gray-400">Vous n'avez pas de contrat actif pour le moment.</p>
                    </div>
                </div>

                <!-- Tab: Available Plans -->
                <div v-if="activeTab === 'plans'" class="grid md:grid-cols-3 gap-6">
                    <div v-for="plan in plans" :key="plan.id"
                         :class="[
                             'bg-white dark:bg-gray-800 rounded-xl shadow-sm border-2 p-6 relative overflow-hidden',
                             plan.is_default
                                 ? 'border-indigo-500 dark:border-indigo-400'
                                 : 'border-gray-100 dark:border-gray-700'
                         ]">
                        <!-- Popular Badge -->
                        <div v-if="plan.is_default" class="absolute top-0 right-0 bg-indigo-500 text-white text-xs px-3 py-1 rounded-bl-lg font-medium">
                            Populaire
                        </div>

                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ plan.name }}</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm mb-4">{{ plan.description }}</p>

                        <div class="mb-4">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ formatPrice(plan.price_monthly) }}</span>
                            <span class="text-gray-500 dark:text-gray-400">/mois</span>
                        </div>

                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">Couverture jusqu'à <strong>{{ formatPrice(plan.coverage_amount) }}</strong></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">Franchise: {{ formatPrice(plan.deductible) }}</span>
                            </div>
                            <div v-for="risk in (plan.covered_risks || [])" :key="risk" class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-700 dark:text-gray-300">{{ risk }}</span>
                            </div>
                        </div>

                        <div v-if="plan.exclusions?.length" class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                            <p class="font-medium mb-1">Exclusions:</p>
                            <ul class="list-disc list-inside">
                                <li v-for="exclusion in plan.exclusions" :key="exclusion">{{ exclusion }}</li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="plans.length === 0" class="col-span-3 text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucune formule disponible</h3>
                        <p class="text-gray-500 dark:text-gray-400">Les formules d'assurance ne sont pas encore configurées.</p>
                    </div>
                </div>

                <!-- Tab: Claims -->
                <div v-if="activeTab === 'claims'" class="space-y-4">
                    <!-- Active Policies for Claims -->
                    <div v-if="policies.length > 0" class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-4 mb-6">
                        <h3 class="font-medium text-indigo-900 dark:text-indigo-300 mb-2">Déclarer un nouveau sinistre</h3>
                        <p class="text-sm text-indigo-700 dark:text-indigo-400 mb-3">
                            Sélectionnez la police concernée pour déclarer un sinistre.
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="policy in policies"
                                :key="policy.id"
                                @click="openClaimModal(policy)"
                                class="px-3 py-1.5 bg-white dark:bg-gray-800 text-indigo-700 dark:text-indigo-400 rounded-lg text-sm hover:bg-indigo-100 dark:hover:bg-gray-700 transition-colors border border-indigo-200 dark:border-indigo-700"
                            >
                                {{ policy.box_name }} ({{ policy.policy_number }})
                            </button>
                        </div>
                    </div>

                    <!-- Claims List -->
                    <div v-for="claim in claims" :key="claim.id"
                         class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-2">
                                    <span :class="['px-3 py-1 text-xs font-medium rounded-full', claimStatusColors[claim.status]]">
                                        {{ claimStatusLabels[claim.status] || claim.status }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ claim.claim_number }}
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">
                                    {{ incidentTypeLabels[claim.incident_type] || claim.incident_type }}
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Police: {{ claim.policy_number }} | Incident le {{ claim.incident_date }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Montant réclamé</p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatPrice(claim.claimed_amount) }}</p>
                                <p v-if="claim.approved_amount" class="text-sm text-green-600 dark:text-green-400">
                                    Approuvé: {{ formatPrice(claim.approved_amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="claims.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl">
                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Aucun sinistre</h3>
                        <p class="text-gray-500 dark:text-gray-400">Vous n'avez déclaré aucun sinistre.</p>
                    </div>
                </div>

                <!-- Subscribe Modal -->
                <div v-if="showSubscribeModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showSubscribeModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Souscrire une assurance</h2>

                            <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Pour le box:</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ selectedContract?.site_name }} - Box {{ selectedContract?.box_name }}
                                </p>
                            </div>

                            <form @submit.prevent="submitSubscription" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Formule d'assurance
                                    </label>
                                    <select
                                        v-model="subscribeForm.plan_id"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                                            {{ plan.name }} - {{ formatPrice(plan.price_monthly) }}/mois (couverture {{ formatPrice(plan.coverage_amount) }})
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Valeur déclarée des biens (optionnel)
                                    </label>
                                    <input
                                        type="number"
                                        v-model="subscribeForm.declared_value"
                                        placeholder="Ex: 5000"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        La valeur déclarée permet de calculer une indemnisation adaptée.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description des biens stockés (optionnel)
                                    </label>
                                    <textarea
                                        v-model="subscribeForm.items_description"
                                        rows="3"
                                        placeholder="Ex: Meubles, électroménager, cartons de vêtements..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    ></textarea>
                                </div>

                                <div v-if="selectedPlan" class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                                    <h4 class="font-medium text-indigo-900 dark:text-indigo-300 mb-2">Récapitulatif</h4>
                                    <div class="text-sm space-y-1 text-indigo-700 dark:text-indigo-400">
                                        <p>Formule: <strong>{{ selectedPlan.name }}</strong></p>
                                        <p>Prime mensuelle: <strong>{{ formatPrice(selectedPlan.price_monthly) }}</strong></p>
                                        <p>Couverture maximale: <strong>{{ formatPrice(selectedPlan.coverage_amount) }}</strong></p>
                                        <p>Franchise: <strong>{{ formatPrice(selectedPlan.deductible) }}</strong></p>
                                    </div>
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button
                                        type="button"
                                        @click="showSubscribeModal = false"
                                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="subscribeForm.processing"
                                        class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {{ subscribeForm.processing ? 'Souscription...' : 'Souscrire' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Claim Modal -->
                <div v-if="showClaimModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showClaimModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-lg w-full p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Déclarer un sinistre</h2>

                            <form @submit.prevent="submitClaim" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Date de l'incident *
                                    </label>
                                    <input
                                        type="date"
                                        v-model="claimForm.incident_date"
                                        required
                                        :max="new Date().toISOString().split('T')[0]"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Type d'incident *
                                    </label>
                                    <select
                                        v-model="claimForm.incident_type"
                                        required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="theft">Vol</option>
                                        <option value="damage">Dommage</option>
                                        <option value="fire">Incendie</option>
                                        <option value="water">Dégât des eaux</option>
                                        <option value="other">Autre</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Description de l'incident *
                                    </label>
                                    <textarea
                                        v-model="claimForm.description"
                                        required
                                        rows="4"
                                        placeholder="Décrivez les circonstances de l'incident..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Estimation des dommages (€) *
                                    </label>
                                    <input
                                        type="number"
                                        v-model="claimForm.estimated_damage"
                                        required
                                        min="0"
                                        placeholder="Ex: 500"
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    />
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button
                                        type="button"
                                        @click="showClaimModal = false"
                                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="claimForm.processing"
                                        class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50"
                                    >
                                        {{ claimForm.processing ? 'Envoi...' : 'Soumettre la déclaration' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Cancel Modal -->
                <div v-if="showCancelModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showCancelModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Résilier l'assurance</h2>

                            <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg text-sm text-red-700 dark:text-red-400">
                                <p class="font-medium mb-1">Attention</p>
                                <p>La résiliation prendra effet à la fin du mois en cours. Vous ne serez plus couvert après cette date.</p>
                            </div>

                            <form @submit.prevent="submitCancel" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Motif de résiliation *
                                    </label>
                                    <textarea
                                        v-model="cancelForm.reason"
                                        required
                                        rows="3"
                                        placeholder="Indiquez la raison de votre résiliation..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    ></textarea>
                                </div>

                                <div class="flex gap-3 pt-4">
                                    <button
                                        type="button"
                                        @click="showCancelModal = false"
                                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        :disabled="cancelForm.processing"
                                        class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                                    >
                                        {{ cancelForm.processing ? 'Résiliation...' : 'Confirmer la résiliation' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>
