<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import { ArrowLeftIcon, UserIcon, ShieldCheckIcon, EyeIcon, KeyIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    user: Object,
    roles: Array,
    currentRole: String,
})

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role: props.currentRole || 'tenant_staff',
    phone: props.user.phone || '',
    status: props.user.status || 'active',
})

const submit = () => {
    form.put(route('tenant.users.update', props.user.id))
}

const getRoleIcon = (roleName) => {
    const icons = {
        'tenant_admin': ShieldCheckIcon,
        'tenant_manager': UserIcon,
        'tenant_staff': UserIcon,
        'tenant_accountant': KeyIcon,
        'tenant_viewer': EyeIcon,
    }
    return icons[roleName] || UserIcon
}
</script>

<template>
    <Head :title="`Modifier: ${user.name}`" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center gap-4 mb-6">
                    <Link
                        :href="route('tenant.users.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Modifier l'utilisateur</h1>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Informations de base -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations personnelles</h3>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom complet *
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Email *
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Telephone
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Changer le mot de passe</h3>
                        <p class="text-sm text-gray-500 mb-4">Laissez vide pour conserver le mot de passe actuel.</p>
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nouveau mot de passe
                                </label>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="********"
                                />
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                    placeholder="********"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Role et statut -->
                    <div class="bg-white rounded-lg shadow-sm border p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Role et permissions</h3>

                        <div class="space-y-4">
                            <div
                                v-for="role in roles"
                                :key="role.id"
                                @click="form.role = role.name"
                                :class="[
                                    'p-4 border rounded-lg cursor-pointer transition-colors',
                                    form.role === role.name
                                        ? 'border-primary-500 bg-primary-50'
                                        : 'border-gray-200 hover:border-gray-300'
                                ]"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="radio"
                                        :value="role.name"
                                        v-model="form.role"
                                        class="mt-1 text-primary-600 focus:ring-primary-500"
                                    />
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <component :is="getRoleIcon(role.name)" class="h-5 w-5 text-gray-500" />
                                            <span class="font-medium text-gray-900">{{ role.label }}</span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">{{ role.description }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.role" class="mt-2 text-sm text-red-600">{{ form.errors.role }}</p>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Statut
                            </label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        v-model="form.status"
                                        value="active"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="text-sm text-gray-700">Actif</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        type="radio"
                                        v-model="form.status"
                                        value="inactive"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="text-sm text-gray-700">Inactif</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-4">
                        <Link
                            :href="route('tenant.users.index')"
                            class="px-4 py-2 text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            Annuler
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white font-medium rounded-lg transition-colors"
                        >
                            {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
