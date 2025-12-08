<template>
    <TenantLayout title="Workflows de relance" :breadcrumbs="[{ label: 'Impayés', href: route('tenant.overdue.index') }, { label: 'Workflows' }]">
        <div class="space-y-6">
            <!-- Actions Bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Automatiser les relances</h3>
                        <p class="text-sm text-gray-500">Configurez des workflows automatiques pour gérer les impayés</p>
                    </div>
                    <button @click="showCreateModal = true" class="btn-primary">
                        <PlusIcon class="w-4 h-4 mr-2" />
                        Nouveau workflow
                    </button>
                </div>
            </div>

            <!-- Workflows List -->
            <div class="space-y-4">
                <div v-for="workflow in workflows" :key="workflow.id" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <!-- Workflow Header -->
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div :class="workflow.is_active ? 'bg-green-100' : 'bg-gray-100'" class="p-3 rounded-xl">
                                <BoltIcon :class="workflow.is_active ? 'text-green-600' : 'text-gray-400'" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ workflow.name }}</h3>
                                <p class="text-sm text-gray-500">
                                    Déclenchement: {{ workflow.trigger_days }} jours après échéance
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="workflow.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-3 py-1 rounded-full text-sm font-medium">
                                {{ workflow.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <button @click="toggleWorkflow(workflow)" class="p-2 text-gray-500 hover:text-gray-700">
                                <PlayIcon v-if="!workflow.is_active" class="w-5 h-5" />
                                <PauseIcon v-else class="w-5 h-5" />
                            </button>
                            <button @click="editWorkflow(workflow)" class="p-2 text-gray-500 hover:text-gray-700">
                                <PencilIcon class="w-5 h-5" />
                            </button>
                            <button @click="deleteWorkflow(workflow)" class="p-2 text-red-500 hover:text-red-700">
                                <TrashIcon class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <!-- Workflow Steps -->
                    <div class="p-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-4">Étapes du workflow</h4>
                        <div class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                            <div class="space-y-6">
                                <div v-for="(step, index) in workflow.steps" :key="step.id" class="relative flex gap-4">
                                    <!-- Step Circle -->
                                    <div :class="getStepIconClass(step.action_type)" class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0">
                                        <component :is="getStepIcon(step.action_type)" class="w-4 h-4" />
                                    </div>

                                    <!-- Step Content -->
                                    <div class="flex-1 bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-medium text-gray-900">{{ getActionLabel(step.action_type) }}</span>
                                                <span class="text-sm text-gray-500 ml-2">
                                                    J+{{ step.delay_days }} après déclenchement
                                                </span>
                                            </div>
                                            <span :class="getConditionBadgeClass(step)" class="px-2 py-1 rounded text-xs font-medium">
                                                {{ step.condition || 'Toujours' }}
                                            </span>
                                        </div>
                                        <p v-if="step.template_name" class="text-sm text-gray-500 mt-1">
                                            Modèle: {{ step.template_name }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="mt-6 pt-4 border-t border-gray-100 grid grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ workflow.executions_count || 0 }}</p>
                                <p class="text-xs text-gray-500">Exécutions</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">{{ workflow.success_rate || 0 }}%</p>
                                <p class="text-xs text-gray-500">Taux de succès</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ formatCurrency(workflow.amount_recovered || 0) }}</p>
                                <p class="text-xs text-gray-500">Récupéré</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="workflows.length === 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                    <BoltIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500">Aucun workflow configuré</p>
                    <button @click="showCreateModal = true" class="text-primary-600 hover:text-primary-800 text-sm mt-2">
                        Créer mon premier workflow
                    </button>
                </div>
            </div>

            <!-- Predefined Templates -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Modèles prédéfinis</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button @click="useTemplate('gentle')" class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50 transition-colors text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <HeartIcon class="w-5 h-5 text-green-600" />
                            </div>
                            <span class="font-medium text-gray-900">Relance douce</span>
                        </div>
                        <p class="text-sm text-gray-500">Email à J+3, rappel à J+10, appel à J+20</p>
                    </button>

                    <button @click="useTemplate('standard')" class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50 transition-colors text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <ScaleIcon class="w-5 h-5 text-blue-600" />
                            </div>
                            <span class="font-medium text-gray-900">Standard</span>
                        </div>
                        <p class="text-sm text-gray-500">Email + SMS à J+1, mise en demeure J+15</p>
                    </button>

                    <button @click="useTemplate('strict')" class="p-4 border border-gray-200 rounded-xl hover:border-primary-300 hover:bg-primary-50 transition-colors text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                            </div>
                            <span class="font-medium text-gray-900">Strict</span>
                        </div>
                        <p class="text-sm text-gray-500">Blocage accès à J+7, procédure à J+30</p>
                    </button>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" @click.self="closeModal">
            <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full mx-4 p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    {{ showEditModal ? 'Modifier le workflow' : 'Nouveau workflow' }}
                </h3>
                <form @submit.prevent="submitWorkflow" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                            <input v-model="workflowForm.name" type="text" class="w-full rounded-xl border-gray-200" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Déclenchement (jours après échéance) *</label>
                            <input v-model="workflowForm.trigger_days" type="number" min="0" class="w-full rounded-xl border-gray-200" required />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea v-model="workflowForm.description" rows="2" class="w-full rounded-xl border-gray-200"></textarea>
                    </div>

                    <!-- Steps -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-700">Étapes</label>
                            <button type="button" @click="addStep" class="text-sm text-primary-600 hover:text-primary-800">
                                + Ajouter une étape
                            </button>
                        </div>
                        <div class="space-y-3">
                            <div v-for="(step, index) in workflowForm.steps" :key="index" class="p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="font-medium text-gray-700">Étape {{ index + 1 }}</span>
                                    <button type="button" @click="removeStep(index)" class="text-red-500 hover:text-red-700">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="text-xs text-gray-500">Action</label>
                                        <select v-model="step.action_type" class="w-full rounded-lg border-gray-200 text-sm mt-1">
                                            <option value="send_email">Email</option>
                                            <option value="send_sms">SMS</option>
                                            <option value="phone_call">Appel</option>
                                            <option value="send_letter">Courrier</option>
                                            <option value="block_access">Bloquer accès</option>
                                            <option value="add_fee">Ajouter frais</option>
                                            <option value="notify_staff">Notifier équipe</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Délai (J+)</label>
                                        <input v-model="step.delay_days" type="number" min="0" class="w-full rounded-lg border-gray-200 text-sm mt-1" />
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Condition</label>
                                        <select v-model="step.condition" class="w-full rounded-lg border-gray-200 text-sm mt-1">
                                            <option value="">Toujours</option>
                                            <option value="if_unpaid">Si toujours impayé</option>
                                            <option value="if_no_response">Si pas de réponse</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="workflowForm.is_active" class="sr-only peer" />
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                        </label>
                        <span class="text-sm text-gray-700">Workflow actif</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="closeModal" class="btn-secondary">Annuler</button>
                        <button type="submit" :disabled="workflowForm.processing" class="btn-primary">
                            {{ showEditModal ? 'Mettre à jour' : 'Créer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    PlusIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
    BoltIcon,
    EnvelopeIcon,
    DevicePhoneMobileIcon,
    PhoneIcon,
    DocumentTextIcon,
    LockClosedIcon,
    CurrencyEuroIcon,
    BellIcon,
    HeartIcon,
    ScaleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    workflows: Array,
})

const showCreateModal = ref(false)
const showEditModal = ref(false)

const workflowForm = useForm({
    id: null,
    name: '',
    description: '',
    trigger_days: 1,
    is_active: true,
    steps: [
        { action_type: 'send_email', delay_days: 0, condition: '' }
    ],
})

const getActionLabel = (type) => {
    const labels = {
        send_email: 'Envoyer email',
        send_sms: 'Envoyer SMS',
        phone_call: 'Appel téléphonique',
        send_letter: 'Envoyer courrier',
        block_access: 'Bloquer accès',
        add_fee: 'Ajouter frais',
        notify_staff: 'Notifier équipe',
    }
    return labels[type] || type
}

const getStepIcon = (type) => {
    const icons = {
        send_email: EnvelopeIcon,
        send_sms: DevicePhoneMobileIcon,
        phone_call: PhoneIcon,
        send_letter: DocumentTextIcon,
        block_access: LockClosedIcon,
        add_fee: CurrencyEuroIcon,
        notify_staff: BellIcon,
    }
    return icons[type] || BoltIcon
}

const getStepIconClass = (type) => {
    const classes = {
        send_email: 'bg-blue-100 text-blue-600',
        send_sms: 'bg-green-100 text-green-600',
        phone_call: 'bg-yellow-100 text-yellow-600',
        send_letter: 'bg-purple-100 text-purple-600',
        block_access: 'bg-red-100 text-red-600',
        add_fee: 'bg-orange-100 text-orange-600',
        notify_staff: 'bg-gray-100 text-gray-600',
    }
    return classes[type] || 'bg-gray-100 text-gray-600'
}

const getConditionBadgeClass = (step) => {
    if (!step.condition) return 'bg-gray-100 text-gray-600'
    return 'bg-blue-100 text-blue-700'
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        maximumFractionDigits: 0
    }).format(amount)
}

