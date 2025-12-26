<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import {
    ArrowLeftIcon,
    CurrencyEuroIcon,
    UserIcon,
    MapPinIcon,
    ClockIcon,
    BanknotesIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    PaperAirplaneIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    auction: Object,
    bids: Array,
    notices: Array,
    timeline: Array,
})

const processing = ref(false)

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        notice_sent: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        scheduled: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        active: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        sold: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        unsold: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        redeemed: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
    }
    return classes[status] || classes.pending
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const sendNotice = (type) => {
    if (confirm(`Envoyer l'avis "${type}" au client ?`)) {
        processing.value = true
        router.post(route('tenant.auctions.send-notice', props.auction.id), { type }, {
            onFinish: () => processing.value = false
        })
    }
}

const scheduleAuction = () => {
    const startDate = prompt('Date de début (YYYY-MM-DD):')
    const endDate = prompt('Date de fin (YYYY-MM-DD):')
    if (startDate && endDate) {
        processing.value = true
        router.post(route('tenant.auctions.schedule', props.auction.id), {
            start_date: startDate,
            end_date: endDate
        }, {
            onFinish: () => processing.value = false
        })
    }
}

const startAuction = () => {
    if (confirm('Démarrer l\'enchère maintenant ?')) {
        processing.value = true
        router.post(route('tenant.auctions.start', props.auction.id), {}, {
            onFinish: () => processing.value = false
        })
    }
}

const markRedeemed = () => {
    if (confirm('Marquer la dette comme remboursée ? L\'enchère sera annulée.')) {
        processing.value = true
        router.post(route('tenant.auctions.redeem', props.auction.id), {}, {
            onFinish: () => processing.value = false
        })
    }
}

const cancelAuction = () => {
    const reason = prompt('Raison de l\'annulation:')
    if (reason) {
        processing.value = true
        router.post(route('tenant.auctions.cancel', props.auction.id), { reason }, {
            onFinish: () => processing.value = false
        })
    }
}
</script>

