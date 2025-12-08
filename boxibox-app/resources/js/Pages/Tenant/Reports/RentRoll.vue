<template>
    <TenantLayout title="Rent Roll" :breadcrumbs="[{ label: 'Rapports', href: route('tenant.reports.index') }, { label: 'Rent Roll' }]">
        <div class="space-y-6">
            <!-- Filters -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex gap-3">
                        <select v-model="filterSite" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>
                        <select v-model="filterStatus" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="pending">En attente</option>
                            <option value="ending_soon">Fin prochaine</option>
                        </select>
                        <input
                            v-model="filterDate"
                            type="date"
                            class="rounded-xl border-gray-200 text-sm"
                        />
                    </div>

                    <div class="flex gap-3">
                        <button @click="exportReport('xlsx')" class="btn-secondary">
                            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                            Excel
                        </button>
                        <button @click="exportReport('pdf')" class="btn-secondary">
                            <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                            PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Contrats actifs</p>
                            <p class="text-3xl font-bold text-gray-900">{{ summary.total_contracts }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <DocumentTextIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Surface louée</p>
                            <p class="text-3xl font-bold text-green-600">{{ summary.total_area }} m²</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-xl">
                            <CubeIcon class="w-6 h-6 text-green-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Loyer mensuel</p>
                            <p class="text-3xl font-bold text-purple-600">{{ formatCurrency(summary.monthly_rent) }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-xl">
                            <CurrencyEuroIcon class="w-6 h-6 text-purple-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Loyer annuel</p>
                            <p class="text-3xl font-bold text-orange-600">{{ formatCurrency(summary.annual_rent) }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <CalendarIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rent Roll Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">Détail des contrats</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Site</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Box</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Contrat</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Début</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Fin</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Surface</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Loyer/mois</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">€/m²</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="site in groupedContracts" :key="site.id">
                                <!-- Site Header -->
                                <tr class="bg-gray-50">
                                    <td colspan="9" class="px-4 py-2">
                                        <span class="font-semibold text-gray-900">{{ site.name }}</span>
                                        <span class="text-sm text-gray-500 ml-2">
                                            ({{ site.contracts.length }} contrats - {{ formatCurrency(site.total_rent) }}/mois)
                                        </span>
                                    </td>
                                </tr>
                                <!-- Contracts -->
                                <tr v-for="contract in site.contracts" :key="contract.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ site.name }}</td>
                                    <td class="px-4 py-3">
                                        <Link :href="route('tenant.boxes.show', contract.box_id)" class="font-medium text-primary-600 hover:text-primary-800">
                                            {{ contract.box?.code }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3">
                                        <Link :href="route('tenant.customers.show', contract.customer_id)" class="text-gray-900 hover:text-primary-600">
                                            {{ contract.customer?.full_name }}
                                        </Link>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ contract.contract_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(contract.start_date) }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="contract.end_date" :class="isEndingSoon(contract.end_date) ? 'text-orange-600' : 'text-gray-600'" class="text-sm">
                                            {{ formatDate(contract.end_date) }}
                                        </span>
                                        <span v-else class="text-sm text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900">{{ contract.box?.size_m2 }} m²</td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(contract.monthly_rent) }}</td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600">{{ calculatePricePerM2(contract) }}</td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="6" class="px-4 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ summary.total_area }} m²</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatCurrency(summary.monthly_rent) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ summary.avg_price_per_m2 }} €/m²</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Site Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Répartition par site</h3>
                    <div class="space-y-4">
                        <div v-for="site in siteBreakdown" :key="site.id">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700">{{ site.name }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(site.total_rent) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div
                                    class="bg-primary-600 h-2 rounded-full"
                                    :style="{ width: (site.total_rent / summary.monthly_rent * 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Répartition par taille</h3>
                    <div class="space-y-4">
                        <div v-for="size in sizeBreakdown" :key="size.range">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700">{{ size.range }}</span>
                                <span class="text-sm font-medium text-gray-900">{{ size.count }} contrats</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div
                                    class="bg-green-600 h-2 rounded-full"
                                    :style="{ width: (size.count / summary.total_contracts * 100) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    DocumentTextIcon,
    CubeIcon,
    CurrencyEuroIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    contracts: Array,
    sites: Array,
    summary: Object,
    siteBreakdown: Array,
    sizeBreakdown: Array,
    filters: Object,
})

const filterSite = ref(props.filters?.site_id || '')
const filterStatus = ref(props.filters?.status || '')
const filterDate = ref(props.filters?.date || new Date().toISOString().split('T')[0])

watch([filterSite, filterStatus, filterDate], () => {
    router.get(route('tenant.reports.rent-roll'), {
        site_id: filterSite.value,
        status: filterStatus.value,
        date: filterDate.value,
    }, { preserveState: true, replace: true })
})

const groupedContracts = computed(() => {
    const grouped = {}
    props.contracts?.forEach(contract => {
        const siteId = contract.site_id
        if (!grouped[siteId]) {
            grouped[siteId] = {
                id: siteId,
                name: contract.site?.name,
                contracts: [],
                total_rent: 0,
            }
        }
        grouped[siteId].contracts.push(contract)
        grouped[siteId].total_rent += parseFloat(contract.monthly_rent || 0)
    })
    return Object.values(grouped)
})

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    }).format(amount || 0)
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    })
}

const isEndingSoon = (endDate) => {
    if (!endDate) return false
    const end = new Date(endDate)
    const now = new Date()
    const diffDays = (end - now) / (1000 * 60 * 60 * 24)
    return diffDays > 0 && diffDays <= 30
}

const calculatePricePerM2 = (contract) => {
    if (!contract.monthly_rent || !contract.box?.size_m2) return '-'
    return (contract.monthly_rent / contract.box.size_m2).toFixed(2) + ' €'
}

const exportReport = (format) => {
    window.location.href = route('tenant.reports.rent-roll.export', {
        format,
        site_id: filterSite.value,
        status: filterStatus.value,
        date: filterDate.value,
    })
}
</script>
