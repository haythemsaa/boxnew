<script setup>
import { ref, reactive, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    SparklesIcon,
    FireIcon,
    ChartBarIcon,
    ArrowPathIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    EyeIcon,
    PhoneIcon,
    EnvelopeIcon,
    UserGroupIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    leads: Object,
    stats: Object,
    filters: Object,
})

const isRecalculating = ref(false)
const localFilters = reactive({
    priority: props.filters?.priority || '',
    min_score: props.filters?.min_score || '',
})

const priorityConfig = {
    very_hot: { label: 'Tres Chaud', color: 'bg-red-500', textColor: 'text-red-600', icon: FireIconSolid, gradient: 'from-red-500 to-orange-500' },
    hot: { label: 'Chaud', color: 'bg-orange-500', textColor: 'text-orange-600', icon: FireIcon, gradient: 'from-orange-500 to-amber-500' },
    warm: { label: 'Tiede', color: 'bg-amber-500', textColor: 'text-amber-600', icon: StarIconSolid, gradient: 'from-amber-500 to-yellow-500' },
    lukewarm: { label: 'Frais', color: 'bg-lime-500', textColor: 'text-lime-600', icon: ChartBarIcon, gradient: 'from-lime-500 to-green-500' },
    cold: { label: 'Froid', color: 'bg-gray-400', textColor: 'text-gray-500', icon: ChartBarIcon, gradient: 'from-gray-400 to-gray-500' },
}

const getPriorityConfig = (priority) => {
    return priorityConfig[priority] || priorityConfig.cold
}

const applyFilters = () => {
    router.get(route('tenant.crm.ai-scoring.leads'), {
        priority: localFilters.priority || undefined,
        min_score: localFilters.min_score || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    localFilters.priority = ''
    localFilters.min_score = ''
    router.get(route('tenant.crm.ai-scoring.leads'))
}

const hasFilters = computed(() => {
    return localFilters.priority || localFilters.min_score
})

const recalculateLead = async (leadId) => {
    isRecalculating.value = leadId
    try {
        await fetch(route('tenant.crm.ai-scoring.leads.recalculate', leadId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        })
        router.reload()
    } catch (error) {
        console.error('Erreur:', error)
    } finally {
        isRecalculating.value = false
    }
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    })
}
</script>

