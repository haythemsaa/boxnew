<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    settings: Object,
    agents: Array,
});

const form = useForm({
    video_enabled: props.settings?.video_enabled ?? true,
    recording_enabled: props.settings?.recording_enabled ?? false,
    chat_enabled: props.settings?.chat_enabled ?? true,
    screen_sharing_enabled: props.settings?.screen_sharing_enabled ?? true,
    waiting_room_enabled: props.settings?.waiting_room_enabled ?? true,
    max_call_duration_minutes: props.settings?.max_call_duration_minutes ?? 60,
    max_concurrent_calls: props.settings?.max_concurrent_calls ?? 5,
    welcome_message: props.settings?.welcome_message ?? '',
    waiting_room_message: props.settings?.waiting_room_message ?? '',
    working_hours: props.settings?.working_hours ?? getDefaultWorkingHours(),
});

function getDefaultWorkingHours() {
    return [
        { day: 1, start: '09:00', end: '18:00', enabled: true },
        { day: 2, start: '09:00', end: '18:00', enabled: true },
        { day: 3, start: '09:00', end: '18:00', enabled: true },
        { day: 4, start: '09:00', end: '18:00', enabled: true },
        { day: 5, start: '09:00', end: '18:00', enabled: true },
        { day: 6, start: '09:00', end: '12:00', enabled: false },
        { day: 0, start: '09:00', end: '12:00', enabled: false },
    ];
}

const dayNames = {
    0: 'Dimanche',
    1: 'Lundi',
    2: 'Mardi',
    3: 'Mercredi',
    4: 'Jeudi',
    5: 'Vendredi',
    6: 'Samedi',
};

const submit = () => {
    form.post(route('tenant.video-calls.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="$t('video_calls.settings')" />

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
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $t('video_calls.video_settings') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $t('video_calls.settings_subtitle') }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- General Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.general_settings') }}
                            </h2>
                        </div>
                        <div class="p-4 space-y-4">
                            <!-- Enable Video Calls -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $t('video_calls.enable_video_calls') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.enable_video_calls_desc') }}
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.video_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Recording -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $t('video_calls.enable_recording') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.enable_recording_desc') }}
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.recording_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Chat -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $t('video_calls.enable_chat') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.enable_chat_desc') }}
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.chat_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Screen Sharing -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $t('video_calls.enable_screen_sharing') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.enable_screen_sharing_desc') }}
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.screen_sharing_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Waiting Room -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $t('video_calls.enable_waiting_room') }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $t('video_calls.enable_waiting_room_desc') }}
                                    </p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.waiting_room_enabled" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Limits -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.limits') }}
                            </h2>
                        </div>
                        <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.max_call_duration') }}
                                </label>
                                <div class="flex items-center">
                                    <input
                                        type="number"
                                        v-model="form.max_call_duration_minutes"
                                        min="5"
                                        max="180"
                                        class="w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $t('common.minutes') }}</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.max_concurrent_calls') }}
                                </label>
                                <input
                                    type="number"
                                    v-model="form.max_concurrent_calls"
                                    min="1"
                                    max="20"
                                    class="w-24 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.messages') }}
                            </h2>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.welcome_message') }}
                                </label>
                                <textarea
                                    v-model="form.welcome_message"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="$t('video_calls.welcome_message_placeholder')"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.waiting_room_message') }}
                                </label>
                                <textarea
                                    v-model="form.waiting_room_message"
                                    rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="$t('video_calls.waiting_room_message_placeholder')"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Working Hours -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.working_hours') }}
                            </h2>
                        </div>
                        <div class="p-4 space-y-3">
                            <div
                                v-for="(schedule, index) in form.working_hours"
                                :key="schedule.day"
                                class="flex items-center space-x-4"
                            >
                                <label class="flex items-center w-32">
                                    <input
                                        type="checkbox"
                                        v-model="schedule.enabled"
                                        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ dayNames[schedule.day] }}
                                    </span>
                                </label>
                                <input
                                    type="time"
                                    v-model="schedule.start"
                                    :disabled="!schedule.enabled"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 text-sm"
                                />
                                <span class="text-gray-500">-</span>
                                <input
                                    type="time"
                                    v-model="schedule.end"
                                    :disabled="!schedule.enabled"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 disabled:opacity-50 text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Agents -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ $t('video_calls.video_agents') }}
                            </h2>
                        </div>
                        <div class="p-4">
                            <div v-if="agents.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-8">
                                {{ $t('video_calls.no_agents_configured') }}
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="agent in agents"
                                    :key="agent.id"
                                    class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"
                                >
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center mr-3">
                                            <span class="text-primary-700 font-medium">{{ agent.name?.charAt(0) }}</span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">{{ agent.name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ agent.email }}</div>
                                        </div>
                                    </div>
                                    <span :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        agent.video_agent_status?.status === 'online' ? 'bg-green-100 text-green-800' :
                                        agent.video_agent_status?.status === 'busy' ? 'bg-red-100 text-red-800' :
                                        agent.video_agent_status?.status === 'away' ? 'bg-yellow-100 text-yellow-800' :
                                        'bg-gray-100 text-gray-800'
                                    ]">
                                        {{ agent.video_agent_status?.status || 'offline' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            {{ form.processing ? $t('common.saving') : $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
