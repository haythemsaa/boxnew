<template>
    <TenantLayout title="Nouvel employé" :breadcrumbs="[{ label: 'Personnel', href: route('tenant.staff.index') }, { label: 'Nouvel employé' }]">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- User Selection or Creation Toggle -->
                    <div v-if="availableUsers.length > 0" class="flex gap-4 mb-4">
                        <button
                            type="button"
                            @click="createNewUser = false"
                            :class="!createNewUser ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Utilisateur existant
                        </button>
                        <button
                            type="button"
                            @click="createNewUser = true"
                            :class="createNewUser ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                        >
                            Créer un utilisateur
                        </button>
                    </div>

                    <!-- Alert if no users available -->
                    <div v-if="availableUsers.length === 0 && !createNewUser" class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 mt-0.5" />
                            <div>
                                <p class="text-amber-800 font-medium">Aucun utilisateur disponible</p>
                                <p class="text-amber-600 text-sm mt-1">
                                    Tous les utilisateurs ont déjà un profil employé ou aucun utilisateur n'existe.
                                    Créez un nouvel utilisateur ci-dessous.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Select Existing User -->
                    <div v-if="!createNewUser && availableUsers.length > 0">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Utilisateur *</label>
                        <select v-model="form.user_id" class="w-full rounded-xl border-gray-200" required>
                            <option value="">Sélectionner un utilisateur existant</option>
                            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                {{ user.name }} ({{ user.email }})
                            </option>
                        </select>
                        <p v-if="form.errors.user_id" class="text-red-500 text-sm mt-1">{{ form.errors.user_id }}</p>
                    </div>

                    <!-- Create New User Section -->
                    <div v-if="createNewUser || availableUsers.length === 0" class="space-y-4 p-4 bg-blue-50 rounded-xl border border-blue-200">
                        <h3 class="font-medium text-blue-900 flex items-center gap-2">
                            <UserPlusIcon class="w-5 h-5" />
                            Nouvel utilisateur
                        </h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet *</label>
                                <input
                                    v-model="form.new_user_name"
                                    type="text"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="Jean Dupont"
                                />
                                <p v-if="form.errors.new_user_name" class="text-red-500 text-sm mt-1">{{ form.errors.new_user_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                <input
                                    v-model="form.new_user_email"
                                    type="email"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="jean@exemple.com"
                                />
                                <p v-if="form.errors.new_user_email" class="text-red-500 text-sm mt-1">{{ form.errors.new_user_email }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe *</label>
                                <input
                                    v-model="form.new_user_password"
                                    type="password"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="••••••••"
                                />
                                <p v-if="form.errors.new_user_password" class="text-red-500 text-sm mt-1">{{ form.errors.new_user_password }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Confirmer *</label>
                                <input
                                    v-model="form.new_user_password_confirmation"
                                    type="password"
                                    class="w-full rounded-xl border-gray-200"
                                    placeholder="••••••••"
                                />
                            </div>
                        </div>
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

                    <!-- Site Assignment -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Site assigné</label>
                        <select v-model="form.site_id" class="w-full rounded-xl border-gray-200">
                            <option value="">Aucun site spécifique</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.site_id" class="text-red-500 text-sm mt-1">{{ form.errors.site_id }}</p>
                    </div>

                    <!-- Employment Info -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche</label>
                        <input
                            v-model="form.hire_date"
                            type="date"
                            class="w-full rounded-xl border-gray-200"
                        />
                        <p v-if="form.errors.hire_date" class="text-red-500 text-sm mt-1">{{ form.errors.hire_date }}</p>
                    </div>

                    <!-- Salary Info -->
                    <div class="grid grid-cols-2 gap-4">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Salaire mensuel (€)</label>
                            <input
                                v-model="form.monthly_salary"
                                type="number"
                                step="0.01"
                                min="0"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="2500.00"
                            />
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact urgence - Nom</label>
                            <input
                                v-model="form.emergency_contact_name"
                                type="text"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="Marie Dupont"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact urgence - Téléphone</label>
                            <input
                                v-model="form.emergency_contact_phone"
                                type="tel"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="+33 6 00 00 00 00"
                            />
                        </div>
                    </div>

                    <!-- Skills -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Compétences</label>
                        <div class="flex flex-wrap gap-2">
                            <label v-for="skill in availableSkills" :key="skill" class="inline-flex items-center gap-1 px-3 py-1 rounded-full cursor-pointer transition-colors" :class="form.skills.includes(skill) ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                                <input
                                    type="checkbox"
                                    :value="skill"
                                    v-model="form.skills"
                                    class="hidden"
                                />
                                <span class="text-sm">{{ skill }}</span>
                                <CheckIcon v-if="form.skills.includes(skill)" class="w-4 h-4" />
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <Link :href="route('tenant.staff.index')" class="btn-secondary">
                            Annuler
                        </Link>
                        <button type="submit" :disabled="form.processing" class="btn-primary">
                            <span v-if="form.processing">Création...</span>
                            <span v-else>Créer le profil employé</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { CheckIcon, ExclamationTriangleIcon, UserPlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    availableUsers: Array,
    sites: Array,
})

const createNewUser = ref(props.availableUsers.length === 0)

const form = useForm({
    user_id: '',
    // New user fields
    new_user_name: '',
    new_user_email: '',
    new_user_password: '',
    new_user_password_confirmation: '',
    create_new_user: false,
    // Staff profile fields
    site_id: '',
    position: '',
    department: '',
    employee_number: '',
    hire_date: new Date().toISOString().split('T')[0],
    hourly_rate: '',
    monthly_salary: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    skills: [],
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
    form.create_new_user = createNewUser.value || props.availableUsers.length === 0
    form.post(route('tenant.staff.store'))
}
</script>
