<template>
    <TenantLayout :title="staff.user?.name" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: staff.user?.name }]">
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Profile Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 font-bold text-2xl">
                                {{ staff.user?.name?.charAt(0) || '?' }}
                            </span>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ staff.user?.name }}</h1>
                            <p class="text-gray-500">{{ staff.position }}</p>
                            <p class="text-sm text-gray-400">{{ staff.user?.email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-sm font-medium"
                            :class="staff.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'">
                            {{ staff.is_active ? 'Actif' : 'Inactif' }}
                        </span>
                        <Link :href="route('tenant.staff.edit', staff.id)" class="btn-secondary">
                            <PencilIcon class="w-4 h-4 mr-1" />
                            Modifier
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-2xl font-bold text-primary-600">{{ monthlyStats.hours_worked }}h</div>
                    <div class="text-sm text-gray-500">Heures ce mois</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-2xl font-bold text-green-600">{{ monthlyStats.tasks_completed }}</div>
                    <div class="text-sm text-gray-500">Tâches terminées</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-2xl font-bold text-blue-600">{{ staff.tenure_in_months || 0 }}</div>
                    <div class="text-sm text-gray-500">Mois d'ancienneté</div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Info Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Informations</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Département</dt>
                            <dd class="text-gray-900">{{ staff.department || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Site assigné</dt>
                            <dd class="text-gray-900">{{ staff.site?.name || 'Non assigné' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Date d'embauche</dt>
                            <dd class="text-gray-900">{{ formatDate(staff.hire_date) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">N° Employé</dt>
                            <dd class="text-gray-900">{{ staff.employee_id || '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contact urgence -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Contact d'urgence</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Nom</dt>
                            <dd class="text-gray-900">{{ staff.emergency_contact_name || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Téléphone</dt>
                            <dd class="text-gray-900">{{ staff.emergency_contact_phone || '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Skills -->
            <div v-if="staff.skills?.length" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Compétences</h3>
                <div class="flex flex-wrap gap-2">
                    <span v-for="skill in staff.skills" :key="skill"
                        class="px-3 py-1 bg-primary-100 text-primary-700 rounded-full text-sm">
                        {{ skill }}
                    </span>
                </div>
            </div>

            <!-- Recent Shifts -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Shifts récents</h3>
                <div v-if="shifts.length" class="space-y-2">
                    <div v-for="shift in shifts" :key="shift.id"
                        class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div>
                            <div class="font-medium">{{ formatDate(shift.shift_date) }}</div>
                            <div class="text-sm text-gray-500">{{ shift.start_time }} - {{ shift.end_time }}</div>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full" :class="getShiftStatusClass(shift.status)">
                            {{ getShiftStatusLabel(shift.status) }}
                        </span>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-center py-4">Aucun shift récent</p>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { PencilIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    staff: Object,
    shifts: Array,
    monthlyStats: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getShiftStatusClass = (status) => ({
    scheduled: 'bg-blue-100 text-blue-700',
    confirmed: 'bg-green-100 text-green-700',
    in_progress: 'bg-yellow-100 text-yellow-700',
    completed: 'bg-gray-100 text-gray-700',
    cancelled: 'bg-red-100 text-red-700',
})[status] || 'bg-gray-100 text-gray-700'

const getShiftStatusLabel = (status) => ({
    scheduled: 'Planifié',
    confirmed: 'Confirmé',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé',
})[status] || status
</script>