const addStep = () => {
    workflowForm.steps.push({
        action_type: 'send_email',
        delay_days: workflowForm.steps.length * 7,
        condition: 'if_unpaid',
    })
}

const removeStep = (index) => {
    workflowForm.steps.splice(index, 1)
}

const editWorkflow = (workflow) => {
    workflowForm.id = workflow.id
    workflowForm.name = workflow.name
    workflowForm.description = workflow.description
    workflowForm.trigger_days = workflow.trigger_days
    workflowForm.is_active = workflow.is_active
    workflowForm.steps = workflow.steps?.map(s => ({
        action_type: s.action_type,
        delay_days: s.delay_days,
        condition: s.condition || '',
    })) || []
    showEditModal.value = true
}

const closeModal = () => {
    showCreateModal.value = false
    showEditModal.value = false
    workflowForm.reset()
    workflowForm.steps = [{ action_type: 'send_email', delay_days: 0, condition: '' }]
}

const submitWorkflow = () => {
    if (showEditModal.value) {
        workflowForm.put(route('tenant.overdue.workflows.update', workflowForm.id), {
            onSuccess: () => closeModal()
        })
    } else {
        workflowForm.post(route('tenant.overdue.workflows.store'), {
            onSuccess: () => closeModal()
        })
    }
}

