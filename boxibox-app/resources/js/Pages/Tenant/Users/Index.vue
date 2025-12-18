<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilSquareIcon,
    TrashIcon,
    UserIcon,
    ShieldCheckIcon,
    EyeIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Object,
    roles: Array,
    stats: Object,
    filters: Object,
    canCreateUser: Boolean,
    limitMessage: String,
    maxUsers: Number,
})

const search = ref(props.filters.search || '')
const roleFilter = ref(props.filters.role || '')
const statusFilter = ref(props.filters.status || '')

const applyFilters = () => {
    router.get(route('tenant.users.index'), {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
        status: statusFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

const resetFilters = () => {
    search.value = ''
    roleFilter.value = ''
    statusFilter.value = ''
    router.get(route('tenant.users.index'))
}

const toggleStatus = (user) => {
    if (confirm(`Changer le statut de ${user.name} ?`)) {
        router.post(route('tenant.users.toggle-status', user.id))
    }
}

const deleteUser = (user) => {
    if (confirm(`Supprimer l'utilisateur ${user.name} ? Cette action est irreversible.`)) {
        router.delete(route('tenant.users.destroy', user.id))
    }
}

const getRoleLabel = (roleName) => {
    const labels = {
        'tenant_admin': 'Administrateur',
        'tenant_manager': 'Manager',
        'tenant_staff': 'Personnel',
        'tenant_accountant': 'Comptable',
        'tenant_viewer': 'Lecteur',
    }
    return labels[roleName] || roleName
}

const getRoleBadgeClass = (roleName) => {
    const classes = {
        'tenant_admin': 'bg-purple-100 text-purple-800',
        'tenant_manager': 'bg-blue-100 text-blue-800',
        'tenant_staff': 'bg-green-100 text-green-800',
        'tenant_accountant': 'bg-amber-100 text-amber-800',
        'tenant_viewer': 'bg-gray-100 text-gray-800',
    }
    return classes[roleName] || 'bg-gray-100 text-gray-800'
}

const getStatusBadgeClass = (status) => {
    return status === 'active'
        ? 'bg-green-100 text-green-800'
        : 'bg-red-100 text-red-800'
}
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Utilisateurs</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Gerez les utilisateurs de votre organisation
                            <span v-if="maxUsers" class="text-gray-400">
                                ({{ stats.total }}/{{ maxUsers }} utilises)
                            </span>
                        </p>
                    </div>
                    <div>
                        <Link
                            v-if="canCreateUser"
                            :href="route('tenant.users.create')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Nouvel utilisateur
                        </Link>
                        <div v-else class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-lg border border-amber-200">
                            <ExclamationTriangleIcon class="h-5 w-5" />
                            {{ limitMessage }}
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex items-center">
                            <UserIcon class="h-8 w-8 text-blue-500" />
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Total utilisateurs</div>
                                <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex items-center">
                            <ShieldCheckIcon class="h-8 w-8 text-green-500" />
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Actifs</div>
                                <div class="text-2xl font-bold text-gray-900">{{ stats.active }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm border p-4">
                        <div class="flex items-center">
                            <ShieldCheckIcon class="h-8 w-8 text-purple-500" />
                            <div class="ml-4">
                                <div class="text-sm text-gray-500">Administrateurs</div>
                                <div class="text-2xl font-bold text-gray-900">{{ stats.admins }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-[200px]">
                            <div class="relative">
                                <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Rechercher..."
                                    @keyup.enter="applyFilters"
                                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                />
                            </div>
                        </div>
                        <select
                            v-model="roleFilter"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Tous les roles</option>
                            <option v-for="role in roles" :key="role.id" :value="role.name">
                                {{ getRoleLabel(role.name) }}
                            </option>
                        </select>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 text-gray-600 hover:text-gray-900 transition-colors"
                        >
                            Reinitialiser
                        </button>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Role
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Derniere connexion
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-primary-100 rounded-full flex items-center justify-center">
                                            <span class="text-primary-700 font-medium">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="user.roles?.length"
                                        :class="[getRoleBadgeClass(user.roles[0].name), 'px-2 py-1 text-xs rounded-full']"
                                    >
                                        {{ getRoleLabel(user.roles[0].name) }}
                                    </span>
                                    <span v-else class="text-gray-400 text-sm">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="toggleStatus(user)"
                                        :class="[getStatusBadgeClass(user.status), 'px-2 py-1 text-xs rounded-full cursor-pointer hover:opacity-80']"
                                    >
                                        {{ user.status === 'active' ? 'Actif' : 'Inactif' }}
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.last_login_at ? new Date(user.last_login_at).toLocaleDateString('fr-FR') : 'Jamais' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            :href="route('tenant.users.edit', user.id)"
                                            class="p-1 text-gray-400 hover:text-primary-600 transition-colors"
                                            title="Modifier"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </Link>
                                        <button
                                            @click="deleteUser(user)"
                                            class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                            title="Supprimer"
                                        >
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Aucun utilisateur trouve.
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="users.links?.length > 3" class="px-6 py-4 border-t bg-gray-50">
                        <Pagination :links="users.links" />
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
