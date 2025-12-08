<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    call: Object,
});

const formatDuration = (seconds) => {
    if (!seconds) return '-';
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hrs > 0) {
        return `${hrs}h ${mins}m ${secs}s`;
    }
    return `${mins}m ${secs}s`;
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

const cancelCall = () => {
    if (confirm($t('video_calls.confirm_cancel'))) {
        router.post(route('tenant.video-calls.cancel', props.call.id));
    }
};

const joinCall = () => {
    router.post(route('tenant.video-calls.start', props.call.id));
};
</script>

<template>
    <Head :title="$t('video_calls.call_details')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link
                        :href="route('tenant.video-calls.index')"
                        class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 flex items-center mb-4"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $t('video_calls.call_details') }}
                            </h1>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                {{ $t('video_calls.call_id') }}: #{{ call.id }}
                            </p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span :class="['px-3 py-1 text-sm font-medium rounded-full', getStatusBadgeClass(call.status)]">
                                {{ $t('video_calls.status.' + call.status) }}
                            </span>
                            <button
                                v-if="call.status === 'scheduled' || call.status === 'waiting'"
                                @click="cancelCall"
                                class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50"
                            >
                                {{ $t('video_calls.cancel_call') }}
                            </button>
                            <button
                                v-if="call.status === 'waiting' || call.status === 'scheduled'"
                                @click="joinCall"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
                            >
                                {{ $t('video_calls.join_call') }}
                            </button>
                            <Link
                                v-if="call.status === 'in_progress'"
                                :href="route('tenant.video-calls.agent-room', call.id)"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                {{ $t('video_calls.enter_room') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Call Information -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.call_information') }}
                                </h2>
                            </div>
                            <div class="p-4">
                                <dl class="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.type') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $t('video_calls.types.' + call.type) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.site') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ call.site?.name || '-' }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.scheduled_at') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(call.scheduled_at) }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.agent') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ call.agent?.name || $t('video_calls.not_assigned') }}
                                        </dd>
                                    </div>
                                    <div v-if="call.started_at">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.started_at') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(call.started_at) }}
                                        </dd>
                                    </div>
                                    <div v-if="call.ended_at">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.ended_at') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDate(call.ended_at) }}
                                        </dd>
                                    </div>
                                    <div v-if="call.duration_seconds">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $t('video_calls.duration') }}</dt>
                                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                            {{ formatDuration(call.duration_seconds) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Participant Info -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.participant_info') }}
                                </h2>
                            </div>
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center mr-4">
                                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-gray-900 dark:text-white">
                                            {{ call.guest_name || call.customer?.full_name || call.prospect?.full_name || '-' }}
                                        </h3>
                                        <p v-if="call.guest_email || call.customer?.email || call.prospect?.email" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ call.guest_email || call.customer?.email || call.prospect?.email }}
                                        </p>
                                        <p v-if="call.guest_phone || call.customer?.phone || call.prospect?.phone" class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ call.guest_phone || call.customer?.phone || call.prospect?.phone }}
                                        </p>
                                        <div v-if="call.customer" class="mt-2">
                                            <Link
                                                :href="route('tenant.customers.show', call.customer.id)"
                                                class="text-sm text-primary-600 hover:text-primary-800"
                                            >
                                                {{ $t('video_calls.view_customer_profile') }}
                                            </Link>
                                        </div>
                                        <div v-if="call.prospect" class="mt-2">
                                            <Link
                                                :href="route('tenant.prospects.show', call.prospect.id)"
                                                class="text-sm text-primary-600 hover:text-primary-800"
                                            >
                                                {{ $t('video_calls.view_prospect_profile') }}
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary & Notes -->
                        <div v-if="call.summary || call.notes" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.summary_notes') }}
                                </h2>
                            </div>
                            <div class="p-4 space-y-4">
                                <div v-if="call.summary">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $t('video_calls.call_summary') }}
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-400">{{ call.summary }}</p>
                                </div>
                                <div v-if="call.notes">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $t('video_calls.internal_notes') }}
                                    </h4>
                                    <p class="text-gray-600 dark:text-gray-400">{{ call.notes }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Chat Messages -->
                        <div v-if="call.messages && call.messages.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.chat_history') }}
                                </h2>
                            </div>
                            <div class="p-4 max-h-96 overflow-y-auto space-y-3">
                                <div
                                    v-for="msg in call.messages"
                                    :key="msg.id"
                                    :class="[
                                        'max-w-[80%] rounded-lg p-3',
                                        msg.sender_type === 'agent'
                                            ? 'ml-auto bg-primary-100 dark:bg-primary-900/30'
                                            : 'bg-gray-100 dark:bg-gray-700'
                                    ]"
                                >
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ msg.sender_name }} - {{ formatDate(msg.created_at) }}
                                    </div>
                                    <div class="text-gray-900 dark:text-white">{{ msg.message }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Invitations -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.invitations') }}
                                </h2>
                            </div>
                            <div class="p-4">
                                <div v-if="!call.invitations || call.invitations.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-4">
                                    {{ $t('video_calls.no_invitations') }}
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="inv in call.invitations"
                                        :key="inv.id"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-3"
                                    >
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ inv.email }}
                                        </div>
                                        <div class="flex items-center justify-between mt-2">
                                            <span :class="[
                                                'px-2 py-0.5 text-xs rounded-full',
                                                inv.status === 'joined' ? 'bg-green-100 text-green-800' :
                                                inv.status === 'sent' ? 'bg-blue-100 text-blue-800' :
                                                inv.status === 'opened' ? 'bg-yellow-100 text-yellow-800' :
                                                'bg-gray-100 text-gray-800'
                                            ]">
                                                {{ inv.status }}
                                            </span>
                                            <span v-if="inv.sent_at" class="text-xs text-gray-500">
                                                {{ formatDate(inv.sent_at) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Room Link -->
                        <div v-if="call.status !== 'completed' && call.status !== 'cancelled'" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ $t('video_calls.room_link') }}
                                </h2>
                            </div>
                            <div class="p-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                    {{ $t('video_calls.share_link_info') }}
                                </p>
                                <div class="flex items-center space-x-2">
                                    <input
                                        type="text"
                                        :value="call.join_url"
                                        readonly
                                        class="flex-1 text-xs rounded-md border-gray-300 bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                    <button
                                        @click="navigator.clipboard.writeText(call.join_url)"
                                        class="p-2 text-gray-500 hover:text-gray-700"
                                        :title="$t('common.copy')"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>
