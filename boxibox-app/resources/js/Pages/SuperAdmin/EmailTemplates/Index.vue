<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    MagnifyingGlassIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    EyeIcon,
    DocumentDuplicateIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    templates: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const category = ref(props.filters?.category || '')

const applyFilters = () => {
    router.get(route('superadmin.email-templates.index'), {
        search: search.value,
        category: category.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    category.value = ''
    applyFilters()
}

const deleteTemplate = (template) => {
    if (confirm(`Supprimer le template "${template.name}" ?`)) {
        router.delete(route('superadmin.email-templates.destroy', template.id))
    }
}

const toggleStatus = (template) => {
    router.post(route('superadmin.email-templates.toggle', template.id))
}

const duplicateTemplate = (template) => {
    router.post(route('superadmin.email-templates.duplicate', template.id))
}

const getCategoryColor = (cat) => {
    const colors = {
        system: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        tenant: 'bg-green-500/10 text-green-400 border-green-500/20',
        billing: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        support: 'bg-purple-500/10 text-purple-400 border-purple-500/20',
        marketing: 'bg-pink-500/10 text-pink-400 border-pink-500/20',
    }
    return colors[cat] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}
</script>

<template>
    <Head title="Templates Email" />

    <SuperAdminLayout title="Templates Email">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Templates Email</h1>
                    <p class="mt-1 text-sm text-gray-400">Gérez les modèles d'emails du système</p>
                </div>
                <Link
                    :href="route('superadmin.email-templates.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouveau Template
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Actifs</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Système</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.by_category?.system || 0 }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Facturation</div>
                    <div class="mt-1 text-2xl font-semibold text-yellow-400">{{ stats.by_category?.billing || 0 }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div class="sm:col-span-2">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Rechercher..."
                                class="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:ring-purple-500 focus:border-purple-500"
                            />
                        </div>
                    </div>
                    <select v-model="category" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Toutes catégories</option>
                        <option value="system">Système</option>
                        <option value="tenant">Tenant</option>
                        <option value="billing">Facturation</option>
                        <option value="support">Support</option>
                        <option value="marketing">Marketing</option>
                    </select>
                    <button @click="clearFilters" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="template in templates.data"
                    :key="template.id"
                    class="bg-gray-800 rounded-xl border border-gray-700 p-4 hover:border-gray-600 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-600/20 rounded-lg">
                                <EnvelopeIcon class="h-5 w-5 text-purple-400" />
                            </div>
                            <div>
                                <h3 class="font-medium text-white">{{ template.name }}</h3>
                                <p class="text-sm text-gray-400">{{ template.slug }}</p>
                            </div>
                        </div>
                        <button
                            @click="toggleStatus(template)"
                            :class="[
                                template.is_active ? 'bg-green-600' : 'bg-gray-600',
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors'
                            ]"
                        >
                            <span
                                :class="[
                                    template.is_active ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition mt-0.5 ml-0.5'
                                ]"
                            />
                        </button>
                    </div>

                    <div class="mt-3">
                        <p class="text-sm text-gray-300 line-clamp-2">{{ template.subject }}</p>
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span :class="[getCategoryColor(template.category), 'px-2 py-1 text-xs rounded-full border']">
                            {{ template.category }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2 pt-3 border-t border-gray-700">
                        <Link
                            :href="route('superadmin.email-templates.show', template.id)"
                            class="flex-1 flex items-center justify-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <EyeIcon class="h-4 w-4" />
                            Voir
                        </Link>
                        <Link
                            :href="route('superadmin.email-templates.edit', template.id)"
                            class="flex-1 flex items-center justify-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Modifier
                        </Link>
                        <button
                            @click="duplicateTemplate(template)"
                            class="p-1.5 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                            title="Dupliquer"
                        >
                            <DocumentDuplicateIcon class="h-4 w-4" />
                        </button>
                        <button
                            @click="deleteTemplate(template)"
                            class="p-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                            title="Supprimer"
                        >
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                <div v-if="templates.data.length === 0" class="col-span-full">
                    <div class="bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                        <EnvelopeIcon class="mx-auto h-12 w-12 text-gray-500" />
                        <h3 class="mt-2 text-sm font-medium text-gray-300">Aucun template</h3>
                        <p class="mt-1 text-sm text-gray-500">Créez votre premier template email.</p>
                        <div class="mt-6">
                            <Link
                                :href="route('superadmin.email-templates.create')"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                            >
                                <PlusIcon class="h-5 w-5" />
                                Nouveau Template
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="templates.links && templates.links.length > 3" class="flex justify-center gap-1">
                <Link
                    v-for="link in templates.links"
                    :key="link.label"
                    :href="link.url"
                    :class="[
                        link.active ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                        'px-3 py-1 text-sm rounded',
                        !link.url && 'opacity-50 cursor-not-allowed'
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </SuperAdminLayout>
</template>
