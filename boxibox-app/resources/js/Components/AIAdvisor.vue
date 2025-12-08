<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Header avec score -->
        <div class="relative overflow-hidden">
            <div :class="[
                'px-6 py-5 bg-gradient-to-r',
                getGradeGradient(score?.grade)
            ]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                </svg>
                            </div>
                            <span class="absolute -bottom-1 -right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center text-xs font-bold" :class="getGradeTextColor(score?.grade)">
                                {{ score?.grade || '-' }}
                            </span>
                        </div>
                        <div class="text-white">
                            <h3 class="text-xl font-bold">Conseiller IA Business</h3>
                            <p class="text-white/80 text-sm">Score de santé: {{ score?.total || 0 }}/100</p>
                        </div>
                    </div>
                    <button
                        @click="refreshAdvice"
                        :class="['p-2 rounded-lg bg-white/20 hover:bg-white/30 transition-colors', isLoading ? 'animate-spin' : '']"
                    >
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>

                <!-- Score breakdown -->
                <div class="mt-4 grid grid-cols-5 gap-2">
                    <div
                        v-for="(detail, key) in score?.details"
                        :key="key"
                        class="bg-white/10 rounded-lg p-2 text-center"
                    >
                        <div class="text-xs text-white/70 capitalize">{{ key }}</div>
                        <div class="text-sm font-bold text-white">{{ detail.score }}/{{ detail.max }}</div>
                    </div>
                </div>
            </div>

            <!-- Decorative pattern -->
            <div class="absolute top-0 right-0 w-32 h-32 opacity-10">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                    <circle cx="50" cy="50" r="40" fill="none" stroke="currentColor" stroke-width="2" class="text-white"/>
                    <circle cx="50" cy="50" r="30" fill="none" stroke="currentColor" stroke-width="2" class="text-white"/>
                    <circle cx="50" cy="50" r="20" fill="none" stroke="currentColor" stroke-width="2" class="text-white"/>
                </svg>
            </div>
        </div>

        <!-- Métriques clés -->
        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold" :class="getMetricColor(metrics?.occupancy_rate, 70, 90)">
                        {{ metrics?.occupancy_rate || 0 }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Occupation</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ formatCurrency(metrics?.monthly_revenue) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Revenus/mois</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold" :class="metrics?.total_overdue > 1000 ? 'text-red-600' : 'text-gray-700 dark:text-gray-300'">
                        {{ formatCurrency(metrics?.total_overdue) }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Impayés</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold" :class="getMetricColor(metrics?.prospect_conversion_rate, 20, 40)">
                        {{ metrics?.prospect_conversion_rate || 0 }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Conversion</div>
                </div>
            </div>
        </div>

        <!-- Conseils -->
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Recommandations prioritaires
                    <span class="text-xs bg-primary-100 text-primary-700 px-2 py-0.5 rounded-full">
                        {{ recommendations?.length || 0 }}
                    </span>
                </h4>
                <div class="flex items-center gap-2">
                    <select
                        v-model="selectedCategory"
                        class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg focus:ring-primary-500"
                    >
                        <option value="all">Toutes catégories</option>
                        <option v-for="cat in categories" :key="cat.key" :value="cat.key">
                            {{ cat.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="isLoading" class="flex items-center justify-center py-12">
                <div class="animate-spin w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full"></div>
            </div>

            <!-- Empty state -->
            <div v-else-if="filteredRecommendations.length === 0" class="text-center py-12">
                <div class="w-20 h-20 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h5 class="font-semibold text-gray-900 dark:text-white">Excellent travail!</h5>
                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
                    Aucune recommandation urgente. Votre business est en bonne santé.
                </p>
            </div>

            <!-- Recommendations list -->
            <div v-else class="space-y-4">
                <div
                    v-for="(rec, index) in filteredRecommendations"
                    :key="index"
                    :class="[
                        'border rounded-xl overflow-hidden transition-all duration-200',
                        expandedCard === index ? 'shadow-lg' : 'hover:shadow-md',
                        getPriorityBorder(rec.priority)
                    ]"
                >
                    <!-- Card header -->
                    <div
                        @click="toggleCard(index)"
                        :class="[
                            'px-4 py-3 cursor-pointer flex items-center justify-between',
                            getPriorityBg(rec.priority)
                        ]"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Priority badge -->
                            <span :class="['px-2 py-1 text-xs font-bold rounded uppercase', getPriorityClass(rec.priority)]">
                                {{ getPriorityLabel(rec.priority) }}
                            </span>
                            <!-- Category icon -->
                            <div :class="['w-8 h-8 rounded-lg flex items-center justify-center', getCategoryBg(rec.color)]">
                                <component :is="getCategoryIcon(rec.icon)" class="w-4 h-4" :class="getCategoryIconColor(rec.color)" />
                            </div>
                            <!-- Title -->
                            <div>
                                <h5 class="font-semibold text-gray-900 dark:text-white">{{ rec.title }}</h5>
                                <span class="text-xs text-gray-500">{{ rec.category_label }}</span>
                            </div>
                        </div>
                        <svg
                            :class="['w-5 h-5 text-gray-400 transition-transform', expandedCard === index ? 'rotate-180' : '']"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    <!-- Card content (expanded) -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="max-h-0 opacity-0"
                        enter-to-class="max-h-[600px] opacity-100"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="max-h-[600px] opacity-100"
                        leave-to-class="max-h-0 opacity-0"
                    >
                        <div v-if="expandedCard === index" class="px-4 py-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <!-- Problem -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-red-600 mb-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    Problème identifié
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ rec.problem }}</p>
                            </div>

                            <!-- Impact -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-orange-600 mb-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                                    </svg>
                                    Impact business
                                </div>
                                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ rec.impact }}</p>
                            </div>

                            <!-- Recommendations -->
                            <div class="mb-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-green-600 mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                                    </svg>
                                    Recommandations
                                </div>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(tip, i) in rec.recommendations"
                                        :key="i"
                                        class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300"
                                    >
                                        <span class="w-5 h-5 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center flex-shrink-0 text-xs font-bold mt-0.5">
                                            {{ i + 1 }}
                                        </span>
                                        {{ tip }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Quick actions -->
                            <div v-if="rec.quick_wins?.length" class="flex flex-wrap gap-2 pt-3 border-t border-gray-200 dark:border-gray-700">
                                <span class="text-xs text-gray-500 mr-2">Actions rapides:</span>
                                <a
                                    v-for="(action, i) in rec.quick_wins"
                                    :key="i"
                                    :href="route(action.route)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-primary-100 text-primary-700 hover:bg-primary-200 dark:bg-primary-900/30 dark:text-primary-300 rounded-lg transition-colors"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                    </svg>
                                    {{ action.action }}
                                </a>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>

        <!-- Footer avec timestamp -->
        <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="text-xs text-gray-500">
                Dernière analyse: {{ generatedAt ? formatDate(generatedAt) : 'Jamais' }}
            </span>
            <a
                :href="route('tenant.analytics.operations')"
                class="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
            >
                Voir les analyses détaillées
            </a>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue'
import axios from 'axios'

// Icon components
const ChartBarIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' })
])
const CurrencyEuroIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M14.121 15.536c-1.171 1.952-3.07 1.952-4.242 0-1.172-1.953-1.172-5.119 0-7.072 1.171-1.952 3.07-1.952 4.242 0M8 10.5h4m-4 3h4m9-1.5a9 9 0 11-18 0 9 9 0 0118 0z' })
])
const CreditCardIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z' })
])
const ArrowTrendingUpIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' })
])
const UsersIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
])
const TagIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z' })
])
const MegaphoneIcon = () => h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24', class: 'w-4 h-4' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' })
])

