<script setup>
import { Link } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ArrowLeftIcon,
    MapPinIcon,
    CubeIcon,
    PhoneIcon,
    EnvelopeIcon,
    PencilSquareIcon,
    MapIcon,
    BuildingOfficeIcon,
    ChartBarIcon,
    ClockIcon,
    ShieldCheckIcon,
    VideoCameraIcon,
    SunIcon,
    TruckIcon,
    CheckCircleIcon,
    XCircleIcon,
    PlusIcon,
    CurrencyEuroIcon,
    CalendarDaysIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline'
import { CubeIcon as CubeIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    site: Object,
    stats: Object,
})

const statusConfig = {
    active: { label: 'Actif', color: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    inactive: { label: 'Inactif', color: 'bg-gray-100 text-gray-600 border-gray-200' },
    maintenance: { label: 'Maintenance', color: 'bg-amber-100 text-amber-700 border-amber-200' },
}

const boxStatusConfig = {
    available: { label: 'Disponible', color: 'bg-emerald-100 text-emerald-700' },
    occupied: { label: 'Occupé', color: 'bg-blue-100 text-blue-700' },
    reserved: { label: 'Réservé', color: 'bg-amber-100 text-amber-700' },
    maintenance: { label: 'Maintenance', color: 'bg-orange-100 text-orange-700' },
    unavailable: { label: 'Indisponible', color: 'bg-gray-100 text-gray-600' },
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    })
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

const occupancyRate = ((props.stats?.occupied_boxes || 0) / (props.stats?.total_boxes || 1) * 100).toFixed(1)

const features = [
    { key: 'has_security', label: 'Sécurité 24/7', icon: ShieldCheckIcon },
    { key: 'has_cctv', label: 'Vidéosurveillance', icon: VideoCameraIcon },
    { key: 'has_climate_control', label: 'Climatisation', icon: SunIcon },
    { key: 'has_loading_dock', label: 'Quai de chargement', icon: TruckIcon },
]
</script>

