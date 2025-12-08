<template>
    <TenantLayout title="Demandes RGPD" :breadcrumbs="[{ label: 'RGPD', href: route('tenant.gdpr.index') }, { label: 'Demandes' }]">
        <div class="space-y-6">
            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex gap-3">
                        <select v-model="filterType" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les types</option>
                            <option value="access">Droit d'accès</option>
                            <option value="rectification">Rectification</option>
                            <option value="erasure">Effacement</option>
                            <option value="portability">Portabilité</option>
                            <option value="restriction">Limitation</option>
                            <option value="objection">Opposition</option>
                        </select>
                        <select v-model="filterStatus" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="pending">En attente</option>
                            <option value="in_progress">En cours</option>
                            <option value="completed">Traitée</option>
                            <option value="rejected">Rejetée</option>
                        </select>
                    </div>

                    <Link :href="route('tenant.gdpr.requests.create')" class="btn-primary">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouvelle demande
                    </Link>
                </div>
            </div>

            <!-- Requests List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Référence</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Type</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date demande</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Date limite</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm text-gray-900">{{ request.reference }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ request.customer?.full_name }}</p>
                                        <p class="text-sm text-gray-500">{{ request.customer?.email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getTypeBadgeClass(request.type)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getTypeLabel(request.type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusBadgeClass(request.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(request.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ formatDate(request.requested_at) }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span :class="getDueDateClass(request)" class="text-sm">
                                            {{ formatDate(request.due_date) }}
                                        </span>
                                        <ExclamationTriangleIcon v-if="isOverdue(request)" class="w-4 h-4 text-red-500" />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('tenant.gdpr.requests.show', request.id)" class="text-gray-500 hover:text-gray-700">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <button v-if="request.status === 'pending'" @click="startProcessing(request)" class="text-blue-600 hover:text-blue-800">
                                            <PlayIcon class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="requests.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="requests.links" />
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 rounded-2xl p-6">
                <div class="flex items-start gap-4">
                    <InformationCircleIcon class="w-6 h-6 text-blue-600 flex-shrink-0" />
                    <div class="text-sm text-blue-800">
                        <p class="font-medium mb-2">Rappel RGPD</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Vous avez <strong>30 jours</strong> pour répondre à une demande de droits</li>
                            <li>Ce délai peut être prolongé de 2 mois pour les demandes complexes (avec notification au demandeur)</li>
                            <li>Conservez une trace de toutes les actions effectuées</li>
                        </ul>
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
    PlusIcon,
    EyeIcon,
    PlayIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    requests: Object,
    filters: Object,
})

const filterType = ref(props.filters?.type || '')
const filterStatus = ref(props.filters?.status || '')

watch([filterType, filterStatus], () => {
    router.get(route('tenant.gdpr.requests'), {
        type: filterType.value,
        status: filterStatus.value,
    }, { preserveState: true, replace: true })
})

const getTypeLabel = (type) => {
    const labels = {
        access: 'Droit d\'accès',
        rectification: 'Rectification',
        erasure: 'Effacement',
        portability: 'Portabilité',
        restriction: 'Limitation',
        objection: 'Opposition',
    }
    return labels[type] || type
}

const getTypeBadgeClass = (type) => {
    const classes = {
        access: 'bg-blue-100 text-blue-700',
        rectification: 'bg-yellow-100 text-yellow-700',
        erasure: 'bg-red-100 text-red-700',
        portability: 'bg-purple-100 text-purple-700',
        restriction: 'bg-orange-100 text-orange-700',
        objection: 'bg-gray-100 text-gray-700',
    }
    return classes[type] || 'bg-gray-100 text-gray-700'
}

const getStatusLabel = (status) => {
    const labels = {
        pending: 'En attente',
        in_progress: 'En cours',
        completed: 'Traitée',
        rejected: 'Rejetée',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-700',
        in_progress: 'bg-blue-100 text-blue-700',
        completed: 'bg-green-100 text-green-700',
        rejected: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getDueDateClass = (request) => {
    if (request.status === 'completed') return 'text-gray-500'
    if (isOverdue(request)) return 'text-red-600 font-medium'
    if (isDueSoon(request)) return 'text-orange-600'
    return 'text-gray-600'
}

const isOverdue = (request) => {
    if (request.status === 'completed') return false
    return new Date(request.due_date) < new Date()
}

const isDueSoon = (request) => {
    if (request.status === 'completed') return false
    const dueDate = new Date(request.due_date)
    const now = new Date()
    const diffDays = (dueDate - now) / (1000 * 60 * 60 * 24)
    return diffDays > 0 && diffDays <= 7
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const startProcessing = (request) => {
    router.post(route('tenant.gdpr.requests.start', request.id))
}
</script>
