<template>
    <TenantLayout title="Rent Roll" :breadcrumbs="[{ label: 'Rapports', href: route('tenant.reports.index') }, { label: 'Rent Roll' }]">
        <div class="space-y-6">
            <!-- Alerts Banner -->
            <div v-if="summary.contracts_with_overdue > 0 || summary.ending_soon_30 > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Overdue Alert -->
                <div v-if="summary.contracts_with_overdue > 0" class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-center gap-4">
                    <div class="p-3 bg-red-100 rounded-full">
                        <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-red-800">{{ summary.contracts_with_overdue }} contrat(s) avec impayes</h4>
                        <p class="text-sm text-red-600">Total: {{ formatCurrency(summary.total_overdue) }} en retard de paiement</p>
                    </div>
                    <button @click="filterStatus = 'overdue'" class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                        Voir
                    </button>
                </div>

                <!-- Ending Soon Alert -->
                <div v-if="summary.ending_soon_30 > 0" class="bg-orange-50 border border-orange-200 rounded-xl p-4 flex items-center gap-4">
                    <div class="p-3 bg-orange-100 rounded-full">
                        <ClockIcon class="w-6 h-6 text-orange-600" />
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-orange-800">{{ summary.ending_soon_30 }} contrat(s) expirent dans 30 jours</h4>
                        <p class="text-sm text-orange-600">
                            +{{ summary.ending_soon_60 }} dans 60j, +{{ summary.ending_soon_90 }} dans 90j
                        </p>
                    </div>
                    <button @click="filterStatus = 'ending_soon'" class="px-4 py-2 bg-orange-600 text-white rounded-lg text-sm hover:bg-orange-700">
                        Voir
                    </button>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Rechercher client, box, contrat..."
                                class="pl-10 pr-4 py-2 rounded-xl border-gray-200 text-sm w-64"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <!-- Site Filter -->
                        <select v-model="filterSite" @change="applyFilters" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les sites</option>
                            <option v-for="site in sites" :key="site.id" :value="site.id">
                                {{ site.name }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select v-model="filterStatus" @change="applyFilters" class="rounded-xl border-gray-200 text-sm">
                            <option value="">Tous les statuts</option>
                            <option value="active">Actifs</option>
                            <option value="ending_soon">Fin dans 30j</option>
                            <option value="ending_60">Fin dans 60j</option>
                            <option value="ending_90">Fin dans 90j</option>
                        </select>

                        <button @click="resetFilters" class="px-3 py-2 text-gray-500 hover:text-gray-700 text-sm">
                            <XMarkIcon class="w-4 h-4 inline mr-1" />
                            Reset
                        </button>
                    </div>

                    <div class="flex gap-3">
                        <button @click="printReport" class="btn-secondary">
                            <PrinterIcon class="w-4 h-4 mr-2" />
                            Imprimer
                        </button>
                        <div class="relative" ref="exportDropdown">
                            <button @click="showExportMenu = !showExportMenu" class="btn-secondary">
                                <ArrowDownTrayIcon class="w-4 h-4 mr-2" />
                                Exporter
                                <ChevronDownIcon class="w-4 h-4 ml-2" />
                            </button>
                            <div v-if="showExportMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border z-10">
                                <button @click="exportReport('xlsx')" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <TableCellsIcon class="w-4 h-4 text-green-600" />
                                    Excel (.xlsx)
                                </button>
                                <button @click="exportReport('csv')" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <DocumentTextIcon class="w-4 h-4 text-blue-600" />
                                    CSV
                                </button>
                                <button @click="exportReport('pdf')" class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 flex items-center gap-2">
                                    <DocumentIcon class="w-4 h-4 text-red-600" />
                                    PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards with Comparison -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Contrats actifs</p>
                            <p class="text-3xl font-bold text-gray-900">{{ summary.total_contracts }}</p>
                            <div v-if="summary.contracts_change !== 0" class="flex items-center mt-1">
                                <component :is="summary.contracts_change > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                    :class="summary.contracts_change > 0 ? 'text-green-500' : 'text-red-500'"
                                    class="w-4 h-4 mr-1" />
                                <span :class="summary.contracts_change > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                    {{ summary.contracts_change > 0 ? '+' : '' }}{{ summary.contracts_change }}%
                                </span>
                                <span class="text-xs text-gray-400 ml-1">vs mois dernier</span>
                            </div>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <DocumentTextIcon class="w-6 h-6 text-blue-600" />
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500">Surface louee</p>
                            <p class="text-3xl font-bold text-green-600">{{ summary.total_area }} m2</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Moyenne: {{ summary.avg_duration_months }} mois d'anciennete
                            </p>
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
                            <div v-if="summary.rent_change !== 0" class="flex items-center mt-1">
                                <component :is="summary.rent_change > 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                    :class="summary.rent_change > 0 ? 'text-green-500' : 'text-red-500'"
                                    class="w-4 h-4 mr-1" />
                                <span :class="summary.rent_change > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                    {{ summary.rent_change > 0 ? '+' : '' }}{{ summary.rent_change }}%
                                </span>
                                <span class="text-xs text-gray-400 ml-1">vs mois dernier</span>
                            </div>
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
                            <p class="text-xs text-gray-400 mt-1">
                                {{ summary.avg_price_per_m2 }} EUR/m2 en moyenne
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-xl">
                            <CalendarIcon class="w-6 h-6 text-orange-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Revenue Evolution Chart -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <ChartBarIcon class="w-5 h-5 text-blue-600" />
                        Evolution du loyer (12 mois)
                    </h3>
                    <div class="h-64">
                        <div class="flex items-end justify-between h-full gap-1">
                            <div v-for="(data, index) in revenueHistory" :key="index" class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-blue-100 rounded-t relative group cursor-pointer"
                                    :style="{ height: getBarHeight(data.rent) + '%' }">
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-10">
                                        {{ formatCurrency(data.rent) }}
                                        <br>{{ data.contracts }} contrats
                                    </div>
                                    <div class="absolute inset-0 bg-blue-500 rounded-t" :style="{ height: '100%' }"></div>
                                </div>
                                <span class="text-xs text-gray-500 mt-2 transform -rotate-45 origin-top-left">{{ data.short_month }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Occupancy by Site -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <BuildingOffice2Icon class="w-5 h-5 text-green-600" />
                        Taux d'occupation par site
                    </h3>
                    <div class="space-y-4">
                        <div v-for="site in occupancyBySite" :key="site.id" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700">{{ site.name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500">{{ site.occupied_boxes }}/{{ site.total_boxes }} boxes</span>
                                    <span :class="getOccupancyColor(site.occupancy_rate)" class="text-sm font-bold">
                                        {{ site.occupancy_rate }}%
                                    </span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3">
                                <div
                                    class="h-3 rounded-full transition-all duration-500"
                                    :class="getOccupancyBarColor(site.occupancy_rate)"
                                    :style="{ width: site.occupancy_rate + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rent Roll Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" id="rent-roll-table">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Detail des contrats</h3>
                    <span class="text-sm text-gray-500">{{ filteredContracts.length }} contrat(s)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th @click="sortByColumn('site')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center gap-1">
                                        Site
                                        <SortIcon :active="sortBy === 'site'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Box</th>
                                <th @click="sortByColumn('customer')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center gap-1">
                                        Client
                                        <SortIcon :active="sortBy === 'customer'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Contrat</th>
                                <th @click="sortByColumn('start_date')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center gap-1">
                                        Debut
                                        <SortIcon :active="sortBy === 'start_date'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th @click="sortByColumn('end_date')" class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center gap-1">
                                        Fin
                                        <SortIcon :active="sortBy === 'end_date'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th @click="sortByColumn('size')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center justify-end gap-1">
                                        Surface
                                        <SortIcon :active="sortBy === 'size'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th @click="sortByColumn('rent')" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase cursor-pointer hover:bg-gray-100">
                                    <div class="flex items-center justify-end gap-1">
                                        Loyer/mois
                                        <SortIcon :active="sortBy === 'rent'" :direction="sortDir" />
                                    </div>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">EUR/m2</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <template v-for="site in groupedContracts" :key="site.id">
                                <!-- Site Header -->
                                <tr class="bg-gray-50">
                                    <td colspan="10" class="px-4 py-2">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span class="font-semibold text-gray-900">{{ site.name }}</span>
                                                <span class="text-sm text-gray-500 ml-2">
                                                    ({{ site.contracts.length }} contrats - {{ formatCurrency(site.total_rent) }}/mois)
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm text-gray-500">
                                                    Occupation: <span class="font-medium" :class="getOccupancyColor(site.occupancy_rate)">{{ site.occupancy_rate }}%</span>
                                                </span>
                                                <span v-if="site.overdue_count > 0" class="text-sm text-red-600">
                                                    {{ site.overdue_count }} impaye(s)
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Contracts -->
                                <tr v-for="contract in site.contracts" :key="contract.id" class="hover:bg-gray-50"
                                    :class="{ 'bg-red-50': contract.has_overdue, 'bg-orange-50': contract.is_ending_soon && !contract.has_overdue }">
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
                                        <p class="text-xs text-gray-500">{{ contract.customer?.email }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ contract.contract_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(contract.start_date) }}</td>
                                    <td class="px-4 py-3">
                                        <div v-if="contract.end_date">
                                            <span :class="contract.is_ending_soon ? 'text-orange-600 font-medium' : 'text-gray-600'" class="text-sm">
                                                {{ formatDate(contract.end_date) }}
                                            </span>
                                            <span v-if="contract.days_until_end !== null && contract.days_until_end >= 0" class="text-xs text-orange-500 block">
                                                {{ contract.days_until_end }}j restants
                                            </span>
                                        </div>
                                        <span v-else class="text-sm text-gray-400">Indefini</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900">{{ contract.box?.size_m2 }} m2</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-medium text-gray-900">{{ formatCurrency(contract.monthly_rent) }}</span>
                                        <span v-if="contract.has_overdue" class="text-xs text-red-600 block">
                                            {{ formatCurrency(contract.overdue_amount) }} impaye
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-600">{{ calculatePricePerM2(contract) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="contract.has_overdue" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <ExclamationCircleIcon class="w-3 h-3 mr-1" />
                                            Impaye
                                        </span>
                                        <span v-else-if="contract.is_ending_soon" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                            <ClockIcon class="w-3 h-3 mr-1" />
                                            Fin proche
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            <CheckCircleIcon class="w-3 h-3 mr-1" />
                                            OK
                                        </span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                            <tr>
                                <td colspan="6" class="px-4 py-3 font-semibold text-gray-900">Total</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ summary.total_area }} m2</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ formatCurrency(summary.monthly_rent) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ summary.avg_price_per_m2 }} EUR</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Bottom Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Site Breakdown -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Repartition par site</h3>
                    <div class="space-y-4">
                        <div v-for="site in siteBreakdown" :key="site.id">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700">{{ site.name }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900">{{ formatCurrency(site.total_rent) }}</span>
                                    <span v-if="site.overdue_count > 0" class="text-xs text-red-500">({{ site.overdue_count }} impaye)</span>
                                </div>
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

                <!-- Size Breakdown -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-semibold text-gray-900 mb-4">Repartition par taille</h3>
                    <div class="space-y-4">
                        <div v-for="size in sizeBreakdown" :key="size.range">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700">{{ size.range }}</span>
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">{{ size.count }} contrats</span>
                                    <span class="text-gray-500 ml-2">({{ formatCurrency(size.total_rent) }})</span>
                                </div>
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
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    DocumentTextIcon,
    CubeIcon,
    CurrencyEuroIcon,
    CalendarIcon,
    ArrowDownTrayIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    PrinterIcon,
    TableCellsIcon,
    DocumentIcon,
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    ClockIcon,
    CheckCircleIcon,
    ChartBarIcon,
    BuildingOffice2Icon,
} from '@heroicons/vue/24/outline'

