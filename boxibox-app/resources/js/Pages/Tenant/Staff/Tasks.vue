<template>
    <TenantLayout title="Tâches du personnel" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Tâches' }]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <select v-model="selectedStatus" @change="filterTasks" class="rounded-xl border-gray-200">
                        <option value="">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="in_progress">En cours</option>
                        <option value="completed">Terminé</option>
                    </select>
                    <select v-model="selectedUser" @change="filterTasks" class="rounded-xl border-gray-200">
                        <option value="">Tous les employés</option>
                        <option v-for="s in staff" :key="s.user_id" :value="s.user_id">
                            {{ s.user?.name }}
                        </option>
                    </select>
                </div>
                <button @click="showAddTask = true" class="btn-primary">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Nouvelle tâche
                </button>
            </div>

            <!-- Tasks List -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
                <div class="divide-y divide-gray-100">
                    <div v-for="task in tasks.data" :key="task.id" class="p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-4">
                                <div class="mt-1">
                                    <button @click="toggleTaskStatus(task)"
                                        class="w-5 h-5 rounded border-2 flex items-center justify-center"
                                        :class="task.status === 'completed' ? 'bg-green-500 border-green-500' : 'border-gray-300'">
                                        <CheckIcon v-if="task.status === 'completed'" class="w-3 h-3 text-white" />
                                    </button>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900" :class="{ 'line-through text-gray-400': task.status === 'completed' }">
                                        {{ task.title }}
                                    </h4>
                                    <p v-if="task.description" class="text-sm text-gray-500 mt-1">{{ task.description }}</p>
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <UserIcon class="w-4 h-4" />
                                            {{ task.assigned_to_user?.name || 'Non assigné' }}
                                        </span>
                                        <span v-if="task.due_date" class="flex items-center gap-1">
                                            <CalendarIcon class="w-4 h-4" />
                                            {{ formatDate(task.due_date) }}
                                        </span>
                                        <span v-if="task.site" class="flex items-center gap-1">
                                            <MapPinIcon class="w-4 h-4" />
                                            {{ task.site.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getPriorityClass(task.priority)">
                                    {{ getPriorityLabel(task.priority) }}
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full" :class="getStatusClass(task.status)">
                                    {{ getStatusLabel(task.status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="tasks.data?.length === 0" class="p-8 text-center text-gray-500">
                        Aucune tâche trouvée
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="tasks.links" class="flex justify-center">
                <nav class="flex gap-1">
                    <Link v-for="link in tasks.links" :key="link.label"
                        :href="link.url || '#'"
                        class="px-3 py-1 rounded-lg text-sm"
                        :class="link.active ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        v-html="link.label" />
                </nav>
            </div>
        </div>

        <!-- Add Task Modal -->
        <div v-if="showAddTask" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl p-6 w-full max-w-md">
                <h3 class="text-lg font-semibold mb-4">Nouvelle tâche</h3>
                <form @submit.prevent="submitTask" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                        <input v-model="taskForm.title" type="text" class="w-full rounded-xl border-gray-200" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="taskForm.description" rows="3" class="w-full rounded-xl border-gray-200"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Assigné à *</label>
                        <select v-model="taskForm.user_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner</option>
                            <option v-for="s in staff" :key="s.user_id" :value="s.user_id">
                                {{ s.user?.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Priorité *</label>
                            <select v-model="taskForm.priority" class="w-full rounded-xl border-gray-200" required>
                                <option value="low">Basse</option>
                                <option value="medium">Moyenne</option>
                                <option value="high">Haute</option>
                                <option value="urgent">Urgente</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Échéance *</label>
                            <input v-model="taskForm.due_date" type="date" class="w-full rounded-xl border-gray-200" required />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                        <select v-model="taskForm.site_id" class="w-full rounded-xl border-gray-200">
                            <option value="">Aucun</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showAddTask = false" class="btn-secondary">Annuler</button>
                        <button type="submit" class="btn-primary" :disabled="taskForm.processing">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { PlusIcon, CheckIcon, UserIcon, CalendarIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tasks: Object,
    staff: Array,
    sites: Array,
    filters: Object,
})

const showAddTask = ref(false)
const selectedStatus = ref(props.filters?.status || '')
const selectedUser = ref(props.filters?.user_id || '')

const taskForm = useForm({
    title: '',
    description: '',
    user_id: '',
    site_id: '',
    priority: 'medium',
    due_date: new Date().toISOString().split('T')[0],
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}

const getPriorityClass = (priority) => ({
    low: 'bg-gray-100 text-gray-700',
    medium: 'bg-blue-100 text-blue-700',
    high: 'bg-orange-100 text-orange-700',
    urgent: 'bg-red-100 text-red-700',
})[priority] || 'bg-gray-100 text-gray-700'

const getPriorityLabel = (priority) => ({
    low: 'Basse',
    medium: 'Moyenne',
    high: 'Haute',
    urgent: 'Urgente',
})[priority] || priority

const getStatusClass = (status) => ({
    pending: 'bg-yellow-100 text-yellow-700',
    in_progress: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
})[status] || 'bg-gray-100 text-gray-700'

const getStatusLabel = (status) => ({
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Terminé',
    cancelled: 'Annulé',
})[status] || status

const filterTasks = () => {
    router.get(route('tenant.staff.tasks'), {
        status: selectedStatus.value || undefined,
        user_id: selectedUser.value || undefined,
    }, { preserveState: true })
}

const toggleTaskStatus = (task) => {
    const newStatus = task.status === 'completed' ? 'pending' : 'completed'
    router.patch(route('tenant.staff.tasks.update-status', task.id), {
        status: newStatus
    })
}

const submitTask = () => {
    taskForm.post(route('tenant.staff.tasks.store'), {
        onSuccess: () => {
            showAddTask.value = false
            taskForm.reset()
        }
    })
}
</script>
