<template>
    <TenantLayout title="Nouveau rapport" :breadcrumbs="[{ label: 'Rapports', href: route('tenant.reports.index') }, { label: 'Nouveau rapport' }]">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Report Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom du rapport *</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Ex: Rapport mensuel d'occupation"
                            required
                        />
                        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                    </div>

                    <!-- Report Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type de rapport *</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label
                                v-for="type in reportTypes"
                                :key="type.value"
                                :class="form.type === type.value ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
                                class="flex flex-col items-center p-4 border-2 rounded-xl cursor-pointer transition-colors"
                            >
                                <input type="radio" v-model="form.type" :value="type.value" class="hidden" />
                                <component :is="type.icon" :class="form.type === type.value ? 'text-primary-600' : 'text-gray-400'" class="w-8 h-8 mb-2" />
                                <span :class="form.type === type.value ? 'text-primary-700' : 'text-gray-600'" class="text-sm font-medium text-center">
                                    {{ type.label }}
                                </span>
                            </label>
                        </div>
                        <p v-if="form.errors.type" class="text-red-500 text-sm mt-1">{{ form.errors.type }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full rounded-xl border-gray-200"
                            placeholder="Description du rapport..."
                        ></textarea>
                    </div>

                    <!-- Filters Section -->
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Filtres</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Sites -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sites</label>
                                <select v-model="form.filters.site_ids" multiple class="w-full rounded-xl border-gray-200 h-32">
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }}
                                    </option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Maintenez Ctrl pour sélectionner plusieurs sites</p>
                            </div>

                            <!-- Date Range -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Période</label>
                                <select v-model="form.filters.date_range" class="w-full rounded-xl border-gray-200 mb-2">
                                    <option value="this_month">Ce mois</option>
                                    <option value="last_month">Mois dernier</option>
                                    <option value="this_quarter">Ce trimestre</option>
                                    <option value="last_quarter">Trimestre dernier</option>
                                    <option value="this_year">Cette année</option>
                                    <option value="last_year">Année dernière</option>
                                    <option value="custom">Personnalisé</option>
                                </select>
                                <div v-if="form.filters.date_range === 'custom'" class="grid grid-cols-2 gap-2">
                                    <input v-model="form.filters.start_date" type="date" class="rounded-xl border-gray-200" />
                                    <input v-model="form.filters.end_date" type="date" class="rounded-xl border-gray-200" />
                                </div>
                            </div>
                        </div>

                        <!-- Type-specific Filters -->
                        <div v-if="form.type === 'occupancy'" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Grouper par</label>
                            <select v-model="form.filters.group_by" class="w-full rounded-xl border-gray-200">
                                <option value="site">Site</option>
                                <option value="size">Taille de box</option>
                                <option value="floor">Étage</option>
                            </select>
                        </div>

                        <div v-if="form.type === 'revenue'" class="mt-4 grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Inclure</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.filters.include_rent" class="rounded text-primary-600" />
                                        <span class="text-sm text-gray-700">Loyers</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.filters.include_services" class="rounded text-primary-600" />
                                        <span class="text-sm text-gray-700">Services</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" v-model="form.filters.include_fees" class="rounded text-primary-600" />
                                        <span class="text-sm text-gray-700">Frais annexes</span>
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Comparaison</label>
                                <select v-model="form.filters.comparison" class="w-full rounded-xl border-gray-200">
                                    <option value="">Aucune</option>
                                    <option value="previous_period">Période précédente</option>
                                    <option value="same_period_last_year">Même période année précédente</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Columns Selection -->
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Colonnes à afficher</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <label v-for="column in availableColumns" :key="column.value" class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl cursor-pointer hover:bg-gray-100">
                                <input
                                    type="checkbox"
                                    :value="column.value"
                                    v-model="form.columns"
                                    class="rounded text-primary-600"
                                />
                                <span class="text-sm text-gray-700">{{ column.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Scheduling -->
                    <div class="border-t border-gray-100 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-semibold text-gray-900">Programmation automatique</h3>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_scheduled" class="sr-only peer" />
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                            </label>
                        </div>

                        <div v-if="form.is_scheduled" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fréquence</label>
                                <select v-model="form.schedule.frequency" class="w-full rounded-xl border-gray-200">
                                    <option value="daily">Quotidien</option>
                                    <option value="weekly">Hebdomadaire</option>
                                    <option value="monthly">Mensuel</option>
                                    <option value="quarterly">Trimestriel</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heure d'envoi</label>
                                <input v-model="form.schedule.time" type="time" class="w-full rounded-xl border-gray-200" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                                <select v-model="form.schedule.format" class="w-full rounded-xl border-gray-200">
                                    <option value="pdf">PDF</option>
                                    <option value="xlsx">Excel</option>
                                    <option value="csv">CSV</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.is_scheduled" class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Destinataires (emails)</label>
                            <input
                                v-model="form.schedule.recipients"
                                type="text"
                                class="w-full rounded-xl border-gray-200"
                                placeholder="email1@example.com, email2@example.com"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-between gap-3 pt-4 border-t border-gray-100">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_favorite" class="rounded text-yellow-500" />
                            <StarIcon class="w-5 h-5 text-yellow-500" />
                            <span class="text-sm text-gray-700">Ajouter aux favoris</span>
                        </label>
                        <div class="flex gap-3">
                            <Link :href="route('tenant.reports.index')" class="btn-secondary">
                                Annuler
                            </Link>
                            <button type="submit" :disabled="form.processing" class="btn-primary">
                                <span v-if="form.processing">Création...</span>
                                <span v-else>Créer le rapport</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    TableCellsIcon,
    ChartPieIcon,
    CurrencyEuroIcon,
    ArrowTrendingUpIcon,
    UsersIcon,
    DocumentTextIcon,
    StarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    sites: Array,
})

