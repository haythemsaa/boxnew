<template>
    <TenantLayout :title="$t('sustainability.energy_readings')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.energy_readings') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.energy_readings_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.site_id" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all_sites') }}</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                    </select>
                    <button @click="showCreateModal = true"
                        class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('sustainability.add_reading') }}
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.total_electricity') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(totals.electricity) }} kWh</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.total_gas') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(totals.gas) }} m³</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.solar_production') }}</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(totals.solar) }} kWh</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.total_cost') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(totals.cost) }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('common.date') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('common.site') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.electricity') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.gas') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.water') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.solar') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.co2') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="reading in readings.data" :key="reading.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ formatDate(reading.reading_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ reading.site?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                {{ formatNumber(reading.electricity_kwh) }} kWh
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                {{ formatNumber(reading.gas_m3) }} m³
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                                {{ formatNumber(reading.water_m3) }} m³
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">
                                {{ formatNumber(reading.solar_generated_kwh) }} kWh
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                {{ formatNumber(reading.co2_emissions_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button @click="editReading(reading)" class="text-primary-600 hover:text-primary-700 mr-3">
                                    {{ $t('common.edit') }}
                                </button>
                                <button @click="deleteReading(reading)" class="text-red-600 hover:text-red-700">
                                    {{ $t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <Pagination :links="readings.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ showEditModal ? $t('sustainability.edit_reading') : $t('sustainability.add_reading') }}
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.site') }}</label>
                            <select v-model="form.site_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.date') }}</label>
                            <input type="date" v-model="form.reading_date" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.electricity') }} (kWh)</label>
                            <input type="number" step="0.01" v-model="form.electricity_kwh" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.gas') }} (m³)</label>
                            <input type="number" step="0.01" v-model="form.gas_m3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.water') }} (m³)</label>
                            <input type="number" step="0.01" v-model="form.water_m3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.solar') }} (kWh)</label>
                            <input type="number" step="0.01" v-model="form.solar_generated_kwh" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.electricity_cost') }}</label>
                            <input type="number" step="0.01" v-model="form.electricity_cost" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.gas_cost') }}</label>
                            <input type="number" step="0.01" v-model="form.gas_cost" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.water_cost') }}</label>
                            <input type="number" step="0.01" v-model="form.water_cost" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="closeModal" class="btn-secondary">{{ $t('common.cancel') }}</button>
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ showEditModal ? $t('common.save') : $t('common.create') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    readings: Object,
    sites: Array,
    filters: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingReading = ref(null);

const filterForm = ref({
    site_id: props.filters.site_id || null,
    year: props.filters.year || new Date().getFullYear(),
});

const form = useForm({
    site_id: null,
    reading_date: new Date().toISOString().split('T')[0],
    electricity_kwh: 0,
    gas_m3: 0,
    water_m3: 0,
    solar_generated_kwh: 0,
    electricity_cost: 0,
    gas_cost: 0,
    water_cost: 0,
});

const totals = computed(() => ({
    electricity: props.readings.data.reduce((sum, r) => sum + parseFloat(r.electricity_kwh || 0), 0),
    gas: props.readings.data.reduce((sum, r) => sum + parseFloat(r.gas_m3 || 0), 0),
    solar: props.readings.data.reduce((sum, r) => sum + parseFloat(r.solar_generated_kwh || 0), 0),
    cost: props.readings.data.reduce((sum, r) => sum + parseFloat(r.total_cost || 0), 0),
}));

const formatNumber = (num) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(num || 0);
const formatCurrency = (num) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

const applyFilters = () => {
    router.get(route('tenant.sustainability.energy'), filterForm.value, { preserveState: true });
};

const editReading = (reading) => {
    editingReading.value = reading;
    form.site_id = reading.site_id;
    form.reading_date = reading.reading_date;
    form.electricity_kwh = reading.electricity_kwh;
    form.gas_m3 = reading.gas_m3;
    form.water_m3 = reading.water_m3;
    form.solar_generated_kwh = reading.solar_generated_kwh;
    form.electricity_cost = reading.electricity_cost;
    form.gas_cost = reading.gas_cost;
    form.water_cost = reading.water_cost;
    showEditModal.value = true;
};

const deleteReading = (reading) => {
    if (confirm('Supprimer ce relevé ?')) {
        router.delete(route('tenant.sustainability.energy.destroy', reading.id));
    }
};

const submitForm = () => {
    if (showEditModal.value) {
        form.put(route('tenant.sustainability.energy.update', editingReading.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('tenant.sustainability.energy.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingReading.value = null;
    form.reset();
};
</script>
