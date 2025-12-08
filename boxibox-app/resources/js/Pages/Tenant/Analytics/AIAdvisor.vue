<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    SparklesIcon,
    ArrowPathIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    ExclamationTriangleIcon,
    ExclamationCircleIcon,
    InformationCircleIcon,
    CheckCircleIcon,
    LightBulbIcon,
    ChartBarIcon,
    CurrencyEuroIcon,
    UserGroupIcon,
    BuildingStorefrontIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    advice: Object,
})

const loading = ref(false)
const selectedCategory = ref('all')
const expandedRecommendations = ref([])

const categories = [
    { id: 'all', name: 'Toutes', icon: SparklesIcon },
    { id: 'occupancy', name: 'Occupation', icon: BuildingStorefrontIcon },
    { id: 'revenue', name: 'Revenus', icon: CurrencyEuroIcon },
    { id: 'payments', name: 'Paiements', icon: ChartBarIcon },
    { id: 'conversion', name: 'Conversion', icon: ArrowTrendingUpIcon },
    { id: 'retention', name: 'Retention', icon: UserGroupIcon },
]

const filteredRecommendations = computed(() => {
    if (!props.advice?.recommendations) return []
    if (selectedCategory.value === 'all') {
        return props.advice.recommendations
    }
    return props.advice.recommendations.filter(r => r.category === selectedCategory.value)
})

const toggleExpand = (id) => {
    const index = expandedRecommendations.value.indexOf(id)
    if (index > -1) {
        expandedRecommendations.value.splice(index, 1)
    } else {
        expandedRecommendations.value.push(id)
    }
}

const isExpanded = (id) => expandedRecommendations.value.includes(id)

const refreshAdvice = async () => {
    loading.value = true
    router.reload({ only: ['advice'] })
    setTimeout(() => loading.value = false, 1000)
}

const getPriorityIcon = (priority) => {
    const icons = {
        critical: ExclamationTriangleIcon,
        high: ExclamationCircleIcon,
        medium: InformationCircleIcon,
        low: CheckCircleIcon,
    }
    return icons[priority] || InformationCircleIcon
}

const getPriorityColor = (priority) => {
    const colors = {
        critical: 'text-red-400 bg-red-500/20',
        high: 'text-orange-400 bg-orange-500/20',
        medium: 'text-yellow-400 bg-yellow-500/20',
        low: 'text-green-400 bg-green-500/20',
    }
    return colors[priority] || 'text-gray-400 bg-gray-500/20'
}

const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-400'
    if (score >= 60) return 'text-yellow-400'
    if (score >= 40) return 'text-orange-400'
    return 'text-red-400'
}

const getGradeColor = (grade) => {
    const colors = {
        A: 'bg-green-500',
        B: 'bg-blue-500',
        C: 'bg-yellow-500',
        D: 'bg-orange-500',
        F: 'bg-red-500',
    }
    return colors[grade] || 'bg-gray-500'
}

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
    }).format(value || 0)
}
</script>

