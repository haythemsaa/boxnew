<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';

const props = defineProps({
    customers: Array,
    sites: Array,
    drivers: Array,
    types: Object,
    timeSlots: Object,
    categories: Object,
    sizes: Object,
});

const form = useForm({
    customer_id: '',
    site_id: '',
    type: 'pickup',
    requested_date: '',
    time_slot: 'morning',
    assigned_driver_id: '',
    address_line1: '',
    address_line2: '',
    city: '',
    postal_code: '',
    floor: '',
    has_elevator: false,
    access_code: '',
    access_instructions: '',
    contact_name: '',
    contact_phone: '',
    contact_email: '',
    notes: '',
    items: [{ valet_item_id: '', item_description: '', category: '', size: 'medium', quantity: 1, is_new_item: true }],
});

const selectedCustomer = computed(() => {
    return props.customers.find(c => c.id === form.customer_id);
});

const customerItems = computed(() => {
    return selectedCustomer.value?.valet_items || [];
});

watch(() => form.customer_id, (newVal) => {
    if (newVal && selectedCustomer.value) {
        const customer = selectedCustomer.value;
        form.contact_name = `${customer.first_name} ${customer.last_name}`;
        form.contact_email = customer.email;
        form.contact_phone = customer.phone || '';
    }
});

const addItem = () => {
    form.items.push({ valet_item_id: '', item_description: '', category: '', size: 'medium', quantity: 1, is_new_item: true });
};

const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const toggleItemType = (index) => {
    form.items[index].is_new_item = !form.items[index].is_new_item;
    if (form.items[index].is_new_item) {
        form.items[index].valet_item_id = '';
    } else {
        form.items[index].item_description = '';
        form.items[index].category = '';
        form.items[index].size = '';
    }
};

const getMinDate = () => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
};

const submit = () => {
    form.post(route('tenant.valet.orders.store'));
};
</script>

