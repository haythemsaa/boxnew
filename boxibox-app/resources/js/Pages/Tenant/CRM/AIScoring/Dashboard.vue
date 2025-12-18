<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    SparklesIcon,
    FireIcon,
    ChartBarIcon,
    ArrowPathIcon,
    ArrowTrendingUpIcon,
    UserGroupIcon,
    LightBulbIcon,
    RocketLaunchIcon,
    BoltIcon,
    EyeIcon,
    PhoneIcon,
    EnvelopeIcon,
    CpuChipIcon,
    BeakerIcon,
    ChartPieIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    stats: Object,
    hotLeads: Array,
    hotProspects: Array,
    scoreDistribution: Object,
    conversionFunnel: Object,
    topSources: Array,
})

const isRecalculating = ref(false)

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

const totalLeadsByPriority = computed(() => {
    return Object.entries(props.conversionFunnel || {}).reduce((sum, [_, data]) => sum + (data.total || 0), 0)
})

const recalculateAllScores = async () => {
    if (isRecalculating.value) return
    isRecalculating.value = true

    try {
        const response = await fetch(route('tenant.crm.ai-scoring.batch-recalculate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            },
        })
        const data = await response.json()
        if (data.success) {
            router.reload()
        }
    } catch (error) {
        console.error('Erreur lors du recalcul:', error)
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

const getScoreColor = (score) => {
    if (score >= 85) return 'text-red-600'
    if (score >= 70) return 'text-orange-600'
    if (score >= 50) return 'text-amber-600'
    if (score >= 30) return 'text-lime-600'
    return 'text-gray-500'
}

const maxDistribution = computed(() => {
    return Math.max(...Object.values(props.scoreDistribution || {}), 1)
})
</script>

<template>
    <TenantLayout title="IA Lead Scoring">
        <div class="space-y-6">
            <!-- Header avec gradient IA -->
            <div class="relative overflow-hidden bg-gradient-to-br from-violet-600 via-purple-600 to-indigo-700 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <!-- Animated background elements -->
                <div class="absolute -top-24 -right-24 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-white/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>
                <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-fuchsia-500/20 rounded-full blur-2xl animate-bounce" style="animation-duration: 3s"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <CpuChipIcon class="h-8 w-8 text-white" />
                                </div>
                                Lead Scoring IA
                            </h1>
                            <p class="mt-2 text-purple-200">
                                Intelligence artificielle pour prioriser vos leads
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button
                                @click="recalculateAllScores"
                                :disabled="isRecalculating"
                                class="inline-flex items-center px-5 py-2.5 bg-white/20 text-white rounded-xl font-medium hover:bg-white/30 transition-all backdrop-blur-sm border border-white/20 disabled:opacity-50"
                            >
                                <ArrowPathIcon class="h-5 w-5 mr-2" :class="{ 'animate-spin': isRecalculating }" />
                                {{ isRecalculating ? 'Calcul en cours...' : 'Recalculer tous les scores' }}
                            </button>
                            <Link
                                :href="route('tenant.crm.ai-scoring.recommendations')"
                                class="inline-flex items-center px-6 py-2.5 bg-white text-purple-600 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200"
                            >
                                <LightBulbIcon class="h-5 w-5 mr-2" />
                                Recommandations
                            </Link>
                        </div>
                    </div>

                    <!-- Stats principales -->
                    <div class="mt-8 grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.total_leads || 0 }}</p>
                            <p class="text-xs text-purple-200 font-medium mt-1">Total Leads</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ (stats?.very_hot || 0) + (stats?.hot || 0) }}
                                <FireIconSolid class="h-6 w-6 ml-2 text-orange-300" />
                            </p>
                            <p class="text-xs text-purple-200 font-medium mt-1">Leads Chauds</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white">{{ stats?.avg_score || 0 }}</p>
                            <p class="text-xs text-purple-200 font-medium mt-1">Score Moyen</p>
                        </div>
                        <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center border border-white/20">
                            <p class="text-3xl font-bold text-white flex items-center justify-center">
                                {{ stats?.avg_conversion_probability || 0 }}%
                                <ArrowTrendingUpIcon class="h-5 w-5 ml-2 text-emerald-300" />
                            </p>
                            <p class="text-xs text-purple-200 font-medium mt-1">Proba. Conversion</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation rapide -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link
                    :href="route('tenant.crm.ai-scoring.leads')"
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-purple-200 transition-all"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
                            <UserGroupIcon class="h-6 w-6 text-purple-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Leads Scores</p>
                            <p class="text-sm text-gray-500">Voir tous les scores</p>
                        </div>
                    </div>
                </Link>
                <Link
                    :href="route('tenant.crm.ai-scoring.recommendations')"
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-amber-200 transition-all"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 rounded-xl group-hover:bg-amber-200 transition-colors">
                            <LightBulbIcon class="h-6 w-6 text-amber-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Recommandations</p>
                            <p class="text-sm text-gray-500">Actions prioritaires</p>
                        </div>
                    </div>
                </Link>
                <Link
                    :href="route('tenant.crm.ai-scoring.analytics')"
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-emerald-200 transition-all"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-emerald-100 rounded-xl group-hover:bg-emerald-200 transition-colors">
                            <ChartPieIcon class="h-6 w-6 text-emerald-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Analytics</p>
                            <p class="text-sm text-gray-500">Performances IA</p>
                        </div>
                    </div>
                </Link>
                <Link
                    :href="route('tenant.crm.churn-risk')"
                    class="group bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md hover:border-red-200 transition-all"
                >
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-red-100 rounded-xl group-hover:bg-red-200 transition-colors">
                            <BeakerIcon class="h-6 w-6 text-red-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">Risque Churn</p>
                            <p class="text-sm text-gray-500">Clients a risque</p>
                        </div>
                    </div>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Distribution des scores -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <ChartBarIcon class="h-5 w-5 text-purple-500" />
                        Distribution des Scores
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(count, range) in scoreDistribution" :key="range" class="flex items-center gap-3">
                            <span class="text-sm text-gray-600 w-16">{{ range }}</span>
                            <div class="flex-1 bg-gray-100 rounded-full h-6 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500 flex items-center justify-end pr-2"
                                    :class="range.startsWith('81') ? 'bg-gradient-to-r from-red-500 to-red-400' :
                                            range.startsWith('61') ? 'bg-gradient-to-r from-orange-500 to-orange-400' :
                                            range.startsWith('41') ? 'bg-gradient-to-r from-amber-500 to-amber-400' :
                                            range.startsWith('21') ? 'bg-gradient-to-r from-lime-500 to-lime-400' :
                                            'bg-gradient-to-r from-gray-400 to-gray-300'"
                                    :style="{ width: `${(count / maxDistribution) * 100}%` }"
                                >
                                    <span v-if="count > 0" class="text-xs font-semibold text-white">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Funnel de conversion par priorite -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                        <RocketLaunchIcon class="h-5 w-5 text-purple-500" />
                        Conversion par Priorite
                    </h3>
                    <div class="space-y-3">
                        <div v-for="(data, priority) in conversionFunnel" :key="priority" class="flex items-center gap-3">
                            <div class="flex items-center gap-2 w-28">
                                <component :is="getPriorityConfig(priority).icon" class="h-5 w-5" :class="getPriorityConfig(priority).textColor" />
                                <span class="text-sm font-medium" :class="getPriorityConfig(priority).textColor">
                                    {{ getPriorityConfig(priority).label }}
                                </span>
                            </div>
                            <div class="flex-1 flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r"
                                        :class="getPriorityConfig(priority).gradient"
                                        :style="{ width: `${data.rate}%` }"
                                    ></div>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 w-16 text-right">{{ data.rate }}%</span>
                            </div>
                            <span class="text-xs text-gray-500 w-20 text-right">{{ data.converted }}/{{ data.total }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leads Chauds -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                        <FireIconSolid class="h-5 w-5 text-red-500" />
                        Leads Tres Chauds
                        <span class="text-sm font-normal text-gray-500 ml-2">Contactez-les en priorite</span>
                    </h3>
                    <Link
                        :href="route('tenant.crm.ai-scoring.leads', { priority: 'very_hot' })"
                        class="text-sm text-purple-600 hover:text-purple-700 font-medium"
                    >
                        Voir tous &rarr;
                    </Link>
                </div>

                <div v-if="hotLeads?.length > 0" class="divide-y divide-gray-50">
                    <Link
                        v-for="lead in hotLeads"
                        :key="lead.id"
                        :href="route('tenant.crm.ai-scoring.leads.show', lead.id)"
                        class="flex items-center gap-4 px-6 py-4 hover:bg-purple-50/50 transition-colors"
                    >
                        <!-- Avatar avec score -->
                        <div class="relative">
                            <div class="h-12 w-12 rounded-full bg-gradient-to-br flex items-center justify-center text-white font-bold shadow-md"
                                 :class="getPriorityConfig(lead.priority).gradient">
                                {{ lead.score }}
                            </div>
                            <div class="absolute -top-1 -right-1">
                                <FireIconSolid class="h-5 w-5 text-red-500 drop-shadow" />
                            </div>
                        </div>

                        <!-- Info lead -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">
                                {{ lead.first_name }} {{ lead.last_name }}
                                <span v-if="lead.company" class="text-gray-500 font-normal">- {{ lead.company }}</span>
                            </p>
                            <div class="flex items-center gap-3 mt-1">
                                <span class="text-xs text-gray-500 flex items-center gap-1">
                                    <EnvelopeIcon class="h-3 w-3" />
                                    {{ lead.email }}
                                </span>
                                <span v-if="lead.phone" class="text-xs text-gray-500 flex items-center gap-1">
                                    <PhoneIcon class="h-3 w-3" />
                                    {{ lead.phone }}
                                </span>
                            </div>
                        </div>

                        <!-- Probabilite de conversion -->
                        <div class="text-right">
                            <p class="text-sm font-semibold text-emerald-600">
                                {{ lead.conversion_probability || 0 }}%
                            </p>
                            <p class="text-xs text-gray-500">Proba. conversion</p>
                        </div>

                        <!-- Action recommandee -->
                        <div class="hidden md:block text-right">
                            <span class="inline-flex items-center gap-1 px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full">
                                <BoltIcon class="h-3 w-3" />
                                Appeler maintenant
                            </span>
                        </div>

                        <EyeIcon class="h-5 w-5 text-gray-400" />
                    </Link>
                </div>
                <div v-else class="px-6 py-12 text-center">
                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <SparklesIcon class="h-8 w-8 text-gray-400" />
                    </div>
                    <p class="text-gray-500">Aucun lead tres chaud pour le moment</p>
                    <p class="text-sm text-gray-400 mt-1">Les leads avec un score &ge; 85 apparaitront ici</p>
                </div>
            </div>

            <!-- Sources les plus performantes -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                    <ArrowTrendingUpIcon class="h-5 w-5 text-emerald-500" />
                    Sources les Plus Performantes
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                                <th class="text-left py-2">Source</th>
                                <th class="text-center py-2">Total</th>
                                <th class="text-center py-2">Convertis</th>
                                <th class="text-center py-2">Taux</th>
                                <th class="text-center py-2">Score Moyen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <tr v-for="source in topSources" :key="source.source" class="hover:bg-gray-50">
                                <td class="py-3">
                                    <span class="font-medium text-gray-900 capitalize">{{ source.source || 'Direct' }}</span>
                                </td>
                                <td class="py-3 text-center text-gray-600">{{ source.total }}</td>
                                <td class="py-3 text-center">
                                    <span class="font-semibold text-emerald-600">{{ source.converted }}</span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                                          :class="source.rate >= 20 ? 'bg-emerald-100 text-emerald-700' :
                                                  source.rate >= 10 ? 'bg-amber-100 text-amber-700' :
                                                  'bg-gray-100 text-gray-600'">
                                        {{ source.rate }}%
                                    </span>
                                </td>
                                <td class="py-3 text-center">
                                    <span class="font-semibold" :class="getScoreColor(source.avg_score)">
                                        {{ source.avg_score }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
