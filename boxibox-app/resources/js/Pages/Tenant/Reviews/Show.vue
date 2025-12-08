<template>
    <TenantLayout title="Détail de l'avis" :breadcrumbs="[{ label: 'Avis', href: route('tenant.reviews.index') }, { label: 'Détail' }]">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Review Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center">
                                <span class="text-primary-600 font-bold text-xl">
                                    {{ getInitials(review?.customer) }}
                                </span>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    {{ review?.customer?.first_name }} {{ review?.customer?.last_name }}
                                </h2>
                                <p class="text-gray-500">
                                    {{ review?.contract?.site?.name }} - Box {{ review?.contract?.box?.number }}
                                </p>
                            </div>
                        </div>
                        <span :class="getStatusBadgeClass(review?.status)" class="px-4 py-2 rounded-full text-sm font-medium">
                            {{ getStatusLabel(review?.status) }}
                        </span>
                    </div>
                </div>

                <!-- Rating -->
                <div class="p-6 bg-gray-50 border-b border-gray-100">
                    <div class="flex items-center justify-center gap-2">
                        <div class="flex gap-1">
                            <StarIcon
                                v-for="n in 5"
                                :key="n"
                                class="w-8 h-8"
                                :class="n <= (review?.rating || 0) ? 'text-yellow-500 fill-current' : 'text-gray-300'"
                            />
                        </div>
                        <span class="text-2xl font-bold text-gray-900 ml-2">{{ review?.rating }}/5</span>
                    </div>
                </div>

                <!-- Comment -->
                <div class="p-6">
                    <h3 class="font-medium text-gray-900 mb-3">Commentaire</h3>
                    <p v-if="review?.comment" class="text-gray-700 leading-relaxed">{{ review.comment }}</p>
                    <p v-else class="text-gray-400 italic">Aucun commentaire</p>
                </div>

                <!-- Response -->
                <div v-if="review?.response" class="p-6 bg-primary-50 border-t border-primary-100">
                    <h3 class="font-medium text-primary-900 mb-2 flex items-center gap-2">
                        <ChatBubbleLeftRightIcon class="w-5 h-5" />
                        Votre réponse
                    </h3>
                    <p class="text-primary-800">{{ review.response }}</p>
                    <p class="text-sm text-primary-600 mt-2">
                        Répondu le {{ formatDate(review.responded_at) }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="p-6 border-t border-gray-100 bg-gray-50">
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-if="!review?.response"
                            @click="showResponseModal = true"
                            class="btn btn-primary"
                        >
                            <ChatBubbleLeftIcon class="w-4 h-4" />
                            Répondre
                        </button>
                        <button
                            v-if="review?.status === 'pending'"
                            @click="moderate('approve')"
                            class="btn btn-success"
                        >
                            <CheckIcon class="w-4 h-4" />
                            Approuver
                        </button>
                        <button
                            v-if="review?.status === 'pending'"
                            @click="moderate('reject')"
                            class="btn btn-danger"
                        >
                            <XMarkIcon class="w-4 h-4" />
                            Rejeter
                        </button>
                        <button
                            v-if="review?.status === 'published'"
                            @click="moderate('flag')"
                            class="btn btn-secondary"
                        >
                            <FlagIcon class="w-4 h-4" />
                            Signaler
                        </button>
                    </div>
                </div>
            </div>

            <!-- Details Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Informations</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500">Date de l'avis</p>
                        <p class="font-medium text-gray-900">{{ formatDate(review?.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Email client</p>
                        <p class="font-medium text-gray-900">{{ review?.customer?.email || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Téléphone</p>
                        <p class="font-medium text-gray-900">{{ review?.customer?.phone || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">Source</p>
                        <p class="font-medium text-gray-900">{{ review?.source || 'Direct' }}</p>
                    </div>
                </div>
            </div>

            <!-- Response Modal -->
            <div v-if="showResponseModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-900/50" @click="showResponseModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-xl max-w-lg w-full p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Répondre à l'avis</h3>
                        <form @submit.prevent="submitResponse">
                            <textarea
                                v-model="responseForm.response"
                                rows="4"
                                class="w-full rounded-xl border-gray-200 focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Votre réponse..."
                                required
                            ></textarea>
                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" @click="showResponseModal = false" class="btn btn-secondary">
                                    Annuler
                                </button>
                                <button type="submit" class="btn btn-primary" :disabled="responseForm.processing">
                                    Publier la réponse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    StarIcon,
    ChatBubbleLeftRightIcon,
    ChatBubbleLeftIcon,
    CheckIcon,
    XMarkIcon,
    FlagIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    review: Object,
})

const showResponseModal = ref(false)

const responseForm = useForm({
    response: '',
})

const getInitials = (customer) => {
    if (!customer) return '?'
    const first = customer.first_name?.[0] || ''
    const last = customer.last_name?.[0] || ''
    return (first + last).toUpperCase() || '?'
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        published: 'Publié',
        rejected: 'Rejeté',
        flagged: 'Signalé',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-700',
        published: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
        flagged: 'bg-orange-100 text-orange-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const moderate = (action) => {
    const messages = {
        approve: 'Approuver cet avis ?',
        reject: 'Rejeter cet avis ?',
        flag: 'Signaler cet avis ?',
    }
    if (confirm(messages[action])) {
        router.post(route('tenant.reviews.moderate', props.review.id), { action })
    }
}

const submitResponse = () => {
    responseForm.post(route('tenant.reviews.respond', props.review.id), {
        onSuccess: () => {
            showResponseModal.value = false
            responseForm.reset()
        },
    })
}
</script>