<template>
    <Head :title="$t('valet.new_order')" />

    <TenantLayout>
        <div class="py-6">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <Link :href="route('tenant.valet.orders')" class="text-sm text-gray-600 hover:text-gray-900 flex items-center mb-4">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        {{ $t('common.back') }}
                    </Link>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ $t('valet.create_order') }}
                    </h1>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Customer & Type -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.order_details') }}</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.customer') }} *
                                </label>
                                <select v-model="form.customer_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ $t('valet.select_customer') }}</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.first_name }} {{ customer.last_name }}
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

                        <!-- Order Type -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.order_type') }} *
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label
                                    v-for="(label, key) in types"
                                    :key="key"
                                    :class="[
                                        'flex items-center justify-center px-4 py-3 border rounded-lg cursor-pointer transition-colors',
                                        form.type === key ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 hover:bg-gray-50'
                                    ]"
                                >
                                    <input type="radio" v-model="form.type" :value="key" class="sr-only" />
                                    <span class="mr-2 text-xl">{{ key === 'pickup' ? '↑' : key === 'delivery' ? '↓' : '↕' }}</span>
                                    <span class="font-medium">{{ label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.requested_date') }} *
                                </label>
                                <input type="date" v-model="form.requested_date" :min="getMinDate()" class="w-full rounded-md border-gray-300 shadow-sm" />
                                <p v-if="form.errors.requested_date" class="mt-1 text-sm text-red-600">{{ form.errors.requested_date }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.time_slot') }} *
                                </label>
                                <select v-model="form.time_slot" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option v-for="(label, key) in timeSlots" :key="key" :value="key">{{ label }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Driver -->
                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ $t('valet.assign_driver') }}
                            </label>
                            <select v-model="form.assigned_driver_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">{{ $t('valet.auto_assign') }}</option>
                                <option v-for="driver in drivers" :key="driver.id" :value="driver.user_id">
                                    {{ driver.user?.name }} ({{ driver.vehicle_type }})
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.pickup_delivery_address') }}</h2>

                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.address_line1') }} *
                                </label>
                                <input type="text" v-model="form.address_line1" class="w-full rounded-md border-gray-300 shadow-sm" />
                                <p v-if="form.errors.address_line1" class="mt-1 text-sm text-red-600">{{ form.errors.address_line1 }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.address_line2') }}
                                </label>
                                <input type="text" v-model="form.address_line2" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.postal_code') }} *
                                    </label>
                                    <input type="text" v-model="form.postal_code" class="w-full rounded-md border-gray-300 shadow-sm" />
                                    <p v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">{{ form.errors.postal_code }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.city') }} *
                                    </label>
                                    <input type="text" v-model="form.city" class="w-full rounded-md border-gray-300 shadow-sm" />
                                    <p v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.floor') }}
                                    </label>
                                    <input type="text" v-model="form.floor" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Ex: 3" />
                                </div>
                                <div class="flex items-end">
                                    <label class="flex items-center">
                                        <input type="checkbox" v-model="form.has_elevator" class="rounded border-gray-300 text-primary-600" />
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $t('valet.has_elevator') }}</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        {{ $t('valet.access_code') }}
                                    </label>
                                    <input type="text" v-model="form.access_code" class="w-full rounded-md border-gray-300 shadow-sm" />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.access_instructions') }}
                                </label>
                                <textarea v-model="form.access_instructions" rows="2" class="w-full rounded-md border-gray-300 shadow-sm" :placeholder="$t('valet.access_instructions_placeholder')"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ $t('valet.contact_info') }}</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.contact_name') }} *
                                </label>
                                <input type="text" v-model="form.contact_name" class="w-full rounded-md border-gray-300 shadow-sm" />
                                <p v-if="form.errors.contact_name" class="mt-1 text-sm text-red-600">{{ form.errors.contact_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.contact_phone') }} *
                                </label>
                                <input type="tel" v-model="form.contact_phone" class="w-full rounded-md border-gray-300 shadow-sm" />
                                <p v-if="form.errors.contact_phone" class="mt-1 text-sm text-red-600">{{ form.errors.contact_phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ $t('valet.contact_email') }}
                                </label>
                                <input type="email" v-model="form.contact_email" class="w-full rounded-md border-gray-300 shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Items -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-white">{{ $t('valet.items') }}</h2>
                            <button type="button" @click="addItem" class="text-sm text-primary-600 hover:text-primary-800">
                                + {{ $t('valet.add_item') }}
                            </button>
                        </div>

                        <div v-for="(item, index) in form.items" :key="index" class="border border-gray-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('valet.item') }} {{ index + 1 }}</span>
                                <div class="flex items-center space-x-4">
                                    <button
                                        type="button"
                                        @click="toggleItemType(index)"
                                        class="text-xs text-primary-600 hover:text-primary-800"
                                    >
                                        {{ item.is_new_item ? $t('valet.select_existing') : $t('valet.new_item') }}
                                    </button>
                                    <button
                                        v-if="form.items.length > 1"
                                        type="button"
                                        @click="removeItem(index)"
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Existing Item Selection (for delivery) -->
                            <div v-if="!item.is_new_item && (form.type === 'delivery' || form.type === 'pickup_delivery')">
                                <select v-model="item.valet_item_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">{{ $t('valet.select_item') }}</option>
                                    <option v-for="valetItem in customerItems" :key="valetItem.id" :value="valetItem.id">
                                        {{ valetItem.barcode }} - {{ valetItem.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- New Item (for pickup) -->
                            <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-2">
                                    <input
                                        type="text"
                                        v-model="item.item_description"
                                        class="w-full rounded-md border-gray-300 shadow-sm"
                                        :placeholder="$t('valet.item_description')"
                                    />
                                </div>
                                <div>
                                    <select v-model="item.category" class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">{{ $t('valet.category') }}</option>
                                        <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <select v-model="item.size" class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option v-for="(label, key) in sizes" :key="key" :value="key">{{ label }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="text-sm text-gray-600 dark:text-gray-400">{{ $t('valet.quantity') }}</label>
                                <input type="number" v-model="item.quantity" min="1" class="w-20 ml-2 rounded-md border-gray-300 shadow-sm text-sm" />
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ $t('valet.notes') }}
                        </label>
                        <textarea v-model="form.notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3">
                        <Link :href="route('tenant.valet.orders')" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            {{ $t('common.cancel') }}
                        </Link>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50">
                            {{ form.processing ? $t('common.saving') : $t('valet.create_order') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </TenantLayout>
</template>