const form = useForm({
    name: '',
    type: 'rent_roll',
    description: '',
    filters: {
        site_ids: [],
        date_range: 'this_month',
        start_date: '',
        end_date: '',
        group_by: 'site',
        include_rent: true,
        include_services: true,
        include_fees: false,
        comparison: '',
    },
    columns: ['site', 'box', 'customer', 'amount'],
    is_favorite: false,
    is_scheduled: false,
    schedule: {
        frequency: 'monthly',
        time: '08:00',
        format: 'pdf',
        recipients: '',
    },
})

const reportTypes = [
    { value: 'rent_roll', label: 'Rent Roll', icon: TableCellsIcon },
    { value: 'occupancy', label: 'Occupation', icon: ChartPieIcon },
    { value: 'revenue', label: 'Revenus', icon: CurrencyEuroIcon },
    { value: 'cash_flow', label: 'Trésorerie', icon: ArrowTrendingUpIcon },
    { value: 'customers', label: 'Clients', icon: UsersIcon },
    { value: 'contracts', label: 'Contrats', icon: DocumentTextIcon },
]

const availableColumns = computed(() => {
    const baseColumns = [
        { value: 'site', label: 'Site' },
        { value: 'box', label: 'Box' },
        { value: 'customer', label: 'Client' },
    ]

    const typeColumns = {
        rent_roll: [
            { value: 'contract_number', label: 'N° Contrat' },
            { value: 'start_date', label: 'Date début' },
            { value: 'monthly_rent', label: 'Loyer mensuel' },
            { value: 'amount', label: 'Montant total' },
        ],
        occupancy: [
            { value: 'total_boxes', label: 'Total boxes' },
            { value: 'occupied', label: 'Occupés' },
            { value: 'available', label: 'Disponibles' },
            { value: 'rate', label: 'Taux' },
        ],
        revenue: [
            { value: 'rent_amount', label: 'Loyers' },
            { value: 'services_amount', label: 'Services' },
            { value: 'fees_amount', label: 'Frais' },
            { value: 'total_amount', label: 'Total' },
        ],
        cash_flow: [
            { value: 'income', label: 'Encaissements' },
            { value: 'expenses', label: 'Décaissements' },
            { value: 'balance', label: 'Solde' },
            { value: 'forecast', label: 'Prévision' },
        ],
        customers: [
            { value: 'email', label: 'Email' },
            { value: 'phone', label: 'Téléphone' },
            { value: 'contracts_count', label: 'Nb contrats' },
            { value: 'total_spent', label: 'CA total' },
        ],
        contracts: [
            { value: 'status', label: 'Statut' },
            { value: 'start_date', label: 'Date début' },
            { value: 'end_date', label: 'Date fin' },
            { value: 'monthly_rent', label: 'Loyer' },
        ],
    }

    return [...baseColumns, ...(typeColumns[form.type] || [])]
})

const submit = () => {
    form.post(route('tenant.reports.store'))
}
</script>
