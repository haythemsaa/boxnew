<template>
    <TenantLayout :title="$t('sustainability.waste_records')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.waste_records') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.waste_records_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.site_id" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all_sites') }}</option>
                        <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                    </select>
                    <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('sustainability.add_record') }}
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.total_waste') }}</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ formatNumber(totals.total) }} kg</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.recycled') }}</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatNumber(totals.recycled) }} kg</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.recycling_rate') }}</p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ formatNumber(totals.recyclingRate) }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('sustainability.disposal_cost') }}</p>
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
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.general') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.recycling') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.cardboard') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.hazardous') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.organic') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('sustainability.rate') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ $t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="record in records.data" :key="record.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ formatDate(record.record_date) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ record.site?.name || '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                {{ formatNumber(record.general_waste_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 dark:text-green-400">
                                {{ formatNumber(record.recycling_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-yellow-600 dark:text-yellow-400">
                                {{ formatNumber(record.cardboard_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 dark:text-red-400">
                                {{ formatNumber(record.hazardous_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-emerald-600 dark:text-emerald-400">
                                {{ formatNumber(record.organic_kg) }} kg
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                <span :class="[
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    record.recycling_rate >= 50 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                    record.recycling_rate >= 25 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                                ]">
                                    {{ formatNumber(record.recycling_rate) }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button @click="editRecord(record)" class="text-primary-600 hover:text-primary-700 mr-3">
                                    {{ $t('common.edit') }}
                                </button>
                                <button @click="deleteRecord(record)" class="text-red-600 hover:text-red-700">
                                    {{ $t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-if="records.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ $t('sustainability.no_waste_records') }}</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('sustainability.no_waste_records_desc') }}</p>
                <button @click="showCreateModal = true" class="mt-4 btn-primary">
                    {{ $t('sustainability.add_record') }}
                </button>
            </div>

            <!-- Pagination -->
            <Pagination :links="records.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ showEditModal ? $t('sustainability.edit_record') : $t('sustainability.add_record') }}
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
                            <input type="date" v-model="form.record_date" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.general_waste') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.general_waste_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.recycling') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.recycling_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.cardboard') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.cardboard_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.hazardous') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.hazardous_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.organic') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.organic_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.disposal_cost') }} (€)</label>
                        <input type="number" step="0.01" v-model="form.disposal_cost" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
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
    records: Object,
    sites: Array,
    filters: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingRecord = ref(null);

const filterForm = ref({
    site_id: props.filters.site_id || null,
});

const form = useForm({
    site_id: null,
    record_date: new Date().toISOString().split('T')[0],
    general_waste_kg: 0,
    recycling_kg: 0,
    cardboard_kg: 0,
    hazardous_kg: 0,
    organic_kg: 0,
    disposal_cost: 0,
});

const totals = computed(() => {
    const data = props.records.data;
    const total = data.reduce((sum, r) => sum + parseFloat(r.total_kg || 0), 0);
    const recycled = data.reduce((sum, r) => sum + parseFloat(r.recycled_kg || 0), 0);
    return {
        total,
        recycled,
        recyclingRate: total > 0 ? (recycled / total) * 100 : 0,
        cost: data.reduce((sum, r) => sum + parseFloat(r.disposal_cost || 0), 0),
    };
});

const formatNumber = (num) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(num || 0);
const formatCurrency = (num) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

const applyFilters = () => {
    router.get(route('tenant.sustainability.waste'), filterForm.value, { preserveState: true });
};

const editRecord = (record) => {
    editingRecord.value = record;
    Object.keys(form).forEach(key => {
        if (key in record) form[key] = record[key];
    });
    showEditModal.value = true;
};

const deleteRecord = (record) => {
    if (confirm('Supprimer cet enregistrement ?')) {
        router.delete(route('tenant.sustainability.waste.destroy', record.id));
    }
};

const submitForm = () => {
    if (showEditModal.value) {
        form.put(route('tenant.sustainability.waste.update', editingRecord.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('tenant.sustainability.waste.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingRecord.value = null;
    form.reset();
};
</script>
