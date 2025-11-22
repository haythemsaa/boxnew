<template>
    <AppLayout title="Analytics - Occupancy">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Occupancy Analytics</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Real-time occupancy tracking and trends
                    </p>
                </div>

                <!-- Period Selector -->
                <div class="mb-6">
                    <select v-model="selectedPeriod" @change="loadData"
                            class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                        <option value="week">Last Week</option>
                        <option value="month">Last Month</option>
                        <option value="quarter">Last Quarter</option>
                        <option value="year">Last Year</option>
                    </select>
                </div>

                <!-- Current Occupancy Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Occupancy Rate</div>
                        <div class="mt-2 flex items-baseline">
                            <div class="text-3xl font-semibold" :class="getOccupancyColor(analytics.current.occupancy_rate)">
                                {{ analytics.current.occupancy_rate.toFixed(1) }}%
                            </div>
                            <span class="ml-2 text-sm" :class="getStatusBadgeClass(analytics.current.occupancy_status)">
                                {{ analytics.current.occupancy_status }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Occupied Boxes</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ analytics.current.occupied }} / {{ analytics.current.total_boxes }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Available Boxes</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">
                            {{ analytics.current.available }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Reserved Boxes</div>
                        <div class="mt-2 text-3xl font-semibold text-yellow-600">
                            {{ analytics.current.reserved }}
                        </div>
                    </div>
                </div>

                <!-- Occupancy Trend Chart -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">12-Month Occupancy Trend</h3>
                    <canvas ref="trendChart" height="100"></canvas>
                </div>

                <!-- Occupancy by Type & Size -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- By Type -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Occupancy by Type</h3>
                        <div class="space-y-3">
                            <div v-for="(data, type) in analytics.by_type" :key="type">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium">{{ type }}</span>
                                    <span>{{ data.occupied }} / {{ data.total }} ({{ data.occupancy_rate.toFixed(1) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" :style="{width: data.occupancy_rate + '%'}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Size -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Occupancy by Size</h3>
                        <div class="space-y-3">
                            <div v-for="(data, size) in analytics.by_size" :key="size">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium">{{ size }}m²</span>
                                    <span>{{ data.occupied }} / {{ data.total }} ({{ data.occupancy_rate.toFixed(1) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" :style="{width: data.occupancy_rate + '%'}"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Move-ins vs Move-outs -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Move-ins vs Move-outs This Month</h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-500">Move-ins</div>
                            <div class="text-2xl font-semibold text-green-600">{{ analytics.move_ins_this_month }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Move-outs</div>
                            <div class="text-2xl font-semibold text-red-600">{{ analytics.move_outs_this_month }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    analytics: Object,
});

const selectedPeriod = ref('month');
const trendChart = ref(null);

const loadData = () => {
    // Reload data with selected period
    router.reload({ data: { period: selectedPeriod.value } });
};

const getOccupancyColor = (rate) => {
    if (rate >= 90) return 'text-green-600';
    if (rate >= 70) return 'text-yellow-600';
    return 'text-red-600';
};

const getStatusBadgeClass = (status) => {
    const classes = {
        'excellent': 'text-green-600 bg-green-100',
        'good': 'text-blue-600 bg-blue-100',
        'medium': 'text-yellow-600 bg-yellow-100',
        'low': 'text-red-600 bg-red-100',
    };
    return `px-2 py-1 rounded-full ${classes[status] || ''}`;
};

onMounted(() => {
    if (trendChart.value) {
        new Chart(trendChart.value, {
            type: 'line',
            data: {
                labels: props.analytics.trend.map(t => t.month_name),
                datasets: [{
                    label: 'Occupancy Rate (%)',
                    data: props.analytics.trend.map(t => t.occupancy_rate),
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
                        max: 100,
                        ticks: {
                            callback: (value) => value + '%'
                        }
                    }
                }
            }
        });
    }
});
</script>
