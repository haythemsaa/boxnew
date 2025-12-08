<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    MagnifyingGlassIcon,
    PlusIcon,
    EyeIcon,
    BanknotesIcon,
    ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    invoices: Object,
    stats: Object,
    tenants: Array,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const tenantId = ref(props.filters?.tenant_id || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('superadmin.billing.index'), {
        search: search.value,
        tenant_id: tenantId.value,
        status: status.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    tenantId.value = ''
    status.value = ''
    applyFilters()
}

const generateMonthlyInvoices = () => {
    if (confirm('Générer les factures mensuelles pour tous les tenants actifs ?')) {
        router.post(route('superadmin.billing.generate-monthly'))
    }
}

const getStatusColor = (status) => {
    const colors = {
        draft: 'bg-gray-500/10 text-gray-400 border-gray-500/20',
        pending: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        paid: 'bg-green-500/10 text-green-400 border-green-500/20',
        overdue: 'bg-red-500/10 text-red-400 border-red-500/20',
        cancelled: 'bg-gray-500/10 text-gray-500 border-gray-500/20',
    }
    return colors[status] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Brouillon',
        pending: 'En attente',
        paid: 'Payée',
        overdue: 'En retard',
        cancelled: 'Annulée',
    }
    return labels[status] || status
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR')
}
</script>

<template>
    <Head title="Facturation Plateforme" />

    <SuperAdminLayout title="Facturation Plateforme">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Facturation Plateforme</h1>
                    <p class="mt-1 text-sm text-gray-400">Gérez les factures des abonnements tenants</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="generateMonthlyInvoices"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        <ArrowPathIcon class="h-5 w-5" />
                        Générer factures mensuelles
                    </button>
                    <Link
                        :href="route('superadmin.billing.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouvelle Facture
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total Factures</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">En attente</div>
                    <div class="mt-1 text-2xl font-semibold text-yellow-400">{{ stats.pending }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Payées</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.paid }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">En retard</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.overdue }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">CA ce mois</div>
                    <div class="mt-1 text-2xl font-semibold text-purple-400">{{ formatCurrency(stats.revenue_this_month) }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
                    <div class="sm:col-span-2">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Rechercher (n° facture)..."
                                class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                    <select v-model="tenantId" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Tous les tenants</option>
                        <option v-for="tenant in tenants" :key="tenant.id" :value="tenant.id">{{ tenant.name }}</option>
                    </select>
                    <select v-model="status" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Tous statuts</option>
                        <option value="draft">Brouillon</option>
                        <option value="pending">En attente</option>
                        <option value="paid">Payée</option>
                        <option value="overdue">En retard</option>
                        <option value="cancelled">Annulée</option>
                    </select>
                    <button @click="clearFilters" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-750">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">N° Facture</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Tenant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Plan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Échéance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Statut</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700">
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-gray-750 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-white">{{ invoice.invoice_number }}</div>
                                <div class="text-xs text-gray-500">{{ formatDate(invoice.created_at) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ invoice.tenant?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs bg-purple-500/10 text-purple-400 rounded-full capitalize">
                                    {{ invoice.plan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-white">
                                {{ formatCurrency(invoice.amount) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ formatDate(invoice.due_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="[getStatusColor(invoice.status), 'px-2 py-1 text-xs rounded-full border']">
                                    {{ getStatusLabel(invoice.status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <Link
                                    :href="route('superadmin.billing.show', invoice.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                    Voir
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                <BanknotesIcon class="mx-auto h-12 w-12 text-gray-500 mb-2" />
                                Aucune facture trouvée
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="invoices.links && invoices.links.length > 3" class="px-6 py-4 border-t border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-400">
                            {{ invoices.from }} à {{ invoices.to }} sur {{ invoices.total }}
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in invoices.links"
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
