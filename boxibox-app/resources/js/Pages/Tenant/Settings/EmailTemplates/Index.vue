<template>
    <TenantLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Templates d'Emails</h1>
                    <p class="text-gray-600 dark:text-gray-400">Personnalisez vos emails automatiques</p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="initializeDefaults"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                    >
                        <i class="fas fa-magic mr-2"></i>
                        Créer les défauts
                    </button>
                    <Link
                        :href="route('tenant.settings.email-templates.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Nouveau template
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total templates</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-green-600">{{ stats.active }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Actifs</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-blue-600">{{ stats.system }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Système</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <div class="text-2xl font-bold text-purple-600">{{ stats.custom }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Personnalisés</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Rechercher un template..."
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <select
                        v-model="selectedCategory"
                        @change="filterByCategory"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">Toutes les catégories</option>
                        <option v-for="(label, key) in categories" :key="key" :value="key">
                            {{ label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Templates Grid -->
            <div v-if="filteredTemplates.length > 0" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="template in filteredTemplates"
                    :key="template.id"
                    class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition"
                >
                    <!-- Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ template.name }}</h3>
                                    <span
                                        v-if="template.is_system"
                                        class="px-2 py-0.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 rounded-full"
                                    >
                                        Système
                                    </span>
                                </div>
                                <span :class="getCategoryClass(template.category)" class="px-2 py-1 text-xs rounded-full">
                                    {{ template.category_label }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span
                                    :class="template.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                    class="px-2 py-0.5 text-xs rounded-full"
                                >
                                    {{ template.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                            <strong>Sujet:</strong> {{ template.subject }}
                        </div>

                        <!-- Variables -->
                        <div v-if="template.variables?.length" class="mb-3">
                            <div class="text-xs text-gray-500 mb-1">Variables disponibles:</div>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    v-for="variable in template.variables.slice(0, 4)"
                                    :key="variable"
                                    class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded font-mono"
                                >
                                    {{ variable }}
                                </span>
                                <span
                                    v-if="template.variables.length > 4"
                                    class="px-2 py-0.5 text-xs text-gray-500"
                                >
                                    +{{ template.variables.length - 4 }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>
                                <i class="fas fa-paper-plane mr-1"></i>
                                {{ template.usage_count }} envois
                            </span>
                            <span v-if="template.last_used_at">
                                Dernier: {{ template.last_used_at }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-2">
                        <button
                            @click="previewTemplate(template)"
                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition"
                            title="Prévisualiser"
                        >
                            <i class="fas fa-eye"></i>
                        </button>
                        <button
                            @click="duplicateTemplate(template)"
                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 transition"
                            title="Dupliquer"
                        >
                            <i class="fas fa-copy"></i>
                        </button>
                        <Link
                            :href="route('tenant.settings.email-templates.edit', template.id)"
                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-green-600 dark:hover:text-green-400 transition"
                            title="Modifier"
                        >
                            <i class="fas fa-edit"></i>
                        </Link>
                        <button
                            v-if="!template.is_system"
                            @click="confirmDelete(template)"
                            class="p-2 text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition"
                            title="Supprimer"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <i class="fas fa-envelope text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Aucun template</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Commencez par créer les templates par défaut ou créez un nouveau template personnalisé.
                </p>
                <div class="flex justify-center gap-3">
                    <button
                        @click="initializeDefaults"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                    >
                        <i class="fas fa-magic mr-2"></i>
                        Créer les défauts
                    </button>
                    <Link
                        :href="route('tenant.settings.email-templates.create')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        <i class="fas fa-plus mr-2"></i>
                        Créer un template
                    </Link>
                </div>
            </div>
        </div>

        <!-- Preview Modal -->
        <div v-if="showPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-3xl max-h-[90vh] overflow-hidden">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Prévisualisation: {{ previewingTemplate?.name }}
                    </h3>
                    <button @click="showPreviewModal = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[70vh]">
                    <div v-if="previewLoading" class="text-center py-8">
                        <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
                    </div>
                    <div v-else-if="previewContent">
                        <div class="mb-4 p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <strong class="text-sm text-gray-600 dark:text-gray-400">Sujet:</strong>
                            <div class="text-gray-900 dark:text-white">{{ previewContent.subject }}</div>
                        </div>
                        <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                            <iframe
                                :srcdoc="previewContent.body_html"
                                class="w-full h-96 bg-white"
                                sandbox
                            ></iframe>
                        </div>
                    </div>
                </div>
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button
                        @click="showPreviewModal = false"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    Supprimer le template
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    Êtes-vous sûr de vouloir supprimer le template "{{ deletingTemplate?.name }}" ? Cette action est irréversible.
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300"
                    >
                        Annuler
                    </button>
                    <button
                        @click="deleteTemplate"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                    >
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import axios from 'axios';

const props = defineProps({
    templates: Array,
    categories: Object,
    stats: Object,
    filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const showPreviewModal = ref(false);
const showDeleteModal = ref(false);
const previewingTemplate = ref(null);
const previewContent = ref(null);
const previewLoading = ref(false);
const deletingTemplate = ref(null);

const filteredTemplates = computed(() => {
    let result = [...props.templates];

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(t =>
            t.name.toLowerCase().includes(query) ||
            t.subject.toLowerCase().includes(query)
        );
    }

    if (selectedCategory.value) {
        result = result.filter(t => t.category === selectedCategory.value);
    }

    return result;
});

const getCategoryClass = (category) => {
    const classes = {
        billing: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
        contracts: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
        reminders: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
        notifications: 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
        marketing: 'bg-pink-100 text-pink-700 dark:bg-pink-900 dark:text-pink-300',
        welcome: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
        support: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
    };
    return classes[category] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300';
};

let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(route('tenant.settings.email-templates.index'), {
            search: searchQuery.value,
            category: selectedCategory.value,
        }, { preserveState: true, preserveScroll: true });
    }, 300);
};

const filterByCategory = () => {
    router.get(route('tenant.settings.email-templates.index'), {
        search: searchQuery.value,
        category: selectedCategory.value,
    }, { preserveState: true, preserveScroll: true });
};

const previewTemplate = async (template) => {
    previewingTemplate.value = template;
    showPreviewModal.value = true;
    previewLoading.value = true;
    previewContent.value = null;

    try {
        const response = await axios.get(route('tenant.settings.email-templates.preview', template.id));
        previewContent.value = response.data;
    } catch (error) {
        console.error('Preview error:', error);
    } finally {
        previewLoading.value = false;
    }
};

const duplicateTemplate = (template) => {
    router.post(route('tenant.settings.email-templates.duplicate', template.id));
};

const confirmDelete = (template) => {
    deletingTemplate.value = template;
    showDeleteModal.value = true;
};

const deleteTemplate = () => {
    router.delete(route('tenant.settings.email-templates.destroy', deletingTemplate.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingTemplate.value = null;
        }
    });
};

const initializeDefaults = () => {
    router.post(route('tenant.settings.email-templates.initialize'));
};
</script>
