<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    stats: Object,
    pendingAttempts: Array,
    recentAttempts: Array,
})

const showRescheduleModal = ref(false)
const attemptToReschedule = ref(null)
const newScheduleDate = ref('')

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatShortDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    })
}

const getStatusStyle = (status) => {
    const styles = {
        pending: { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500' },
        scheduled: { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
        processing: { bg: 'bg-purple-100', text: 'text-purple-700', dot: 'bg-purple-500' },
        succeeded: { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500' },
        failed: { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
        cancelled: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' },
        card_updated: { bg: 'bg-indigo-100', text: 'text-indigo-700', dot: 'bg-indigo-500' },
    }
    return styles[status] || styles.pending
}

const statusLabels = {
    pending: 'En attente',
    scheduled: 'Planifié',
    processing: 'En cours',
    succeeded: 'Récupéré',
    failed: 'Échoué',
    cancelled: 'Annulé',
    card_updated: 'Carte MAJ',
}

const getFailureLabel = (code) => {
    const labels = {
        insufficient_funds: 'Fonds insuffisants',
        card_declined: 'Carte refusée',
        expired_card: 'Carte expirée',
        processing_error: 'Erreur technique',
        authentication_required: 'Auth. requise',
    }
    return labels[code] || code || 'Inconnu'
}

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}

const manualRetry = (attempt) => {
    if (confirm(`Relancer le paiement de ${attempt.formatted_amount} maintenant ?`)) {
        router.post(route('tenant.payment-retries.manual-retry', attempt.id))
    }
}

const cancelAttempt = (attempt) => {
    if (confirm('Annuler cette tentative de recouvrement ?')) {
        router.post(route('tenant.payment-retries.cancel', attempt.id))
    }
}

const openReschedule = (attempt) => {
    attemptToReschedule.value = attempt
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    newScheduleDate.value = tomorrow.toISOString().slice(0, 16)
    showRescheduleModal.value = true
}

const rescheduleAttempt = () => {
    router.post(route('tenant.payment-retries.reschedule', attemptToReschedule.value.id), {
        scheduled_at: newScheduleDate.value,
    }, {
        onSuccess: () => {
            showRescheduleModal.value = false
            attemptToReschedule.value = null
        }
    })
}

const recoveryRateColor = computed(() => {
    const rate = props.stats.recovery_rate || 0
    if (rate >= 70) return 'text-emerald-600'
    if (rate >= 50) return 'text-amber-600'
    return 'text-red-600'
})
</script>

<template>
    <TenantLayout title="Récupération Paiements">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-orange-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Récupération des Paiements</h1>
                                <p class="text-gray-500 mt-1">Système intelligent de retry automatique</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 sm:mt-0 flex gap-3 animate-fade-in-up" style="animation-delay: 0.1s">
                        <Link
                            :href="route('tenant.payment-retries.analytics')"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 transition-all duration-200"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Analytics
                        </Link>
                        <Link
                            :href="route('tenant.payment-retries.config')"
                            class="inline-flex items-center px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-orange-600 to-red-600 hover:from-orange-700 hover:to-red-700 transition-all duration-200 shadow-lg shadow-orange-500/25"
                        >
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Configuration
                        </Link>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5 mb-8">
                    <!-- En attente -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-amber-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">En attente</p>
                                <p class="text-2xl font-bold text-amber-600 mt-1">{{ stats.total_pending || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Récupérés ce mois -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-emerald-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.15s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Récupérés ce mois</p>
                                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ stats.recovered_this_month || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Montant Récupéré -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Montant récupéré</p>
                                <p class="text-2xl font-bold text-blue-600 mt-1">{{ formatCurrency(stats.amount_recovered_this_month) }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Taux de Récupération -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.25s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Taux récupération</p>
                                <p class="text-2xl font-bold mt-1" :class="recoveryRateColor">{{ stats.recovery_rate || 0 }}%</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Échecs définitifs -->
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-red-200 transition-all duration-300 animate-fade-in-up" style="animation-delay: 0.3s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Échecs définitifs</p>
                                <p class="text-2xl font-bold text-red-600 mt-1">{{ stats.failed_permanently || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/25">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Tentatives en attente -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.35s">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50 to-orange-50">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tentatives Planifiées
                                </h2>
                                <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                    {{ pendingAttempts?.length || 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                            <div v-if="!pendingAttempts || pendingAttempts.length === 0" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-medium">Aucune tentative en attente</p>
                                <p class="text-gray-400 text-sm mt-1">Tous les paiements sont à jour</p>
                            </div>
                            <div
                                v-else
                                v-for="attempt in pendingAttempts"
                                :key="attempt.id"
                                class="px-6 py-4 hover:bg-gray-50/50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-amber-600">{{ attempt.attempt_number }}/{{ attempt.max_attempts }}</span>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ getCustomerName(attempt.customer) }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Facture #{{ attempt.invoice?.invoice_number || attempt.invoice_id }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-sm font-semibold text-gray-900">{{ attempt.formatted_amount }}</span>
                                                <span class="text-xs text-gray-400">•</span>
                                                <span class="text-xs text-gray-500">{{ formatDate(attempt.scheduled_at) }}</span>
                                            </div>
                                            <span class="inline-flex items-center mt-1.5 px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">
                                                {{ getFailureLabel(attempt.decline_code || attempt.failure_code) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button
                                            @click="manualRetry(attempt)"
                                            class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Relancer maintenant"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="openReschedule(attempt)"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                            title="Replanifier"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="cancelAttempt(attempt)"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Annuler"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tentatives récentes -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.4s">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-slate-50">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Historique Récent
                                </h2>
                                <Link
                                    :href="route('tenant.payment-retries.attempts')"
                                    class="text-sm text-orange-600 hover:text-orange-700 font-medium"
                                >
                                    Voir tout →
                                </Link>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                            <div v-if="!recentAttempts || recentAttempts.length === 0" class="px-6 py-12 text-center">
                                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <p class="text-gray-500 font-medium">Aucun historique</p>
                            </div>
                            <div
                                v-else
                                v-for="attempt in recentAttempts"
                                :key="attempt.id"
                                class="px-6 py-4 hover:bg-gray-50/50 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start gap-3">
                                        <div
                                            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                            :class="attempt.status === 'succeeded' ? 'bg-gradient-to-br from-emerald-100 to-emerald-200' : 'bg-gradient-to-br from-red-100 to-red-200'"
                                        >
                                            <svg v-if="attempt.status === 'succeeded'" class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ getCustomerName(attempt.customer) }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ attempt.formatted_amount }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    :class="[getStatusStyle(attempt.status).bg, getStatusStyle(attempt.status).text]"
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
                                                >
                                                    <span :class="getStatusStyle(attempt.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                                    {{ statusLabels[attempt.status] || attempt.status }}
                                                </span>
                                                <span class="text-xs text-gray-400">Tentative {{ attempt.attempt_number }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ formatShortDate(attempt.updated_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Failure Reasons Analysis -->
                <div v-if="stats.failure_reasons && Object.keys(stats.failure_reasons).length > 0" class="mt-6 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.45s">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Analyse des Échecs par Raison
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div
                                v-for="(data, reason) in stats.failure_reasons"
                                :key="reason"
                                class="bg-gray-50 rounded-xl p-4"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">{{ getFailureLabel(reason) }}</span>
                                    <span class="text-xs font-semibold" :class="data.rate >= 50 ? 'text-emerald-600' : 'text-red-600'">
                                        {{ data.rate }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full transition-all duration-500"
                                        :class="data.rate >= 50 ? 'bg-emerald-500' : 'bg-red-500'"
                                        :style="{ width: `${data.rate}%` }"
                                    ></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">{{ data.recovered }}/{{ data.total }} récupérés</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reschedule Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showRescheduleModal"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title"
                    role="dialog"
                    aria-modal="true"
                >
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <div
                            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                            aria-hidden="true"
                            @click="showRescheduleModal = false"
                        ></div>

                        <div class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-6 pt-6 pb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Replanifier la tentative</h3>
                                <p class="mt-2 text-sm text-gray-500">
                                    Choisissez une nouvelle date pour la tentative de recouvrement
                                </p>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date et heure</label>
                                    <input
                                        v-model="newScheduleDate"
                                        type="datetime-local"
                                        class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                    />
                                </div>
                            </div>
                            <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                                <button
                                    type="button"
                                    @click="rescheduleAttempt"
                                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-orange-600 hover:bg-orange-700 transition-colors shadow-sm"
                                >
                                    Replanifier
                                </button>
                                <button
                                    type="button"
                                    @click="showRescheduleModal = false"
                                    class="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"
                                >
                                    Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </TenantLayout>
</template>