// Sort Icon Component
const SortIcon = {
    props: ['active', 'direction'],
    template: `
        <span class="inline-flex flex-col">
            <ChevronUpIcon class="w-3 h-3 -mb-1" :class="active && direction === 'asc' ? 'text-primary-600' : 'text-gray-300'" />
            <ChevronDownIcon class="w-3 h-3" :class="active && direction === 'desc' ? 'text-primary-600' : 'text-gray-300'" />
        </span>
    `,
    components: { ChevronUpIcon, ChevronDownIcon }
}

const props = defineProps({
    contracts: Array,
    sites: Array,
    summary: Object,
    siteBreakdown: Array,
    sizeBreakdown: Array,
    revenueHistory: Array,
    occupancyBySite: Array,
    filters: Object,
})

// Filters
const searchQuery = ref(props.filters?.search || '')
const filterSite = ref(props.filters?.site_id || '')
const filterStatus = ref(props.filters?.status || '')
const sortBy = ref(props.filters?.sort_by || 'site')
const sortDir = ref(props.filters?.sort_dir || 'asc')
const showExportMenu = ref(false)
const exportDropdown = ref(null)

// Close dropdown when clicking outside
const handleClickOutside = (e) => {
    if (exportDropdown.value && !exportDropdown.value.contains(e.target)) {
        showExportMenu.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})

