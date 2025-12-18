<template>
    <TenantLayout title="Controle d'Acces">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Access Control Dashboard</h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Real-time access monitoring and smart locks management
                    </p>
                </div>

                <!-- Access Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Access Attempts</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900 dark:text-white">
                            {{ analytics.total_access_attempts }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Success Rate</div>
                        <div class="mt-2 text-3xl font-semibold text-green-600">
                            {{ analytics.success_rate.toFixed(1) }}%
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Denied</div>
                        <div class="mt-2 text-3xl font-semibold text-yellow-600">
                            {{ analytics.denied }}
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Suspicious Activity</div>
                        <div class="mt-2 text-3xl font-semibold" :class="analytics.suspicious_activity > 0 ? 'text-red-600' : 'text-green-600'">
                            {{ analytics.suspicious_activity }}
                        </div>
                    </div>
                </div>

                <!-- Locks Status -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">Smart Locks Status</h3>
                        <Link :href="route('tenant.access-control.locks.index')"
                              class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All Locks →
                        </Link>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded">
                            <div class="text-3xl font-bold text-green-600">{{ locks_status.active }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Active</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded">
                            <div class="text-3xl font-bold text-gray-600">{{ locks_status.offline }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Offline</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                            <div class="text-3xl font-bold text-yellow-600">{{ locks_status.low_battery }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Low Battery</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <div class="text-3xl font-bold text-blue-600">{{ locks_status.total }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Locks</div>
                        </div>
                    </div>

                    <!-- Locks needing attention -->
                    <div v-if="locks_status.locks.filter(l => l.needs_attention).length > 0"
                         class="border-t pt-4">
                        <h4 class="font-medium text-red-600 mb-3">⚠️ Locks Needing Attention</h4>
                        <div class="space-y-2">
                            <div v-for="lock in locks_status.locks.filter(l => l.needs_attention)"
                                 :key="lock.id"
                                 class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 rounded">
                                <div>
                                    <div class="font-medium">Box {{ lock.box }}</div>
                                    <div class="text-sm text-gray-600">
                                        <span v-if="lock.battery_level <= 20" class="text-red-600">
                                            🔋 Battery: {{ lock.battery_level }}%
                                        </span>
                                        <span v-if="lock.status === 'offline'" class="text-red-600">
                                            📡 Offline - Last seen: {{ lock.last_seen }}
                                        </span>
                                    </div>
                                </div>
                                <button class="px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700">
                                    Action Required
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Access by Method -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Access by Method</h3>
                        <div class="space-y-3">
                            <div v-for="(count, method) in analytics.by_method" :key="method">
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-medium capitalize">{{ method }}</span>
                                    <span>{{ count }} ({{ ((count / analytics.total_access_attempts) * 100).toFixed(0) }}%)</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full"
                                         :style="{width: ((count / analytics.total_access_attempts) * 100) + '%'}"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">Access by Hour</h3>
                        <div class="h-48">
                            <canvas ref="hourlyChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Suspicious Activity -->
                <div v-if="suspicious_activity.length > 0"
                     class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-red-600">🚨 Suspicious Activity (Last 24h)</h3>
                        <Link :href="route('tenant.access-control.logs.index', {suspicious: 1})"
                              class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All Logs →
                        </Link>
                    </div>
                    <div class="space-y-3">
                        <div v-for="activity in suspicious_activity.slice(0, 5)"
                             :key="activity.id"
                             class="flex items-center justify-between p-4 border-l-4 rounded"
                             :class="activity.severity === 'high' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'">
                            <div>
                                <div class="font-medium">Box {{ activity.box }} - {{ activity.customer }}</div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ activity.status }} - {{ activity.reason }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ activity.time_ago }}</div>
                            </div>
                            <span class="px-3 py-1 rounded text-xs font-medium"
                                  :class="activity.severity === 'high' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'">
                                {{ activity.severity.toUpperCase() }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Link :href="route('tenant.access-control.logs.index')"
                          class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition text-center">
                        <div class="text-4xl mb-3">📋</div>
                        <div class="font-semibold">View Access Logs</div>
                        <div class="text-sm text-gray-500 mt-1">Complete access history</div>
                    </Link>

                    <Link :href="route('tenant.access-control.locks.index')"
                          class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition text-center">
                        <div class="text-4xl mb-3">🔒</div>
                        <div class="font-semibold">Manage Smart Locks</div>
                        <div class="text-sm text-gray-500 mt-1">Configure and monitor locks</div>
                    </Link>

                    <a href="#" class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-lg transition text-center">
                        <div class="text-4xl mb-3">⚙️</div>
                        <div class="font-semibold">Access Settings</div>
                        <div class="text-sm text-gray-500 mt-1">Configure access policies</div>
                    </a>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import TenantLayout from '@/Layouts/TenantLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    analytics: Object,
    locks_status: Object,
    suspicious_activity: Array,
});

const hourlyChart = ref(null);

onMounted(() => {
    if (hourlyChart.value && props.analytics.by_hour) {
        new Chart(hourlyChart.value, {
            type: 'bar',
            data: {
                labels: props.analytics.by_hour.map(h => h.hour + 'h'),
                datasets: [{
                    label: 'Access Attempts',
                    data: props.analytics.by_hour.map(h => h.count),
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                    }
                }
            }
        });
    }
});
</script>
