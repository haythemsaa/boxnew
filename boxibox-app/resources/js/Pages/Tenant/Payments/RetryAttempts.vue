<script setup>
import { ref, watch } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    attempts: Object,
    statusFilter: String,
})

const status = ref(props.statusFilter || '')

watch(status, () => {
    router.get(route('tenant.payment-retries.attempts'), {
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    })
})

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
</script>

<template>
    <TenantLayout title="Historique Tentatives">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-orange-50/30">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div class="animate-fade-in-up">
                        <div class="flex items-center gap-3 mb-2">
                            <Link
                                :href="route('tenant.payment-retries.index')"
                                class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
                            >
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </Link>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900">Historique des Tentatives</h1>
                                <p class="text-gray-500 mt-1">Toutes les tentatives de recouvrement</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 animate-fade-in-up" style="animation-delay: 0.1s">
                    <div class="flex flex-wrap gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Filtrer par statut</label>
                            <select
                                v-model="status"
                                class="block w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="pending">En attente</option>
                                <option value="scheduled">Planifié</option>
                                <option value="processing">En cours</option>
                                <option value="succeeded">Récupéré</option>
                                <option value="failed">Échoué</option>
                                <option value="cancelled">Annulé</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in-up" style="animation-delay: 0.15s">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Facture</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Montant</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tentative</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Raison</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Planifié</th>
                                    <th class="px-6 py-4"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-if="!attempts.data || attempts.data.length === 0">
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </div>
                                            <p class="text-gray-500 font-medium">Aucune tentative trouvée</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-else
                                    v-for="attempt in attempts.data"
                                    :key="attempt.id"
                                    class="hover:bg-gray-50/50 transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium text-gray-900">{{ getCustomerName(attempt.customer) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600">#{{ attempt.invoice?.invoice_number || attempt.invoice_id }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <span class="text-sm font-semibold text-gray-900">{{ attempt.formatted_amount }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[getStatusStyle(attempt.status).bg, getStatusStyle(attempt.status).text]"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium"
                                        >
                                            <span :class="getStatusStyle(attempt.status).dot" class="w-1.5 h-1.5 rounded-full"></span>
                                            {{ statusLabels[attempt.status] || attempt.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600">{{ attempt.attempt_number }}/{{ attempt.max_attempts }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-if="attempt.decline_code || attempt.failure_code" class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
                                            {{ getFailureLabel(attempt.decline_code || attempt.failure_code) }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-600">{{ formatDate(attempt.scheduled_at) }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Link
                                                :href="route('tenant.payment-retries.show', attempt.id)"
                                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Détails"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </Link>
                                            <button
                                                v-if="['pending', 'scheduled', 'failed'].includes(attempt.status) && attempt.attempt_number < attempt.max_attempts"
                                                @click="manualRetry(attempt)"
                                                class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                                title="Relancer"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="['pending', 'scheduled'].includes(attempt.status)"
                                                @click="cancelAttempt(attempt)"
                                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Annuler"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="attempts.links && attempts.links.length > 3"
                        class="bg-gray-50/50 px-6 py-4 flex items-center justify-between border-t border-gray-100"
                    >
                        <p class="text-sm text-gray-600">
                            Affichage de
                            <span class="font-semibold">{{ attempts.from }}</span>
                            à
                            <span class="font-semibold">{{ attempts.to }}</span>
                            sur
                            <span class="font-semibold">{{ attempts.total }}</span>
                        </p>
                        <nav class="flex gap-1">
                            <template v-for="(link, index) in attempts.links" :key="index">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        link.active
                                            ? 'bg-orange-600 text-white border-orange-600'
                                            : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50',
                                        'px-3 py-2 border text-sm font-medium rounded-lg transition-colors',
                                    ]"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