<template>
    <Head title="Conseiller IA" />

    <TenantLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <SparklesIcon class="h-8 w-8 text-purple-400" />
                        Conseiller IA Business
                    </h1>
                    <p class="mt-1 text-sm text-gray-400">
                        Recommandations intelligentes pour optimiser votre activite
                    </p>
                </div>
                <button
                    @click="refreshAdvice"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors disabled:opacity-50"
                >
                    <ArrowPathIcon :class="['h-5 w-5', loading && 'animate-spin']" />
                    Actualiser
                </button>
            </div>

            <!-- Score Card -->
            <div class="bg-gradient-to-r from-purple-900/50 to-blue-900/50 rounded-xl border border-purple-500/30 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm text-gray-400">Score de Sante Business</div>
                        <div class="mt-2 flex items-baseline gap-3">
                            <span :class="['text-5xl font-bold', getScoreColor(advice?.score?.value || 0)]">
                                {{ advice?.score?.value || 0 }}
                            </span>
                            <span class="text-2xl text-gray-500">/100</span>
                            <span :class="[getGradeColor(advice?.score?.grade), 'px-3 py-1 text-lg font-bold text-white rounded-lg']">
                                {{ advice?.score?.grade || 'N/A' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-gray-400">{{ advice?.score?.interpretation }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-gray-400">Recommandations</div>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-400">
                                    {{ advice?.recommendations?.filter(r => r.priority === 'critical').length || 0 }}
                                </div>
                                <div class="text-xs text-gray-500">Critiques</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-400">
                                    {{ advice?.recommendations?.filter(r => r.priority === 'high').length || 0 }}
                                </div>
                                <div class="text-xs text-gray-500">Importantes</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-yellow-400">
                                    {{ advice?.recommendations?.filter(r => r.priority === 'medium').length || 0 }}
                                </div>
                                <div class="text-xs text-gray-500">Moyennes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-500/20 rounded-lg">
                            <BuildingStorefrontIcon class="h-6 w-6 text-blue-400" />
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Taux d'Occupation</div>
                            <div class="text-2xl font-bold text-white">{{ advice?.metrics?.occupancy_rate || 0 }}%</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-green-500/20 rounded-lg">
                            <CurrencyEuroIcon class="h-6 w-6 text-green-400" />
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Revenus du Mois</div>
                            <div class="text-2xl font-bold text-white">{{ formatCurrency(advice?.metrics?.monthly_revenue) }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-red-500/20 rounded-lg">
                            <ExclamationCircleIcon class="h-6 w-6 text-red-400" />
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Impayes</div>
                            <div class="text-2xl font-bold text-white">{{ formatCurrency(advice?.metrics?.total_overdue) }}</div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-purple-500/20 rounded-lg">
                            <ArrowTrendingUpIcon class="h-6 w-6 text-purple-400" />
                        </div>
                        <div>
                            <div class="text-sm text-gray-400">Taux de Conversion</div>
                            <div class="text-2xl font-bold text-white">{{ advice?.metrics?.prospect_conversion_rate || 0 }}%</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.id"
                    :class="[
                        selectedCategory === cat.id ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap'
                    ]"
                >
                    <component :is="cat.icon" class="h-4 w-4" />
                    {{ cat.name }}
                </button>
            </div>

            <!-- Recommendations List -->
            <div class="space-y-4">
                <div
                    v-for="rec in filteredRecommendations"
                    :key="rec.id"
                    class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden"
                >
                    <!-- Header -->
                    <div
                        @click="toggleExpand(rec.id)"
                        class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-center gap-4">
                            <div :class="[getPriorityColor(rec.priority), 'p-2 rounded-lg']">
                                <component :is="getPriorityIcon(rec.priority)" class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="font-medium text-white">{{ rec.title }}</h3>
                                <p class="text-sm text-gray-400">{{ rec.message }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="rec.potential_impact" class="text-sm text-green-400 font-medium">
                                {{ rec.potential_impact }}
                            </span>
                            <component
                                :is="isExpanded(rec.id) ? ChevronUpIcon : ChevronDownIcon"
                                class="h-5 w-5 text-gray-400"
                            />
                        </div>
                    </div>

                    <!-- Expanded Content -->
                    <div v-if="isExpanded(rec.id)" class="px-4 pb-4 border-t border-gray-700 pt-4">
                        <div class="flex items-start gap-3">
                            <LightBulbIcon class="h-5 w-5 text-yellow-400 flex-shrink-0 mt-0.5" />
                            <div>
                                <h4 class="font-medium text-white mb-2">Actions Recommandees</h4>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(action, idx) in rec.actions"
                                        :key="idx"
                                        class="flex items-start gap-2 text-sm text-gray-300"
                                    >
                                        <span class="text-purple-400 font-medium">{{ idx + 1 }}.</span>
                                        {{ action }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Quick Action Link -->
                        <div v-if="rec.action_link" class="mt-4">
                            <Link
                                :href="rec.action_link"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors"
                            >
                                Agir maintenant
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredRecommendations.length === 0" class="bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                    <CheckCircleIcon class="mx-auto h-12 w-12 text-green-400" />
                    <h3 class="mt-2 text-sm font-medium text-white">Aucune recommandation</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Votre business est en bonne sante dans cette categorie !
                    </p>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
