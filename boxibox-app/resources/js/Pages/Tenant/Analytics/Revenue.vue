<template>
    <AppLayout title="Analytics - Revenue">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Revenue Analytics</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Financial performance and revenue metrics
                    </p>
                </div>

                <!-- Recurring Revenue Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">Monthly Recurring Revenue</div>
                        <div class="mt-2 text-4xl font-bold">€{{ formatNumber(analytics.recurring.mrr) }}</div>
                        <div class="mt-2 text-sm opacity-75">{{ analytics.recurring.active_contracts }} active contracts</div>
                    </div>

                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">Annual Recurring Revenue</div>
                        <div class="mt-2 text-4xl font-bold">€{{ formatNumber(analytics.recurring.arr) }}</div>
                        <div class="mt-2 text-sm opacity-75">Projected annual revenue</div>
                    </div>

                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">Current Period Revenue</div>
                        <div class="mt-2 text-4xl font-bold">€{{ formatNumber(analytics.current_period.total_revenue) }}</div>
                        <div class="mt-2 text-sm opacity-75">{{ analytics.current_period.collected_rate.toFixed(1) }}% collected</div>
                    </div>
                </div>

                <!-- Revenue Status -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Revenue</div>
                        <div class="mt-2 text-2xl font-semibold text-yellow-600">
                            €{{ formatNumber(analytics.current_period.pending_revenue) }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Overdue Revenue</div>
                        <div class="mt-2 text-2xl font-semibold text-red-600">
                            €{{ formatNumber(analytics.current_period.overdue_revenue) }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Collection Rate</div>
                        <div class="mt-2 text-2xl font-semibold text-green-600">
                            {{ analytics.current_period.collected_rate.toFixed(1) }}%
                        </div>
                    </div>
                </div>

                <!-- Key Metrics -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Key Performance Metrics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <div class="text-sm text-gray-500">RevPAU (Revenue Per Available Unit)</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                €{{ analytics.metrics.revpau.toFixed(2) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">RevPAF (Revenue Per Available Foot)</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                €{{ analytics.metrics.revpaf.toFixed(2) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">ARPU (Average Revenue Per User)</div>
                            <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                €{{ analytics.metrics.arpu.toFixed(2) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Trend -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">12-Month Revenue Trend</h3>
                    <canvas ref="revenueChart" height="100"></canvas>
                </div>

                <!-- Revenue Breakdown -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Revenue Breakdown</h3>
                    <div class="space-y-3">
                        <div v-for="(amount, type) in analytics.breakdown" :key="type">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-medium capitalize">{{ type }}</span>
                                <span>€{{ formatNumber(amount) }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full"
                                     :style="{width: getPercentage(amount, Object.values(analytics.breakdown).reduce((a,b) => a+b, 0)) + '%'}">
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
import { ref, onMounted } from 'vue';
import { Chart, registerables } from 'chart.js';
import AppLayout from '@/Layouts/AppLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    analytics: Object,
});

const revenueChart = ref(null);

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

const getPercentage = (value, total) => {
    return total > 0 ? (value / total) * 100 : 0;
};

onMounted(() => {
    if (revenueChart.value) {
        new Chart(revenueChart.value, {
            type: 'bar',
            data: {
                labels: props.analytics.trend.map(t => t.month_name),
                datasets: [{
                    label: 'Revenue (€)',
                    data: props.analytics.trend.map(t => t.revenue),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
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
