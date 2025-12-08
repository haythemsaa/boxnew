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
    UserIcon,
    BuildingOffice2Icon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tenants: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const planFilter = ref(props.filters?.plan || '')

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-500/10 text-green-400 border-green-500/20',
        trial: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        suspended: 'bg-red-500/10 text-red-400 border-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getPlanColor = (plan) => {
    const colors = {
        free: 'bg-gray-500/10 text-gray-300',
        starter: 'bg-blue-500/10 text-blue-400',
        professional: 'bg-purple-500/10 text-purple-400',
        enterprise: 'bg-amber-500/10 text-amber-400',
    }
    return colors[plan] || 'bg-gray-500/10 text-gray-300'
}

const applyFilters = () => {
    router.get(route('superadmin.tenants.index'), {
        search: search.value,
        status: statusFilter.value,
        plan: planFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const suspendTenant = (tenant) => {
    if (confirm(`Êtes-vous sûr de vouloir suspendre "${tenant.name}" ?`)) {
        router.post(route('superadmin.tenants.suspend', tenant.id))
    }
}

const activateTenant = (tenant) => {
    router.post(route('superadmin.tenants.activate', tenant.id))
}

const impersonateTenant = (tenant) => {
    if (confirm(`Vous allez vous connecter en tant qu'admin de "${tenant.name}". Continuer ?`)) {
        router.post(route('superadmin.tenants.impersonate', tenant.id))
    }
}

const deleteTenant = (tenant) => {
    if (confirm(`ATTENTION: Êtes-vous sûr de vouloir désactiver "${tenant.name}" ? Cette action est irréversible.`)) {
        router.delete(route('superadmin.tenants.destroy', tenant.id))
    }
}
</script>

<template>
    <Head title="Gestion des Tenants" />

    <SuperAdminLayout title="Tenants">
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Gestion des Tenants</h1>
                    <p class="mt-1 text-sm text-gray-400">{{ stats.total }} tenants au total</p>
                </div>
                <Link
                    :href="route('superadmin.tenants.create')"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Nouveau Tenant
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Actifs</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">En essai</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.trial }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Suspendus</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.suspended }}</div>
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
                                placeholder="Rechercher par nom, slug ou email..."
                                class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="trial">En essai</option>
                            <option value="suspended">Suspendu</option>
                            <option value="cancelled">Annulé</option>
                        </select>
                        <select
                            v-model="planFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les plans</option>
                            <option value="free">Free</option>
                            <option value="starter">Starter</option>
                            <option value="professional">Professional</option>
                            <option value="enterprise">Enterprise</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tenants Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Users</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Sites</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Contrats</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="tenant in tenants.data" :key="tenant.id" class="hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-purple-600/20 rounded-lg flex items-center justify-center">
                                        <BuildingOffice2Icon class="h-5 w-5 text-purple-400" />
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-white">{{ tenant.name }}</div>
                                        <div class="text-sm text-gray-400">{{ tenant.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getPlanColor(tenant.subscription_plan), 'px-2 py-1 text-xs rounded-full uppercase font-medium']">
                                    {{ tenant.subscription_plan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getStatusColor(tenant.status), 'px-2 py-1 text-xs rounded-full border']">
                                    {{ tenant.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-300">
                                {{ tenant.users_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-300">
                                {{ tenant.sites_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-300">
                                {{ tenant.contracts_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('superadmin.tenants.show', tenant.id)"
                                        class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
                                        title="Voir"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link
                                        :href="route('superadmin.tenants.edit', tenant.id)"
                                        class="p-1.5 text-gray-400 hover:text-white hover:bg-gray-700 rounded transition-colors"
                                        title="Modifier"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        @click="impersonateTenant(tenant)"
                                        class="p-1.5 text-gray-400 hover:text-purple-400 hover:bg-gray-700 rounded transition-colors"
                                        title="Se connecter comme"
                                    >
                                        <UserIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="tenant.status === 'active'"
                                        @click="suspendTenant(tenant)"
                                        class="p-1.5 text-gray-400 hover:text-amber-400 hover:bg-gray-700 rounded transition-colors"
                                        title="Suspendre"
                                    >
                                        <PauseIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-else-if="tenant.status === 'suspended'"
                                        @click="activateTenant(tenant)"
                                        class="p-1.5 text-gray-400 hover:text-green-400 hover:bg-gray-700 rounded transition-colors"
                                        title="Activer"
                                    >
                                        <PlayIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        @click="deleteTenant(tenant)"
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
                <div v-if="tenants.links && tenants.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Affichage {{ tenants.from }} à {{ tenants.to }} sur {{ tenants.total }} résultats
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in tenants.links"
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
