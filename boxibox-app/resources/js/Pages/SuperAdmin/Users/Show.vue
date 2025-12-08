<script setup>
import { Head, Link } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import { ArrowLeftIcon, PencilIcon, UserCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    user: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

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
</script>

<template>
    <Head :title="`Utilisateur: ${user.name}`" />

    <SuperAdminLayout :title="user.name">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.users.index')"
                        class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 bg-gray-600 rounded-full flex items-center justify-center">
                            <UserCircleIcon class="h-10 w-10 text-gray-400" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">{{ user.name }}</h1>
                            <p class="mt-1 text-sm text-gray-400">{{ user.email }}</p>
                        </div>
                    </div>
                </div>
                <Link
                    :href="route('superadmin.users.edit', user.id)"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <PencilIcon class="h-4 w-4 mr-2" />
                    Modifier
                </Link>
            </div>

            <!-- User Info -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Informations</h3>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-gray-400">Nom</dt>
                        <dd class="mt-1 text-sm text-white">{{ user.name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Email</dt>
                        <dd class="mt-1 text-sm text-white">{{ user.email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Tenant</dt>
                        <dd class="mt-1 text-sm text-white">{{ user.tenant?.name || 'Aucun' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Statut</dt>
                        <dd class="mt-1">
                            <span :class="[getStatusColor(user.status), 'px-2 py-1 text-xs rounded-full border']">
                                {{ user.status }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Rôles</dt>
                        <dd class="mt-1 flex gap-2">
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                :class="[getRoleColor(role.name), 'px-2 py-1 text-xs rounded-full']"
                            >
                                {{ role.name }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Créé le</dt>
                        <dd class="mt-1 text-sm text-white">{{ formatDate(user.created_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-400">Dernière modification</dt>
                        <dd class="mt-1 text-sm text-white">{{ formatDate(user.updated_at) }}</dd>
                    </div>
                    <div v-if="user.email_verified_at">
                        <dt class="text-sm text-gray-400">Email vérifié le</dt>
                        <dd class="mt-1 text-sm text-white">{{ formatDate(user.email_verified_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Permissions -->
            <div v-if="user.permissions?.length" class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Permissions</h3>
                <div class="flex flex-wrap gap-2">
                    <span
                        v-for="permission in user.permissions"
                        :key="permission.id"
                        class="px-2 py-1 text-xs bg-gray-700 text-gray-300 rounded"
                    >
                        {{ permission.name }}
                    </span>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
