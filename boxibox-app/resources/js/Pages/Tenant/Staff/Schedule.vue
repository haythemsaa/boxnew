<template>
    <TenantLayout title="Planning du personnel" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Planning' }]">
        <div class="space-y-6">
            <!-- Header with navigation -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button @click="previousWeek" class="btn-secondary">
                        <ChevronLeftIcon class="w-5 h-5" />
                    </button>
                    <h2 class="text-lg font-semibold">
                        Semaine du {{ formatDate(weekStart) }}
                    </h2>
                    <button @click="nextWeek" class="btn-secondary">
                        <ChevronRightIcon class="w-5 h-5" />
                    </button>
                </div>
                <button @click="showAddShift = true" class="btn-primary">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Ajouter un shift
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="grid grid-cols-8 border-b border-gray-200">
                    <div class="p-4 bg-gray-50 font-medium text-gray-700">Employé</div>
                    <div v-for="day in weekDays" :key="day.date" class="p-4 bg-gray-50 text-center border-l border-gray-200">
                        <div class="font-medium text-gray-700">{{ day.name }}</div>
                        <div class="text-sm text-gray-500">{{ day.formatted }}</div>
                    </div>
                </div>

                <div v-for="staffMember in staff" :key="staffMember.id" class="grid grid-cols-8 border-b border-gray-100 last:border-0">
                    <div class="p-4 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center">
                            <span class="text-primary-700 font-medium text-sm">
                                {{ staffMember.user?.name?.charAt(0) || '?' }}
                            </span>
                        </div>
                        <div>
                            <div class="font-medium text-gray-900">{{ staffMember.user?.name }}</div>
                            <div class="text-xs text-gray-500">{{ staffMember.position }}</div>
                        </div>
                    </div>
                    <div v-for="day in weekDays" :key="day.date" class="p-2 border-l border-gray-100 min-h-[80px]">
                        <div v-for="shift in getShiftsForDay(staffMember.user_id, day.date)" :key="shift.id"
                            class="text-xs p-2 rounded-lg mb-1"
                            :class="getShiftClass(shift.status)">
                            <div class="font-medium">{{ shift.start_time }} - {{ shift.end_time }}</div>
                            <div class="text-gray-600">{{ shift.site?.name }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="staff.length === 0" class="p-8 text-center text-gray-500">
                    Aucun employé trouvé
                </div>
            </div>
        </div>

        <!-- Add Shift Modal -->
        <div v-if="showAddShift" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Ajouter un shift</h3>
                <form @submit.prevent="submitShift" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employé</label>
                        <select v-model="shiftForm.user_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner</option>
                            <option v-for="s in staff" :key="s.user_id" :value="s.user_id">
                                {{ s.user?.name }}
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
                        <input v-model="shiftForm.shift_date" type="date" class="w-full rounded-xl border-gray-200" required />
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
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAddShift = false" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary" :disabled="shiftForm.processing">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    shifts: Object,
    staff: Array,
    weekStart: String,
    sites: Array,
})

const showAddShift = ref(false)

const shiftForm = useForm({
    user_id: '',
    site_id: '',
    shift_date: props.weekStart,
    start_time: '09:00',
    end_time: '17:00',
})

const weekDays = computed(() => {
    const days = []
    const start = new Date(props.weekStart)
    const dayNames = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']

    for (let i = 0; i < 7; i++) {
        const date = new Date(start)
        date.setDate(start.getDate() + i)
        days.push({
            date: date.toISOString().split('T')[0],
            name: dayNames[date.getDay()],
            formatted: date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
        })
    }
    return days
})

const formatDate = (dateStr) => {
    return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

const getShiftsForDay = (userId, date) => {
    const userShifts = props.shifts[userId] || []
    return userShifts.filter(s => s.shift_date === date)
}

const getShiftClass = (status) => {
    const classes = {
        scheduled: 'bg-blue-100 text-blue-800',
        confirmed: 'bg-green-100 text-green-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    }
    return classes[status] || 'bg-gray-100 text-gray-800'
}

const previousWeek = () => {
    const date = new Date(props.weekStart)
    date.setDate(date.getDate() - 7)
    router.get(route('tenant.staff.schedule'), { week_start: date.toISOString().split('T')[0] })
}

const nextWeek = () => {
    const date = new Date(props.weekStart)
    date.setDate(date.getDate() + 7)
    router.get(route('tenant.staff.schedule'), { week_start: date.toISOString().split('T')[0] })
}

const submitShift = () => {
    shiftForm.post(route('tenant.staff.shifts.store'), {
        onSuccess: () => {
            showAddShift.value = false
            shiftForm.reset()
        }
    })
}
</script>
