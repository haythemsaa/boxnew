<template>
    <TenantLayout :title="$t('sustainability.goals')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.goals') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.goals_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.status" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all') }}</option>
                        <option value="active">{{ $t('sustainability.active_goals') }}</option>
                        <option value="achieved">{{ $t('sustainability.achieved_goals') }}</option>
                    </select>
                    <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('sustainability.add_goal') }}
                    </button>
                </div>
            </div>

            <!-- Goals List -->
            <div class="space-y-4">
                <div v-for="goal in goals.data" :key="goal.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ goal.name }}</h3>
                                <span v-if="goal.is_achieved" class="px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded text-xs font-medium">
                                    {{ $t('sustainability.achieved') }}
                                </span>
                                <span v-else-if="goal.is_on_track" class="px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded text-xs font-medium">
                                    {{ $t('sustainability.on_track') }}
                                </span>
                                <span v-else class="px-2 py-0.5 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded text-xs font-medium">
                                    {{ $t('sustainability.behind_schedule') }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                {{ metrics[goal.metric] }} - {{ $t('sustainability.target') }}: {{ goal.target_year }}
                            </p>
                            <p v-if="goal.description" class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                {{ goal.description }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="editGoal(goal)" class="text-primary-600 hover:text-primary-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="deleteGoal(goal)" class="text-red-600 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-6">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $t('sustainability.baseline') }}: {{ formatNumber(goal.baseline_value) }} {{ goal.unit }}
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ goal.progress_percent }}%
                            </span>
                            <span class="text-gray-500 dark:text-gray-400">
                                {{ $t('sustainability.target') }}: {{ formatNumber(goal.target_value) }} {{ goal.unit }}
                            </span>
                        </div>
                        <div class="relative w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div :class="[
                                'h-4 rounded-full transition-all',
                                goal.is_achieved ? 'bg-green-600' : goal.is_on_track ? 'bg-blue-600' : 'bg-yellow-600'
                            ]" :style="{ width: Math.min(100, goal.progress_percent) + '%' }"></div>
                            <!-- Current value marker -->
                            <div class="absolute top-full mt-1 transform -translate-x-1/2 text-xs text-gray-600 dark:text-gray-400"
                                :style="{ left: Math.min(100, goal.progress_percent) + '%' }">
                                {{ formatNumber(goal.current_value) }} {{ goal.unit }}
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="mt-8 grid grid-cols-3 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.remaining') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ formatNumber(goal.remaining) }} {{ goal.unit }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.years_left') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ goal.target_year - currentYear }}</p>
                        </div>
                        <div v-if="goal.achieved_at">
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('sustainability.achieved_date') }}</p>
                            <p class="font-medium text-green-600 dark:text-green-400">{{ formatDate(goal.achieved_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="goals.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ $t('sustainability.no_goals') }}</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('sustainability.no_goals_desc') }}</p>
                <button @click="showCreateModal = true" class="mt-4 btn-primary">
                    {{ $t('sustainability.add_goal') }}
                </button>
            </div>

            <!-- Pagination -->
            <Pagination :links="goals.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ showEditModal ? $t('sustainability.edit_goal') : $t('sustainability.add_goal') }}
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }}</label>
                        <input type="text" v-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.metric') }}</label>
                            <select v-model="form.metric" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="(label, key) in metrics" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.unit') }}</label>
                            <input type="text" v-model="form.unit" required placeholder="kg, %, kWh..." class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.baseline_value') }}</label>
                            <input type="number" step="0.01" v-model="form.baseline_value" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.current_value') }}</label>
                            <input type="number" step="0.01" v-model="form.current_value" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.target_value') }}</label>
                            <input type="number" step="0.01" v-model="form.target_value" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.target_year') }}</label>
                        <select v-model="form.target_year" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                            <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.description') }}</label>
                        <textarea v-model="form.description" rows="3" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"></textarea>
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
    goals: Object,
    filters: Object,
    metrics: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingGoal = ref(null);
const currentYear = new Date().getFullYear();

const filterForm = ref({
    status: props.filters.status || null,
});

const form = useForm({
    name: '',
    metric: 'co2_reduction',
    baseline_value: 0,
    target_value: 0,
    current_value: null,
    unit: 'kg',
    target_year: currentYear + 5,
    description: '',
});

const availableYears = computed(() => {
    return Array.from({ length: 20 }, (_, i) => currentYear + i);
});

const formatNumber = (num) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(num || 0);
const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

const applyFilters = () => {
    router.get(route('tenant.sustainability.goals'), filterForm.value, { preserveState: true });
};

const editGoal = (goal) => {
    editingGoal.value = goal;
    Object.keys(form).forEach(key => {
        if (key in goal) form[key] = goal[key];
    });
    showEditModal.value = true;
};

const deleteGoal = (goal) => {
    if (confirm('Supprimer cet objectif ?')) {
        router.delete(route('tenant.sustainability.goals.destroy', goal.id));
    }
};

const submitForm = () => {
    if (showEditModal.value) {
        form.put(route('tenant.sustainability.goals.update', editingGoal.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('tenant.sustainability.goals.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingGoal.value = null;
    form.reset();
};
</script>
