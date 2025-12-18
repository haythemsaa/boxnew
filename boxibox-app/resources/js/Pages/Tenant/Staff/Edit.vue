<template>
    <TenantLayout :title="`Modifier ${staff.user?.name}`" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: staff.user?.name, href: route('tenant.staff.show', staff.id) }, { label: 'Modifier' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- User Info (readonly) -->
                    <div class="p-4 bg-gray-50 rounded-xl">
                        <h3 class="font-medium text-gray-900 mb-2">Utilisateur</h3>
                        <p class="text-gray-700">{{ staff.user?.name }} ({{ staff.user?.email }})</p>
                    </div>

                    <!-- Position/Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Poste *</label>
                        <select v-model="form.position" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner un poste</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technicien</option>
                            <option value="receptionist">Réceptionniste</option>
                            <option value="security">Agent de sécurité</option>
                            <option value="cleaner">Agent d'entretien</option>
                        </select>
                        <p v-if="form.errors.position" class="text-red-500 text-sm mt-1">{{ form.errors.position }}</p>
                    </div>

                    <!-- Department -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Département</label>
                        <input v-model="form.department" type="text" class="w-full rounded-xl border-gray-200" placeholder="Ex: Maintenance" />
                    </div>

                    <!-- Site Assignment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site assigné</label>
                        <select v-model="form.site_id" class="w-full rounded-xl border-gray-200">
                            <option value="">Aucun site spécifique</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Salary Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Taux horaire (€)</label>
                            <input v-model="form.hourly_rate" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200" placeholder="15.00" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salaire mensuel (€)</label>
                            <input v-model="form.monthly_salary" type="number" step="0.01" min="0" class="w-full rounded-xl border-gray-200" placeholder="2500.00" />
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact urgence - Nom</label>
                            <input v-model="form.emergency_contact_name" type="text" class="w-full rounded-xl border-gray-200" placeholder="Marie Dupont" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact urgence - Téléphone</label>
                            <input v-model="form.emergency_contact_phone" type="tel" class="w-full rounded-xl border-gray-200" placeholder="+33 6 00 00 00 00" />
                        </div>
                    </div>

                    <!-- Skills -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Compétences</label>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="skill in availableSkills" :key="skill"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full cursor-pointer transition-colors"
                                :class="form.skills.includes(skill) ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                <input type="checkbox" :value="skill" v-model="form.skills" class="hidden" />
                                <span class="text-sm">{{ skill }}</span>
                                <CheckIcon v-if="form.skills.includes(skill)" class="w-4 h-4" />
                            </label>
                        </div>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm font-medium text-gray-700">Employé actif</span>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.staff.show', staff.id)" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Enregistrement...</span>
                            <span v-else>Enregistrer</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { CheckIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    staff: Object,
    sites: Array,
})

const form = useForm({
    position: props.staff.position || '',
    department: props.staff.department || '',
    site_id: props.staff.site_id || '',
    hourly_rate: props.staff.hourly_rate || '',
    monthly_salary: props.staff.monthly_salary || '',
    emergency_contact_name: props.staff.emergency_contact_name || '',
    emergency_contact_phone: props.staff.emergency_contact_phone || '',
    skills: props.staff.skills || [],
    is_active: props.staff.is_active ?? true,
})

const availableSkills = [
    'Maintenance',
    'Électricité',
    'Plomberie',
    'Serrurerie',
    'Nettoyage',
    'Accueil client',
    'Sécurité',
    'Informatique',
    'Gestion',
]

const submit = () => {
    form.put(route('tenant.staff.update', props.staff.id))
}
</script>
