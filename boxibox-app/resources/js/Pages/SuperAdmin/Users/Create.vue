<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    tenants: Array,
})

const form = useForm({
    name: '',
    email: '',
    password: '',
    tenant_id: '',
    role: 'tenant_staff',
    status: 'active',
})

const submit = () => {
    form.post(route('superadmin.users.store'))
}
</script>

<template>
    <Head title="Créer un Utilisateur" />

    <SuperAdminLayout title="Créer un Utilisateur">
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('superadmin.users.index')"
                    class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <ArrowLeftIcon class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-white">Créer un nouvel Utilisateur</h1>
                    <p class="mt-1 text-sm text-gray-400">Remplissez les informations pour créer un nouvel utilisateur</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Nom *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Mot de passe *</label>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                minlength="8"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            />
                            <p class="mt-1 text-xs text-gray-400">Minimum 8 caractères</p>
                            <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">{{ form.errors.password }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Tenant</label>
                            <select
                                v-model="form.tenant_id"
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="">Aucun (Super Admin)</option>
                                <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                    {{ tenant.name }}
                                </option>
                            </select>
                            <p class="mt-1 text-xs text-gray-400">Laisser vide pour un Super Admin</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Rôle *</label>
                            <select
                                v-model="form.role"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="super_admin">Super Admin</option>
                                <option value="tenant_admin">Tenant Admin</option>
                                <option value="tenant_staff">Staff</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Statut *</label>
                            <select
                                v-model="form.status"
                                required
                                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                            >
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                                <option value="suspended">Suspendu</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Link
                        :href="route('superadmin.users.index')"
                        class="px-4 py-2 text-gray-300 hover:text-white transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                    >
                        {{ form.processing ? 'Création...' : 'Créer l\'Utilisateur' }}
                    </button>
                </div>
            </form>
        </div>
    </SuperAdminLayout>
</template>