<template>
    <TenantLayout :title="site.name">
        <!-- Gradient Header -->
        <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Back Button -->
                <Link
                    :href="route('tenant.sites.index')"
                    class="inline-flex items-center text-indigo-100 hover:text-white transition-colors mb-6"
                >
                    <ArrowLeftIcon class="w-5 h-5 mr-2" />
                    Retour aux sites
                </Link>

                <!-- Header Content -->
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start space-x-5">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-xl">
                            <MapPinIcon class="w-10 h-10 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-3 mb-2">
                                <h1 class="text-3xl font-bold text-white">{{ site.name }}</h1>
                                <span :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold border',
                                    statusConfig[site.status]?.color || 'bg-gray-100 text-gray-600'
                                ]">
                                    {{ statusConfig[site.status]?.label || site.status }}
                                </span>
                            </div>
                            <p class="text-indigo-100 font-mono text-lg">{{ site.code }}</p>
                            <p v-if="site.description" class="text-indigo-200 mt-2 max-w-xl">{{ site.description }}</p>
                        </div>
                    </div>
                    <div class="mt-6 lg:mt-0 flex flex-wrap gap-3">
                        <Link
                            :href="route('tenant.boxes.plan', site.id)"
                            class="inline-flex items-center px-4 py-2.5 bg-white/20 backdrop-blur-sm text-white rounded-xl font-medium hover:bg-white/30 transition-all border border-white/20"
                        >
                            <MapIcon class="w-5 h-5 mr-2" />
                            Voir le plan
                        </Link>
                        <Link
                            :href="route('tenant.sites.edit', site.id)"
                            class="inline-flex items-center px-4 py-2.5 bg-white text-indigo-600 rounded-xl font-medium hover:bg-indigo-50 transition-all shadow-lg"
                        >
                            <PencilSquareIcon class="w-5 h-5 mr-2" />
                            Modifier
                        </Link>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-8">
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm">Total boxes</p>
                                <p class="text-2xl font-bold text-white">{{ stats?.total_boxes || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <CubeIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm">Disponibles</p>
                                <p class="text-2xl font-bold text-emerald-300">{{ stats?.available_boxes || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-emerald-500/30 rounded-xl flex items-center justify-center">
                                <CheckCircleIcon class="w-6 h-6 text-emerald-300" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm">Occupés</p>
                                <p class="text-2xl font-bold text-blue-300">{{ stats?.occupied_boxes || 0 }}</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-500/30 rounded-xl flex items-center justify-center">
                                <CubeIconSolid class="w-6 h-6 text-blue-300" />
                            </div>
                        </div>
                    </div>
                    <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 border border-white/20">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-indigo-200 text-sm">Taux d'occupation</p>
                                <p class="text-2xl font-bold text-white">{{ occupancyRate }}%</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                                <ChartBarIcon class="w-6 h-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 pb-12">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Site Details -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <BuildingOfficeIcon class="w-5 h-5 text-indigo-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Détails du site</h2>
                        </div>
                        <div class="p-6">
                            <dl class="grid grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                                        <span class="w-2 h-2 bg-indigo-500 rounded-full mr-2"></span>
                                        Code
                                    </dt>
                                    <dd class="mt-1 text-gray-900 font-mono font-semibold">{{ site.code }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                                        <span class="w-2 h-2 bg-purple-500 rounded-full mr-2"></span>
                                        Surface totale
                                    </dt>
                                    <dd class="mt-1 text-gray-900 font-semibold">{{ site.total_area || '-' }} m²</dd>
                                </div>
                                <div class="col-span-2">
                                    <dt class="text-sm font-medium text-gray-500 flex items-center mb-2">
                                        <MapPinIcon class="w-4 h-4 text-gray-400 mr-2" />
                                        Adresse
                                    </dt>
                                    <dd class="text-gray-900 bg-gray-50 rounded-xl p-4">
                                        <p class="font-medium">{{ site.address }}</p>
                                        <p class="text-gray-600">{{ site.postal_code }} {{ site.city }}</p>
                                        <p class="text-gray-500">{{ site.country }}</p>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                                        <PhoneIcon class="w-4 h-4 text-gray-400 mr-2" />
                                        Téléphone
                                    </dt>
                                    <dd class="mt-1 text-gray-900">{{ site.phone || '-' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                                        <EnvelopeIcon class="w-4 h-4 text-gray-400 mr-2" />
                                        Email
                                    </dt>
                                    <dd class="mt-1 text-gray-900">{{ site.email || '-' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Opening Hours -->
                    <div v-if="site.opening_hours" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <ClockIcon class="w-5 h-5 text-indigo-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Horaires d'ouverture</h2>
                        </div>
                        <div class="p-6">
                            <dl class="space-y-3">
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <dt class="text-gray-600">Lundi - Vendredi</dt>
                                    <dd class="font-semibold text-gray-900">{{ site.opening_hours?.weekdays || '07:00 - 21:00' }}</dd>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <dt class="text-gray-600">Samedi</dt>
                                    <dd class="font-semibold text-gray-900">{{ site.opening_hours?.saturday || '08:00 - 18:00' }}</dd>
                                </div>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <dt class="text-gray-600">Dimanche</dt>
                                    <dd class="font-semibold text-gray-900">{{ site.opening_hours?.sunday || 'Fermé' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Boxes List -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                            <div class="flex items-center">
                                <CubeIcon class="w-5 h-5 text-indigo-600 mr-2" />
                                <h2 class="text-lg font-semibold text-gray-900">Boxes</h2>
                                <span class="ml-2 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-medium rounded-full">
                                    {{ site.boxes?.length || 0 }}
                                </span>
                            </div>
                            <Link
                                :href="route('tenant.boxes.create', { site_id: site.id })"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors"
                            >
                                <PlusIcon class="w-4 h-4 mr-1" />
                                Ajouter
                            </Link>
                        </div>
                        <div v-if="site.boxes && site.boxes.length > 0">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Surface</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix/mois</th>
                                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr v-for="box in site.boxes.slice(0, 10)" :key="box.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <Link :href="route('tenant.boxes.show', box.id)" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                                    {{ box.code }}
                                                </Link>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ box.size }} m²</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ formatCurrency(box.monthly_price) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    boxStatusConfig[box.status]?.color || 'bg-gray-100 text-gray-600'
                                                ]">
                                                    {{ boxStatusConfig[box.status]?.label || box.status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="site.boxes.length > 10" class="px-6 py-4 border-t border-gray-100 text-center bg-gray-50">
                                <Link
                                    :href="route('tenant.boxes.index', { site_id: site.id })"
                                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                                >
                                    Voir les {{ site.boxes.length }} boxes →
                                </Link>
                            </div>
                        </div>
                        <div v-else class="px-6 py-12 text-center">
                            <CubeIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
                            <p class="text-gray-500 mb-4">Aucun box pour ce site.</p>
                            <Link
                                :href="route('tenant.boxes.create', { site_id: site.id })"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors"
                            >
                                <PlusIcon class="w-4 h-4 mr-2" />
                                Ajouter le premier box
                            </Link>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="site.notes" class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ site.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Sidebar) -->
                <div class="space-y-6">
                    <!-- Revenue Card -->
                    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl overflow-hidden">
                        <div class="px-6 py-8 text-center">
                            <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <CurrencyEuroIcon class="w-7 h-7 text-white" />
                            </div>
                            <p class="text-emerald-100 text-sm font-medium uppercase tracking-wide">Revenu mensuel</p>
                            <p class="mt-2 text-3xl font-bold text-white">{{ formatCurrency(stats?.monthly_revenue || 0) }}</p>
                        </div>
                    </div>

                    <!-- Occupancy Chart -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <ArrowTrendingUpIcon class="w-5 h-5 text-indigo-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Occupation</h2>
                        </div>
                        <div class="p-6">
                            <!-- Progress Bar -->
                            <div class="mb-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Taux d'occupation</span>
                                    <span class="text-sm font-bold text-indigo-600">{{ occupancyRate }}%</span>
                                </div>
                                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        :style="{ width: occupancyRate + '%' }"
                                        class="h-full bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                                    ></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 text-right">
                                    {{ stats?.occupied_boxes || 0 }} / {{ stats?.total_boxes || 0 }} boxes
                                </p>
                            </div>

                            <!-- Stats Grid -->
                            <div class="grid grid-cols-2 gap-4 mt-6">
                                <div class="bg-emerald-50 rounded-xl p-4 text-center">
                                    <p class="text-2xl font-bold text-emerald-600">{{ stats?.available_boxes || 0 }}</p>
                                    <p class="text-xs text-emerald-700 font-medium">Disponibles</p>
                                </div>
                                <div class="bg-blue-50 rounded-xl p-4 text-center">
                                    <p class="text-2xl font-bold text-blue-600">{{ stats?.occupied_boxes || 0 }}</p>
                                    <p class="text-xs text-blue-700 font-medium">Occupés</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <ShieldCheckIcon class="w-5 h-5 text-indigo-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Équipements</h2>
                        </div>
                        <div class="p-6">
                            <ul class="space-y-3">
                                <li v-for="feature in features" :key="feature.key" class="flex items-center">
                                    <div :class="[
                                        'w-8 h-8 rounded-lg flex items-center justify-center mr-3',
                                        site[feature.key] ? 'bg-emerald-100' : 'bg-gray-100'
                                    ]">
                                        <component
                                            :is="site[feature.key] ? CheckCircleIcon : XCircleIcon"
                                            :class="[
                                                'w-5 h-5',
                                                site[feature.key] ? 'text-emerald-600' : 'text-gray-400'
                                            ]"
                                        />
                                    </div>
                                    <span :class="site[feature.key] ? 'text-gray-900' : 'text-gray-400'">
                                        {{ feature.label }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex items-center">
                            <CalendarDaysIcon class="w-5 h-5 text-indigo-600 mr-2" />
                            <h2 class="text-lg font-semibold text-gray-900">Chronologie</h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <PlusIcon class="w-4 h-4 text-indigo-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Création</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(site.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start">
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                        <PencilSquareIcon class="w-4 h-4 text-purple-600" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Dernière modification</p>
                                        <p class="text-sm text-gray-500">{{ formatDate(site.updated_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-200/50 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900">Actions rapides</h2>
                        </div>
                        <div class="p-4 space-y-2">
                            <Link
                                :href="route('tenant.boxes.create', { site_id: site.id })"
                                class="flex items-center w-full px-4 py-3 text-left text-gray-700 hover:bg-gray-50 rounded-xl transition-colors"
                            >
                                <PlusIcon class="w-5 h-5 text-gray-400 mr-3" />
                                <span>Ajouter un box</span>
                            </Link>
                            <Link
                                :href="route('tenant.boxes.plan', site.id)"
                                class="flex items-center w-full px-4 py-3 text-left text-gray-700 hover:bg-gray-50 rounded-xl transition-colors"
                            >
                                <MapIcon class="w-5 h-5 text-gray-400 mr-3" />
                                <span>Voir le plan interactif</span>
                            </Link>
                            <Link
                                :href="route('tenant.boxes.index', { site_id: site.id })"
                                class="flex items-center w-full px-4 py-3 text-left text-gray-700 hover:bg-gray-50 rounded-xl transition-colors"
                            >
                                <CubeIcon class="w-5 h-5 text-gray-400 mr-3" />
                                <span>Voir tous les boxes</span>
                            </Link>
                            <Link
                                :href="route('tenant.sites.edit', site.id)"
                                class="flex items-center w-full px-4 py-3 text-left text-gray-700 hover:bg-gray-50 rounded-xl transition-colors"
                            >
                                <PencilSquareIcon class="w-5 h-5 text-gray-400 mr-3" />
                                <span>Modifier le site</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
