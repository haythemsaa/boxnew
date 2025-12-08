<template>
    <TenantLayout title="Nouvelle inspection" :breadcrumbs="[{ label: 'Inspections', href: route('tenant.inspections.index') }, { label: 'Nouvelle inspection' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type d'inspection *</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <label
                                v-for="type in inspectionTypes"
                                :key="type.value"
                                :class="form.type === type.value ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-colors"
                            >
                                <input type="radio" v-model="form.type" :value="type.value" class="hidden" />
                                <component :is="type.icon" :class="form.type === type.value ? 'text-primary-600' : 'text-gray-400'" class="w-8 h-8 mb-2" />
                                <span :class="form.type === type.value ? 'text-primary-700' : 'text-gray-600'" class="text-sm font-medium text-center">
                                    {{ type.label }}
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.type" class="text-red-500 text-sm mt-1">{{ form.errors.type }}</p>
                    </div>

                    <!-- Site & Box -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Site *</label>
                            <select v-model="form.site_id" class="w-full rounded-xl border-gray-200" required @change="loadBoxes">
                                <option value="">Sélectionner un site</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.site_id" class="text-red-500 text-sm mt-1">{{ form.errors.site_id }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Box *</label>
                            <select v-model="form.box_id" class="w-full rounded-xl border-gray-200" required :disabled="!form.site_id">
                                <option value="">Sélectionner un box</option>
                                <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                    {{ box.code }} - {{ box.size_m2 }}m²
                                </option>
                            </select>
                            <p v-if="form.errors.box_id" class="text-red-500 text-sm mt-1">{{ form.errors.box_id }}</p>
                        </div>
                    </div>

                    <!-- Contract (for move-in/move-out) -->
                    <div v-if="form.type === 'move_in' || form.type === 'move_out'">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contrat associé</label>
                        <select v-model="form.contract_id" class="w-full rounded-xl border-gray-200">
                            <option value="">Sélectionner un contrat</option>
                            <option v-for="contract in filteredContracts" :key="contract.id" :value="contract.id">
                                #{{ contract.contract_number }} - {{ contract.customer?.full_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Date & Assignee -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'inspection *</label>
                            <input
                                v-model="form.inspection_date"
                                type="date"
                                class="w-full rounded-xl border-gray-200"
                                required
                            />
                            <p v-if="form.errors.inspection_date" class="text-red-500 text-sm mt-1">{{ form.errors.inspection_date }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Inspecteur</label>
                            <select v-model="form.inspector_id" class="w-full rounded-xl border-gray-200">
                                <option value="">Non assigné</option>
                                <option v-for="user in inspectors" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Checklist Template -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Modèle de checklist</label>
                        <select v-model="form.template_id" class="w-full rounded-xl border-gray-200" @change="loadTemplate">
                            <option value="">Checklist vide</option>
                            <option v-for="template in templates" :key="template.id" :value="template.id">
                                {{ template.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Checklist Items -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700">Points à vérifier</label>
                            <button type="button" @click="addChecklistItem" class="text-sm text-primary-600 hover:text-primary-800">
                                + Ajouter un point
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(item, index) in form.checklist" :key="index" class="flex items-center gap-2">
                                <input
                                    v-model="form.checklist[index]"
                                    type="text"
                                    class="flex-1 rounded-xl border-gray-200"
                                    placeholder="Point à vérifier..."
                                />
                                <button type="button" @click="removeChecklistItem(index)" class="p-2 text-red-500 hover:text-red-700">
                                    <TrashIcon class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes préliminaires</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Notes ou instructions pour l'inspection..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.inspections.index')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Planifier l'inspection</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowRightStartOnRectangleIcon,
    TruckIcon,
    ArrowPathIcon,
    WrenchScrewdriverIcon,
    ExclamationTriangleIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
    boxes: Array,
    contracts: Array,
    inspectors: Array,
    templates: Array,
})

const form = useForm({
    type: 'routine',
    site_id: '',
    box_id: '',
    contract_id: '',
    inspection_date: new Date().toISOString().split('T')[0],
    inspector_id: '',
    template_id: '',
    checklist: [
        'État général du box',
        'Propreté',
        'Éclairage',
        'Serrure et porte',
        'Humidité',
    ],
    notes: '',
})

const inspectionTypes = [
    { value: 'move_in', label: 'État des lieux entrée', icon: ArrowRightStartOnRectangleIcon },
    { value: 'move_out', label: 'État des lieux sortie', icon: TruckIcon },
    { value: 'routine', label: 'Inspection routine', icon: ArrowPathIcon },
    { value: 'maintenance', label: 'Inspection maintenance', icon: WrenchScrewdriverIcon },
    { value: 'complaint', label: 'Suite réclamation', icon: ExclamationTriangleIcon },
]

const filteredBoxes = computed(() => {
    if (!form.site_id) return []
    return props.boxes.filter(box => box.site_id === parseInt(form.site_id))
})

const filteredContracts = computed(() => {
    if (!form.box_id) return []
    return props.contracts.filter(contract => contract.box_id === parseInt(form.box_id))
})

const loadBoxes = () => {
    form.box_id = ''
    form.contract_id = ''
}

const loadTemplate = () => {
    const template = props.templates?.find(t => t.id === parseInt(form.template_id))
    if (template?.checklist_items) {
        form.checklist = [...template.checklist_items]
    }
}

const addChecklistItem = () => {
    form.checklist.push('')
}

const removeChecklistItem = (index) => {
    form.checklist.splice(index, 1)
}

const submit = () => {
    form.post(route('tenant.inspections.store'))
}
</script>