// Computed
const filteredContracts = computed(() => props.contracts || [])

const groupedContracts = computed(() => {
    const grouped = {}
    filteredContracts.value.forEach(contract => {
        const siteId = contract.site_id
        if (!grouped[siteId]) {
            const siteData = props.siteBreakdown?.find(s => s.id === siteId)
            grouped[siteId] = {
                id: siteId,
                name: contract.site?.name || 'Non assigne',
                contracts: [],
                total_rent: 0,
                occupancy_rate: siteData?.occupancy_rate || 0,
                overdue_count: siteData?.overdue_count || 0,
            }
        }
        grouped[siteId].contracts.push(contract)
        grouped[siteId].total_rent += parseFloat(contract.monthly_rent || 0)
    })
    return Object.values(grouped)
})

// Methods
const applyFilters = () => {
    router.get(route('tenant.reports.rent-roll'), {
        site_id: filterSite.value || undefined,
        status: filterStatus.value || undefined,
        search: searchQuery.value || undefined,
        sort_by: sortBy.value,
        sort_dir: sortDir.value,
    }, { preserveState: true, replace: true })
}

const resetFilters = () => {
    searchQuery.value = ''
    filterSite.value = ''
    filterStatus.value = ''
    sortBy.value = 'site'
    sortDir.value = 'asc'
    applyFilters()
}

