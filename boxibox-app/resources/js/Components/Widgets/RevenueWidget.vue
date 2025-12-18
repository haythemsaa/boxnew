<template>
    <DashboardWidget
        :title="title"
        icon="💰"
        :loading="loading"
        :refreshable="true"
        :collapsible="true"
        @refresh="refresh"
    >
        <div class="space-y-4">
            <!-- Main Value -->
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-3xl font-bold text-gray-900">{{ formatCurrency(revenue) }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ periodLabel }}</p>
                </div>
                <div
                    class="flex items-center gap-1 px-2 py-1 rounded-full text-sm font-medium"
                    :class="trend >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                >
                    <svg v-if="trend >= 0" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                    </svg>
                    <span>{{ Math.abs(trend) }}%</span>
                </div>
            </div>

            <!-- Mini Chart -->
            <div v-if="chartData.length > 0" class="h-16">
                <div class="flex items-end justify-between h-full gap-1">
                    <div
                        v-for="(value, index) in chartData"
                        :key="index"
                        class="flex-1 bg-primary-100 hover:bg-primary-200 transition-colors rounded-t cursor-pointer relative group"
                        :style="{ height: getBarHeight(value) + '%' }"
                    >
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block">
                            <div class="bg-gray-800 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                {{ formatCurrency(value) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Breakdown -->
            <div v-if="breakdown" class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-500">Encaisse</p>
                    <p class="text-sm font-semibold text-green-600">{{ formatCurrency(breakdown.collected) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">En attente</p>
                    <p class="text-sm font-semibold text-amber-600">{{ formatCurrency(breakdown.pending) }}</p>
                </div>
            </div>
        </div>

        <template #footer>
            <Link href="/tenant/invoices" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                Voir toutes les factures →
            </Link>
        </template>
    </DashboardWidget>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Revenus',
    },
    revenue: {
        type: Number,
        default: 0,
    },
    previousRevenue: {
        type: Number,
        default: 0,
    },
    period: {
        type: String,
        default: 'month', // 'day', 'week', 'month', 'year'
    },
    chartData: {
        type: Array,
        default: () => [],
    },
    breakdown: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['refresh']);

const loading = ref(false);

const periodLabel = computed(() => {
    const labels = {
        day: "Aujourd'hui",
        week: 'Cette semaine',
        month: 'Ce mois',
        year: 'Cette annee',
    };
    return labels[props.period] || 'Ce mois';
});

const trend = computed(() => {
    if (!props.previousRevenue || props.previousRevenue === 0) return 0;
    return Math.round(((props.revenue - props.previousRevenue) / props.previousRevenue) * 100);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const getBarHeight = (value) => {
    const max = Math.max(...props.chartData);
    if (max === 0) return 0;
    return Math.max(10, (value / max) * 100);
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
.bg-primary-100 {
    background-color: rgba(143, 189, 86, 0.2);
}
.bg-primary-200 {
    background-color: rgba(143, 189, 86, 0.4);
}
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
</style>
