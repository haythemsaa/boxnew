<template>
    <TenantLayout title="Performance du personnel" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Performance' }]">
        <div class="space-y-6">
            <!-- Period Selector -->
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Performance de l'équipe</h2>
                <select v-model="selectedPeriod" @change="changePeriod" class="rounded-xl border-gray-200">
                    <option value="week">Cette semaine</option>
                    <option value="month">Ce mois</option>
                    <option value="quarter">Ce trimestre</option>
                    <option value="year">Cette année</option>
                </select>
            </div>

            <!-- Performance Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-medium text-gray-700">Employé</th>
                            <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Heures travaillées</th>
                            <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Tâches terminées</th>
                            <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Taux completion</th>
                            <th class="px-6 py-4 text-center text-sm font-medium text-gray-700">Présence</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="perf in performances" :key="perf.staff.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        <span class="text-primary-700 font-medium">
                                            {{ perf.staff.user?.name?.charAt(0) || '?' }}
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ perf.staff.user?.name }}</div>
                                        <div class="text-sm text-gray-500">{{ perf.staff.position }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-gray-900">{{ perf.hours_worked }}h</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-semibold text-gray-900">{{ perf.tasks_completed }}</span>
                                <span class="text-gray-500">/{{ perf.total_tasks }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full"
                                            :class="getCompletionRateColor(perf.completion_rate)"
                                            :style="{ width: `${perf.completion_rate}%` }">
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium" :class="getCompletionRateTextColor(perf.completion_rate)">
                                        {{ perf.completion_rate }}%
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full"
                                            :class="getAttendanceColor(perf.attendance_rate)"
                                            :style="{ width: `${perf.attendance_rate}%` }">
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium" :class="getAttendanceTextColor(perf.attendance_rate)">
                                        {{ perf.attendance_rate }}%
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="performances.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                Aucune donnée de performance disponible
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-3xl font-bold text-primary-600">{{ totalHours }}h</div>
                    <div class="text-sm text-gray-500">Total heures</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-3xl font-bold text-green-600">{{ totalTasksCompleted }}</div>
                    <div class="text-sm text-gray-500">Tâches terminées</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-3xl font-bold text-blue-600">{{ averageCompletion }}%</div>
                    <div class="text-sm text-gray-500">Taux completion moyen</div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <div class="text-3xl font-bold text-purple-600">{{ averageAttendance }}%</div>
                    <div class="text-sm text-gray-500">Présence moyenne</div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    performances: Array,
    period: String,
})

const selectedPeriod = ref(props.period || 'month')

const totalHours = computed(() => {
    return props.performances.reduce((sum, p) => sum + (p.hours_worked || 0), 0).toFixed(1)
})

const totalTasksCompleted = computed(() => {
    return props.performances.reduce((sum, p) => sum + (p.tasks_completed || 0), 0)
})

const averageCompletion = computed(() => {
    if (!props.performances.length) return 0
    const sum = props.performances.reduce((sum, p) => sum + (p.completion_rate || 0), 0)
    return Math.round(sum / props.performances.length)
})

const averageAttendance = computed(() => {
    if (!props.performances.length) return 0
    const sum = props.performances.reduce((sum, p) => sum + (p.attendance_rate || 0), 0)
    return Math.round(sum / props.performances.length)
})

const getCompletionRateColor = (rate) => {
    if (rate >= 80) return 'bg-green-500'
    if (rate >= 60) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getCompletionRateTextColor = (rate) => {
    if (rate >= 80) return 'text-green-600'
    if (rate >= 60) return 'text-yellow-600'
    return 'text-red-600'
}

const getAttendanceColor = (rate) => {
    if (rate >= 90) return 'bg-green-500'
    if (rate >= 75) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getAttendanceTextColor = (rate) => {
    if (rate >= 90) return 'text-green-600'
    if (rate >= 75) return 'text-yellow-600'
    return 'text-red-600'
}

const changePeriod = () => {
    router.get(route('tenant.staff.performance'), { period: selectedPeriod.value }, { preserveState: true })
}
</script>