const sortByColumn = (column) => {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = column
        sortDir.value = 'asc'
    }
    applyFilters()
}

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

const calculatePricePerM2 = (contract) => {
    if (!contract.monthly_rent || !contract.box?.size_m2) return '-'
    return (contract.monthly_rent / contract.box.size_m2).toFixed(2) + ' EUR'
}

const getBarHeight = (value) => {
    const max = Math.max(...(props.revenueHistory?.map(d => d.rent) || [1]))
    return max > 0 ? Math.max(5, (value / max) * 100) : 5
}

const getOccupancyColor = (rate) => {
    if (rate >= 90) return 'text-green-600'
    if (rate >= 70) return 'text-blue-600'
    if (rate >= 50) return 'text-orange-600'
    return 'text-red-600'
}

const getOccupancyBarColor = (rate) => {
    if (rate >= 90) return 'bg-green-500'
    if (rate >= 70) return 'bg-blue-500'
    if (rate >= 50) return 'bg-orange-500'
    return 'bg-red-500'
}

const exportReport = (format) => {
    showExportMenu.value = false
    window.location.href = route('tenant.reports.rent-roll.export', {
        format,
        site_id: filterSite.value,
        status: filterStatus.value,
        search: searchQuery.value,
    })
}

const printReport = () => {
    window.print()
}
</script>

<style scoped>
@media print {
    .btn-secondary, .btn-primary, button, select, input {
        display: none !important;
    }
}
</style>
