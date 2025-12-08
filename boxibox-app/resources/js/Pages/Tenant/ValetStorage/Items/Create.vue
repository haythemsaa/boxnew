<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    customers: Array,
    sites: Array,
    categories: Object,
    sizes: Object,
    conditions: Object,
});

const form = useForm({
    customer_id: '',
    site_id: '',
    name: '',
    description: '',
    category: '',
    size: 'medium',
    weight_kg: '',
    volume_m3: '',
    condition: 'good',
    storage_location: '',
    monthly_fee: '',
    declared_value: '',
    is_fragile: false,
    requires_climate_control: false,
    special_instructions: '',
    photos: [],
});

const submit = () => {
    form.post(route('tenant.valet.items.store'));
};
</script>

<template>
    <Head :title="$t('valet.add_item')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('tenant.valet.items')" class="text-sm text-gray-600 hover:text-gray-900 flex items-center mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $t('valet.add_new_item') }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-6 space-y-6">
                        <!-- Customer & Site -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.customer') }} *
                                </label>
                                <select v-model="form.customer_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ $t('valet.select_customer') }}</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.first_name }} {{ customer.last_name }} - {{ customer.email }}
                                    </option>
                                </select>
                                <p v-if="form.errors.customer_id" class="mt-1 text-sm text-red-600">{{ form.errors.customer_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.site') }} *
                                </label>
                                <select v-model="form.site_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ $t('valet.select_site') }}</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                                </select>
                                <p v-if="form.errors.site_id" class="mt-1 text-sm text-red-600">{{ form.errors.site_id }}</p>
                            </div>
                        </div>

                        <!-- Item Name & Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.item_name') }} *
                            </label>
                            <input type="text" v-model="form.name" class="w-full rounded-md border-gray-300 shadow-sm" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.description') }}
                            </label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>

                        <!-- Category & Size -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.category') }}
                                </label>
                                <select v-model="form.category" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ $t('valet.select_category') }}</option>
                                    <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.size') }} *
                                </label>
                                <select v-model="form.size" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option v-for="(label, key) in sizes" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Weight & Volume -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.weight_kg') }}
                                </label>
                                <input type="number" step="0.01" v-model="form.weight_kg" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.volume_m3') }}
                                </label>
                                <input type="number" step="0.001" v-model="form.volume_m3" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>
                        </div>

                        <!-- Condition & Location -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.condition') }} *
                                </label>
                                <select v-model="form.condition" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option v-for="(label, key) in conditions" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.storage_location') }}
                                </label>
                                <input type="text" v-model="form.storage_location" class="w-full rounded-md border-gray-300 shadow-sm" :placeholder="$t('valet.location_placeholder')" />
                            </div>
                        </div>

                        <!-- Pricing -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.monthly_fee') }} *
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" v-model="form.monthly_fee" class="w-full rounded-md border-gray-300 shadow-sm pl-8" />
                                    <span class="absolute left-3 top-2.5 text-gray-500">€</span>
                                </div>
                                <p v-if="form.errors.monthly_fee" class="mt-1 text-sm text-red-600">{{ form.errors.monthly_fee }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.declared_value') }}
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" v-model="form.declared_value" class="w-full rounded-md border-gray-300 shadow-sm pl-8" />
                                    <span class="absolute left-3 top-2.5 text-gray-500">€</span>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requirements -->
                        <div class="flex items-center space-x-6">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.is_fragile" class="rounded border-gray-300 text-primary-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('valet.fragile') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="form.requires_climate_control" class="rounded border-gray-300 text-primary-600" />
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('valet.climate_control') }}</span>
                            </label>
                        </div>

                        <!-- Special Instructions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.special_instructions') }}
                            </label>
                            <textarea v-model="form.special_instructions" rows="2" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 rounded-b-lg flex justify-end space-x-3">
                        <Link :href="route('tenant.valet.items')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ $t('common.cancel') }}
                        </Link>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50">
                            {{ form.processing ? $t('common.saving') : $t('valet.create_item') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
