<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    sites: Array,
    agents: Array,
    customers: Array,
    prospects: Array,
    types: Object,
});

const participantType = ref('guest');

const form = useForm({
    type: 'tour',
    site_id: '',
    agent_id: '',
    customer_id: '',
    prospect_id: '',
    guest_name: '',
    guest_email: '',
    guest_phone: '',
    scheduled_at: '',
    notes: '',
    send_invitation: true,
});

const submit = () => {
    form.post(route('tenant.video-calls.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const getMinDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() + 15);
    return now.toISOString().slice(0, 16);
};
</script>

<template>
    <Head :title="$t('video_calls.schedule_call')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
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
                        {{ $t('video_calls.schedule_new_call') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $t('video_calls.schedule_subtitle') }}
                    </p>
                </div>

                <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 space-y-6">
                        <!-- Call Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.call_type') }} *
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <label
                                    v-for="(label, key) in types"
                                    :key="key"
                                    :class="[
                                        'flex items-center justify-center px-4 py-3 border rounded-lg cursor-pointer transition-colors',
                                        form.type === key
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300'
                                            : 'border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'
                                    ]"
                                >
                                    <input
                                        type="radio"
                                        v-model="form.type"
                                        :value="key"
                                        class="sr-only"
                                    />
                                    <span class="text-sm font-medium">{{ label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
                        </div>

                        <!-- Date & Time -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.date_time') }} *
                            </label>
                            <input
                                type="datetime-local"
                                v-model="form.scheduled_at"
                                :min="getMinDateTime()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                            <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                        </div>

                        <!-- Site -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.site') }}
                            </label>
                            <select
                                v-model="form.site_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ $t('video_calls.select_site') }}</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">
                                    {{ site.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Agent -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.assign_agent') }}
                            </label>
                            <select
                                v-model="form.agent_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ $t('video_calls.auto_assign') }}</option>
                                <option v-for="agent in agents" :key="agent.id" :value="agent.id">
                                    {{ agent.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Participant Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.participant_type') }}
                            </label>
                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="participantType"
                                        value="guest"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('video_calls.new_guest') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="participantType"
                                        value="customer"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('video_calls.existing_customer') }}</span>
                                </label>
                                <label class="flex items-center">
                                    <input
                                        type="radio"
                                        v-model="participantType"
                                        value="prospect"
                                        class="text-primary-600 focus:ring-primary-500"
                                    />
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('video_calls.prospect') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Guest Info -->
                        <div v-if="participantType === 'guest'" class="space-y-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('video_calls.guest_name') }} *
                                </label>
                                <input
                                    type="text"
                                    v-model="form.guest_name"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                />
                                <p v-if="form.errors.guest_name" class="mt-1 text-sm text-red-600">{{ form.errors.guest_name }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $t('video_calls.guest_email') }}
                                    </label>
                                    <input
                                        type="email"
                                        v-model="form.guest_email"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $t('video_calls.guest_phone') }}
                                    </label>
                                    <input
                                        type="tel"
                                        v-model="form.guest_phone"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Existing Customer -->
                        <div v-if="participantType === 'customer'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.select_customer') }} *
                            </label>
                            <select
                                v-model="form.customer_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ $t('video_calls.choose_customer') }}</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.first_name }} {{ customer.last_name }} - {{ customer.email }}
                                </option>
                            </select>
                            <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                        </div>

                        <!-- Prospect -->
                        <div v-if="participantType === 'prospect'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.select_prospect') }} *
                            </label>
                            <select
                                v-model="form.prospect_id"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ $t('video_calls.choose_prospect') }}</option>
                                <option v-for="prospect in prospects" :key="prospect.id" :value="prospect.id">
                                    {{ prospect.first_name }} {{ prospect.last_name }} - {{ prospect.email }}
                                </option>
                            </select>
                            <p v-if="form.errors.prospect_id" class="mt-1 text-sm text-red-600">{{ form.errors.prospect_id }}</p>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('video_calls.notes') }}
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                :placeholder="$t('video_calls.notes_placeholder')"
                            ></textarea>
                        </div>

                        <!-- Send Invitation -->
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                v-model="form.send_invitation"
                                id="send_invitation"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            />
                            <label for="send_invitation" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                {{ $t('video_calls.send_invitation_email') }}
                            </label>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg flex justify-end space-x-3">
                        <Link
                            :href="route('tenant.video-calls.index')"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            {{ $t('common.cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
                        >
                            {{ form.processing ? $t('common.saving') : $t('video_calls.schedule_call') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