<template>
    <Head :title="`Enchère ${auction.auction_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('tenant.auctions.index')"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            Enchère {{ auction.auction_number }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Box {{ auction.box?.name }} - {{ auction.site?.name }}
                        </p>
                    </div>
                </div>
                <span :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusBadgeClass(auction.status)]">
                    {{ auction.status_label }}
                </span>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Debt Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Résumé de la dette
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Dette totale</p>
                                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                                        {{ formatCurrency(auction.total_debt) }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Loyers impayés</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(auction.unpaid_rent) }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Pénalités</p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(auction.late_fees) }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Jours de retard</p>
                                    <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                        {{ auction.days_overdue }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Customer & Contract Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Informations
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Client</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <UserIcon class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-900 dark:text-white">
                                                {{ auction.customer?.full_name || 'N/A' }}
                                            </span>
                                        </div>
                                        <div v-if="auction.customer?.email" class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                                            {{ auction.customer.email }}
                                        </div>
                                        <div v-if="auction.customer?.phone" class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                                            {{ auction.customer.phone }}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">Box</h4>
                                    <div class="space-y-2">
                                        <div class="flex items-center space-x-2">
                                            <MapPinIcon class="w-4 h-4 text-gray-400" />
                                            <span class="text-gray-900 dark:text-white">
                                                Box {{ auction.box?.name }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 ml-6">
                                            {{ auction.box?.size }} m² - {{ auction.site?.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Auction Details (if scheduled/active) -->
                        <div v-if="['scheduled', 'active', 'sold', 'unsold'].includes(auction.status)"
                             class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Détails de l'enchère
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Mise de départ</p>
                                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                        {{ formatCurrency(auction.starting_bid) }}
                                    </p>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Enchère actuelle</p>
                                    <p class="text-xl font-bold text-green-600 dark:text-green-400">
                                        {{ formatCurrency(auction.current_bid) }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Début</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatDate(auction.auction_start_date) }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Fin</p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatDate(auction.auction_end_date) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Bids List -->
                        <div v-if="bids && bids.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Historique des enchères ({{ bids.length }})
                            </h3>
                            <div class="space-y-3">
                                <div
                                    v-for="(bid, index) in bids"
                                    :key="bid.id"
                                    :class="[
                                        'flex items-center justify-between p-4 rounded-lg',
                                        index === 0 ? 'bg-green-50 dark:bg-green-900/20 border-2 border-green-500' : 'bg-gray-50 dark:bg-gray-700'
                                    ]"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <span class="text-sm font-medium">{{ index + 1 }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ bid.bidder_name || 'Enchérisseur anonyme' }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatDate(bid.created_at) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p :class="[
                                            'text-xl font-bold',
                                            index === 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-900 dark:text-white'
                                        ]">
                                            {{ formatCurrency(bid.amount) }}
                                        </p>
                                        <span v-if="index === 0" class="text-xs text-green-600 dark:text-green-400 font-medium">
                                            Meilleure offre
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notices Sent -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Avis envoyés
                            </h3>
                            <div v-if="notices && notices.length > 0" class="space-y-3">
                                <div
                                    v-for="notice in notices"
                                    :key="notice.id"
                                    class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
                                >
                                    <div class="flex items-center space-x-3">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-400" />
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">
                                                {{ notice.type_label }}
                                            </p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Envoyé le {{ formatDate(notice.sent_at) }}
                                            </p>
                                        </div>
                                    </div>
                                    <span :class="[
                                        'px-2 py-1 text-xs rounded-full',
                                        notice.delivered_at
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                    ]">
                                        {{ notice.delivered_at ? 'Délivré' : 'En attente' }}
                                    </span>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <DocumentTextIcon class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                                <p class="text-gray-500 dark:text-gray-400">Aucun avis envoyé</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Actions
                            </h3>
                            <div class="space-y-3">
                                <!-- Send notices -->
                                <template v-if="auction.status === 'pending'">
                                    <button
                                        @click="sendNotice('first_warning')"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 disabled:opacity-50"
                                    >
                                        <PaperAirplaneIcon class="w-4 h-4 mr-2" />
                                        Envoyer 1er avis
                                    </button>
                                </template>

                                <template v-if="auction.status === 'notice_sent'">
                                    <button
                                        @click="sendNotice('final_notice')"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 disabled:opacity-50"
                                    >
                                        <PaperAirplaneIcon class="w-4 h-4 mr-2" />
                                        Envoyer avis final
                                    </button>
                                    <button
                                        @click="scheduleAuction"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <ClockIcon class="w-4 h-4 mr-2" />
                                        Programmer l'enchère
                                    </button>
                                </template>

                                <template v-if="auction.status === 'scheduled'">
                                    <button
                                        @click="startAuction"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
                                    >
                                        <CheckCircleIcon class="w-4 h-4 mr-2" />
                                        Démarrer maintenant
                                    </button>
                                </template>

                                <!-- Redeem debt -->
                                <template v-if="['pending', 'notice_sent', 'scheduled'].includes(auction.status)">
                                    <button
                                        @click="markRedeemed"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50"
                                    >
                                        <BanknotesIcon class="w-4 h-4 mr-2" />
                                        Marquer dette remboursée
                                    </button>
                                </template>

                                <!-- Cancel -->
                                <template v-if="!['sold', 'redeemed', 'cancelled'].includes(auction.status)">
                                    <button
                                        @click="cancelAuction"
                                        :disabled="processing"
                                        class="w-full flex items-center justify-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50"
                                    >
                                        <XCircleIcon class="w-4 h-4 mr-2" />
                                        Annuler l'enchère
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Chronologie
                            </h3>
                            <div class="space-y-4">
                                <div class="flex items-start space-x-3">
                                    <ExclamationTriangleIcon class="w-5 h-5 text-red-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Défaut de paiement</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.overdue_since) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.first_notice_at" class="flex items-start space-x-3">
                                    <DocumentTextIcon class="w-5 h-5 text-yellow-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">1er avis</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.first_notice_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.final_notice_at" class="flex items-start space-x-3">
                                    <DocumentTextIcon class="w-5 h-5 text-orange-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Avis final</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.final_notice_at) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.auction_start_date" class="flex items-start space-x-3">
                                    <ClockIcon class="w-5 h-5 text-blue-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Début enchère</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.auction_start_date) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.auction_end_date" class="flex items-start space-x-3">
                                    <ClockIcon class="w-5 h-5 text-gray-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Fin enchère</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.auction_end_date) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.sold_at" class="flex items-start space-x-3">
                                    <CheckCircleIcon class="w-5 h-5 text-green-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Vendu</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.sold_at) }} - {{ formatCurrency(auction.final_price) }}
                                        </p>
                                    </div>
                                </div>
                                <div v-if="auction.redeemed_at" class="flex items-start space-x-3">
                                    <BanknotesIcon class="w-5 h-5 text-purple-500 mt-0.5" />
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Dette remboursée</p>
                                        <p class="font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(auction.redeemed_at) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow p-6 text-white">
                            <h3 class="text-lg font-medium mb-4">Récupération potentielle</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-indigo-100">Dette à récupérer</span>
                                    <span class="font-bold">{{ formatCurrency(auction.total_debt) }}</span>
                                </div>
                                <div v-if="auction.current_bid > 0" class="flex justify-between">
                                    <span class="text-indigo-100">Meilleure offre</span>
                                    <span class="font-bold">{{ formatCurrency(auction.current_bid) }}</span>
                                </div>
                                <div class="border-t border-indigo-400 pt-3 flex justify-between">
                                    <span class="text-indigo-100">Taux de couverture</span>
                                    <span class="font-bold">
                                        {{ auction.total_debt > 0
                                            ? Math.round((auction.current_bid || auction.starting_bid) / auction.total_debt * 100)
                                            : 0 }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
