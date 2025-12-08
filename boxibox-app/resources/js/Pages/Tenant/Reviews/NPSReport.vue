<template>
    <TenantLayout title="Rapport NPS" :breadcrumbs="[{ label: 'Avis', href: route('tenant.reviews.index') }, { label: 'Rapport NPS' }]">
        <div class="space-y-6">
            <!-- Period Selector -->
            <div class="flex justify-end">
                <select v-model="selectedPeriod" @change="changePeriod" class="rounded-xl border-gray-200 text-sm">
                    <option value="month">Dernier mois</option>
                    <option value="quarter">Dernier trimestre</option>
                    <option value="year">Dernière année</option>
                </select>
            </div>

            <!-- NPS Score Card -->
            <div class="bg-gradient-to-br from-indigo-600 via-purple-600 to-purple-700 rounded-2xl p-8 text-white">
                <div class="text-center">
                    <p class="text-purple-200 text-sm font-medium mb-2">Net Promoter Score</p>
                    <div class="text-7xl font-bold mb-4" :class="getNpsColorClass(npsScore)">
                        {{ npsScore >= 0 ? '+' : '' }}{{ npsScore }}
                    </div>
                    <p class="text-purple-200">
                        Basé sur {{ breakdown?.total ?? 0 }} réponses
                    </p>
                    <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full" :class="getNpsBadgeClass(npsScore)">
                        <component :is="getNpsIcon(npsScore)" class="w-5 h-5" />
                        {{ getNpsLabel(npsScore) }}
                    </div>
                </div>
            </div>

            <!-- Breakdown -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-xl">
                            <FaceSmileIcon class="w-6 h-6 text-green-600" />
                        </div>
                        <span class="text-3xl font-bold text-green-600">{{ getPercentage(breakdown?.promoters) }}%</span>
                    </div>
                    <h3 class="font-semibold text-gray-900">Promoteurs</h3>
                    <p class="text-sm text-gray-500">Note 5/5 ({{ breakdown?.promoters ?? 0 }} réponses)</p>
                    <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-green-500 h-full rounded-full transition-all" :style="{ width: getPercentage(breakdown?.promoters) + '%' }"></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-yellow-100 rounded-xl">
                            <FaceFrownIcon class="w-6 h-6 text-yellow-600" />
                        </div>
                        <span class="text-3xl font-bold text-yellow-600">{{ getPercentage(breakdown?.passives) }}%</span>
                    </div>
                    <h3 class="font-semibold text-gray-900">Passifs</h3>
                    <p class="text-sm text-gray-500">Note 4/5 ({{ breakdown?.passives ?? 0 }} réponses)</p>
                    <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-yellow-500 h-full rounded-full transition-all" :style="{ width: getPercentage(breakdown?.passives) + '%' }"></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-red-100 rounded-xl">
                            <FaceAngryIcon class="w-6 h-6 text-red-600" />
                        </div>
                        <span class="text-3xl font-bold text-red-600">{{ getPercentage(breakdown?.detractors) }}%</span>
                    </div>
                    <h3 class="font-semibold text-gray-900">Détracteurs</h3>
                    <p class="text-sm text-gray-500">Note 1-3/5 ({{ breakdown?.detractors ?? 0 }} réponses)</p>
                    <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-red-500 h-full rounded-full transition-all" :style="{ width: getPercentage(breakdown?.detractors) + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- Monthly Evolution Chart -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-6">Évolution mensuelle du NPS</h3>
                <div v-if="monthlyNPS && monthlyNPS.length > 0" class="h-64">
                    <div class="flex items-end justify-between h-full gap-2">
                        <div
                            v-for="(month, index) in monthlyNPS"
                            :key="index"
                            class="flex-1 flex flex-col items-center"
                        >
                            <div class="relative flex-1 w-full flex items-end justify-center">
                                <div
                                    v-if="month.nps !== null"
                                    class="w-full max-w-[40px] rounded-t-lg transition-all duration-500"
                                    :class="getNpsBarClass(month.nps)"
                                    :style="{ height: getBarHeight(month.nps) + '%' }"
                                >
                                    <div class="absolute -top-6 left-1/2 -translate-x-1/2 text-xs font-semibold whitespace-nowrap">
                                        {{ month.nps >= 0 ? '+' : '' }}{{ month.nps }}
                                    </div>
                                </div>
                                <div v-else class="text-gray-300 text-xs">-</div>
                            </div>
                            <div class="mt-2 text-xs text-gray-500 text-center">
                                {{ month.month?.split(' ')[0] }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="h-64 flex items-center justify-center text-gray-400">
                    Pas de données disponibles
                </div>
            </div>

            <!-- NPS Explanation -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">Comprendre le NPS</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                    <div class="p-4 bg-red-50 rounded-xl">
                        <p class="font-semibold text-red-700 mb-2">-100 à 0</p>
                        <p class="text-red-600">Amélioration nécessaire. Focus sur la résolution des problèmes clients.</p>
                    </div>
                    <div class="p-4 bg-yellow-50 rounded-xl">
                        <p class="font-semibold text-yellow-700 mb-2">0 à 50</p>
                        <p class="text-yellow-600">Bon score. Continuez d'améliorer l'expérience client.</p>
                    </div>
                    <div class="p-4 bg-green-50 rounded-xl">
                        <p class="font-semibold text-green-700 mb-2">50 à 100</p>
                        <p class="text-green-600">Excellent ! Vos clients sont vos meilleurs ambassadeurs.</p>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import TenantLayout from '@/Layouts/TenantLayout.vue'
import {
    FaceSmileIcon,
    FaceFrownIcon,
    TrophyIcon,
    ExclamationTriangleIcon,
    ArrowTrendingUpIcon,
} from '@heroicons/vue/24/outline'

// Custom icon for angry face (using frown with different color context)
const FaceAngryIcon = FaceFrownIcon

const props = defineProps({
    npsScore: {
        type: Number,
        default: 0,
    },
    breakdown: Object,
    monthlyNPS: Array,
    period: String,
})

const selectedPeriod = ref(props.period || 'year')

const changePeriod = () => {
    router.get(route('tenant.reviews.nps'), {
        period: selectedPeriod.value,
    }, { preserveState: true })
}

const getPercentage = (count) => {
    const total = props.breakdown?.total || 1
    return Math.round(((count || 0) / total) * 100)
}

const getNpsColorClass = (score) => {
    if (score >= 50) return 'text-green-300'
    if (score >= 0) return 'text-yellow-300'
    return 'text-red-300'
}

const getNpsBadgeClass = (score) => {
    if (score >= 50) return 'bg-green-500/30 text-green-100'
    if (score >= 0) return 'bg-yellow-500/30 text-yellow-100'
    return 'bg-red-500/30 text-red-100'
}

const getNpsLabel = (score) => {
    if (score >= 70) return 'Excellent'
    if (score >= 50) return 'Très bien'
    if (score >= 30) return 'Bien'
    if (score >= 0) return 'Correct'
    return 'À améliorer'
}

const getNpsIcon = (score) => {
    if (score >= 50) return TrophyIcon
    if (score >= 0) return ArrowTrendingUpIcon
    return ExclamationTriangleIcon
}

const getNpsBarClass = (score) => {
    if (score >= 50) return 'bg-green-500'
    if (score >= 0) return 'bg-yellow-500'
    return 'bg-red-500'
}

const getBarHeight = (score) => {
    // Normalize score from -100 to 100 into 0 to 100%
    return Math.max(10, ((score + 100) / 200) * 100)
}
</script>
