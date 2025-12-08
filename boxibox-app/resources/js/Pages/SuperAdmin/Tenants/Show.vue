<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    PencilIcon,
    UserIcon,
    BuildingOffice2Icon,
    CubeIcon,
    UserGroupIcon,
    DocumentTextIcon,
    CurrencyEuroIcon,
    PuzzlePieceIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    tenant: Object,
    stats: Object,
    recentContracts: Array,
    recentInvoices: Array,
})

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(value || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
}

const getStatusColor = (status) => {
    const colors = {
        active: 'bg-green-500/10 text-green-400 border-green-500/20',
        trial: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        suspended: 'bg-red-500/10 text-red-400 border-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        paid: 'bg-green-500/10 text-green-400',
        pending: 'bg-amber-500/10 text-amber-400',
        overdue: 'bg-red-500/10 text-red-400',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400'
}

const impersonateTenant = () => {
    if (confirm(`Vous allez vous connecter en tant qu'admin de "${props.tenant.name}". Continuer ?`)) {
        router.post(route('superadmin.tenants.impersonate', props.tenant.id))
    }
}
</script>

<template>
    <Head :title="`Tenant: ${tenant.name}`" />

    <SuperAdminLayout :title="tenant.name">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.tenants.index')"
                        class="p-2 text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ tenant.name }}</h1>
                        <p class="mt-1 text-sm text-gray-400">{{ tenant.email }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="impersonateTenant"
                        class="inline-flex items-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <UserIcon class="h-4 w-4 mr-2" />
                        Se connecter comme
                    </button>
                    <Link
                        :href="route('superadmin.modules.tenant', tenant.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <PuzzlePieceIcon class="h-4 w-4 mr-2" />
                        Modules
                    </Link>
                    <Link
                        :href="route('superadmin.tenants.edit', tenant.id)"
                        class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <PencilIcon class="h-4 w-4 mr-2" />
                        Modifier
                    </Link>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Tenant Info -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Informations</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Slug</dt>
                            <dd class="text-sm text-white">{{ tenant.slug }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Téléphone</dt>
                            <dd class="text-sm text-white">{{ tenant.phone || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Adresse</dt>
                            <dd class="text-sm text-white text-right">{{ tenant.address || '-' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Ville</dt>
                            <dd class="text-sm text-white">{{ tenant.city || '-' }} {{ tenant.postal_code }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Pays</dt>
                            <dd class="text-sm text-white">{{ tenant.country || 'FR' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-400">Créé le</dt>
                            <dd class="text-sm text-white">{{ formatDate(tenant.created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Subscription -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Abonnement</h3>
                    <dl class="space-y-3">
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-400">Plan</dt>
                            <dd>
                                <span class="px-2 py-1 text-xs rounded-full uppercase font-medium bg-purple-500/10 text-purple-400">
                                    {{ tenant.subscription_plan }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between items-center">
                            <dt class="text-sm text-gray-400">Statut</dt>
                            <dd>
                                <span :class="[getStatusColor(tenant.status), 'px-2 py-1 text-xs rounded-full border']">
                                    {{ tenant.status }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between" v-if="tenant.trial_ends_at">
                            <dt class="text-sm text-gray-400">Fin d'essai</dt>
                            <dd class="text-sm text-white">{{ formatDate(tenant.trial_ends_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Statistiques</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-3 bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-white">{{ stats.users }}</div>
                            <div class="text-xs text-gray-400">Utilisateurs</div>
                        </div>
                        <div class="text-center p-3 bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-white">{{ stats.sites }}</div>
                            <div class="text-xs text-gray-400">Sites</div>
                        </div>
                        <div class="text-center p-3 bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-white">{{ stats.boxes }}</div>
                            <div class="text-xs text-gray-400">Boxes</div>
                        </div>
                        <div class="text-center p-3 bg-gray-700/50 rounded-lg">
                            <div class="text-2xl font-bold text-white">{{ stats.customers }}</div>
                            <div class="text-xs text-gray-400">Clients</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Stats -->
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <DocumentTextIcon class="h-8 w-8 text-indigo-400" />
                        <div class="ml-4">
                            <div class="text-sm text-gray-400">Contrats actifs</div>
                            <div class="text-2xl font-bold text-white">{{ stats.active_contracts }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <CurrencyEuroIcon class="h-8 w-8 text-green-400" />
                        <div class="ml-4">
                            <div class="text-sm text-gray-400">Revenus totaux</div>
                            <div class="text-2xl font-bold text-white">{{ formatCurrency(stats.total_revenue) }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center">
                        <CurrencyEuroIcon class="h-8 w-8 text-amber-400" />
                        <div class="ml-4">
                            <div class="text-sm text-gray-400">Factures en attente</div>
                            <div class="text-2xl font-bold text-white">{{ formatCurrency(stats.pending_invoices) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Recent Contracts -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Derniers Contrats</h3>
                    <div class="space-y-3">
                        <div
                            v-for="contract in recentContracts"
                            :key="contract.id"
                            class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg"
                        >
                            <div>
                                <div class="text-sm font-medium text-white">{{ contract.contract_number }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ contract.customer?.first_name }} {{ contract.customer?.last_name }}
                                    - Box {{ contract.box?.number }}
                                </div>
                            </div>
                            <span :class="[getStatusColor(contract.status), 'px-2 py-0.5 text-xs rounded-full']">
                                {{ contract.status }}
                            </span>
                        </div>
                        <div v-if="!recentContracts?.length" class="text-center text-gray-400 py-4">
                            Aucun contrat récent
                        </div>
                    </div>
                </div>

                <!-- Recent Invoices -->
                <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <h3 class="text-lg font-medium text-white mb-4">Dernières Factures</h3>
                    <div class="space-y-3">
                        <div
                            v-for="invoice in recentInvoices"
                            :key="invoice.id"
                            class="flex items-center justify-between p-3 bg-gray-700/50 rounded-lg"
                        >
                            <div>
                                <div class="text-sm font-medium text-white">{{ invoice.invoice_number }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ invoice.customer?.first_name }} {{ invoice.customer?.last_name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-white">{{ formatCurrency(invoice.total_amount) }}</div>
                                <span :class="[getStatusColor(invoice.status), 'px-2 py-0.5 text-xs rounded-full']">
                                    {{ invoice.status }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!recentInvoices?.length" class="text-center text-gray-400 py-4">
                            Aucune facture récente
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Utilisateurs du Tenant</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Nom</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Rôle</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-400 uppercase">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="user in tenant.users" :key="user.id">
                                <td class="px-4 py-3 text-sm text-white">{{ user.name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-300">{{ user.email }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-0.5 bg-purple-500/10 text-purple-400 text-xs rounded-full">
                                        {{ user.roles?.[0]?.name || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span :class="[getStatusColor(user.status), 'px-2 py-0.5 text-xs rounded-full']">
                                        {{ user.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
