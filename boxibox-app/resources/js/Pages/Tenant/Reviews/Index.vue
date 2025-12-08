<template>
    <TenantLayout title="Avis & Réputation" :breadcrumbs="[{ label: 'Avis' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Note moyenne</p>
                            <div class="flex items-center gap-2">
                                <p class="text-3xl font-bold text-gray-900">{{ (stats?.average_rating ?? 0).toFixed(1) }}</p>
                                <StarIcon class="w-6 h-6 text-yellow-500 fill-current" />
                            </div>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-xl">
                            <StarIcon class="w-6 h-6 text-yellow-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total avis</p>
                            <p class="text-3xl font-bold text-blue-600">{{ stats?.total_reviews ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <ChatBubbleLeftRightIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Score NPS</p>
                            <p class="text-3xl font-bold" :class="getNpsClass(stats?.nps_score ?? 0)">{{ stats?.nps_score ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <ChartBarIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">En attente</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats?.pending_reviews ?? 0 }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <ClockIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Distribution -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Distribution des notes</h3>
                    <div class="space-y-3">
                        <div v-for="n in 5" :key="n" class="flex items-center gap-3">
                            <span class="text-sm text-gray-600 w-8">{{ 6 - n }} ★</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                <div
                                    class="bg-yellow-500 h-full rounded-full transition-all"
                                    :style="{ width: getRatingPercentage(6 - n) + '%' }"
                                ></div>
                            </div>
                            <span class="text-sm text-gray-500 w-12 text-right">{{ getRatingCount(6 - n) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Actions rapides</h3>
                    <div class="space-y-3">
                        <Link :href="route('tenant.reviews.request')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <PaperAirplaneIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Demander des avis</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.reviews.nps')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <ChartBarIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Rapport NPS détaillé</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.reviews.settings')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <CogIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Paramètres des avis</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- NPS Breakdown -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 rounded-2xl p-6 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="font-semibold text-lg">Net Promoter Score (NPS)</h3>
                        <p class="text-purple-200 text-sm">Basé sur {{ stats?.nps_responses ?? 0 }} réponses</p>
                    </div>
                    <div class="text-4xl font-bold">{{ stats?.nps_score ?? 0 }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white/20 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold">{{ stats?.promoters ?? 0 }}%</p>
                        <p class="text-sm text-purple-200">Promoteurs (9-10)</p>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold">{{ stats?.passives ?? 0 }}%</p>
                        <p class="text-sm text-purple-200">Passifs (7-8)</p>
                    </div>
                    <div class="bg-white/20 rounded-xl p-4 text-center">
                        <p class="text-3xl font-bold">{{ stats?.detractors ?? 0 }}%</p>
                        <p class="text-sm text-purple-200">Détracteurs (0-6)</p>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Avis récents</h3>
                    <div class="flex items-center gap-3">
                        <select v-model="filterRating" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Toutes les notes</option>
                            <option value="5">5 étoiles</option>
                            <option value="4">4 étoiles</option>
                            <option value="3">3 étoiles</option>
                            <option value="2">2 étoiles</option>
                            <option value="1">1 étoile</option>
                        </select>
                        <select v-model="filterStatus" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="approved">Approuvé</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                    </div>
                </div>

                <div v-if="!reviews?.data || reviews.data.length === 0" class="px-6 py-12 text-center">
                    <ChatBubbleLeftRightIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun avis pour le moment</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="review in reviews.data" :key="review.id" class="px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <span class="text-primary-600 font-semibold">{{ getInitials(review.customer) }}</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900">{{ review.customer?.full_name }}</p>
                                        <div class="flex">
                                            <StarIcon v-for="n in 5" :key="n" class="w-4 h-4" :class="n <= review.rating ? 'text-yellow-500 fill-current' : 'text-gray-300'" />
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ review.site?.name }} - {{ formatDate(review.created_at) }}</p>
                                    <p v-if="review.comment" class="text-gray-700 mt-2">{{ review.comment }}</p>

                                    <!-- Response -->
                                    <div v-if="review.response" class="mt-3 pl-4 border-l-2 border-primary-200">
                                        <p class="text-sm text-gray-500 font-medium">Votre réponse :</p>
                                        <p class="text-sm text-gray-600">{{ review.response }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="getStatusBadgeClass(review.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(review.status) }}
                                </span>
                                <div class="flex gap-1">
                                    <button v-if="!review.response" @click="openResponseModal(review)" class="text-gray-500 hover:text-gray-700 p-1">
                                        <ChatBubbleLeftIcon class="w-5 h-5" />
                                    </button>
                                    <button v-if="review.status === 'pending'" @click="approveReview(review)" class="text-green-600 hover:text-green-800 p-1">
                                        <CheckIcon class="w-5 h-5" />
                                    </button>
                                    <button v-if="review.status === 'pending'" @click="rejectReview(review)" class="text-red-600 hover:text-red-800 p-1">
                                        <XMarkIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="reviews?.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="reviews.links" />
                </div>
            </div>

            <!-- Review Requests -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Demandes d'avis envoyées</h3>
                    <Link :href="route('tenant.reviews.request')" class="btn-primary">
                        <PaperAirplaneIcon class="w-4 h-4 mr-2" />
                        Nouvelle demande
                    </Link>
                </div>

                <div v-if="!reviewRequests || reviewRequests.length === 0" class="px-6 py-8 text-center">
                    <PaperAirplaneIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucune demande d'avis envoyée</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="request in reviewRequests" :key="request.id" class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ request.customer?.full_name }}</p>
                            <p class="text-sm text-gray-500">Envoyé le {{ formatDate(request.sent_at) }}</p>
                        </div>
                        <div class="text-right">
                            <span :class="getRequestStatusBadgeClass(request.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                {{ getRequestStatusLabel(request.status) }}
                            </span>
                            <p v-if="request.completed_at" class="text-xs text-gray-500 mt-1">
                                Complété le {{ formatDate(request.completed_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    StarIcon,
    ChatBubbleLeftRightIcon,
    ChatBubbleLeftIcon,
    ChartBarIcon,
    ClockIcon,
    PaperAirplaneIcon,
    ChevronRightIcon,
    CogIcon,
    CheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    reviews: Object,
    stats: Object,
    reviewRequests: Array,
    ratingDistribution: Object,
    filters: Object,
})

const filterRating = ref(props.filters?.rating || '')
const filterStatus = ref(props.filters?.status || '')

watch([filterRating, filterStatus], () => {
    router.get(route('tenant.reviews.index'), {
        rating: filterRating.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true })
})

const getNpsClass = (score) => {
    if (score >= 50) return 'text-green-600'
    if (score >= 0) return 'text-yellow-600'
    return 'text-red-600'
}

const getRatingPercentage = (rating) => {
    const total = props.stats?.total_reviews || 1
    const count = props.ratingDistribution?.[rating] || 0
    return (count / total) * 100
}

const getRatingCount = (rating) => {
    return props.ratingDistribution?.[rating] || 0
}

const getInitials = (customer) => {
    if (!customer?.full_name) return '?'
    return customer.full_name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        approved: 'Approuvé',
        rejected: 'Rejeté',
        published: 'Publié',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-700',
        approved: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        published: 'bg-blue-100 text-blue-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getRequestStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        sent: 'Envoyé',
        opened: 'Ouvert',
        completed: 'Complété',
        expired: 'Expiré',
    }
    return labels[status] || status
}

const getRequestStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        opened: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-green-100 text-green-700',
        expired: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const openResponseModal = (review) => {
    // TODO: Implement modal
    const response = prompt('Votre réponse à cet avis :')
    if (response) {
        router.post(route('tenant.reviews.respond', review.id), { response })
    }
}

const approveReview = (review) => {
    if (confirm('Approuver cet avis ?')) {
        router.post(route('tenant.reviews.approve', review.id))
    }
}

const rejectReview = (review) => {
    if (confirm('Rejeter cet avis ?')) {
        router.post(route('tenant.reviews.reject', review.id))
    }
}
</script>
