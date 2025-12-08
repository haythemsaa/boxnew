<template>
    <TenantLayout title="Nouvel employé" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Nouvel employé' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- User Selection or Creation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Utilisateur *</label>
                        <select v-model="form.user_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner un utilisateur existant</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <p v-if="form.errors.user_id" class="text-red-500 text-sm mt-1">{{ form.errors.user_id }}</p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rôle *</label>
                        <select v-model="form.role" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner un rôle</option>
                            <option value="manager">Manager</option>
                            <option value="technician">Technicien</option>
                            <option value="receptionist">Réceptionniste</option>
                            <option value="security">Agent de sécurité</option>
                            <option value="cleaner">Agent d'entretien</option>
                        </select>
                        <p v-if="form.errors.role" class="text-red-500 text-sm mt-1">{{ form.errors.role }}</p>
                    </div>

                    <!-- Sites -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sites assignés *</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label v-for="site in sites" :key="site.id" class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                                <input
                                    type="checkbox"
                                    :value="site.id"
                                    v-model="form.site_ids"
                                    class="rounded text-primary-600"
                                />
                                <span class="text-sm text-gray-700">{{ site.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.errors.site_ids" class="text-red-500 text-sm mt-1">{{ form.errors.site_ids }}</p>
                    </div>

                    <!-- Contact Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="+33 6 00 00 00 00"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone urgence</label>
                            <input
                                v-model="form.emergency_phone"
                                type="tel"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="+33 6 00 00 00 00"
                            />
                        </div>
                    </div>

                    <!-- Work Schedule -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Heures par semaine</label>
                            <input
                                v-model="form.hours_per_week"
                                type="number"
                                min="0"
                                max="60"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="35"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Taux horaire (€)</label>
                            <input
                                v-model="form.hourly_rate"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="15.00"
                            />
                        </div>
                    </div>

                    <!-- Employment Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche</label>
                            <input
                                v-model="form.hire_date"
                                type="date"
                                class="w-full rounded-xl border-gray-200"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de contrat</label>
                            <select v-model="form.contract_type" class="w-full rounded-xl border-gray-200">
                                <option value="cdi">CDI</option>
                                <option value="cdd">CDD</option>
                                <option value="interim">Intérim</option>
                                <option value="freelance">Freelance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Skills -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Compétences</label>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="skill in availableSkills" :key="skill" class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full cursor-pointer hover:bg-gray-200">
                                <input
                                    type="checkbox"
                                    :value="skill"
                                    v-model="form.skills"
                                    class="hidden"
                                />
                                <span :class="form.skills.includes(skill) ? 'text-primary-600 font-medium' : 'text-gray-600'" class="text-sm">
                                    {{ skill }}
                                </span>
                                <CheckIcon v-if="form.skills.includes(skill)" class="w-4 h-4 text-primary-600" />
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Notes internes sur l'employé..."
                        ></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.staff.index')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer le profil</span>
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
    availableUsers: Array,
    sites: Array,
})

const form = useForm({
    user_id: '',
    role: '',
    site_ids: [],
    phone: '',
    emergency_phone: '',
    hours_per_week: 35,
    hourly_rate: '',
    hire_date: '',
    contract_type: 'cdi',
    skills: [],
    notes: '',
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
    form.post(route('tenant.staff.store'))
}
</script>