const props = defineProps({
    initialData: { type: Object, default: null },
})

const isLoading = ref(false)
const metrics = ref({})
const recommendations = ref([])
const score = ref(null)
const generatedAt = ref(null)
const expandedCard = ref(0)
const selectedCategory = ref('all')

const categories = [
    { key: 'occupancy', label: 'Occupation' },
    { key: 'revenue', label: 'Revenus' },
    { key: 'payments', label: 'Impayés' },
    { key: 'conversion', label: 'Conversion' },
    { key: 'retention', label: 'Fidélisation' },
    { key: 'pricing', label: 'Tarification' },
    { key: 'marketing', label: 'Marketing' },
]

const filteredRecommendations = computed(() => {
    if (selectedCategory.value === 'all') {
        return recommendations.value
    }
    return recommendations.value.filter(r => r.category === selectedCategory.value)
})

const fetchAdvice = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(route('tenant.ai-advisor.get'))
        metrics.value = response.data.metrics || {}
        recommendations.value = response.data.recommendations || []
        score.value = response.data.score || null
        generatedAt.value = response.data.generated_at
    } catch (error) {
        console.error('Erreur chargement conseils IA:', error)
    } finally {
        isLoading.value = false
    }
}

const refreshAdvice = () => {
    expandedCard.value = 0
    fetchAdvice()
}