<template>
    <TenantLayout title="Leads - Score IA">
        <div class="space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <SparklesIcon class="h-8 w-8 text-white" />
                                </div>
                                Leads avec Score IA
                            </h1>
                            <p class="mt-2 text-purple-200">
                                Tous vos leads tries par score de conversion
                            </p>
                        </div>
                        <Link
                            :href="route('tenant.crm.ai-scoring.dashboard')"
                            class="inline-flex items-center px-6 py-2.5 bg-white text-purple-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <ChartBarIcon class="h-5 w-5 mr-2" />
                            Dashboard IA
                        </Link>
                    </div>

                    <!-- Stats rapides -->
                    <div class="mt-6 grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-white">{{ stats?.total_leads || 0 }}</p>
                            <p class="text-xs text-purple-200">Total</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-red-300">{{ stats?.very_hot || 0 }}</p>
                            <p class="text-xs text-purple-200">Tres Chauds</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-orange-300">{{ stats?.hot || 0 }}</p>
                            <p class="text-xs text-purple-200">Chauds</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-amber-300">{{ stats?.warm || 0 }}</p>
                            <p class="text-xs text-purple-200">Tiedes</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-3 text-center border border-white/20">
                            <p class="text-2xl font-bold text-gray-300">{{ stats?.cold || 0 }}</p>
                            <p class="text-xs text-purple-200">Froids</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex flex-col lg:flex-row gap-4 items-center">
                    <div class="flex items-center gap-2 text-gray-500">
                        <FunnelIcon class="h-5 w-5" />
                        <span class="text-sm font-medium">Filtres:</span>
                    </div>

                    <select
                        v-model="localFilters.priority"
                        @change="applyFilters"
                        class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                    >
                        <option value="">Toutes priorites</option>
                        <option value="very_hot">Tres Chaud</option>
                        <option value="hot">Chaud</option>
                        <option value="warm">Tiede</option>
                        <option value="lukewarm">Frais</option>
                        <option value="cold">Froid</option>
                    </select>

                    <select
                        v-model="localFilters.min_score"
                        @change="applyFilters"
                        class="px-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                    >
                        <option value="">Score minimum</option>
                        <option value="80">Score &ge; 80</option>
                        <option value="60">Score &ge; 60</option>
                        <option value="40">Score &ge; 40</option>
                        <option value="20">Score &ge; 20</option>
                    </select>

                    <button
                        v-if="hasFilters"
                        @click="clearFilters"
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
                    >
                        <ArrowPathIcon class="h-4 w-4" />
                        Reinitialiser
                    </button>

                    <div class="flex-1"></div>

                    <p class="text-sm text-gray-500">
                        {{ leads?.total || 0 }} leads
                    </p>
                </div>
            </div>

            <!-- Liste des leads -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div v-if="leads?.data?.length > 0">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Score</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Lead</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Priorite</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Proba. Conversion</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Source</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr
                                v-for="lead in leads.data"
                                :key="lead.id"
                                class="hover:bg-purple-50/50 transition-colors"
                            >
                                <!-- Score -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-12 h-12 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold shadow-md"
                                            :class="getPriorityConfig(lead.priority).gradient"
                                        >
                                            {{ lead.score || 0 }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Lead info -->
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">
                                            {{ lead.first_name }} {{ lead.last_name }}
                                        </p>
                                        <div class="flex items-center gap-3 mt-1 text-sm text-gray-500">
                                            <a :href="`mailto:${lead.email}`" class="flex items-center gap-1 hover:text-purple-600">
                                                <EnvelopeIcon class="h-4 w-4" />
                                                {{ lead.email }}
                                            </a>
                                            <a v-if="lead.phone" :href="`tel:${lead.phone}`" class="flex items-center gap-1 hover:text-purple-600">
                                                <PhoneIcon class="h-4 w-4" />
                                                {{ lead.phone }}
                                            </a>
                                        </div>
                                    </div>
                                </td>

                                <!-- Priorite -->
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold text-white"
                                        :class="getPriorityConfig(lead.priority).color"
                                    >
                                        <component :is="getPriorityConfig(lead.priority).icon" class="h-4 w-4" />
                                        {{ getPriorityConfig(lead.priority).label }}
                                    </span>
                                </td>

                                <!-- Probabilite -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-100 rounded-full h-2 w-24">
                                            <div
                                                class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                                :style="{ width: `${lead.conversion_probability || 0}%` }"
                                            ></div>
                                        </div>
                                        <span class="text-sm font-semibold text-emerald-600">
                                            {{ lead.conversion_probability || 0 }}%
                                        </span>
                                    </div>
                                </td>

                                <!-- Source -->
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600 capitalize">{{ lead.source || '-' }}</span>
                                </td>

                                <!-- Date -->
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500">{{ formatDate(lead.created_at) }}</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="recalculateLead(lead.id)"
                                            :disabled="isRecalculating === lead.id"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="Recalculer le score"
                                        >
                                            <ArrowPathIcon class="h-5 w-5" :class="{ 'animate-spin': isRecalculating === lead.id }" />
                                        </button>
                                        <Link
                                            :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors"
                                            title="Voir les details IA"
                                        >
                                            <EyeIcon class="h-5 w-5" />
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="leads?.links" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                Affichage {{ leads.from }} - {{ leads.to }} sur {{ leads.total }}
                            </p>
                            <div class="flex gap-1">
                                <template v-for="link in leads.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 text-sm rounded-lg transition-all',
                                            link.active
                                                ? 'bg-gradient-to-r from-purple-500 to-indigo-500 text-white'
                                                : 'text-gray-600 hover:bg-gray-100'
                                        ]"
                                        preserve-scroll
                                        v-html="link.label"
                                    />
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Etat vide -->
                <div v-else class="py-16 text-center">
                    <div class="mx-auto w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-4">
                        <UserGroupIcon class="h-8 w-8 text-purple-500" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Aucun lead trouve</h3>
                    <p class="text-gray-500">Modifiez vos filtres ou creez de nouveaux leads</p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
