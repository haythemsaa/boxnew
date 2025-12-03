<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    UserGroupIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowPathIcon,
    PencilSquareIcon,
    CheckCircleIcon,
    PhoneIcon,
    EnvelopeIcon,
    FireIcon,
    StarIcon,
    ChartBarIcon,
    UserPlusIcon,
    TrophyIcon,
    ClockIcon,
    ExclamationCircleIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    leads: Object,
    analytics: Object,
})

const showCreateModal = ref(false)
const filters = reactive({
    status: '',
    source: '',
    hot: '',
    search: '',
})

let searchTimeout = null

const statusOptions = [
    { value: 'new', label: 'Nouveau', icon: '🆕', color: 'bg-blue-100 text-blue-700 border-blue-200' },
    { value: 'contacted', label: 'Contacté', icon: '📞', color: 'bg-yellow-100 text-yellow-700 border-yellow-200' },
    { value: 'qualified', label: 'Qualifié', icon: '⭐', color: 'bg-purple-100 text-purple-700 border-purple-200' },
    { value: 'converted', label: 'Converti', icon: '✅', color: 'bg-green-100 text-green-700 border-green-200' },
    { value: 'lost', label: 'Perdu', icon: '❌', color: 'bg-gray-100 text-gray-700 border-gray-200' },
]

const sourceOptions = [
    { value: 'website', label: 'Site web', icon: '🌐' },
    { value: 'phone', label: 'Téléphone', icon: '📞' },
    { value: 'referral', label: 'Parrainage', icon: '👥' },
    { value: 'walk-in', label: 'Visite', icon: '🚶' },
    { value: 'google_ads', label: 'Google Ads', icon: '🔍' },
    { value: 'facebook', label: 'Facebook', icon: '📘' },
]

const getStatusConfig = (status) => {
    return statusOptions.find(s => s.value === status) || statusOptions[0]
}

const getSourceConfig = (source) => {
    return sourceOptions.find(s => s.value === source) || { value: source, label: source || '-', icon: '📌' }
}

const getScoreColor = (score) => {
    if (score >= 70) return 'text-red-600'
    if (score >= 40) return 'text-yellow-600'
    return 'text-gray-500'
}

const getScoreGradient = (score) => {
    if (score >= 70) return 'from-red-500 to-orange-500'
    if (score >= 40) return 'from-yellow-500 to-amber-500'
    return 'from-gray-400 to-gray-500'
}

const applyFilters = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.crm.leads.index'), filters, {
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
}

const clearFilters = () => {
    filters.status = ''
    filters.source = ''
    filters.hot = ''
    filters.search = ''
    router.get(route('tenant.crm.leads.index'))
}

const hasActiveFilters = computed(() => {
    return filters.status || filters.source || filters.hot || filters.search
})

const editLead = (lead) => {
    router.visit(route('tenant.crm.leads.edit', lead.id))
}

const convertLead = (lead) => {
    if (confirm(`Convertir ${lead.first_name} ${lead.last_name} en client ?`)) {
        router.post(route('tenant.crm.leads.convert', lead.id))
    }
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    })
}
</script>

