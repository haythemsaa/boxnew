<template>
    <AppLayout title="Analytics - Operations">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Operational Analytics</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Operational efficiency and profitability metrics
                    </p>
                </div>

                <!-- Cost Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Monthly Expenses</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            €{{ formatNumber(analytics.costs.total_expenses) }}
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            €{{ analytics.costs.cost_per_unit.toFixed(2) }} per unit
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Expense Ratio</div>
                        <div class="mt-2 text-3xl font-semibold" :class="getExpenseRatioColor(analytics.costs.expense_ratio)">
                            {{ analytics.costs.expense_ratio.toFixed(1) }}%
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            Target: 25-40%
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Cost Per Unit</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            €{{ analytics.costs.cost_per_unit.toFixed(2) }}
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            Per occupied box
                        </div>
                    </div>
                </div>

                <!-- Profitability Metrics -->
                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-8 mb-8 text-white">
                    <h3 class="text-2xl font-bold mb-6">Profitability</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <div class="text-sm font-medium opacity-90">Net Operating Income (NOI)</div>
                            <div class="mt-2 text-5xl font-bold">
                                €{{ formatNumber(analytics.profitability.noi) }}
                            </div>
                            <div class="mt-2 text-sm opacity-75">
                                {{ analytics.profitability.noi_margin.toFixed(1) }}% margin
                            </div>
                        </div>
                        <div class="flex items-center justify-center">
                            <div class="text-center">
                                <div class="text-6xl font-bold mb-2">
                                    {{ analytics.profitability.noi >= 0 ? '📈' : '📉' }}
                                </div>
                                <div class="text-lg">
                                    {{ analytics.profitability.noi >= 0 ? 'Profitable' : 'Needs Improvement' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Efficiency Metrics -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-6">Staff Efficiency</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-4xl font-bold text-blue-600 mb-2">
                                {{ analytics.efficiency.staff_count }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Staff Members
                            </div>
                        </div>

                        <div class="text-center p-6 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-4xl font-bold text-green-600 mb-2">
                                €{{ formatNumber(analytics.efficiency.revenue_per_staff) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Revenue per Staff
                            </div>
                        </div>

                        <div class="text-center p-6 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <div class="text-4xl font-bold text-purple-600 mb-2">
                                {{ analytics.efficiency.units_per_staff.toFixed(1) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Units per Staff
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-6">Operational KPIs Summary</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium">Operating Efficiency</div>
                                <div class="text-sm text-gray-500">Lower is better</div>
                            </div>
                            <div class="text-2xl font-bold" :class="getExpenseRatioColor(analytics.costs.expense_ratio)">
                                {{ analytics.costs.expense_ratio.toFixed(0) }}%
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium">Profit Margin</div>
                                <div class="text-sm text-gray-500">Higher is better</div>
                            </div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ analytics.profitability.noi_margin.toFixed(1) }}%
                            </div>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium">Staff Productivity</div>
                                <div class="text-sm text-gray-500">Revenue generation per employee</div>
                            </div>
                            <div class="text-2xl font-bold text-blue-600">
                                €{{ formatNumber(analytics.efficiency.revenue_per_staff) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recommendations -->
                <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-6">
                    <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-3">💡 Optimization Recommendations</h4>
                    <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                        <li v-if="analytics.costs.expense_ratio > 40" class="flex items-start">
                            <span class="mr-2">⚠️</span>
                            <span>Expense ratio is above target (40%). Review operational costs to improve profitability.</span>
                        </li>
                        <li v-if="analytics.efficiency.units_per_staff > 30" class="flex items-start">
                            <span class="mr-2">⚠️</span>
                            <span>High units per staff (>30). Consider hiring to improve customer service quality.</span>
                        </li>
                        <li v-if="analytics.profitability.noi_margin < 20" class="flex items-start">
                            <span class="mr-2">⚠️</span>
                            <span>Low profit margin (<20%). Focus on revenue optimization and cost reduction.</span>
                        </li>
                        <li v-if="analytics.costs.expense_ratio <= 40 && analytics.profitability.noi_margin >= 20" class="flex items-start">
                            <span class="mr-2">✅</span>
                            <span>Excellent operational performance! Maintain current efficiency levels.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    analytics: Object,
});

const formatNumber = (num) => {
    return new Intl.NumberFormat('fr-FR').format(num);
};

const getExpenseRatioColor = (ratio) => {
    if (ratio <= 25) return 'text-green-600';
    if (ratio <= 40) return 'text-yellow-600';
    return 'text-red-600';
};
</script>