const toggleWorkflow = (workflow) => {
    router.post(route('tenant.overdue.workflows.toggle', workflow.id))
}

const deleteWorkflow = (workflow) => {
    if (confirm(`Supprimer le workflow "${workflow.name}" ?`)) {
        router.delete(route('tenant.overdue.workflows.destroy', workflow.id))
    }
}

const useTemplate = (templateName) => {
    const templates = {
        gentle: {
            name: 'Relance douce',
            trigger_days: 3,
            steps: [
                { action_type: 'send_email', delay_days: 0, condition: '' },
                { action_type: 'send_email', delay_days: 7, condition: 'if_unpaid' },
                { action_type: 'phone_call', delay_days: 17, condition: 'if_unpaid' },
            ]
        },
        standard: {
            name: 'Relance standard',
            trigger_days: 1,
            steps: [
                { action_type: 'send_email', delay_days: 0, condition: '' },
                { action_type: 'send_sms', delay_days: 0, condition: '' },
                { action_type: 'send_email', delay_days: 7, condition: 'if_unpaid' },
                { action_type: 'send_letter', delay_days: 14, condition: 'if_unpaid' },
            ]
        },
        strict: {
            name: 'Relance stricte',
            trigger_days: 1,
            steps: [
                { action_type: 'send_email', delay_days: 0, condition: '' },
                { action_type: 'send_sms', delay_days: 3, condition: 'if_unpaid' },
                { action_type: 'block_access', delay_days: 6, condition: 'if_unpaid' },
                { action_type: 'add_fee', delay_days: 14, condition: 'if_unpaid' },
                { action_type: 'send_letter', delay_days: 29, condition: 'if_unpaid' },
            ]
        },
    }

    const template = templates[templateName]
    if (template) {
        workflowForm.name = template.name
        workflowForm.trigger_days = template.trigger_days
        workflowForm.steps = [...template.steps]
        showCreateModal.value = true
    }
}
</script>
