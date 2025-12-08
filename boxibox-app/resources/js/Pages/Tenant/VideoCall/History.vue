<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    calls: Object,
    agents: Array,
    filters: Object,
    statuses: Object,
    types: Object,
});

const localFilters = ref({
    status: props.filters?.status || '',
    type: props.filters?.type || '',
    agent_id: props.filters?.agent_id || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

const applyFilters = () => {
    router.get(route('tenant.video-calls.history'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        status: '',
        type: '',
        agent_id: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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
</script>

<template>
    <Head :title="$t('video_calls.history')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                            {{ $t('video_calls.call_history') }}
                        </h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('video_calls.history_subtitle') }}
                        </p>
                    </div>
                    <Link
                        :href="route('tenant.video-calls.index')"
                        class="text-primary-600 hover:text-primary-800"
                    >
                        {{ $t('video_calls.back_to_dashboard') }}
                    </Link>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.status_label') }}
                                </label>
                                <select
                                    v-model="localFilters.status"
                                    @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">{{ $t('common.all') }}</option>
                                    <option v-for="(label, key) in statuses" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.type') }}
                                </label>
                                <select
                                    v-model="localFilters.type"
                                    @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">{{ $t('common.all') }}</option>
                                    <option v-for="(label, key) in types" :key="key" :value="key">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.agent') }}
                                </label>
                                <select
                                    v-model="localFilters.agent_id"
                                    @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                                    <option value="">{{ $t('common.all') }}</option>
                                    <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                        {{ agent.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.date_from') }}
                                </label>
                                <input
                                    type="date"
                                    v-model="localFilters.date_from"
                                    @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.date_to') }}
                                </label>
                                <input
                                    type="date"
                                    v-model="localFilters.date_to"
                                    @change="applyFilters"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                            <div class="flex items-end">
                                <button
                                    @click="resetFilters"
                                    class="w-full px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                                >
                                    {{ $t('common.reset') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calls Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                    {{ $t('video_calls.date') }}
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
                                    {{ $t('video_calls.duration') }}
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
                            <tr v-for="call in calls.data" :key="call.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ formatDate(call.created_at) }}
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
                                    {{ types[call.type] || call.type }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ call.agent?.name || '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ formatDuration(call.duration_seconds) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 py-1 text-xs font-medium rounded-full', getStatusBadgeClass(call.status)]">
                                        {{ statuses[call.status] || call.status }}
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
                            <tr v-if="calls.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('video_calls.no_calls_found') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="calls.last_page > 1" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('common.showing') }} {{ calls.from }} - {{ calls.to }} {{ $t('common.of') }} {{ calls.total }}
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in calls.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-1 rounded text-sm',
                                        link.active
                                            ? 'bg-primary-600 text-white'
                                            : link.url
                                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-400 cursor-not-allowed'
                                    ]"
                                    v-html="link.label"
                                ></Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
