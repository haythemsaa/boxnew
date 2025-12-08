<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    PlayIcon,
    PauseIcon,
    UserCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    users: Object,
    stats: Object,
    tenants: Array,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const tenantFilter = ref(props.filters?.tenant_id || '')
const roleFilter = ref(props.filters?.role || '')
const statusFilter = ref(props.filters?.status || '')

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-500/10 text-green-400 border-green-500/20',
        inactive: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        suspended: 'bg-red-500/10 text-red-400 border-red-500/20',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getRoleColor = (role) => {
    const colors = {
        super_admin: 'bg-purple-500/10 text-purple-400',
        tenant_admin: 'bg-blue-500/10 text-blue-400',
        tenant_staff: 'bg-cyan-500/10 text-cyan-400',
        client: 'bg-gray-500/10 text-gray-400',
    }
    return colors[role] || 'bg-gray-500/10 text-gray-400'
}

const applyFilters = () => {
    router.get(route('superadmin.users.index'), {
        search: search.value,
        tenant_id: tenantFilter.value,
        role: roleFilter.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const toggleStatus = (user) => {
    const action = user.status === 'active' ? 'suspendre' : 'activer'
    if (confirm(`Êtes-vous sûr de vouloir ${action} "${user.name}" ?`)) {
        router.post(route('superadmin.users.toggle-status', user.id))
    }
}

const deleteUser = (user) => {
    if (confirm(`ATTENTION: Êtes-vous sûr de vouloir supprimer "${user.name}" ?`)) {
        router.delete(route('superadmin.users.destroy', user.id))
    }
}
</script>

<template>
    <Head title="Gestion des Utilisateurs" />

    <SuperAdminLayout title="Utilisateurs">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Gestion des Utilisateurs</h1>
                    <p class="mt-1 text-sm text-gray-400">{{ stats.total }} utilisateurs au total</p>
                </div>
                <Link
                    :href="route('superadmin.users.create')"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Nouvel Utilisateur
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Actifs</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Inactifs</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-400">{{ stats.inactive }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Super Admins</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-400">{{ stats.super_admins }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Tenant Admins</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.tenant_admins }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Rechercher par nom ou email..."
                                class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                    <div class="flex gap-4 flex-wrap">
                        <select
                            v-model="tenantFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les tenants</option>
                            <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">
                                {{ tenant.name }}
                            </option>
                        </select>
                        <select
                            v-model="roleFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les rôles</option>
                            <option value="super_admin">Super Admin</option>
                            <option value="tenant_admin">Tenant Admin</option>
                            <option value="tenant_staff">Staff</option>
                            <option value="client">Client</option>
                        </select>
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                            <option value="suspended">Suspendu</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Rôle</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-gray-600 rounded-full flex items-center justify-center">
                                        <UserCircleIcon class="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white">{{ user.name }}</div>
                                        <div class="text-sm text-gray-400">{{ user.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-300">{{ user.tenant?.name || 'Aucun' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    :class="[getRoleColor(role.name), 'px-2 py-1 text-xs rounded-full']"
                                >
                                    {{ role.name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getStatusColor(user.status), 'px-2 py-1 text-xs rounded-full border']">
                                    {{ user.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('superadmin.users.show', user.id)"
                                        class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
                                        title="Voir"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        :href="route('superadmin.users.edit', user.id)"
                                        class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
                                        title="Modifier"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        @click="toggleStatus(user)"
                                        :class="user.status === 'active' ? 'hover:text-amber-400' : 'hover:text-green-400'"
                                        class="p-1.5 text-gray-400 hover:bg-gray-700 rounded transition-colors"
                                        :title="user.status === 'active' ? 'Suspendre' : 'Activer'"
                                    >
                                        <PauseIcon v-if="user.status === 'active'" class="h-4 w-4" />
                                        <PlayIcon v-else class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
                                        class="p-1.5 text-gray-400 hover:text-red-400 hover:bg-gray-700 rounded transition-colors"
                                        title="Supprimer"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Affichage {{ users.from }} à {{ users.to }} sur {{ users.total }} résultats
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url"
                                :class="[
                                    link.active ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                                    'px-3 py-1 text-sm rounded',
                                    !link.url && 'opacity-50 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
