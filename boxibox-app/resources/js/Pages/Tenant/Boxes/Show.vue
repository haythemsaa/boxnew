<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    CubeIcon,
    BuildingOfficeIcon,
    CurrencyEuroIcon,
    DocumentTextIcon,
    CalendarDaysIcon,
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    UserIcon,
    MapPinIcon,
    KeyIcon,
    BoltIcon,
    WifiIcon,
    ShieldCheckIcon,
    HomeIcon,
    Square3Stack3DIcon,
    Cog6ToothIcon,
    ChartBarIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    box: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const statusConfig = {
    available: { label: 'Disponible', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    occupied: { label: 'Occupé', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    reserved: { label: 'Réservé', color: 'bg-amber-100 text-amber-700 border-amber-200' },
    maintenance: { label: 'Maintenance', color: 'bg-orange-100 text-orange-700 border-orange-200' },
    unavailable: { label: 'Indisponible', color: 'bg-gray-100 text-gray-600 border-gray-200' },
}

const contractStatusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700' },
    pending_signature: { label: 'En attente', color: 'bg-amber-100 text-amber-700' },
    active: { label: 'Actif', color: 'bg-emerald-100 text-emerald-700' },
    expired: { label: 'Expiré', color: 'bg-red-100 text-red-700' },
    terminated: { label: 'Résilié', color: 'bg-red-100 text-red-700' },
}

const features = [
    { key: 'is_climate_controlled', label: 'Climatisé', icon: Cog6ToothIcon },
    { key: 'has_electricity', label: 'Électricité', icon: BoltIcon },
    { key: 'is_ground_floor', label: 'Rez-de-chaussée', icon: HomeIcon },
    { key: 'has_drive_up', label: 'Accès voiture', icon: CubeIcon },
]

const getCustomerName = (customer) => {
    if (!customer) return '-'
    return customer.type === 'company'
        ? customer.company_name
        : `${customer.first_name} ${customer.last_name}`
}
</script>

<template>
    <TenantLayout :title="`Box ${box.code}`">
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-amber-50 to-orange-50 py-8">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header avec gradient -->
                <div class="relative mb-8 overflow-hidden rounded-2xl bg-gradient-to-r from-amber-500 via-amber-600 to-orange-600 p-8 shadow-xl">
                    <div class="absolute inset-0 bg-grid-white/10"></div>
                    <div class="absolute top-0 right-0 -mt-10 -mr-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 -mb-10 -ml-10 h-40 w-40 rounded-full bg-orange-400/20 blur-3xl"></div>

                    <div class="relative">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                            <div class="flex items-center gap-4">
                                <Link
                                    :href="route('tenant.boxes.index')"
                                    class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 transition-all"
                                >
                                    <ArrowLeftIcon class="w-5 h-5" />
                                </Link>
                                <div>
                                    <div class="flex items-center gap-3 mb-2">
                                        <h1 class="text-2xl lg:text-3xl font-bold text-white">Box {{ box.code }}</h1>
                                        <span :class="[statusConfig[box.status]?.color || 'bg-gray-100 text-gray-700', 'px-3 py-1 text-xs font-semibold rounded-full border']">
                                            {{ statusConfig[box.status]?.label || box.status }}
                                        </span>
                                    </div>
                                    <p class="text-amber-100">
                                        {{ box.site?.name }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <Link
                                    :href="route('tenant.boxes.edit', box.id)"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-amber-700 hover:bg-amber-50 transition-all font-medium shadow-lg"
                                >
                                    <PencilSquareIcon class="w-5 h-5" />
                                    <span>Modifier</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Stats rapides -->
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CurrencyEuroIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-amber-200">Prix mensuel</p>
                                        <p class="text-lg font-bold text-white">{{ formatCurrency(box.monthly_price || box.base_price) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <Square3Stack3DIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-amber-200">Surface</p>
                                        <p class="text-lg font-bold text-white">{{ box.size || (box.length * box.width).toFixed(1) }} m²</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <CubeIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-amber-200">Type</p>
                                        <p class="text-lg font-bold text-white capitalize">{{ box.type || 'Standard' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-white/10 rounded-lg">
                                        <BuildingOfficeIcon class="w-5 h-5 text-white" />
                                    </div>
                                    <div>
                                        <p class="text-xs text-amber-200">Étage</p>
                                        <p class="text-lg font-bold text-white">{{ box.floor?.name || 'RDC' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Contenu principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Détails du box -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-amber-100 rounded-lg">
                                        <CubeIcon class="w-5 h-5 text-amber-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Détails du box</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="grid grid-cols-2 gap-6">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <Square3Stack3DIcon class="w-4 h-4" />
                                            Surface
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ box.size || (box.length * box.width).toFixed(1) }} m²</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CubeIcon class="w-4 h-4" />
                                            Type
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900 capitalize">{{ box.type || 'Standard' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <BuildingOfficeIcon class="w-4 h-4" />
                                            Étage
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ box.floor?.name || 'RDC' }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <CurrencyEuroIcon class="w-4 h-4" />
                                            Prix mensuel
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-emerald-600">{{ formatCurrency(box.monthly_price || box.base_price) }}</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Largeur</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ box.width || '-' }} m</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Profondeur</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ box.depth || box.length || '-' }} m</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500">Hauteur</dt>
                                        <dd class="mt-2 text-base font-semibold text-gray-900">{{ box.height || '-' }} m</dd>
                                    </div>
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <dt class="text-sm font-medium text-gray-500 flex items-center gap-2">
                                            <KeyIcon class="w-4 h-4" />
                                            Code d'accès
                                        </dt>
                                        <dd class="mt-2 text-base font-mono font-semibold text-gray-900">{{ box.access_code || '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Équipements -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-teal-100 rounded-lg">
                                        <Cog6ToothIcon class="w-5 h-5 text-teal-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Équipements</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div v-for="feature in features" :key="feature.key" class="flex items-center gap-3">
                                        <div :class="[
                                            'w-10 h-10 rounded-lg flex items-center justify-center',
                                            box[feature.key] ? 'bg-teal-100' : 'bg-gray-100'
                                        ]">
                                            <component :is="feature.icon" :class="[
                                                'w-5 h-5',
                                                box[feature.key] ? 'text-teal-600' : 'text-gray-400'
                                            ]" />
                                        </div>
                                        <div>
                                            <span :class="[
                                                'text-sm font-medium',
                                                box[feature.key] ? 'text-gray-900' : 'text-gray-400 line-through'
                                            ]">
                                                {{ feature.label }}
                                            </span>
                                            <CheckCircleIcon v-if="box[feature.key]" class="inline-block w-4 h-4 ml-1 text-teal-500" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contrat actuel -->
                        <div v-if="box.current_contract" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-emerald-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-emerald-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Contrat actuel</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="flex items-center justify-between p-4 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100">
                                    <div>
                                        <p class="text-base font-semibold text-gray-900">{{ box.current_contract.contract_number }}</p>
                                        <p class="text-sm text-gray-500">{{ getCustomerName(box.current_contract.customer) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span :class="[contractStatusConfig[box.current_contract.status]?.color || 'bg-gray-100 text-gray-700', 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                            {{ contractStatusConfig[box.current_contract.status]?.label || box.current_contract.status }}
                                        </span>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ formatDate(box.current_contract.start_date) }} - {{ formatDate(box.current_contract.end_date) }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <Link
                                        :href="route('tenant.contracts.show', box.current_contract.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-emerald-600 hover:text-emerald-700 transition-colors"
                                    >
                                        <DocumentTextIcon class="w-4 h-4" />
                                        Voir le contrat
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Historique des contrats -->
                        <div v-if="box.contracts && box.contracts.length > 0" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-indigo-100 rounded-lg">
                                            <DocumentTextIcon class="w-5 h-5 text-indigo-600" />
                                        </div>
                                        <h2 class="text-lg font-semibold text-gray-900">Historique des contrats</h2>
                                    </div>
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-sm font-semibold rounded-full">
                                        {{ box.contracts.length }} contrat(s)
                                    </span>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contrat</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Client</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Période</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="contract in box.contracts" :key="contract.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Link :href="route('tenant.contracts.show', contract.id)" class="text-indigo-600 hover:text-indigo-700 font-medium">
                                                    {{ contract.contract_number }}
                                                </Link>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ getCustomerName(contract.customer) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ formatDate(contract.start_date) }} - {{ formatDate(contract.end_date) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="[contractStatusConfig[contract.status]?.color || 'bg-gray-100 text-gray-700', 'px-2.5 py-1 rounded-full text-xs font-semibold']">
                                                    {{ contractStatusConfig[contract.status]?.label || contract.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="box.notes" class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ box.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Informations site -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <BuildingOfficeIcon class="w-5 h-5 text-blue-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Site</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <BuildingOfficeIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ box.site?.name }}</dd>
                                        </div>
                                    </div>
                                    <div v-if="box.site?.address" class="flex items-start gap-3">
                                        <MapPinIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Adresse</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                                {{ box.site.address }}<br>
                                                {{ box.site.postal_code }} {{ box.site.city }}
                                            </dd>
                                        </div>
                                    </div>
                                </dl>
                                <div class="mt-6 pt-4 border-t border-gray-100">
                                    <Link
                                        :href="route('tenant.sites.show', box.site?.id)"
                                        class="inline-flex items-center gap-2 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                                    >
                                        <BuildingOfficeIcon class="w-4 h-4" />
                                        Voir le site
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-purple-100 rounded-lg">
                                        <ChartBarIcon class="w-5 h-5 text-purple-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Statistiques</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <dt class="text-sm text-gray-600">Total contrats</dt>
                                        <dd class="text-sm font-bold text-gray-900">{{ box.contracts?.length || 0 }}</dd>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <dt class="text-sm text-gray-600">Taux d'occupation</dt>
                                        <dd class="text-sm font-bold text-gray-900">{{ box.occupancy_rate || 0 }}%</dd>
                                    </div>
                                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <dt class="text-sm text-gray-600">Revenus générés</dt>
                                        <dd class="text-sm font-bold text-emerald-600">{{ formatCurrency(box.total_revenue || 0) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Chronologie -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <ClockIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Chronologie</h2>
                                </div>
                            </div>
                            <div class="p-6">
                                <dl class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <CalendarDaysIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Création</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(box.created_at) }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <ClockIcon class="w-5 h-5 text-gray-400 mt-0.5" />
                                        <div>
                                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Dernière modification</dt>
                                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDate(box.updated_at) }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-gray-100 rounded-lg">
                                        <DocumentTextIcon class="w-5 h-5 text-gray-600" />
                                    </div>
                                    <h2 class="text-lg font-semibold text-gray-900">Actions</h2>
                                </div>
                            </div>
                            <div class="p-4 space-y-3">
                                <Link
                                    v-if="box.status === 'available'"
                                    :href="route('tenant.contracts.create', { box_id: box.id })"
                                    class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white rounded-xl text-sm font-medium hover:from-emerald-600 hover:to-teal-700 transition-all shadow-lg"
                                >
                                    <PlusIcon class="w-5 h-5" />
                                    Créer un contrat
                                </Link>
                                <button class="w-full flex items-center justify-center gap-2 px-4 py-3 border border-gray-200 rounded-xl text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    <Cog6ToothIcon class="w-5 h-5 text-gray-500" />
                                    Mettre en maintenance
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
