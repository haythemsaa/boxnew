<template>
    <TenantLayout :title="`Ticket #${ticket.ticket_number}`" :breadcrumbs="[{ label: 'Maintenance', href: route('tenant.maintenance.index') }, { label: `Ticket #${ticket.ticket_number}` }]">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Ticket Header -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900">{{ ticket.title }}</h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Créé le {{ formatDate(ticket.created_at) }} par {{ ticket.reporter?.name || 'Système' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span :class="getPriorityBadgeClass(ticket.priority)" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ getPriorityLabel(ticket.priority) }}
                            </span>
                            <span :class="getStatusBadgeClass(ticket.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ getStatusLabel(ticket.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="prose prose-sm max-w-none">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ ticket.description }}</p>
                    </div>
                </div>

                <!-- Comments -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-semibold text-gray-900">Commentaires & Activité</h3>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <!-- Activity Timeline -->
                        <div v-for="item in timeline" :key="item.id" class="px-6 py-4">
                            <div class="flex gap-4">
                                <div :class="item.type === 'comment' ? 'bg-blue-100' : 'bg-gray-100'" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
                                    <ChatBubbleLeftIcon v-if="item.type === 'comment'" class="w-5 h-5 text-blue-600" />
                                    <ClockIcon v-else class="w-5 h-5 text-gray-500" />
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ item.user?.name || 'Système' }}</span>
                                        <span class="text-xs text-gray-500">{{ formatDateTime(item.created_at) }}</span>
                                    </div>
                                    <p v-if="item.type === 'comment'" class="text-gray-700 mt-1">{{ item.content }}</p>
                                    <p v-else class="text-sm text-gray-500 mt-1">{{ item.description }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="timeline.length === 0" class="px-6 py-8 text-center text-gray-500">
                            Aucune activité pour le moment
                        </div>
                    </div>

                    <!-- Add Comment -->
                    <div class="px-6 py-4 border-t border-gray-100">
                        <form @submit.prevent="addComment">
                            <textarea
                                v-model="commentForm.content"
                                rows="3"
                                class="w-full rounded-xl border-gray-200 mb-3"
                                placeholder="Ajouter un commentaire..."
                            ></textarea>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="!commentForm.content || commentForm.processing" class="btn-primary">
                                    Ajouter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Actions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <button v-if="ticket.status === 'open'" @click="updateStatus('in_progress')" class="w-full btn-primary">
                            <PlayIcon class="w-4 h-4 mr-2" />
                            Démarrer
                        </button>
                        <button v-if="ticket.status === 'in_progress'" @click="updateStatus('resolved')" class="w-full btn-success">
                            <CheckIcon class="w-4 h-4 mr-2" />
                            Marquer résolu
                        </button>
                        <button v-if="ticket.status === 'resolved'" @click="updateStatus('closed')" class="w-full btn-secondary">
                            <ArchiveBoxIcon class="w-4 h-4 mr-2" />
                            Clôturer
                        </button>
                        <Link :href="route('tenant.maintenance.edit', ticket.id)" class="w-full btn-secondary inline-flex justify-center">
                            <PencilIcon class="w-4 h-4 mr-2" />
                            Modifier
                        </Link>
                    </div>
                </div>

                <!-- Details -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Détails</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Catégorie</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.category?.name || '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Site</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.site?.name || '-' }}</dd>
                        </div>
                        <div v-if="ticket.box">
                            <dt class="text-sm text-gray-500">Box</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.box.code }}</dd>
                        </div>
                        <div v-if="ticket.customer">
                            <dt class="text-sm text-gray-500">Client</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.customer.full_name }}</dd>
                        </div>
                        <div v-if="ticket.assigned_to">
                            <dt class="text-sm text-gray-500">Assigné à</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.assignee?.name }}</dd>
                        </div>
                        <div v-if="ticket.vendor">
                            <dt class="text-sm text-gray-500">Prestataire</dt>
                            <dd class="font-medium text-gray-900">{{ ticket.vendor.name }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Costs -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Coûts</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm text-gray-500">Estimé</dt>
                            <dd class="font-medium text-gray-900">{{ formatCurrency(ticket.estimated_cost) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Réel</dt>
                            <dd class="font-medium text-gray-900">{{ formatCurrency(ticket.actual_cost) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Assignment -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Assignation</h3>
                    <form @submit.prevent="assignTicket" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Assigner à</label>
                            <select v-model="assignForm.assigned_to" class="w-full rounded-xl border-gray-200">
                                <option value="">Non assigné</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">Prestataire</label>
                            <select v-model="assignForm.vendor_id" class="w-full rounded-xl border-gray-200">
                                <option value="">Aucun</option>
                                <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                                    {{ vendor.name }}
                                </option>
                            </select>
                        </div>
                        <button type="submit" :disabled="assignForm.processing" class="w-full btn-secondary">
                            Mettre à jour
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChatBubbleLeftIcon,
    ClockIcon,
    PlayIcon,
    CheckIcon,
    ArchiveBoxIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    ticket: Object,
    comments: Array,
    history: Array,
    users: Array,
    vendors: Array,
})

const commentForm = useForm({
    content: '',
})

const assignForm = useForm({
    assigned_to: props.ticket.assigned_to || '',
    vendor_id: props.ticket.vendor_id || '',
})

const timeline = computed(() => {
    const items = []

    // Add comments
    props.comments?.forEach(comment => {
        items.push({
            id: `comment-${comment.id}`,
            type: 'comment',
            content: comment.content,
            user: comment.user,
            created_at: comment.created_at,
        })
    })

    // Add history
    props.history?.forEach(entry => {
        items.push({
            id: `history-${entry.id}`,
            type: 'history',
            description: entry.description,
            user: entry.user,
            created_at: entry.created_at,
        })
    })

    // Sort by date descending
    return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const getPriorityLabel = (priority) => {
    const labels = { low: 'Basse', medium: 'Moyenne', high: 'Haute', urgent: 'Urgente' }
    return labels[priority] || priority
}

const getPriorityBadgeClass = (priority) => {
    const classes = {
        low: 'bg-gray-100 text-gray-700',
        medium: 'bg-yellow-100 text-yellow-700',
        high: 'bg-orange-100 text-orange-700',
        urgent: 'bg-red-100 text-red-700',
    }
    return classes[priority] || 'bg-gray-100 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        open: 'Ouvert',
        in_progress: 'En cours',
        on_hold: 'En attente',
        resolved: 'Résolu',
        closed: 'Clôturé',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        open: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        on_hold: 'bg-gray-100 text-gray-700',
        resolved: 'bg-green-100 text-green-700',
        closed: 'bg-purple-100 text-purple-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const formatDateTime = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const formatCurrency = (amount) => {
    if (!amount) return '-'
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount)
}

const updateStatus = (status) => {
    router.post(route('tenant.maintenance.update-status', props.ticket.id), { status })
}

const addComment = () => {
    commentForm.post(route('tenant.maintenance.comments.store', props.ticket.id), {
        onSuccess: () => {
            commentForm.reset()
        }
    })
}

const assignTicket = () => {
    assignForm.post(route('tenant.maintenance.assign', props.ticket.id))
}
</script>
