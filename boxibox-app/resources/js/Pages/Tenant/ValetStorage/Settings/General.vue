<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    settings: Object,
    sites: Array,
});

const form = useForm({
    site_id: props.settings?.site_id || '',
    valet_enabled: props.settings?.valet_enabled ?? true,
    allow_same_day: props.settings?.allow_same_day ?? false,
    min_lead_time_hours: props.settings?.min_lead_time_hours ?? 24,
    max_items_per_order: props.settings?.max_items_per_order ?? 50,
    earliest_time: props.settings?.earliest_time?.substring(0, 5) || '08:00',
    latest_time: props.settings?.latest_time?.substring(0, 5) || '20:00',
    available_days: props.settings?.available_days || [1, 2, 3, 4, 5],
    time_slots: props.settings?.time_slots || [
        { label: 'Matin', value: 'morning', start: '08:00', end: '12:00' },
        { label: 'Après-midi', value: 'afternoon', start: '12:00', end: '18:00' },
        { label: 'Soir', value: 'evening', start: '18:00', end: '20:00' },
    ],
    free_delivery_threshold: props.settings?.free_delivery_threshold || '',
    terms_conditions: props.settings?.terms_conditions || '',
    pickup_instructions: props.settings?.pickup_instructions || '',
    delivery_instructions: props.settings?.delivery_instructions || '',
});

const days = [
    { value: 1, label: 'Lundi' },
    { value: 2, label: 'Mardi' },
    { value: 3, label: 'Mercredi' },
    { value: 4, label: 'Jeudi' },
    { value: 5, label: 'Vendredi' },
    { value: 6, label: 'Samedi' },
    { value: 7, label: 'Dimanche' },
];

const toggleDay = (dayValue) => {
    const index = form.available_days.indexOf(dayValue);
    if (index > -1) {
        form.available_days.splice(index, 1);
    } else {
        form.available_days.push(dayValue);
        form.available_days.sort();
    }
};

const submit = () => {
    form.post(route('tenant.valet.settings.update'));
};
</script>

<template>
    <Head :title="$t('valet.settings')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $t('valet.settings') }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {{ $t('valet.settings_subtitle') }}
                    </p>
                </div>

                <!-- Settings Nav -->
                <div class="flex space-x-4 mb-6">
                    <Link :href="route('tenant.valet.settings')" class="px-4 py-2 bg-primary-600 text-white rounded-lg">
                        {{ $t('valet.general') }}
                    </Link>
                    <Link :href="route('tenant.valet.zones')" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50">
                        {{ $t('valet.zones') }}
                    </Link>
                    <Link :href="route('tenant.valet.pricing')" class="px-4 py-2 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50">
                        {{ $t('valet.pricing') }}
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- General Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.general_settings') }}</h2>

                        <div class="space-y-6">
                            <!-- Enable/Disable -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $t('valet.enable_valet') }}</p>
                                    <p class="text-sm text-gray-500">{{ $t('valet.enable_valet_desc') }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.valet_enabled" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Same Day -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $t('valet.allow_same_day') }}</p>
                                    <p class="text-sm text-gray-500">{{ $t('valet.allow_same_day_desc') }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.allow_same_day" class="sr-only peer" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                </label>
                            </div>

                            <!-- Lead Time & Max Items -->
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.min_lead_time') }}
                                    </label>
                                    <div class="flex items-center">
                                        <input type="number" v-model="form.min_lead_time_hours" min="0" class="w-24 rounded-md border-gray-300 shadow-sm" />
                                        <span class="ml-2 text-gray-500">{{ $t('valet.hours') }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.max_items') }}
                                    </label>
                                    <input type="number" v-model="form.max_items_per_order" min="1" class="w-24 rounded-md border-gray-300 shadow-sm" />
                                </div>
                            </div>

                            <!-- Free Delivery -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.free_delivery_threshold') }}
                                </label>
                                <div class="flex items-center">
                                    <span class="text-gray-500 mr-2">€</span>
                                    <input type="number" step="0.01" v-model="form.free_delivery_threshold" class="w-32 rounded-md border-gray-300 shadow-sm" placeholder="0.00" />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $t('valet.free_delivery_desc') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Settings -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.schedule_settings') }}</h2>

                        <!-- Operating Hours -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.operating_hours') }}
                            </label>
                            <div class="flex items-center space-x-4">
                                <input type="time" v-model="form.earliest_time" class="rounded-md border-gray-300 shadow-sm" />
                                <span class="text-gray-500">{{ $t('valet.to') }}</span>
                                <input type="time" v-model="form.latest_time" class="rounded-md border-gray-300 shadow-sm" />
                            </div>
                        </div>

                        <!-- Available Days -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.available_days') }}
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="day in days"
                                    :key="day.value"
                                    type="button"
                                    @click="toggleDay(day.value)"
                                    :class="[
                                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                                        form.available_days.includes(day.value)
                                            ? 'bg-primary-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'
                                    ]"
                                >
                                    {{ day.label }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.instructions') }}</h2>

                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.pickup_instructions') }}
                                </label>
                                <textarea v-model="form.pickup_instructions" rows="3" class="w-full rounded-md border-gray-300 shadow-sm" :placeholder="$t('valet.pickup_instructions_placeholder')"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.delivery_instructions') }}
                                </label>
                                <textarea v-model="form.delivery_instructions" rows="3" class="w-full rounded-md border-gray-300 shadow-sm" :placeholder="$t('valet.delivery_instructions_placeholder')"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.terms_conditions') }}
                                </label>
                                <textarea v-model="form.terms_conditions" rows="4" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            {{ form.processing ? $t('common.saving') : $t('common.save') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
