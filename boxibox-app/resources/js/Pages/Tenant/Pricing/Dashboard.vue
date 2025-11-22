<template>
    <AppLayout title="Dynamic Pricing Dashboard">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Dynamic Pricing Dashboard</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        AI-powered pricing optimization and revenue recommendations
                    </p>
                </div>

                <!-- Key Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Occupancy</div>
                        <div class="mt-2 text-3xl font-semibold" :class="getOccupancyColor(analytics.occupancy.rate)">
                            {{ (analytics.occupancy.rate * 100).toFixed(1) }}%
                        </div>
                        <div class="mt-1 text-xs text-gray-500">{{ analytics.occupancy.status }}</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Revenue</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            €{{ formatNumber(analytics.revenue.current) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Per month</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Optimal Revenue</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">
                            €{{ formatNumber(analytics.revenue.optimal) }}
                        </div>
                        <div class="mt-1 text-xs text-green-600">+{{ analytics.revenue.gap_percentage.toFixed(0) }}% potential</div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Revenue Gap</div>
                        <div class="mt-2 text-3xl font-semibold text-orange-600">
                            €{{ formatNumber(analytics.revenue.gap) }}
                        </div>
                        <div class="mt-1 text-xs text-gray-500">Lost per month</div>
                    </div>
                </div>

                <!-- AI Recommendations -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">🎯 AI Recommendations</h3>
                    <div class="space-y-4">
                        <div v-for="(rec, index) in analytics.recommendations" :key="index"
                             class="border-l-4 p-4 rounded"
                             :class="getPriorityClass(rec.priority)">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium px-2 py-1 rounded"
                                              :class="getPriorityBadge(rec.priority)">
                                            {{ rec.priority.toUpperCase() }}
                                        </span>
                                        <h4 class="ml-3 font-semibold">{{ rec.title }}</h4>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ rec.description }}</p>
                                    <div class="mt-2 text-sm font-medium text-green-600">
                                        Expected Impact: {{ rec.impact }}
                                    </div>
                                </div>
                                <button @click="applyRecommendation(rec)"
                                        class="ml-4 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <div v-if="!analytics.recommendations || analytics.recommendations.length === 0"
                             class="text-center text-gray-500 py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2">Your pricing is already optimized! No recommendations at this time.</p>
                        </div>
                    </div>
                </div>

                <!-- Revenue Forecast -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">📊 6-Month Revenue Forecast</h3>
                    <canvas ref="forecastChart" height="100"></canvas>
                    <div class="mt-4 grid grid-cols-6 gap-2 text-xs">
                        <div v-for="month in analytics.forecast" :key="month.month"
                             class="text-center">
                            <div class="font-medium">{{ month.month_name }}</div>
                            <div class="text-gray-500">€{{ formatNumber(month.projected_revenue) }}</div>
                            <div class="text-xs" :class="getConfidenceColor(month.confidence)">
                                {{ month.confidence }} confidence
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing Simulator -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">🧮 Pricing Simulator</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium mb-2">Target Occupancy Rate</label>
                            <input type="range" v-model="simulator.targetOccupancy" min="50" max="100" step="5"
                                   class="w-full">
                            <div class="text-sm text-gray-600">{{ simulator.targetOccupancy }}%</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">Price Adjustment</label>
                            <input type="range" v-model="simulator.priceAdjustment" min="-30" max="30" step="5"
                                   class="w-full">
                            <div class="text-sm text-gray-600">{{ simulator.priceAdjustment > 0 ? '+' : '' }}{{ simulator.priceAdjustment }}%</div>
                        </div>
                    </div>

                    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                        <h4 class="font-semibold mb-2">Simulation Results</h4>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-gray-600">Projected Occupancy</div>
                                <div class="text-lg font-semibold">{{ calculateProjectedOccupancy() }}%</div>
                            </div>
                            <div>
                                <div class="text-gray-600">Projected Revenue</div>
                                <div class="text-lg font-semibold">€{{ formatNumber(calculateProjectedRevenue()) }}</div>
                            </div>
                            <div>
                                <div class="text-gray-600">Change</div>
                                <div class="text-lg font-semibold" :class="getChangeColor()">
                                    {{ calculateRevenueChange() > 0 ? '+' : '' }}{{ calculateRevenueChange().toFixed(1) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    analytics: Object,
});

const forecastChart = ref(null);
const simulator = reactive({
    targetOccupancy: 85,
    priceAdjustment: 0,
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

const getOccupancyColor = (rate) => {
    if (rate >= 0.90) return 'text-green-600';
    if (rate >= 0.70) return 'text-yellow-600';
    return 'text-red-600';
};

const getPriorityClass = (priority) => {
    const classes = {
        'high': 'border-red-500 bg-red-50 dark:bg-red-900/10',
        'medium': 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/10',
        'low': 'border-blue-500 bg-blue-50 dark:bg-blue-900/10',
    };
    return classes[priority] || classes.low;
};

const getPriorityBadge = (priority) => {
    const classes = {
        'high': 'bg-red-100 text-red-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'low': 'bg-blue-100 text-blue-800',
    };
    return classes[priority] || classes.low;
};

const getConfidenceColor = (confidence) => {
    const colors = {
        'high': 'text-green-600',
        'medium': 'text-yellow-600',
        'low': 'text-gray-500',
    };
    return colors[confidence] || colors.low;
};

const applyRecommendation = (rec) => {
    if (confirm(`Apply recommendation: ${rec.title}?`)) {
        router.post(route('tenant.pricing.apply-recommendation'), {
            action: rec.action,
        });
    }
};

const calculateProjectedOccupancy = () => {
    const baseOccupancy = props.analytics.occupancy.rate * 100;
    const adjustment = simulator.priceAdjustment * -0.5; // Price up = occupancy down
    return Math.min(100, Math.max(0, baseOccupancy + adjustment)).toFixed(1);
};

const calculateProjectedRevenue = () => {
    const baseRevenue = props.analytics.revenue.current;
    const occupancyMultiplier = calculateProjectedOccupancy() / (props.analytics.occupancy.rate * 100);
    const priceMultiplier = 1 + (simulator.priceAdjustment / 100);
    return baseRevenue * occupancyMultiplier * priceMultiplier;
};

const calculateRevenueChange = () => {
    return ((calculateProjectedRevenue() - props.analytics.revenue.current) / props.analytics.revenue.current) * 100;
};

const getChangeColor = () => {
    const change = calculateRevenueChange();
    return change >= 0 ? 'text-green-600' : 'text-red-600';
};

onMounted(() => {
    if (forecastChart.value) {
        new Chart(forecastChart.value, {
            type: 'line',
            data: {
                labels: props.analytics.forecast.map(f => f.month_name),
                datasets: [{
                    label: 'Projected Revenue (€)',
                    data: props.analytics.forecast.map(f => f.projected_revenue),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '€' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    }
});
</script>
