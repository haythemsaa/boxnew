<template>
    <Head title="Email Automation" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Email Automation
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400">
                            Automatisez vos campagnes email et nurturing clients
                        </p>
                    </div>
                    <Link :href="route('tenant.marketing.automation.create')"
                        class="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nouvelle automatisation
                    </Link>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-8">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Automatisations actives</div>
                        <div class="mt-1 text-2xl font-semibold text-indigo-600">{{ stats.active_automations }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Contacts inscrits</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.total_enrolled }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Emails envoyés (30j)</div>
                        <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ stats.emails_sent_30d }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Taux d'ouverture</div>
                        <div class="mt-1 text-2xl font-semibold text-green-600">{{ stats.open_rate }}%</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Taux de clic</div>
                        <div class="mt-1 text-2xl font-semibold text-blue-600">{{ stats.click_rate }}%</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400">Taux de conversion</div>
                        <div class="mt-1 text-2xl font-semibold text-purple-600">{{ stats.conversion_rate }}%</div>
                    </div>
                </div>

                <!-- Performance Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performance des 30 derniers jours</h3>
                    <canvas ref="performanceChart" height="80"></canvas>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sequences List -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Automatisations</h3>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div v-for="sequence in sequences" :key="sequence.id"
                                    class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <Link :href="route('tenant.marketing.automation.show', sequence.id)"
                                                    class="text-lg font-medium text-gray-900 dark:text-white hover:text-indigo-600">
                                                    {{ sequence.name }}
                                                </Link>
                                                <span :class="sequence.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                    class="px-2 py-0.5 text-xs font-medium rounded-full">
                                                    {{ sequence.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                {{ sequence.description || 'Aucune description' }}
                                            </p>

                                            <div class="mt-3 flex items-center gap-6 text-sm">
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                    </svg>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ sequence.trigger_label }}</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                                    </svg>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ sequence.steps_count }} étapes</span>
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ sequence.enrolled }} inscrits</span>
                                                </div>
                                            </div>

                                            <!-- Mini funnel -->
                                            <div class="mt-3 flex items-center gap-2">
                                                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                    <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: sequence.completion_rate + '%' }"></div>
                                                </div>
                                                <span class="text-xs text-gray-500">{{ sequence.completion_rate }}% complété</span>
                                            </div>
                                        </div>

                                        <div class="ml-4 flex items-center gap-2">
                                            <button @click="toggleSequence(sequence)"
                                                :class="sequence.is_active ? 'text-yellow-600 hover:text-yellow-700' : 'text-green-600 hover:text-green-700'"
                                                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg v-if="sequence.is_active" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <Link :href="route('tenant.marketing.automation.edit', sequence.id)"
                                                class="p-2 text-blue-600 hover:text-blue-700 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="sequences.length === 0" class="p-8 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-2">Aucune automatisation. Créez votre première séquence !</p>
                                    <Link :href="route('tenant.marketing.automation.create')"
                                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                                        Créer une automatisation
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="lg:col-span-1">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Activité récente</h3>
                            </div>
                            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                                <div v-for="activity in recentActivity" :key="activity.id"
                                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <div class="flex items-start gap-3">
                                        <div :class="getActivityIconClass(activity.status)"
                                            class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center">
                                            <svg v-if="activity.status === 'opened'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg v-else-if="activity.status === 'delivered'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ activity.recipient }}
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">{{ activity.subject }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ formatDate(activity.created_at) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="recentActivity.length === 0" class="p-4 text-center text-gray-500 text-sm">
                                    Aucune activité récente
                                </div>
                            </div>
                        </div>

                        <!-- Quick Templates -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mt-6">
                            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Templates populaires</h3>
                            </div>
                            <div class="p-4 space-y-3">
                                <button v-for="template in quickTemplates" :key="template.id"
                                    @click="createFromTemplate(template)"
                                    class="w-full text-left p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ template.icon }}</span>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">{{ template.name }}</div>
                                            <div class="text-xs text-gray-500">{{ template.description }}</div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';
import TenantLayout from '@/Layouts/TenantLayout.vue';

Chart.register(...registerables);

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            active_automations: 0,
            total_enrolled: 0,
            emails_sent_30d: 0,
            open_rate: 0,
            click_rate: 0,
            conversion_rate: 0,
        }),
    },
    sequences: {
        type: Array,
        default: () => [],
    },
    recentActivity: {
        type: Array,
        default: () => [],
    },
    performanceChart: {
        type: Array,
        default: () => [],
    },
    triggers: {
        type: Array,
        default: () => [],
    },
});

const performanceChart = ref(null);

const quickTemplates = [
    { id: 1, icon: '👋', name: 'Bienvenue client', description: 'Séquence d\'onboarding' },
    { id: 2, icon: '🛒', name: 'Panier abandonné', description: 'Récupération de leads' },
    { id: 3, icon: '🎂', name: 'Anniversaire', description: 'Email personnalisé' },
    { id: 4, icon: '⏰', name: 'Renouvellement', description: 'Rappel avant expiration' },
];

const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();
    const diff = (now - d) / 1000;

    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
};

const getActivityIconClass = (status) => {
    const classes = {
        opened: 'bg-green-100 text-green-600',
        delivered: 'bg-blue-100 text-blue-600',
        sent: 'bg-gray-100 text-gray-600',
    };
    return classes[status] || classes.sent;
};

const toggleSequence = (sequence) => {
    router.post(route('tenant.marketing.automation.toggle', sequence.id), {}, {
        preserveScroll: true,
    });
};

const createFromTemplate = (template) => {
    router.get(route('tenant.marketing.automation.create'), {
        template: template.id,
    });
};

onMounted(() => {
    if (performanceChart.value && props.performanceChart.length > 0) {
        new Chart(performanceChart.value, {
            type: 'line',
            data: {
                labels: props.performanceChart.map(p => p.date),
                datasets: [
                    {
                        label: 'Emails envoyés',
                        data: props.performanceChart.map(p => p.sent),
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                    {
                        label: 'Emails ouverts',
                        data: props.performanceChart.map(p => p.opened),
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                    },
                },
            },
        });
    }
});
</script>
