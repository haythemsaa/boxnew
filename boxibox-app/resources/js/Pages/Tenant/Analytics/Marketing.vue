<template>
    <AppLayout title="Analytics - Marketing & Sales">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Marketing & Sales Analytics</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Lead generation, conversion funnel, and customer acquisition metrics
                    </p>
                </div>

                <!-- Lead Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Leads</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ analytics.leads.total }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Converted</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">
                            {{ analytics.leads.converted }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Conversion Rate</div>
                        <div class="mt-2 text-3xl font-semibold text-blue-600">
                            {{ analytics.leads.conversion_rate.toFixed(1) }}%
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">New Customers</div>
                        <div class="mt-2 text-3xl font-semibold text-purple-600">
                            {{ analytics.customers.new }}
                        </div>
                    </div>
                </div>

                <!-- Customer Value Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">Customer Lifetime Value (LTV)</div>
                        <div class="mt-2 text-4xl font-bold">€{{ analytics.value.ltv.toFixed(0) }}</div>
                    </div>

                    <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">Customer Acquisition Cost (CAC)</div>
                        <div class="mt-2 text-4xl font-bold">€{{ analytics.value.cac.toFixed(0) }}</div>
                    </div>

                    <div class="bg-gradient-to-br from-teal-500 to-teal-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="text-sm font-medium opacity-90">LTV / CAC Ratio</div>
                        <div class="mt-2 text-4xl font-bold">{{ analytics.value.ltv_cac_ratio.toFixed(1) }}x</div>
                        <div class="mt-2 text-sm opacity-75">Target: > 3x</div>
                    </div>
                </div>

                <!-- Conversion Funnel -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-6">Conversion Funnel</h3>
                    <div class="space-y-4">
                        <div v-for="(step, index) in funnelSteps" :key="index">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="font-medium">{{ step.label }}</span>
                                <span>{{ step.count }} ({{ step.rate.toFixed(1) }}%)</span>
                            </div>
                            <div class="relative">
                                <div class="w-full bg-gray-200 rounded-full h-10">
                                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-10 rounded-full flex items-center justify-center text-white font-semibold"
                                         :style="{width: step.width + '%'}">
                                        {{ step.width.toFixed(0) }}%
                                    </div>
                                </div>
                            </div>
                            <div v-if="index < funnelSteps.length - 1" class="text-xs text-gray-500 mt-1">
                                Drop-off: {{ ((step.count - funnelSteps[index + 1].count) / step.count * 100).toFixed(1) }}%
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campaign Performance (if available) -->
                <div v-if="campaigns && campaigns.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Campaign Performance</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sent</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Open Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Click Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conversion</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="campaign in campaigns" :key="campaign.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">{{ campaign.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ campaign.sent }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ campaign.open_rate.toFixed(1) }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ campaign.click_rate.toFixed(1) }}%</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ campaign.conversion_rate.toFixed(1) }}%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    analytics: Object,
    funnel: Object,
    campaigns: Array,
});

const funnelSteps = computed(() => {
    if (!props.funnel) return [];

    const visitors = props.funnel.visitors || 0;
    const maxWidth = 100;

    return [
        {
            label: 'Visitors',
            count: visitors,
            rate: 100,
            width: maxWidth
        },
        {
            label: 'Leads',
            count: props.funnel.leads || 0,
            rate: props.funnel.visitor_to_lead || 0,
            width: props.funnel.visitor_to_lead || 0
        },
        {
            label: 'Qualified',
            count: props.funnel.qualified || 0,
            rate: props.funnel.lead_to_qualified || 0,
            width: (props.funnel.lead_to_qualified / 100) * (props.funnel.visitor_to_lead || 0)
        },
        {
            label: 'Customers',
            count: props.funnel.customers || 0,
            rate: props.funnel.overall_conversion || 0,
            width: props.funnel.overall_conversion || 0
        }
    ];
});
</script>
