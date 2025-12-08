<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    MagnifyingGlassIcon,
    ArrowPathIcon,
    CurrencyEuroIcon,
    BuildingOffice2Icon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    subscriptions: Object,
    stats: Object,
    plans: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const planFilter = ref(props.filters?.plan || '')
const statusFilter = ref(props.filters?.status || '')

const showChangePlanModal = ref(false)
const showExtendTrialModal = ref(false)
const selectedTenant = ref(null)
const selectedPlan = ref('')
const trialDays = ref(14)

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
        free: 'bg-gray-500/10 text-gray-300 border-gray-500/20',
        starter: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        professional: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        enterprise: 'bg-amber-500/10 text-amber-400 border-amber-500/20',
    }
    return colors[plan] || 'bg-gray-500/10 text-gray-300 border-gray-500/20'
}

const applyFilters = () => {
    router.get(route('superadmin.subscriptions.index'), {
        search: search.value,
        plan: planFilter.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const openChangePlanModal = (tenant) => {
    selectedTenant.value = tenant
    selectedPlan.value = tenant.subscription_plan
    showChangePlanModal.value = true
}

const openExtendTrialModal = (tenant) => {
    selectedTenant.value = tenant
    trialDays.value = 14
    showExtendTrialModal.value = true
}

const changePlan = () => {
    router.post(route('superadmin.subscriptions.change-plan', selectedTenant.value.id), {
        plan: selectedPlan.value,
    }, {
        onSuccess: () => {
            showChangePlanModal.value = false
        }
    })
}

const extendTrial = () => {
    router.post(route('superadmin.subscriptions.extend-trial', selectedTenant.value.id), {
        days: trialDays.value,
    }, {
        onSuccess: () => {
            showExtendTrialModal.value = false
        }
    })
}
</script>

<template>
    <Head title="Gestion des Abonnements" />

    <SuperAdminLayout title="Abonnements">
        <div class="space-y-6">
            <!-- Page Header -->
            <div>
                <h1 class="text-2xl font-bold text-white">Gestion des Abonnements</h1>
                <p class="mt-1 text-sm text-gray-400">Gérer les plans et abonnements des tenants</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Free</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-300">{{ stats.free }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Starter</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.starter }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Professional</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-400">{{ stats.professional }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Enterprise</div>
                    <div class="mt-1 text-2xl font-semibold text-amber-400">{{ stats.enterprise }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">En essai</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.trial }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="flex items-center gap-2">
                        <CurrencyEuroIcon class="h-5 w-5 text-green-400" />
                        <div class="text-sm text-gray-400">MRR</div>
                    </div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ formatCurrency(stats.total_mrr) }}</div>
                </div>
            </div>

            <!-- Plans Overview -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div v-for="(plan, key) in plans" :key="key" class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-white">{{ plan.name }}</h3>
                        <span :class="[getPlanColor(key), 'px-2 py-1 text-xs rounded-full border uppercase']">{{ key }}</span>
                    </div>
                    <div class="text-3xl font-bold text-white mb-4">{{ plan.price }}€<span class="text-sm text-gray-400 font-normal">/mois</span></div>
                    <ul class="space-y-2">
                        <li v-for="feature in plan.features" :key="feature" class="text-sm text-gray-400 flex items-center gap-2">
                            <span class="h-1.5 w-1.5 bg-purple-400 rounded-full"></span>
                            {{ feature }}
                        </li>
                    </ul>
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
                    <div class="flex gap-4">
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
                        <select
                            v-model="statusFilter"
                            @change="applyFilters"
                            class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="trial">En essai</option>
                            <option value="suspended">Suspendu</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Subscriptions Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Sites</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Contrats</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="tenant in subscriptions.data" :key="tenant.id" class="hover:bg-gray-750 transition-colors">
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
                                <span :class="[getPlanColor(tenant.subscription_plan), 'px-2 py-1 text-xs rounded-full border uppercase font-medium']">
                                    {{ tenant.subscription_plan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getStatusColor(tenant.status), 'px-2 py-1 text-xs rounded-full border']">
                                    {{ tenant.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-300">
                                {{ tenant.sites_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-300">
                                {{ tenant.contracts_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        @click="openChangePlanModal(tenant)"
                                        class="px-3 py-1 text-xs bg-purple-600 hover:bg-purple-700 text-white rounded transition-colors"
                                    >
                                        Changer plan
                                    </button>
                                    <button
                                        v-if="tenant.status === 'trial'"
                                        @click="openExtendTrialModal(tenant)"
                                        class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors"
                                    >
                                        Prolonger essai
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="subscriptions.links && subscriptions.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            Affichage {{ subscriptions.from }} à {{ subscriptions.to }} sur {{ subscriptions.total }} résultats
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in subscriptions.links"
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

        <!-- Change Plan Modal -->
        <div v-if="showChangePlanModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/80" @click="showChangePlanModal = false"></div>
            <div class="relative bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Changer de plan</h3>
                <p class="text-sm text-gray-400 mb-4">Tenant: {{ selectedTenant?.name }}</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nouveau plan</label>
                    <select
                        v-model="selectedPlan"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                    >
                        <option value="free">Free (0€/mois)</option>
                        <option value="starter">Starter (49€/mois)</option>
                        <option value="professional">Professional (99€/mois)</option>
                        <option value="enterprise">Enterprise (249€/mois)</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="showChangePlanModal = false" class="px-4 py-2 text-gray-300 hover:text-white">
                        Annuler
                    </button>
                    <button @click="changePlan" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>

        <!-- Extend Trial Modal -->
        <div v-if="showExtendTrialModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-gray-900/80" @click="showExtendTrialModal = false"></div>
            <div class="relative bg-gray-800 rounded-xl p-6 w-full max-w-md border border-gray-700">
                <h3 class="text-lg font-medium text-white mb-4">Prolonger la période d'essai</h3>
                <p class="text-sm text-gray-400 mb-4">Tenant: {{ selectedTenant?.name }}</p>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-300 mb-1">Nombre de jours</label>
                    <input
                        v-model="trialDays"
                        type="number"
                        min="1"
                        max="90"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500"
                    />
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="showExtendTrialModal = false" class="px-4 py-2 text-gray-300 hover:text-white">
                        Annuler
                    </button>
                    <button @click="extendTrial" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg">
                        Prolonger
                    </button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
