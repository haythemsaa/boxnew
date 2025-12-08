<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    CheckCircleIcon,
    XCircleIcon,
    PrinterIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoice: Object,
})

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        pending: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        paid: 'bg-green-500/10 text-green-400 border-green-500/20',
        overdue: 'bg-red-500/10 text-red-400 border-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-500 border-gray-500/20',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        pending: 'En attente',
        paid: 'Payée',
        overdue: 'En retard',
        cancelled: 'Annulée',
    }
    return labels[status] || status
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const markAsPaid = () => {
    if (confirm('Marquer cette facture comme payée ?')) {
        router.post(route('superadmin.billing.mark-paid', props.invoice.id))
    }
}

const markAsCancelled = () => {
    if (confirm('Annuler cette facture ?')) {
        router.post(route('superadmin.billing.cancel', props.invoice.id))
    }
}

const sendReminder = () => {
    if (confirm('Envoyer un rappel de paiement au tenant ?')) {
        router.post(route('superadmin.billing.send-reminder', props.invoice.id))
    }
}
</script>

<template>
    <Head :title="`Facture ${invoice.invoice_number}`" />

    <SuperAdminLayout :title="`Facture ${invoice.invoice_number}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.billing.index')"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ invoice.invoice_number }}</h1>
                        <p class="mt-1 text-sm text-gray-400">Créée le {{ formatDate(invoice.created_at) }}</p>
                    </div>
                </div>
                <span :class="[getStatusColor(invoice.status), 'px-3 py-1.5 text-sm rounded-full border font-medium']">
                    {{ getStatusLabel(invoice.status) }}
                </span>
            </div>

            <!-- Invoice Details -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-white">Boxibox SaaS</h2>
                        <p class="text-sm text-gray-400">Facturation Plateforme</p>
                    </div>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-white">{{ formatCurrency(invoice.amount) }}</div>
                        <div v-if="invoice.tax_amount > 0" class="text-sm text-gray-400">
                            dont TVA: {{ formatCurrency(invoice.tax_amount) }}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 pt-6 border-t border-gray-700">
                    <div>
                        <h3 class="text-sm font-medium text-gray-400 mb-2">Tenant</h3>
                        <div class="text-white font-medium">{{ invoice.tenant?.name || '-' }}</div>
                        <div class="text-sm text-gray-400">{{ invoice.tenant?.email }}</div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-400 mb-2">Plan</h3>
                        <span class="px-2 py-1 text-sm bg-purple-500/10 text-purple-400 rounded-full capitalize">
                            {{ invoice.plan }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-6 mt-6 pt-6 border-t border-gray-700">
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Période</h3>
                        <div class="mt-1 text-white">
                            {{ formatDate(invoice.period_start) }} - {{ formatDate(invoice.period_end) }}
                        </div>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-400">Échéance</h3>
                        <div class="mt-1 text-white">{{ formatDate(invoice.due_date) }}</div>
                    </div>
                    <div v-if="invoice.paid_at">
                        <h3 class="text-sm font-medium text-gray-400">Payée le</h3>
                        <div class="mt-1 text-green-400">{{ formatDate(invoice.paid_at) }}</div>
                    </div>
                </div>

                <div v-if="invoice.payment_method || invoice.payment_reference" class="mt-6 pt-6 border-t border-gray-700">
                    <h3 class="text-sm font-medium text-gray-400 mb-2">Paiement</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div v-if="invoice.payment_method">
                            <div class="text-xs text-gray-500">Méthode</div>
                            <div class="text-white capitalize">{{ invoice.payment_method }}</div>
                        </div>
                        <div v-if="invoice.payment_reference">
                            <div class="text-xs text-gray-500">Référence</div>
                            <div class="text-white font-mono">{{ invoice.payment_reference }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="invoice.notes" class="mt-6 pt-6 border-t border-gray-700">
                    <h3 class="text-sm font-medium text-gray-400 mb-2">Notes</h3>
                    <p class="text-gray-300">{{ invoice.notes }}</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h3 class="text-sm font-medium text-gray-400 mb-4">Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <button
                        v-if="invoice.status === 'pending' || invoice.status === 'overdue'"
                        @click="markAsPaid"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                    >
                        <CheckCircleIcon class="h-5 w-5" />
                        Marquer comme payée
                    </button>
                    <button
                        v-if="invoice.status === 'pending' || invoice.status === 'overdue'"
                        @click="sendReminder"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors"
                    >
                        Envoyer un rappel
                    </button>
                    <button
                        v-if="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                        @click="markAsCancelled"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                    >
                        <XCircleIcon class="h-5 w-5" />
                        Annuler
                    </button>
                    <button
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        <PrinterIcon class="h-5 w-5" />
                        Imprimer / PDF
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
