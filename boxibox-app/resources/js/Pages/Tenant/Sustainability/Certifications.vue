<template>
    <TenantLayout :title="$t('sustainability.certifications')">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $t('sustainability.certifications') }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        {{ $t('sustainability.certifications_subtitle') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <select v-model="filterForm.status" @change="applyFilters"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                        <option :value="null">{{ $t('common.all') }}</option>
                        <option value="active">{{ $t('sustainability.valid') }}</option>
                        <option value="expiring">{{ $t('sustainability.expiring_soon') }}</option>
                        <option value="expired">{{ $t('sustainability.expired') }}</option>
                    </select>
                    <button @click="showCreateModal = true" class="btn-primary flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $t('sustainability.add_certification') }}
                    </button>
                </div>
            </div>

            <!-- Certifications Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="cert in certifications.data" :key="cert.id"
                    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div :class="[
                        'h-2',
                        statusColors[cert.status]?.bar || 'bg-gray-400'
                    ]"></div>
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="w-14 h-14 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <span :class="[
                                'px-2 py-1 rounded text-xs font-medium',
                                statusColors[cert.status]?.badge || 'bg-gray-100 text-gray-800'
                            ]">
                                {{ statusLabels[cert.status] }}
                            </span>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ cert.name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ types[cert.type] || cert.type }}</p>

                        <div class="mt-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.issuing_body') }}</span>
                                <span class="text-gray-900 dark:text-white">{{ cert.issuing_body }}</span>
                            </div>
                            <div v-if="cert.certification_number" class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.cert_number') }}</span>
                                <span class="text-gray-900 dark:text-white">{{ cert.certification_number }}</span>
                            </div>
                            <div v-if="cert.level" class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.level') }}</span>
                                <span :class="[
                                    'px-2 py-0.5 rounded text-xs font-medium',
                                    levelColors[cert.level] || 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ levels[cert.level] || cert.level }}
                                </span>
                            </div>
                            <div v-if="cert.score" class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.score') }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ cert.score }}/100</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.issued') }}</p>
                                    <p class="text-gray-900 dark:text-white">{{ formatDate(cert.issue_date) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-500 dark:text-gray-400">{{ $t('sustainability.expires') }}</p>
                                    <p :class="[
                                        cert.is_expired ? 'text-red-600 dark:text-red-400' :
                                        cert.is_expiring_soon ? 'text-yellow-600 dark:text-yellow-400' :
                                        'text-gray-900 dark:text-white'
                                    ]">
                                        {{ cert.expiry_date ? formatDate(cert.expiry_date) : $t('sustainability.no_expiry') }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="cert.days_until_expiry !== null && cert.days_until_expiry >= 0" class="mt-2">
                                <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                                    {{ cert.days_until_expiry }} {{ $t('common.days') }} {{ $t('sustainability.remaining') }}
                                </div>
                                <div class="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div :class="[
                                        'h-1.5 rounded-full',
                                        cert.days_until_expiry > 90 ? 'bg-green-500' :
                                        cert.days_until_expiry > 30 ? 'bg-yellow-500' : 'bg-red-500'
                                    ]" :style="{ width: Math.max(5, Math.min(100, cert.days_until_expiry / 365 * 100)) + '%' }"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <button @click="editCertification(cert)" class="text-sm text-primary-600 hover:text-primary-700">
                                {{ $t('common.edit') }}
                            </button>
                            <button @click="deleteCertification(cert)" class="text-sm text-red-600 hover:text-red-700">
                                {{ $t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="certifications.data.length === 0" class="text-center py-12 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
                <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">{{ $t('sustainability.no_certifications') }}</h3>
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $t('sustainability.no_certifications_desc') }}</p>
                <button @click="showCreateModal = true" class="mt-4 btn-primary">
                    {{ $t('sustainability.add_certification') }}
                </button>
            </div>

            <!-- Pagination -->
            <Pagination :links="certifications.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showCreateModal || showEditModal" @close="closeModal" maxWidth="xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ showEditModal ? $t('sustainability.edit_certification') : $t('sustainability.add_certification') }}
                </h3>
                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }}</label>
                            <input type="text" v-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.type') }}</label>
                            <select v-model="form.type" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
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
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.issuing_body') }}</label>
                            <input type="text" v-model="form.issuing_body" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.cert_number') }}</label>
                            <input type="text" v-model="form.certification_number" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.issue_date') }}</label>
                            <input type="date" v-model="form.issue_date" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.expiry_date') }}</label>
                            <input type="date" v-model="form.expiry_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.level') }}</label>
                            <select v-model="form.level" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                <option :value="null">{{ $t('common.none') }}</option>
                                <option v-for="(label, key) in levels" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('sustainability.score') }}</label>
                            <input type="number" v-model="form.score" min="0" max="100" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700" />
                        </div>
                    </div>
                    <div v-if="showEditModal" class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.is_active" id="is_active" class="rounded border-gray-300 dark:border-gray-600" />
                        <label for="is_active" class="text-sm text-gray-700 dark:text-gray-300">{{ $t('sustainability.active_certification') }}</label>
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
    certifications: Object,
    sites: Array,
    filters: Object,
    types: Object,
    levels: Object,
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCertification = ref(null);

const filterForm = ref({
    status: props.filters.status || null,
});

const form = useForm({
    site_id: null,
    name: '',
    type: 'iso_14001',
    issuing_body: '',
    certification_number: '',
    issue_date: new Date().toISOString().split('T')[0],
    expiry_date: null,
    level: null,
    score: null,
    is_active: true,
});

const statusColors = {
    valid: { bar: 'bg-green-500', badge: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    expiring: { bar: 'bg-yellow-500', badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    expired: { bar: 'bg-red-500', badge: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
    inactive: { bar: 'bg-gray-400', badge: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' },
};

const statusLabels = {
    valid: 'Valide',
    expiring: 'Expire bientôt',
    expired: 'Expiré',
    inactive: 'Inactif',
};

const levelColors = {
    bronze: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
    silver: 'bg-gray-200 text-gray-800 dark:bg-gray-600 dark:text-gray-200',
    gold: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    platinum: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
    certified: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    outstanding: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
};

const formatDate = (date) => new Date(date).toLocaleDateString('fr-FR');

const applyFilters = () => {
    router.get(route('tenant.sustainability.certifications'), filterForm.value, { preserveState: true });
};

const editCertification = (cert) => {
    editingCertification.value = cert;
    Object.keys(form).forEach(key => {
        if (key in cert) form[key] = cert[key];
    });
    showEditModal.value = true;
};

const deleteCertification = (cert) => {
    if (confirm('Supprimer cette certification ?')) {
        router.delete(route('tenant.sustainability.certifications.destroy', cert.id));
    }
};

const submitForm = () => {
    if (showEditModal.value) {
        form.put(route('tenant.sustainability.certifications.update', editingCertification.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('tenant.sustainability.certifications.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showCreateModal.value = false;
    showEditModal.value = false;
    editingCertification.value = null;
    form.reset();
};
</script>
