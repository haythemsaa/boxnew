<template>
    <TenantLayout title="Gestion du personnel" :breadcrumbs="[{ label: 'Personnel' }]">
        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Total employés</p>
                            <p class="text-3xl font-bold text-gray-900">{{ stats.total_staff }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <UsersIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">En service aujourd'hui</p>
                            <p class="text-3xl font-bold text-green-600">{{ stats.on_shift_today }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <ClockIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Tâches en cours</p>
                            <p class="text-3xl font-bold text-orange-600">{{ stats.active_tasks }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <ClipboardDocumentListIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Heures cette semaine</p>
                            <p class="text-3xl font-bold text-purple-600">{{ stats.hours_this_week }}h</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <CalendarDaysIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex gap-3">
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Rechercher un employé..."
                                class="pl-10 rounded-xl border-gray-200 text-sm w-64"
                            />
                        </div>
                        <select v-model="filterRole" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les rôles</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technicien</option>
                            <option value="receptionist">Réceptionniste</option>
                            <option value="security">Sécurité</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <Link :href="route('tenant.staff.shifts')" class="btn-secondary">
                            <CalendarIcon class="w-4 h-4 mr-2" />
                            Planning
                        </Link>
                        <Link :href="route('tenant.staff.tasks')" class="btn-secondary">
                            <ClipboardDocumentListIcon class="w-4 h-4 mr-2" />
                            Tâches
                        </Link>
                        <Link :href="route('tenant.staff.create')" class="btn-primary">
                            <PlusIcon class="w-4 h-4 mr-2" />
                            Nouvel employé
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Staff List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Employé</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Rôle</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Sites assignés</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">Heures/semaine</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="staff in staffProfiles.data" :key="staff.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-primary-600 font-semibold">{{ getInitials(staff.user) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ staff.user?.name }}</p>
                                            <p class="text-sm text-gray-500">{{ staff.user?.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getRoleBadgeClass(staff.role)" class="px-3 py-1 rounded-full text-xs font-medium">
                                        {{ getRoleLabel(staff.role) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="site in staff.sites?.slice(0, 2)" :key="site.id" class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">
                                            {{ site.name }}
                                        </span>
                                        <span v-if="staff.sites?.length > 2" class="px-2 py-1 bg-gray-100 rounded text-xs text-gray-600">
                                            +{{ staff.sites.length - 2 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <span :class="staff.is_on_shift ? 'bg-green-500' : 'bg-gray-300'" class="w-2 h-2 rounded-full"></span>
                                        <span class="text-sm text-gray-600">{{ staff.is_on_shift ? 'En service' : 'Hors service' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-900">{{ staff.hours_per_week || '-' }}h</p>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="route('tenant.staff.show', staff.id)" class="text-gray-500 hover:text-gray-700">
                                            <EyeIcon class="w-5 h-5" />
                                        </Link>
                                        <Link :href="route('tenant.staff.edit', staff.id)" class="text-gray-500 hover:text-gray-700">
                                            <PencilIcon class="w-5 h-5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="staffProfiles.links" class="px-6 py-4 border-t border-gray-100">
                    <Pagination :links="staffProfiles.links" />
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Planning du jour</h3>
                </div>

                <div v-if="todayShifts.length === 0" class="px-6 py-8 text-center">
                    <CalendarDaysIcon class="w-10 h-10 text-gray-300 mx-auto mb-3" />
                    <p class="text-gray-500 text-sm">Aucun shift planifié aujourd'hui</p>
                </div>

                <div v-else class="divide-y divide-gray-100">
                    <div v-for="shift in todayShifts" :key="shift.id" class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                                <span class="text-gray-600 font-semibold">{{ getInitials(shift.staff?.user) }}</span>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900">{{ shift.staff?.user?.name }}</p>
                                <p class="text-sm text-gray-500">{{ shift.site?.name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-medium text-gray-900">{{ formatTime(shift.start_time) }} - {{ formatTime(shift.end_time) }}</p>
                            <p class="text-xs text-gray-500">{{ shift.shift_type }}</p>
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
    UsersIcon,
    ClockIcon,
    ClipboardDocumentListIcon,
    CalendarDaysIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    staffProfiles: Object,
    stats: Object,
    todayShifts: Array,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const filterRole = ref(props.filters?.role || '')

watch([search, filterRole], () => {
    router.get(route('tenant.staff.index'), {
        search: search.value,
        role: filterRole.value,
    }, { preserveState: true, replace: true })
})

const getInitials = (user) => {
    if (!user?.name) return '?'
    return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const getRoleLabel = (role) => {
    const labels = {
        manager: 'Manager',
        technician: 'Technicien',
        receptionist: 'Réceptionniste',
        security: 'Sécurité',
        cleaner: 'Agent entretien',
    }
    return labels[role] || role
}

const getRoleBadgeClass = (role) => {
    const classes = {
        manager: 'bg-purple-100 text-purple-700',
        technician: 'bg-blue-100 text-blue-700',
        receptionist: 'bg-green-100 text-green-700',
        security: 'bg-red-100 text-red-700',
        cleaner: 'bg-orange-100 text-orange-700',
    }
    return classes[role] || 'bg-gray-100 text-gray-700'
}

const formatTime = (time) => {
    if (!time) return '-'
    return time.substring(0, 5)
}
</script>