<template>
    <TenantLayout title="Leads CRM">
        <div class="space-y-6">
            <!-- Header avec gradient -->
            <div class="relative overflow-hidden bg-gradient-to-br from-cyan-500 via-teal-500 to-emerald-500 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <UserGroupIcon class="h-8 w-8 text-white" />
                                </div>
                                Gestion des Leads
                            </h1>
                            <p class="mt-2 text-cyan-100">
                                Gérez et convertissez vos leads en clients
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.crm.leads.create')"
                            class="inline-flex items-center px-6 py-3 bg-white text-teal-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <UserPlusIcon class="h-5 w-5 mr-2" />
                            Nouveau Lead
                        </Link>
                    </div>

                    <!-- Stats analytiques -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ analytics?.total_leads || 0 }}</p>
                            <p class="text-xs text-cyan-100 font-medium mt-1">Total Leads</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ analytics?.converted || 0 }}</p>
                            <p class="text-xs text-cyan-100 font-medium mt-1">Convertis</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ analytics?.conversion_rate?.toFixed(1) || 0 }}%
                                <ArrowTrendingUpIcon class="h-5 w-5 ml-1 text-emerald-300" />
                            </p>
                            <p class="text-xs text-cyan-100 font-medium mt-1">Taux conversion</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ analytics?.hot_leads || 0 }}
                                <FireIconSolid class="h-5 w-5 ml-1 text-orange-300" />
                            </p>
                            <p class="text-xs text-cyan-100 font-medium mt-1">Leads Chauds</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ analytics?.unassigned || 0 }}
                                <ExclamationCircleIcon class="h-5 w-5 ml-1 text-yellow-300" />
                            </p>
                            <p class="text-xs text-cyan-100 font-medium mt-1">Non assignés</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Recherche -->
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Rechercher un lead (nom, email)..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all"
                            @input="applyFilters"
                        />
                    </div>

                    <!-- Filtre Statut -->
                    <div class="w-full lg:w-44">
                        <select
                            v-model="filters.status"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all bg-white"
                            @change="applyFilters"
                        >
                            <option value="">Tous les statuts</option>
                            <option v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.icon }} {{ status.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtre Source -->
                    <div class="w-full lg:w-44">
                        <select
                            v-model="filters.source"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all bg-white"
                            @change="applyFilters"
                        >
                            <option value="">Toutes les sources</option>
                            <option v-for="source in sourceOptions" :key="source.value" :value="source.value">
                                {{ source.icon }} {{ source.label }}
                            </option>
                        </select>
                    </div>

                    <!-- Filtre Score -->
                    <div class="w-full lg:w-44">
                        <select
                            v-model="filters.hot"
                            class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all bg-white"
                            @change="applyFilters"
                        >
                            <option value="">Tous les leads</option>
                            <option value="1">Leads Chauds (&ge;70)</option>
                        </select>
                    </div>

                    <!-- Bouton Reset -->
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors"
                    >
                        <ArrowPathIcon class="h-5 w-5 mr-2" />
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste des Leads -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- État vide -->
                <div v-if="!leads?.data?.length" class="py-16 px-4 text-center">
                    <div class="mx-auto w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-4">
                        <UserGroupIcon class="h-8 w-8 text-teal-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun lead trouvé</h3>
                    <p class="text-gray-500 mb-6">Commencez par créer votre premier lead</p>
                    <Link
                        :href="route('tenant.crm.leads.create')"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-teal-500 to-emerald-500 text-white rounded-xl font-semibold hover:shadow-lg transition-all"
                    >
                        <UserPlusIcon class="h-5 w-5 mr-2" />
                        Nouveau Lead
                    </Link>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Lead
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Score
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Source
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Assigné à
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Créé le
                                </th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr
                                v-for="(lead, index) in leads.data"
                                :key="lead.id"
                                class="hover:bg-teal-50/50 transition-colors duration-150"
                                :style="{ animationDelay: `${index * 50}ms` }"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-semibold shadow-sm" :class="getScoreGradient(lead.score)">
                                                {{ (lead.first_name?.charAt(0) || '') + (lead.last_name?.charAt(0) || '') }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">
                                                {{ lead.first_name }} {{ lead.last_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ lead.company || '-' }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <a :href="`mailto:${lead.email}`" class="text-sm text-gray-900 hover:text-teal-600 flex items-center gap-1">
                                            <EnvelopeIcon class="h-4 w-4 text-gray-400" />
                                            {{ lead.email }}
                                        </a>
                                        <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="text-sm text-gray-500 hover:text-teal-600 flex items-center gap-1">
                                            <PhoneIcon class="h-4 w-4 text-gray-400" />
                                            {{ lead.phone }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="relative">
                                            <div class="text-2xl font-bold" :class="getScoreColor(lead.score)">
                                                {{ lead.score }}
                                            </div>
                                            <!-- Progress bar -->
                                            <div class="w-12 h-1 bg-gray-200 rounded-full mt-1">
                                                <div
                                                    class="h-1 rounded-full transition-all"
                                                    :class="lead.score >= 70 ? 'bg-red-500' : lead.score >= 40 ? 'bg-yellow-500' : 'bg-gray-400'"
                                                    :style="{ width: `${lead.score}%` }"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="text-lg">
                                            <template v-if="lead.score >= 70">
                                                <FireIconSolid class="h-5 w-5 text-red-500" />
                                            </template>
                                            <template v-else-if="lead.score >= 40">
                                                <StarIconSolid class="h-5 w-5 text-yellow-500" />
                                            </template>
                                            <template v-else>
                                                <ChartBarIcon class="h-5 w-5 text-gray-400" />
                                            </template>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                                        :class="getStatusConfig(lead.status).color"
                                    >
                                        {{ getStatusConfig(lead.status).icon }}
                                        {{ getStatusConfig(lead.status).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 text-sm text-gray-600">
                                        {{ getSourceConfig(lead.source).icon }}
                                        {{ getSourceConfig(lead.source).label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <template v-if="lead.assigned_to">
                                        <div class="flex items-center gap-2">
                                            <div class="h-6 w-6 rounded-full bg-teal-100 flex items-center justify-center text-xs font-medium text-teal-700">
                                                {{ lead.assigned_to.name?.charAt(0) }}
                                            </div>
                                            <span class="text-sm text-gray-700">{{ lead.assigned_to.name }}</span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <span class="text-sm text-gray-400 italic">Non assigné</span>
                                    </template>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1 text-sm text-gray-500">
                                        <ClockIcon class="h-4 w-4" />
                                        {{ formatDate(lead.created_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <!-- Modifier -->
                                        <button
                                            @click="editLead(lead)"
                                            class="p-2 text-teal-600 hover:bg-teal-50 rounded-lg transition-colors"
                                            title="Modifier"
                                        >
                                            <PencilSquareIcon class="h-5 w-5" />
                                        </button>
                                        <!-- Convertir -->
                                        <button
                                            v-if="lead.status !== 'converted'"
                                            @click="convertLead(lead)"
                                            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Convertir en client"
                                        >
                                            <CheckCircleIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="leads?.data?.length > 0" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-600">
                            Affichage de <span class="font-semibold">{{ leads.from }}</span> à
                            <span class="font-semibold">{{ leads.to }}</span> sur
                            <span class="font-semibold">{{ leads.total }}</span> résultats
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in leads.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm rounded-lg transition-all',
                                        link.active
                                            ? 'bg-gradient-to-r from-teal-500 to-emerald-500 text-white shadow-sm'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    ]"
                                    :preserve-scroll="true"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-300 cursor-not-allowed"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
