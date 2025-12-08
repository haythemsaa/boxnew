<template>
    <TenantLayout title="Nouveau ticket" :breadcrumbs="[{ label: 'Maintenance', href: route('tenant.maintenance.index') }, { label: 'Nouveau ticket' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Titre du ticket *</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Décrivez brièvement le problème"
                            required
                        />
                        <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie *</label>
                        <select v-model="form.category_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner une catégorie</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.category_id" class="text-red-500 text-sm mt-1">{{ form.errors.category_id }}</p>
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Box (optionnel)</label>
                            <select v-model="form.box_id" class="w-full rounded-xl border-gray-200" :disabled="!form.site_id">
                                <option value="">Aucun box spécifique</option>
                                <option v-for="box in filteredBoxes" :key="box.id" :value="box.id">
                                    {{ box.number }} - {{ (box.length * box.width).toFixed(1) }}m²
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priorité *</label>
                        <div class="flex gap-4">
                            <label v-for="priority in priorities" :key="priority.value" class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    v-model="form.priority"
                                    :value="priority.value"
                                    class="text-primary-600"
                                />
                                <span :class="priority.class" class="px-3 py-1 rounded-full text-sm font-medium">
                                    {{ priority.label }}
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description détaillée *</label>
                        <textarea
                            v-model="form.description"
                            rows="5"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Décrivez le problème en détail..."
                            required
                        ></textarea>
                        <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                    </div>

                    <!-- Reported By -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Signalé par</label>
                        <select v-model="form.reported_by_type" class="w-full rounded-xl border-gray-200 mb-2">
                            <option value="staff">Personnel</option>
                            <option value="customer">Client</option>
                        </select>
                        <select v-if="form.reported_by_type === 'customer'" v-model="form.customer_id" class="w-full rounded-xl border-gray-200">
                            <option value="">Sélectionner un client</option>
                            <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                {{ customer.first_name }} {{ customer.last_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Estimated Cost -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Coût estimé (€)</label>
                            <input
                                v-model="form.estimated_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="0.00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date limite souhaitée</label>
                            <input
                                v-model="form.due_date"
                                type="date"
                                class="w-full rounded-xl border-gray-200"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.maintenance.index')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer le ticket</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'

const props = defineProps({
    categories: Array,
    sites: Array,
    boxes: Array,
    customers: Array,
})

const form = useForm({
    title: '',
    category_id: '',
    site_id: '',
    box_id: '',
    priority: 'medium',
    description: '',
    reported_by_type: 'staff',
    customer_id: '',
    estimated_cost: '',
    due_date: '',
})

const priorities = [
    { value: 'low', label: 'Basse', class: 'bg-gray-100 text-gray-700' },
    { value: 'medium', label: 'Moyenne', class: 'bg-yellow-100 text-yellow-700' },
    { value: 'high', label: 'Haute', class: 'bg-orange-100 text-orange-700' },
    { value: 'urgent', label: 'Urgente', class: 'bg-red-100 text-red-700' },
]

const filteredBoxes = computed(() => {
    if (!form.site_id || !props.boxes) return []
    return props.boxes.filter(box => box.site_id === parseInt(form.site_id))
})

const loadBoxes = () => {
    form.box_id = ''
}

const submit = () => {
    form.post(route('tenant.maintenance.store'))
}
</script>
