<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    SparklesIcon,
    ChartBarIcon,
    ArrowPathIcon,
    ArrowLeftIcon,
    EnvelopeIcon,
    PhoneIcon,
    BuildingOfficeIcon,
    CalendarIcon,
    LightBulbIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    BoltIcon,
} from '@heroicons/vue/24/outline'
import { FireIcon as FireIconSolid, StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'

const props = defineProps({
    prospect: Object,
    scoreResult: Object,
})

const isRecalculating = ref(false)

const priorityConfig = {
    very_hot: { label: 'Tres Chaud', color: 'bg-red-500', textColor: 'text-red-600', gradient: 'from-red-500 to-orange-500' },
    hot: { label: 'Chaud', color: 'bg-orange-500', textColor: 'text-orange-600', gradient: 'from-orange-500 to-amber-500' },
    warm: { label: 'Tiede', color: 'bg-amber-500', textColor: 'text-amber-600', gradient: 'from-amber-500 to-yellow-500' },
    lukewarm: { label: 'Frais', color: 'bg-lime-500', textColor: 'text-lime-600', gradient: 'from-lime-500 to-green-500' },
    cold: { label: 'Froid', color: 'bg-gray-400', textColor: 'text-gray-500', gradient: 'from-gray-400 to-gray-500' },
}

const getPriorityConfig = (priority) => {
    return priorityConfig[priority] || priorityConfig.cold
}

const categoryLabels = {
    behavior: 'Comportement',
    engagement: 'Engagement',
    profile: 'Profil',
    timing: 'Timing',
    historical: 'Historique',
    ml_adjustment: 'Ajustement IA',
}

const categoryColors = {
    behavior: 'bg-purple-500',
    engagement: 'bg-blue-500',
    profile: 'bg-emerald-500',
    timing: 'bg-amber-500',
    historical: 'bg-pink-500',
    ml_adjustment: 'bg-indigo-500',
}

const recalculateScore = async () => {
    if (isRecalculating.value) return
    isRecalculating.value = true

    try {
        const response = await fetch(route('tenant.crm.ai-scoring.prospects.recalculate', props.prospect.id), {
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
        month: 'long',
        year: 'numeric'
    })
}

const maxBreakdown = Math.max(...Object.values(props.scoreResult?.breakdown || {}), 1)
</script>

<template>
    <TenantLayout :title="`Score IA - ${prospect.first_name} ${prospect.last_name}`">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('tenant.crm.ai-scoring.dashboard')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            {{ prospect.first_name }} {{ prospect.last_name }}
                        </h1>
                        <p class="text-gray-500">{{ prospect.company_name || 'Prospect individuel' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="recalculateScore"
                        :disabled="isRecalculating"
                        class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-700 rounded-lg font-medium hover:bg-purple-200 transition-colors disabled:opacity-50"
                    >
                        <ArrowPathIcon class="h-5 w-5 mr-2" :class="{ 'animate-spin': isRecalculating }" />
                        {{ isRecalculating ? 'Calcul...' : 'Recalculer' }}
                    </button>
                </div>
            </div>

            <!-- Score et recommandation -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Score Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="text-center">
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-40 h-40 transform -rotate-90">
                                <circle cx="80" cy="80" r="70" stroke="#E5E7EB" stroke-width="12" fill="none" />
                                <circle
                                    cx="80" cy="80" r="70"
                                    :stroke="scoreResult?.score >= 85 ? '#DC2626' : scoreResult?.score >= 70 ? '#F97316' : scoreResult?.score >= 50 ? '#F59E0B' : scoreResult?.score >= 30 ? '#84CC16' : '#9CA3AF'"
                                    stroke-width="12" fill="none" stroke-linecap="round"
                                    :stroke-dasharray="`${(scoreResult?.score / 100) * 440} 440`"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-4xl font-bold" :class="getPriorityConfig(scoreResult?.priority).textColor">
                                    {{ scoreResult?.score }}
                                </span>
                                <span class="text-sm text-gray-500">/100</span>
                            </div>
                        </div>

                        <div class="mt-4">
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-white font-semibold text-sm"
                                  :class="getPriorityConfig(scoreResult?.priority).color">
                                <FireIconSolid v-if="scoreResult?.priority === 'very_hot'" class="h-5 w-5" />
                                <StarIconSolid v-else class="h-5 w-5" />
                                {{ getPriorityConfig(scoreResult?.priority).label }}
                            </span>
                        </div>

                        <div class="mt-4 p-4 bg-emerald-50 rounded-xl">
                            <p class="text-sm text-emerald-600 font-medium">Probabilite de Conversion</p>
                            <p class="text-3xl font-bold text-emerald-700 mt-1">{{ scoreResult?.conversion_probability }}%</p>
                        </div>
                    </div>
                </div>

                <!-- Recommandation et Insights -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="bg-gradient-to-r rounded-2xl p-6 text-white shadow-lg" :class="getPriorityConfig(scoreResult?.priority).gradient">
                        <div class="flex items-start gap-4">
                            <div class="p-3 bg-white/20 rounded-xl backdrop-blur-sm">
                                <BoltIcon class="h-6 w-6" />
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold">Action Recommandee</h3>
                                <p class="mt-2 text-white/90 text-lg">{{ scoreResult?.recommendation?.action }}</p>
                                <div class="mt-4 flex gap-3">
                                    <a v-if="prospect.phone" :href="`tel:${prospect.phone}`"
                                       class="inline-flex items-center px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors backdrop-blur-sm">
                                        <PhoneIcon class="h-5 w-5 mr-2" /> Appeler
                                    </a>
                                    <a :href="`mailto:${prospect.email}`"
                                       class="inline-flex items-center px-4 py-2 bg-white/20 rounded-lg hover:bg-white/30 transition-colors backdrop-blur-sm">
                                        <EnvelopeIcon class="h-5 w-5 mr-2" /> Email
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <LightBulbIcon class="h-5 w-5 text-amber-500" />
                            Insights IA
                        </h3>
                        <div class="space-y-3">
                            <div v-for="(insight, index) in scoreResult?.insights" :key="index"
                                 class="p-3 bg-gray-50 rounded-xl text-gray-700">
                                {{ insight }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Breakdown -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <ChartBarIcon class="h-5 w-5 text-purple-500" />
                        Decomposition du Score
                    </h3>
                    <div class="space-y-4">
                        <div v-for="(value, category) in scoreResult?.breakdown" :key="category">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ categoryLabels[category] || category }}</span>
                                <span class="text-sm font-semibold" :class="value > 0 ? 'text-emerald-600' : value < 0 ? 'text-red-600' : 'text-gray-500'">
                                    {{ value > 0 ? '+' : '' }}{{ value }}
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3">
                                <div class="h-3 rounded-full transition-all duration-500"
                                     :class="value >= 0 ? categoryColors[category] : 'bg-red-400'"
                                     :style="{ width: `${Math.min((Math.abs(value) / maxBreakdown) * 100, 100)}%` }">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Facteurs -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <SparklesIcon class="h-5 w-5 text-purple-500" />
                        Facteurs Cles
                    </h3>
                    <div class="space-y-2 max-h-80 overflow-y-auto">
                        <div v-for="(factor, index) in scoreResult?.factors" :key="index"
                             class="flex items-center justify-between p-3 rounded-lg"
                             :class="factor.type === 'positive' ? 'bg-emerald-50' : 'bg-red-50'">
                            <div class="flex items-center gap-2">
                                <CheckCircleIcon v-if="factor.type === 'positive'" class="h-5 w-5 text-emerald-500" />
                                <ExclamationTriangleIcon v-else class="h-5 w-5 text-red-500" />
                                <span class="text-sm text-gray-700">{{ factor.name }}</span>
                            </div>
                            <span class="text-sm font-semibold"
                                  :class="factor.type === 'positive' ? 'text-emerald-600' : 'text-red-600'">
                                {{ factor.impact }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations du prospect -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du Prospect</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <EnvelopeIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Email</p>
                            <a :href="`mailto:${prospect.email}`" class="text-sm font-medium text-purple-600 hover:text-purple-700">
                                {{ prospect.email }}
                            </a>
                        </div>
                    </div>
                    <div v-if="prospect.phone" class="flex items-start gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <PhoneIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Telephone</p>
                            <a :href="`tel:${prospect.phone}`" class="text-sm font-medium text-purple-600 hover:text-purple-700">
                                {{ prospect.phone }}
                            </a>
                        </div>
                    </div>
                    <div v-if="prospect.company_name" class="flex items-start gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <BuildingOfficeIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Entreprise</p>
                            <p class="text-sm font-medium text-gray-900">{{ prospect.company_name }}</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-gray-100 rounded-lg">
                            <CalendarIcon class="h-5 w-5 text-gray-600" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Cree le</p>
                            <p class="text-sm font-medium text-gray-900">{{ formatDate(prospect.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
