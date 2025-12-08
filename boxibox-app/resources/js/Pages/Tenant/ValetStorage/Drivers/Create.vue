<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    users: Array,
    vehicleTypes: Object,
});

const form = useForm({
    user_id: '',
    phone: '',
    license_number: '',
    vehicle_type: 'van',
    vehicle_plate: '',
    max_capacity_kg: '',
    max_capacity_m3: '',
});

const submit = () => {
    form.post(route('tenant.valet.drivers.store'));
};
</script>

<template>
    <Head :title="$t('valet.add_driver')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('tenant.valet.drivers')" class="text-sm text-gray-600 hover:text-gray-900 flex items-center mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $t('valet.add_driver') }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 space-y-6">
                        <!-- User Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.select_user') }} *
                            </label>
                            <select v-model="form.user_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">{{ $t('valet.choose_user') }}</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">{{ form.errors.user_id }}</p>
                            <p v-if="users.length === 0" class="mt-1 text-sm text-yellow-600">
                                {{ $t('valet.no_users_available') }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.phone') }} *
                            </label>
                            <input type="tel" v-model="form.phone" class="w-full rounded-md border-gray-300 shadow-sm" />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <!-- License -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.license_number') }}
                            </label>
                            <input type="text" v-model="form.license_number" class="w-full rounded-md border-gray-300 shadow-sm" />
                        </div>

                        <!-- Vehicle Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.vehicle_type') }} *
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label
                                    v-for="(label, key) in vehicleTypes"
                                    :key="key"
                                    :class="[
                                        'flex flex-col items-center justify-center p-4 border rounded-lg cursor-pointer transition-colors',
                                        form.vehicle_type === key ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <input type="radio" v-model="form.vehicle_type" :value="key" class="sr-only" />
                                    <svg v-if="key === 'bike'" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <svg v-else-if="key === 'van'" class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4 4m4-4l-4-4M8 17H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    <svg v-else class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>
                                    </svg>
                                    <span class="text-sm font-medium">{{ label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Vehicle Plate -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.vehicle_plate') }}
                            </label>
                            <input type="text" v-model="form.vehicle_plate" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="AB-123-CD" />
                        </div>

                        <!-- Capacity -->
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.max_capacity_kg') }}
                                </label>
                                <input type="number" step="0.01" v-model="form.max_capacity_kg" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.max_capacity_m3') }}
                                </label>
                                <input type="number" step="0.01" v-model="form.max_capacity_m3" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg flex justify-end space-x-3">
                        <Link :href="route('tenant.valet.drivers')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ $t('common.cancel') }}
                        </Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50">
                            {{ form.processing ? $t('common.saving') : $t('valet.add_driver') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
