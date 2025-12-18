<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    ChartBarIcon,
    ChartPieIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    CheckCircleIcon,
    XCircleIcon,
    SparklesIcon,
    CalendarIcon,
    BeakerIcon,
    LightBulbIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    period: [String, Number],
    conversionByPriority: Object,
    scoreAccuracy: Object,
    leadVelocity: Array,
    topFactors: Array,
    stats: Object,
})

const selectedPeriod = ref(props.period || 30)

const changePeriod = (newPeriod) => {
    selectedPeriod.value = newPeriod
    router.get(route('tenant.crm.ai-scoring.analytics'), { period: newPeriod }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const priorityConfig = {
    very_hot: { label: 'Tres Chaud', color: 'bg-red-500', textColor: 'text-red-600' },
    hot: { label: 'Chaud', color: 'bg-orange-500', textColor: 'text-orange-600' },
    warm: { label: 'Tiede', color: 'bg-amber-500', textColor: 'text-amber-600' },
    lukewarm: { label: 'Frais', color: 'bg-lime-500', textColor: 'text-lime-600' },
    cold: { label: 'Froid', color: 'bg-gray-400', textColor: 'text-gray-500' },
}

const factorLabels = {
    quote_requested: 'Devis demande',
    visit_scheduled: 'Visite planifiee',
    calculator_used: 'Calculateur utilise',
    referral: 'Parrainage',
    business_customer: 'Client entreprise',
    high_budget: 'Budget eleve',
}

const maxVelocity = computed(() => {
    return Math.max(...(props.leadVelocity || []).map(v => v.count), 1)
})

const maxFactorPercentage = computed(() => {
    return Math.max(...(props.topFactors || []).map(f => f.percentage), 1)
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
    })
}
</script>

<template>
    <TenantLayout title="Analytics IA - Lead Scoring">
        <div class="space-y-6">
            <!-- Header -->
            <div class="relative overflow-hidden bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-700 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>

                <div class="relative px-6 py-8 sm:px-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                                <div class="p-2 bg-white/20 rounded-xl backdrop-blur-sm">
                                    <ChartPieIcon class="h-8 w-8 text-white" />
                                </div>
                                Analytics IA
                            </h1>
                            <p class="mt-2 text-emerald-100">
                                Performances et precision du scoring IA
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-for="p in [7, 30, 90]"
                                :key="p"
                                @click="changePeriod(p)"
                                class="px-4 py-2 rounded-lg font-medium transition-all"
                                :class="selectedPeriod == p
                                    ? 'bg-white text-emerald-600 shadow-lg'
                                    : 'bg-white/20 text-white hover:bg-white/30'"
                            >
                                {{ p }} jours
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Precision du scoring -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-6">
                        <BeakerIcon class="h-5 w-5 text-emerald-500" />
                        Precision du Scoring IA
                    </h3>

                    <div class="grid grid-cols-3 gap-6">
                        <div class="text-center p-4 bg-emerald-50 rounded-xl">
                            <p class="text-3xl font-bold text-emerald-600">{{ scoreAccuracy?.avg_score_converted || 0 }}</p>
                            <p class="text-sm text-gray-600 mt-1">Score moyen des convertis</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-xl">
                            <p class="text-3xl font-bold text-red-600">{{ scoreAccuracy?.avg_score_not_converted || 0 }}</p>
                            <p class="text-sm text-gray-600 mt-1">Score moyen des perdus</p>
                        </div>
                        <div class="text-center p-4 rounded-xl" :class="scoreAccuracy?.score_difference > 0 ? 'bg-emerald-100' : 'bg-amber-100'">
                            <p class="text-3xl font-bold" :class="scoreAccuracy?.score_difference > 0 ? 'text-emerald-700' : 'text-amber-700'">
                                {{ scoreAccuracy?.score_difference > 0 ? '+' : '' }}{{ scoreAccuracy?.score_difference || 0 }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1">Difference</p>
                        </div>
                    </div>

                    <div class="mt-6 p-4 rounded-xl" :class="scoreAccuracy?.accuracy_indicator === 'good' ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
                        <div class="flex items-center gap-3">
                            <CheckCircleIcon v-if="scoreAccuracy?.accuracy_indicator === 'good'" class="h-6 w-6 text-emerald-500" />
                            <LightBulbIcon v-else class="h-6 w-6 text-amber-500" />
                            <div>
                                <p class="font-medium" :class="scoreAccuracy?.accuracy_indicator === 'good' ? 'text-emerald-700' : 'text-amber-700'">
                                    {{ scoreAccuracy?.accuracy_indicator === 'good' ? 'Scoring performant!' : 'Scoring a ameliorer' }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ scoreAccuracy?.accuracy_indicator === 'good'
                                        ? 'L\'IA discrimine correctement entre leads convertis et perdus.'
                                        : 'Plus de donnees sont necessaires pour affiner le modele.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats rapides -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistiques</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Total leads</span>
                            <span class="font-bold text-gray-900">{{ stats?.total_leads || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Score moyen</span>
                            <span class="font-bold text-purple-600">{{ stats?.avg_score || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-gray-600">Proba. moyenne</span>
                            <span class="font-bold text-emerald-600">{{ stats?.avg_conversion_probability || 0 }}%</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-lg">
                            <span class="text-gray-600">Haute proba. (&ge;50%)</span>
                            <span class="font-bold text-emerald-700">{{ stats?.high_probability_leads || 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Taux de conversion par priorite -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <ChartBarIcon class="h-5 w-5 text-purple-500" />
                        Taux de Conversion par Priorite
                    </h3>
                    <div class="space-y-4">
                        <div v-for="(data, priority) in conversionByPriority" :key="priority" class="space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full" :class="priorityConfig[priority]?.color || 'bg-gray-400'"></div>
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ priorityConfig[priority]?.label || priority }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-semibold" :class="data.rate >= 20 ? 'text-emerald-600' : data.rate >= 10 ? 'text-amber-600' : 'text-gray-500'">
                                        {{ data.rate }}%
                                    </span>
                                    <span class="text-xs text-gray-400 ml-2">({{ data.converted }}/{{ data.total }})</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5">
                                <div
                                    class="h-2.5 rounded-full transition-all duration-500"
                                    :class="priorityConfig[priority]?.color || 'bg-gray-400'"
                                    :style="{ width: `${Math.min(data.rate * 2, 100)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-purple-50 rounded-lg text-sm text-purple-700">
                        <span class="font-medium">Insight:</span>
                        Les leads "Tres Chaud" devraient avoir un taux de conversion &gt;30% pour un scoring optimal.
                    </div>
                </div>

                <!-- Facteurs de conversion -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <SparklesIcon class="h-5 w-5 text-amber-500" />
                        Top Facteurs de Conversion
                    </h3>
                    <div class="space-y-3">
                        <div v-for="factor in topFactors" :key="factor.factor" class="flex items-center gap-3">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">
                                        {{ factorLabels[factor.factor] || factor.factor }}
                                    </span>
                                    <span class="text-sm text-gray-500">{{ factor.count }} leads</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div
                                        class="h-2 rounded-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500"
                                        :style="{ width: `${(factor.percentage / maxFactorPercentage) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-amber-600 w-12 text-right">{{ factor.percentage }}%</span>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-amber-50 rounded-lg text-sm text-amber-700">
                        <span class="font-medium">Insight:</span>
                        Concentrez vos efforts sur les leads ayant ces caracteristiques pour maximiser les conversions.
                    </div>
                </div>
            </div>

            <!-- Velocite des leads -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                    <CalendarIcon class="h-5 w-5 text-emerald-500" />
                    Velocite des Leads ({{ selectedPeriod }} derniers jours)
                </h3>
                <div class="overflow-x-auto">
                    <div class="flex items-end gap-1 h-40 min-w-max">
                        <div
                            v-for="day in leadVelocity"
                            :key="day.date"
                            class="flex flex-col items-center gap-1"
                            style="min-width: 40px;"
                        >
                            <span class="text-xs text-gray-500">{{ day.count }}</span>
                            <div
                                class="w-8 bg-gradient-to-t from-emerald-500 to-teal-400 rounded-t-sm transition-all duration-300 hover:from-emerald-600 hover:to-teal-500"
                                :style="{ height: `${(day.count / maxVelocity) * 100}px`, minHeight: '4px' }"
                                :title="`${day.count} leads - Score moyen: ${Math.round(day.avg_score)}`"
                            ></div>
                            <span class="text-xs text-gray-400 transform -rotate-45 origin-top-left mt-1">
                                {{ formatDate(day.date) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-emerald-600">
                            {{ leadVelocity?.reduce((sum, d) => sum + d.count, 0) || 0 }}
                        </p>
                        <p class="text-sm text-gray-500">Total leads</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-purple-600">
                            {{ Math.round(leadVelocity?.reduce((sum, d) => sum + (d.avg_score || 0), 0) / (leadVelocity?.length || 1)) || 0 }}
                        </p>
                        <p class="text-sm text-gray-500">Score moyen</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-amber-600">
                            {{ Math.round(leadVelocity?.reduce((sum, d) => sum + d.count, 0) / (leadVelocity?.length || 1) * 10) / 10 || 0 }}
                        </p>
                        <p class="text-sm text-gray-500">Leads/jour moyen</p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
