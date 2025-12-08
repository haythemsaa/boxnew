<template>
    <TenantLayout title="Inspections & Rondes" :breadcrumbs="[{ label: 'Inspections' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Inspections ce mois</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.inspections_this_month }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <ClipboardDocumentCheckIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Rondes effectuées</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.patrols_completed }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <ShieldCheckIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Problèmes détectés</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.issues_found }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <ExclamationTriangleIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Planifiées aujourd'hui</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.scheduled_today }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <CalendarIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Inspections Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <ClipboardDocumentCheckIcon class="w-8 h-8 text-blue-600" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Inspections de box</h3>
                            <p class="text-sm text-gray-500">États des lieux et contrôles</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <Link :href="route('tenant.inspections.list')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <DocumentTextIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Voir les inspections</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.inspections.create')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <PlusIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Nouvelle inspection</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.inspections.schedules')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <CalendarDaysIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Planning des inspections</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                    </div>
                </div>

                <!-- Patrols Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <ShieldCheckIcon class="w-8 h-8 text-green-600" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Rondes de sécurité</h3>
                            <p class="text-sm text-gray-500">Surveillance et checkpoints</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <Link :href="route('tenant.inspections.patrols')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <MapIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Voir les rondes</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.inspections.patrols.create')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <PlusIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Démarrer une ronde</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                        <Link :href="route('tenant.inspections.checkpoints')" class="flex items-center justify-between p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <MapPinIcon class="w-5 h-5 text-gray-500" />
                                <span class="text-sm font-medium text-gray-700">Gérer les checkpoints</span>
                            </div>
                            <ChevronRightIcon class="w-5 h-5 text-gray-400" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Inspections -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Dernières inspections</h3>
                    <Link :href="route('tenant.inspections.list')" class="text-primary-600 hover:text-primary-800 text-sm">
                        Voir toutes
                    </Link>
                </div>

                <div v-if="recentInspections.length === 0" class="px-6 py-8 text-center">
                    <ClipboardDocumentCheckIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucune inspection récente</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="inspection in recentInspections" :key="inspection.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div :class="getTypeIconClass(inspection.type)" class="p-2 rounded-lg">
                                <component :is="getTypeIcon(inspection.type)" class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ getTypeLabel(inspection.type) }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ inspection.box?.code }} - {{ inspection.site?.name }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span :class="getStatusBadgeClass(inspection.status)" class="px-3 py-1 rounded-full text-xs font-medium">
                                {{ getStatusLabel(inspection.status) }}
                            </span>
                            <p class="text-xs text-gray-500 mt-1">{{ formatDate(inspection.inspection_date) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Patrols -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Dernières rondes</h3>
                    <Link :href="route('tenant.inspections.patrols')" class="text-primary-600 hover:text-primary-800 text-sm">
                        Voir toutes
                    </Link>
                </div>

                <div v-if="recentPatrols.length === 0" class="px-6 py-8 text-center">
                    <ShieldCheckIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucune ronde récente</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="patrol in recentPatrols" :key="patrol.id" class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-600 font-semibold text-sm">{{ getInitials(patrol.user) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ patrol.user?.name }}</p>
                                <p class="text-sm text-gray-500">{{ patrol.site?.name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">{{ patrol.checkpoints_scanned }}/{{ patrol.total_checkpoints }} checkpoints</p>
                            <p class="text-xs text-gray-500">{{ formatDateTime(patrol.started_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Schedules -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Prochaines inspections planifiées</h3>
                </div>

                <div v-if="upcomingSchedules.length === 0" class="px-6 py-8 text-center">
                    <CalendarIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucune inspection planifiée</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="schedule in upcomingSchedules" :key="schedule.id" class="px-6 py-4 flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-900">{{ schedule.name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ schedule.site?.name }} - {{ getFrequencyLabel(schedule.frequency) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-900">{{ formatDate(schedule.next_run_at) }}</p>
                            <span :class="schedule.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'" class="px-2 py-1 rounded-full text-xs font-medium">
                                {{ schedule.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ClipboardDocumentCheckIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    CalendarIcon,
    CalendarDaysIcon,
    DocumentTextIcon,
    MapIcon,
    MapPinIcon,
    PlusIcon,
    ChevronRightIcon,
    ArrowPathIcon,
    TruckIcon,
    ArrowRightStartOnRectangleIcon,
} from '@heroicons/vue/24/outline'

defineProps({
    stats: Object,
    recentInspections: Array,
    recentPatrols: Array,
    upcomingSchedules: Array,
})

const getTypeLabel = (type) => {
    const labels = {
        move_in: 'État des lieux entrée',
        move_out: 'État des lieux sortie',
        routine: 'Inspection routine',
        maintenance: 'Inspection maintenance',
        complaint: 'Suite réclamation',
    }
    return labels[type] || type
}

const getTypeIcon = (type) => {
    const icons = {
        move_in: ArrowRightStartOnRectangleIcon,
        move_out: TruckIcon,
        routine: ArrowPathIcon,
        maintenance: ClipboardDocumentCheckIcon,
        complaint: ExclamationTriangleIcon,
    }
    return icons[type] || ClipboardDocumentCheckIcon
}

const getTypeIconClass = (type) => {
    const classes = {
        move_in: 'bg-green-100 text-green-600',
        move_out: 'bg-orange-100 text-orange-600',
        routine: 'bg-blue-100 text-blue-600',
        maintenance: 'bg-purple-100 text-purple-600',
        complaint: 'bg-red-100 text-red-600',
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

const getStatusLabel = (status) => {
    const labels = {
        scheduled: 'Planifiée',
        in_progress: 'En cours',
        completed: 'Terminée',
        cancelled: 'Annulée',
    }
    return labels[status] || status
}

const getStatusBadgeClass = (status) => {
    const classes = {
        scheduled: 'bg-blue-100 text-blue-700',
        in_progress: 'bg-yellow-100 text-yellow-700',
        completed: 'bg-green-100 text-green-700',
        cancelled: 'bg-gray-100 text-gray-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
}

const getFrequencyLabel = (frequency) => {
    const labels = {
        daily: 'Quotidien',
        weekly: 'Hebdomadaire',
        monthly: 'Mensuel',
        quarterly: 'Trimestriel',
        yearly: 'Annuel',
    }
    return labels[frequency] || frequency
}

const getInitials = (user) => {
    if (!user?.name) return '?'
    return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}

const formatDateTime = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    })
}
</script>