const toggleCard = (index) => {
    expandedCard.value = expandedCard.value === index ? -1 : index
}

// Helpers
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(amount || 0)
}

const formatDate = (date) => {
    return new Date(date).toLocaleString('fr-FR', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const getGradeGradient = (grade) => {
    const gradients = {
        'A': 'from-green-500 to-emerald-600',
        'B': 'from-blue-500 to-indigo-600',
        'C': 'from-yellow-500 to-orange-600',
        'D': 'from-orange-500 to-red-600',
        'F': 'from-red-500 to-rose-700',
    }
    return gradients[grade] || 'from-gray-500 to-gray-600'
}

const getGradeTextColor = (grade) => {
    const colors = {
        'A': 'text-green-600',
        'B': 'text-blue-600',
        'C': 'text-yellow-600',
        'D': 'text-orange-600',
        'F': 'text-red-600',
    }
    return colors[grade] || 'text-gray-600'
}

const getMetricColor = (value, low, high) => {
    if (value >= high) return 'text-green-600'
    if (value >= low) return 'text-yellow-600'
    return 'text-red-600'
}

const getPriorityClass = (priority) => {
    const classes = {
        'critical': 'bg-red-600 text-white',
        'high': 'bg-orange-500 text-white',
        'medium': 'bg-yellow-500 text-gray-900',
        'low': 'bg-blue-500 text-white',
    }
    return classes[priority] || 'bg-gray-500 text-white'
}

const getPriorityLabel = (priority) => {
    const labels = {
        'critical': 'Critique',
        'high': 'Urgent',
        'medium': 'Important',
        'low': 'Info',
    }
    return labels[priority] || priority
}

const getPriorityBorder = (priority) => {
    const borders = {
        'critical': 'border-red-300 dark:border-red-800',
        'high': 'border-orange-300 dark:border-orange-800',
        'medium': 'border-yellow-300 dark:border-yellow-800',
        'low': 'border-blue-300 dark:border-blue-800',
    }
    return borders[priority] || 'border-gray-300'
}

const getPriorityBg = (priority) => {
    const bgs = {
        'critical': 'bg-red-50 dark:bg-red-900/20',
        'high': 'bg-orange-50 dark:bg-orange-900/20',
        'medium': 'bg-yellow-50 dark:bg-yellow-900/20',
        'low': 'bg-blue-50 dark:bg-blue-900/20',
    }
    return bgs[priority] || 'bg-gray-50'
}

const getCategoryBg = (color) => {
    const bgs = {
        'blue': 'bg-blue-100 dark:bg-blue-900/30',
        'green': 'bg-green-100 dark:bg-green-900/30',
        'red': 'bg-red-100 dark:bg-red-900/30',
        'purple': 'bg-purple-100 dark:bg-purple-900/30',
        'orange': 'bg-orange-100 dark:bg-orange-900/30',
        'indigo': 'bg-indigo-100 dark:bg-indigo-900/30',
        'pink': 'bg-pink-100 dark:bg-pink-900/30',
    }
    return bgs[color] || 'bg-gray-100'
}

const getCategoryIconColor = (color) => {
    const colors = {
        'blue': 'text-blue-600',
        'green': 'text-green-600',
        'red': 'text-red-600',
        'purple': 'text-purple-600',
        'orange': 'text-orange-600',
        'indigo': 'text-indigo-600',
        'pink': 'text-pink-600',
    }
    return colors[color] || 'text-gray-600'
}

const getCategoryIcon = (icon) => {
    const icons = {
        'chart-bar': ChartBarIcon,
        'currency-euro': CurrencyEuroIcon,
        'credit-card': CreditCardIcon,
        'arrow-trending-up': ArrowTrendingUpIcon,
        'users': UsersIcon,
        'tag': TagIcon,
        'megaphone': MegaphoneIcon,
    }
    return icons[icon] || ChartBarIcon
}

onMounted(() => {
    if (props.initialData) {
        metrics.value = props.initialData.metrics || {}
        recommendations.value = props.initialData.recommendations || []
        score.value = props.initialData.score || null
        generatedAt.value = props.initialData.generated_at
    } else {
        fetchAdvice()
    }
})
</script>
