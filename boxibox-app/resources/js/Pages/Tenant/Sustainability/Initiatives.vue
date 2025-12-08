<template>
    <TenantLayout :title="$t('sustainability.initiatives')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.initiatives') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.initiatives_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.status" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all_statuses') }}</option>
                        <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('sustainability.add_initiative') }}
                    </button>
                </div>
            </div>

            <!-- Initiatives Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="initiative in initiatives.data" :key="initiative.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div :class="[
                                'w-12 h-12 rounded-lg flex items-center justify-center',
                                categoryColors[initiative.category]
                            ]">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="categoryIcons[initiative.category]"/>
                                </svg>
                            </div>
                            <span :class="[
                                'px-2 py-1 rounded text-xs font-medium',
                                statusColors[initiative.status]
                            ]">
                                {{ statuses[initiative.status] }}
                            </span>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ initiative.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ categories[initiative.category] }}</p>
                        <p v-if="initiative.description" class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">
                            {{ initiative.description }}
                        </p>

                        <!-- Progress -->
                        <div class="mt-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('common.progress') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ initiative.progress_percent }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full" :style="{ width: initiative.progress_percent + '%' }"></div>
                            </div>
                        </div>

                        <!-- Metrics -->
                        <div class="mt-4 grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div v-if="initiative.investment_cost">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.investment') }}</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(initiative.investment_cost) }}</p>
                            </div>
                            <div v-if="initiative.annual_savings">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.annual_savings') }}</p>
                                <p class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(initiative.annual_savings) }}</p>
                            </div>
                            <div v-if="initiative.co2_reduction_kg">
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.co2_reduction') }}</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatNumber(initiative.co2_reduction_kg) }} kg</p>
                            </div>
                            <div v-if="initiative.roi_years">
                                <p class="text-xs text-gray-500 dark:text-gray-400">ROI</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ initiative.roi_years }} {{ $t('common.years') }}</p>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4 flex justify-end gap-2">
                            <button @click="editInitiative(initiative)" class="text-sm text-primary-600 hover:text-primary-700">
                                {{ $t('common.edit') }}
                            </button>
                            <button @click="deleteInitiative(initiative)" class="text-sm text-red-600 hover:text-red-700">
                                {{ $t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="initiatives.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ $t('sustainability.no_initiatives') }}</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('sustainability.no_initiatives_desc') }}</p>
                <button @click="showCreateModal = true" class="mt-4 btn-primary">
                    {{ $t('sustainability.add_initiative') }}
                </button>
            </div>

            <!-- Pagination -->
            <Pagination :links="initiatives.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal" maxWidth="2xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ showEditModal ? $t('sustainability.edit_initiative') : $t('sustainability.add_initiative') }}
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }}</label>
                            <input type="text" v-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.category') }}</label>
                            <select v-model="form.category" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.site') }}</label>
                            <select v-model="form.site_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option :value="null">{{ $t('common.all_sites') }}</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.start_date') }}</label>
                            <input type="date" v-model="form.start_date" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.end_date') }}</label>
                            <input type="date" v-model="form.end_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.status') }}</label>
                            <select v-model="form.status" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.progress') }} (%)</label>
                            <input type="number" v-model="form.progress_percent" min="0" max="100" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.description') }}</label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.investment') }} (€)</label>
                            <input type="number" step="0.01" v-model="form.investment_cost" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.annual_savings') }} (€)</label>
                            <input type="number" step="0.01" v-model="form.annual_savings" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.co2_reduction') }} (kg)</label>
                            <input type="number" step="0.01" v-model="form.co2_reduction_kg" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
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
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    initiatives: Object,
    sites: Array,
    filters: Object,
    categories: Object,
    statuses: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingInitiative = ref(null);

const filterForm = ref({
    status: props.filters.status || null,
    category: props.filters.category || null,
});

const form = useForm({
    site_id: null,
    name: '',
    category: 'energy',
    description: '',
    start_date: new Date().toISOString().split('T')[0],
    end_date: null,
    investment_cost: null,
    annual_savings: null,
    co2_reduction_kg: null,
    status: 'planned',
    progress_percent: 0,
});

const categoryColors = {
    energy: 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400',
    waste: 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400',
    transport: 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400',
    water: 'bg-cyan-100 dark:bg-cyan-900 text-cyan-600 dark:text-cyan-400',
    materials: 'bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400',
    biodiversity: 'bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400',
};

const categoryIcons = {
    energy: 'M13 10V3L4 14h7v7l9-11h-7z',
    waste: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    transport: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
    water: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
    materials: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    biodiversity: 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064',
};

const statusColors = {
    planned: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
};

const formatNumber = (num) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(num || 0);
const formatCurrency = (num) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(num || 0);

const applyFilters = () => {
    router.get(route('tenant.sustainability.initiatives'), filterForm.value, { preserveState: true });
};

const editInitiative = (initiative) => {
    editingInitiative.value = initiative;
    Object.keys(form).forEach(key => {
        if (key in initiative) form[key] = initiative[key];
    });
    showEditModal.value = true;
};

const deleteInitiative = (initiative) => {
    if (confirm('Supprimer cette initiative ?')) {
        router.delete(route('tenant.sustainability.initiatives.destroy', initiative.id));
    }
};

const submitForm = () => {
    if (showEditModal.value) {
        form.put(route('tenant.sustainability.initiatives.update', editingInitiative.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('tenant.sustainability.initiatives.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingInitiative.value = null;
    form.reset();
};
</script>
