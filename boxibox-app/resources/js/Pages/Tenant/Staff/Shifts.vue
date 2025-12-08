<template>
    <TenantLayout title="Planning des shifts" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Planning' }]">
        <div class="space-y-6">
            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <button @click="previousWeek" class="p-2 hover:bg-gray-100 rounded-lg">
                            <ChevronLeftIcon class="w-5 h-5 text-gray-600" />
                        </button>
                        <h2 class="text-lg font-semibold text-gray-900">
                            Semaine du {{ formatDate(weekStart) }}
                        </h2>
                        <button @click="nextWeek" class="p-2 hover:bg-gray-100 rounded-lg">
                            <ChevronRightIcon class="w-5 h-5 text-gray-600" />
                        </button>
                        <button @click="goToToday" class="text-sm text-primary-600 hover:text-primary-800">
                            Aujourd'hui
                        </button>
                    </div>

                    <div class="flex gap-3">
                        <select v-model="filterSite" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                        <button @click="showCreateModal = true" class="btn-primary">
                            <PlusIcon class="w-4 h-4 mr-2" />
                            Nouveau shift
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase w-48">
                                    Employé
                                </th>
                                <th v-for="day in weekDays" :key="day.date" class="px-2 py-3 text-center text-xs font-semibold text-gray-500 uppercase min-w-[120px]">
                                    <div>{{ day.name }}</div>
                                    <div class="text-gray-400 font-normal">{{ formatShortDate(day.date) }}</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="staff in staffWithShifts" :key="staff.id" class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                                            <span class="text-primary-600 text-sm font-semibold">{{ getInitials(staff.user) }}</span>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-sm">{{ staff.user?.name }}</p>
                                            <p class="text-xs text-gray-500">{{ getRoleLabel(staff.role) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td v-for="day in weekDays" :key="day.date" class="px-2 py-3 text-center">
                                    <div v-if="getShift(staff.id, day.date)" class="space-y-1">
                                        <div
                                            v-for="shift in getShifts(staff.id, day.date)"
                                            :key="shift.id"
                                            @click="editShift(shift)"
                                            :class="getShiftClass(shift)"
                                            class="px-2 py-1 rounded-lg text-xs cursor-pointer hover:opacity-80"
                                        >
                                            {{ shift.start_time?.substring(0, 5) }} - {{ shift.end_time?.substring(0, 5) }}
                                        </div>
                                    </div>
                                    <button
                                        v-else
                                        @click="openCreateShift(staff.id, day.date)"
                                        class="w-full py-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                    >
                                        <PlusIcon class="w-4 h-4 mx-auto" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Weekly Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Résumé de la semaine</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Total shifts</dt>
                            <dd class="font-medium text-gray-900">{{ weekSummary.total_shifts }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Heures planifiées</dt>
                            <dd class="font-medium text-gray-900">{{ weekSummary.total_hours }}h</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-500">Employés actifs</dt>
                            <dd class="font-medium text-gray-900">{{ weekSummary.active_staff }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Par type de shift</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                <span class="text-sm text-gray-600">Matin</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ weekSummary.morning_shifts || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                <span class="text-sm text-gray-600">Après-midi</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ weekSummary.afternoon_shifts || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                <span class="text-sm text-gray-600">Nuit</span>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ weekSummary.night_shifts || 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <span class="text-sm font-medium text-gray-700">Copier semaine précédente</span>
                            <DocumentDuplicateIcon class="w-5 h-5 text-gray-500" />
                        </button>
                        <button class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                            <span class="text-sm font-medium text-gray-700">Exporter planning</span>
                            <ArrowDownTrayIcon class="w-5 h-5 text-gray-500" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Shift Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="showCreateModal = false">
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Nouveau shift</h3>
                <form @submit.prevent="submitShift" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employé</label>
                        <select v-model="shiftForm.staff_profile_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner</option>
                            <option v-for="staff in staffProfiles" :key="staff.id" :value="staff.id">
                                {{ staff.user?.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select v-model="shiftForm.site_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input v-model="shiftForm.date" type="date" class="w-full rounded-xl border-gray-200" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Début</label>
                            <input v-model="shiftForm.start_time" type="time" class="w-full rounded-xl border-gray-200" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fin</label>
                            <input v-model="shiftForm.end_time" type="time" class="w-full rounded-xl border-gray-200" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select v-model="shiftForm.shift_type" class="w-full rounded-xl border-gray-200">
                            <option value="morning">Matin</option>
                            <option value="afternoon">Après-midi</option>
                            <option value="night">Nuit</option>
                            <option value="full_day">Journée complète</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showCreateModal = false" class="btn-secondary">
                            Annuler
                        </button>
                        <button type="submit" :disabled="shiftForm.processing" class="btn-primary">
                            Créer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    PlusIcon,
    DocumentDuplicateIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    staffProfiles: Array,
    shifts: Array,
    sites: Array,
    weekStart: String,
    weekSummary: Object,
})

const showCreateModal = ref(false)
const filterSite = ref('')

const shiftForm = useForm({
    staff_profile_id: '',
    site_id: '',
    date: '',
    start_time: '09:00',
    end_time: '17:00',
    shift_type: 'full_day',
})

const weekDays = computed(() => {
    const days = []
    const start = new Date(props.weekStart)
    const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']

    for (let i = 0; i < 7; i++) {
        const date = new Date(start)
        date.setDate(start.getDate() + i)
        days.push({
            name: dayNames[i],
            date: date.toISOString().split('T')[0],
        })
    }
    return days
})

const staffWithShifts = computed(() => {
    if (!filterSite.value) return props.staffProfiles
    return props.staffProfiles.filter(staff =>
        staff.sites?.some(site => site.id === parseInt(filterSite.value))
    )
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
        cleaner: 'Entretien',
    }
    return labels[role] || role
}

const getShift = (staffId, date) => {
    return props.shifts?.find(s => s.staff_profile_id === staffId && s.date === date)
}

const getShifts = (staffId, date) => {
    return props.shifts?.filter(s => s.staff_profile_id === staffId && s.date === date) || []
}

const getShiftClass = (shift) => {
    const classes = {
        morning: 'bg-blue-100 text-blue-700',
        afternoon: 'bg-yellow-100 text-yellow-700',
        night: 'bg-purple-100 text-purple-700',
        full_day: 'bg-green-100 text-green-700',
    }
    return classes[shift.shift_type] || 'bg-gray-100 text-gray-700'
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const formatShortDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit'
    })
}

const previousWeek = () => {
    const start = new Date(props.weekStart)
    start.setDate(start.getDate() - 7)
    router.get(route('tenant.staff.shifts'), { week: start.toISOString().split('T')[0] })
}

const nextWeek = () => {
    const start = new Date(props.weekStart)
    start.setDate(start.getDate() + 7)
    router.get(route('tenant.staff.shifts'), { week: start.toISOString().split('T')[0] })
}

const goToToday = () => {
    router.get(route('tenant.staff.shifts'))
}

const openCreateShift = (staffId, date) => {
    shiftForm.staff_profile_id = staffId
    shiftForm.date = date
    showCreateModal.value = true
}

const editShift = (shift) => {
    // TODO: Implement edit modal
    console.log('Edit shift', shift)
}

const submitShift = () => {
    shiftForm.post(route('tenant.staff.shifts.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            shiftForm.reset()
        }
    })
}
</script>
