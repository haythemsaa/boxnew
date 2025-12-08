<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    settings: Object,
    todaysCalls: Array,
    waitingCalls: Array,
    activeCalls: Array,
    onlineAgents: Array,
    availableAgents: Array,
    recentCalls: Array,
    stats: Object,
    isWithinWorkingHours: Boolean,
});

const agentStatus = ref('offline');
const pingInterval = ref(null);

const updateStatus = (status) => {
    agentStatus.value = status;
    router.post(route('tenant.video-calls.agent-status'), { status }, {
        preserveScroll: true,
    });
};

const startPing = () => {
    pingInterval.value = setInterval(() => {
        router.post(route('tenant.video-calls.agent-ping'), {}, {
            preserveScroll: true,
            preserveState: true,
        });
    }, 30000);
};

const joinCall = (callId) => {
    router.post(route('tenant.video-calls.start', callId));
};

const formatDuration = (seconds) => {
    if (!seconds) return '-';
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins}:${secs.toString().padStart(2, '0')}`;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        scheduled: 'bg-blue-100 text-blue-800',
        waiting: 'bg-yellow-100 text-yellow-800',
        in_progress: 'bg-green-100 text-green-800',
        completed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
        missed: 'bg-orange-100 text-orange-800',
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
};

onMounted(() => {
    if (agentStatus.value !== 'offline') {
        startPing();
    }
});

onUnmounted(() => {
    if (pingInterval.value) {
        clearInterval(pingInterval.value);
    }
});
</script>

<template>
    <Head :title="$t('video_calls.title')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('video_calls.live_agent') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('video_calls.dashboard_subtitle') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Agent Status Toggle -->
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $t('video_calls.my_status') }}:</span>
                            <select
                                v-model="agentStatus"
                                @change="updateStatus(agentStatus)"
                                class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="offline">{{ $t('video_calls.status.offline') }}</option>
                                <option value="online">{{ $t('video_calls.status.online') }}</option>
                                <option value="busy">{{ $t('video_calls.status.busy') }}</option>
                                <option value="away">{{ $t('video_calls.status.away') }}</option>
                            </select>
                        </div>
                        <Link
                            :href="route('tenant.video-calls.schedule')"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            {{ $t('video_calls.schedule_call') }}
                        </Link>
                    </div>
                </div>

                <!-- Working Hours Alert -->
                <div v-if="!isWithinWorkingHours" class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-yellow-800">{{ $t('video_calls.outside_working_hours') }}</span>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.today_total }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.today_total') }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-green-600">{{ stats.today_completed }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.completed') }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-yellow-600">{{ stats.waiting }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.waiting') }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.in_progress }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.in_progress') }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-purple-600">{{ stats.agents_online }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.agents_online') }}</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <div class="text-2xl font-bold text-teal-600">{{ stats.agents_available }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.stats.agents_available') }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Waiting Room -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2 animate-pulse"></span>
                                {{ $t('video_calls.waiting_room') }}
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 rounded-full">
                                    {{ waitingCalls.length }}
                                </span>
                            </h2>
                        </div>
                        <div class="p-4">
                            <div v-if="waitingCalls.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                {{ $t('video_calls.no_waiting_calls') }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="call in waitingCalls"
                                    :key="call.id"
                                    class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ call.guest_name || $t('video_calls.anonymous') }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ call.site?.name || $t('video_calls.no_site') }}
                                            </div>
                                            <div class="text-xs text-gray-400 mt-1">
                                                {{ $t('video_calls.types.' + call.type) }}
                                            </div>
                                        </div>
                                        <button
                                            @click="joinCall(call.id)"
                                            class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700"
                                        >
                                            {{ $t('video_calls.answer') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Calls -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-2 animate-pulse"></span>
                                {{ $t('video_calls.active_calls') }}
                                <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                    {{ activeCalls.length }}
                                </span>
                            </h2>
                        </div>
                        <div class="p-4">
                            <div v-if="activeCalls.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                {{ $t('video_calls.no_active_calls') }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="call in activeCalls"
                                    :key="call.id"
                                    class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ call.guest_name || call.customer?.full_name || call.prospect?.full_name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $t('video_calls.agent') }}: {{ call.agent?.name }}
                                            </div>
                                        </div>
                                        <Link
                                            :href="route('tenant.video-calls.show', call.id)"
                                            class="text-primary-600 hover:text-primary-800 text-sm"
                                        >
                                            {{ $t('common.view') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Online Agents -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.online_agents') }}
                            </h2>
                        </div>
                        <div class="p-4">
                            <div v-if="onlineAgents.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                {{ $t('video_calls.no_online_agents') }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="agent in onlineAgents"
                                    :key="agent.id"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                            <span class="text-primary-700 font-medium text-sm">
                                                {{ agent.user?.name?.charAt(0) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white text-sm">
                                                {{ agent.user?.name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ agent.current_call ? $t('video_calls.on_call') : $t('video_calls.status.' + agent.status) }}
                                            </div>
                                        </div>
                                    </div>
                                    <span
                                        :class="[
                                            'w-2 h-2 rounded-full',
                                            agent.status === 'online' ? 'bg-green-500' :
                                            agent.status === 'busy' ? 'bg-red-500' : 'bg-yellow-500'
                                        ]"
                                    ></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Scheduled Calls -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $t('video_calls.todays_schedule') }}
                        </h2>
                        <Link
                            :href="route('tenant.video-calls.history')"
                            class="text-sm text-primary-600 hover:text-primary-800"
                        >
                            {{ $t('video_calls.view_all_history') }}
                        </Link>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('video_calls.time') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('video_calls.participant') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('video_calls.type') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('video_calls.agent') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('video_calls.status_label') }}
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        {{ $t('common.actions') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="call in todaysCalls" :key="call.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ new Date(call.scheduled_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ call.guest_name || call.customer?.full_name || call.prospect?.full_name || '-' }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ call.site?.name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.types.' + call.type) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ call.agent?.name || $t('video_calls.not_assigned') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(call.status)]">
                                            {{ $t('video_calls.status.' + call.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <Link
                                            :href="route('tenant.video-calls.show', call.id)"
                                            class="text-primary-600 hover:text-primary-900"
                                        >
                                            {{ $t('common.view') }}
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="todaysCalls.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.no_scheduled_today') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Completed Calls -->
                <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $t('video_calls.recent_calls') }}
                        </h2>
                    </div>
                    <div class="p-4">
                        <div v-if="recentCalls.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                            {{ $t('video_calls.no_recent_calls') }}
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="call in recentCalls"
                                :key="call.id"
                                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                            >
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mr-3">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white text-sm">
                                            {{ call.guest_name || '-' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ call.agent?.name }} - {{ formatDuration(call.duration_seconds) }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(call.ended_at).toLocaleDateString('fr-FR') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
