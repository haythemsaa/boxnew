<template>
    <DashboardWidget
        :title="title"
        icon="🤖"
        :loading="loading"
        :refreshable="true"
        :collapsible="true"
        @refresh="refresh"
        content-class="p-0"
    >
        <!-- Stats Row -->
        <div class="grid grid-cols-2 divide-x divide-gray-100 border-b border-gray-100">
            <div class="p-4 text-center">
                <p class="text-2xl font-bold text-red-600">{{ stats.churnAtRisk }}</p>
                <p class="text-xs text-gray-500">Clients a risque</p>
            </div>
            <div class="p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ stats.upsellOpportunities }}</p>
                <p class="text-xs text-gray-500">Opportunites upsell</p>
            </div>
        </div>

        <!-- Insights List -->
        <div class="divide-y divide-gray-100">
            <div
                v-for="insight in insights.slice(0, 3)"
                :key="insight.id"
                class="p-4 hover:bg-gray-50 transition-colors"
            >
                <div class="flex items-start gap-3">
                    <div
                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                        :class="getInsightBgClass(insight.type)"
                    >
                        <span class="text-sm">{{ getInsightIcon(insight.type) }}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ insight.title }}</p>
                        <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ insight.description }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span
                                class="text-xs px-2 py-0.5 rounded-full font-medium"
                                :class="getConfidenceBadgeClass(insight.confidence)"
                            >
                                Confiance: {{ insight.confidence }}%
                            </span>
                            <span v-if="insight.potentialValue" class="text-xs text-green-600 font-medium">
                                +{{ formatCurrency(insight.potentialValue) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="insights.length === 0" class="p-8 text-center">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="text-xl">🔍</span>
                </div>
                <p class="text-sm font-medium text-gray-900">Analyse en cours</p>
                <p class="text-xs text-gray-500 mt-1">Les insights seront disponibles bientot</p>
            </div>
        </div>

        <template #footer>
            <Link href="/tenant/analytics/ai" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                Voir tous les insights IA →
            </Link>
        </template>
    </DashboardWidget>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Insights IA',
    },
    stats: {
        type: Object,
        default: () => ({
            churnAtRisk: 0,
            upsellOpportunities: 0,
        }),
    },
    insights: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['refresh']);

const loading = ref(false);

const getInsightIcon = (type) => {
    const icons = {
        churn: '⚠️',
        upsell: '📈',
        pricing: '💰',
        retention: '🎯',
        forecast: '📊',
    };
    return icons[type] || '💡';
};

const getInsightBgClass = (type) => {
    const classes = {
        churn: 'bg-red-100',
        upsell: 'bg-green-100',
        pricing: 'bg-blue-100',
        retention: 'bg-purple-100',
        forecast: 'bg-indigo-100',
    };
    return classes[type] || 'bg-gray-100';
};

const getConfidenceBadgeClass = (confidence) => {
    if (confidence >= 80) return 'bg-green-100 text-green-700';
    if (confidence >= 60) return 'bg-amber-100 text-amber-700';
    return 'bg-gray-100 text-gray-700';
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const refresh = async () => {
    loading.value = true;
    emit('refresh');
    setTimeout(() => {
        loading.value = false;
    }, 1000);
};
</script>

<style scoped>
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
