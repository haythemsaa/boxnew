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
    MegaphoneIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    announcements: Object,
    stats: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const type = ref(props.filters?.type || '')
const target = ref(props.filters?.target || '')

const applyFilters = () => {
    router.get(route('superadmin.announcements.index'), {
        search: search.value,
        type: type.value,
        target: target.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const clearFilters = () => {
    search.value = ''
    type.value = ''
    target.value = ''
    applyFilters()
}

const deleteAnnouncement = (announcement) => {
    if (confirm(`Supprimer l'annonce "${announcement.title}" ?`)) {
        router.delete(route('superadmin.announcements.destroy', announcement.id))
    }
}

const toggleStatus = (announcement) => {
    router.post(route('superadmin.announcements.toggle', announcement.id))
}

const getTypeColor = (t) => {
    const colors = {
        info: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
        warning: 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
        success: 'bg-green-500/10 text-green-400 border-green-500/20',
        error: 'bg-red-500/10 text-red-400 border-red-500/20',
    }
    return colors[t] || 'bg-gray-500/10 text-gray-400 border-gray-500/20'
}

const getTargetLabel = (t) => {
    const labels = {
        all: 'Tous',
        tenants: 'Tenants',
        specific: 'Spécifique',
    }
    return labels[t] || t
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    })
}
</script>

<template>
    <Head title="Annonces" />

    <SuperAdminLayout title="Annonces">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Annonces</h1>
                    <p class="mt-1 text-sm text-gray-400">Communiquez avec les utilisateurs de la plateforme</p>
                </div>
                <Link
                    :href="route('superadmin.announcements.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouvelle Annonce
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Actives</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Planifiées</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.scheduled }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Expirées</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-400">{{ stats.expired }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-5">
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
                    <select v-model="type" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Tous les types</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="success">Success</option>
                        <option value="error">Error</option>
                    </select>
                    <select v-model="target" @change="applyFilters" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="">Toutes cibles</option>
                        <option value="all">Tous</option>
                        <option value="tenants">Tenants</option>
                        <option value="specific">Spécifique</option>
                    </select>
                    <button @click="clearFilters" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors">
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="space-y-4">
                <div
                    v-for="announcement in announcements.data"
                    :key="announcement.id"
                    class="bg-gray-800 rounded-xl border border-gray-700 p-4 hover:border-gray-600 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4">
                            <div :class="[getTypeColor(announcement.type), 'p-2 rounded-lg border']">
                                <MegaphoneIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="font-medium text-white">{{ announcement.title }}</h3>
                                <p class="mt-1 text-sm text-gray-400 line-clamp-2">{{ announcement.content }}</p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                                    <span :class="[getTypeColor(announcement.type), 'px-2 py-0.5 rounded-full border']">
                                        {{ announcement.type }}
                                    </span>
                                    <span>Cible: {{ getTargetLabel(announcement.target) }}</span>
                                    <span v-if="announcement.starts_at">Du {{ formatDate(announcement.starts_at) }}</span>
                                    <span v-if="announcement.ends_at">au {{ formatDate(announcement.ends_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="toggleStatus(announcement)"
                                :class="[
                                    announcement.is_active ? 'bg-green-600' : 'bg-gray-600',
                                    'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors'
                                ]"
                            >
                                <span
                                    :class="[
                                        announcement.is_active ? 'translate-x-5' : 'translate-x-0',
                                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition mt-0.5 ml-0.5'
                                    ]"
                                />
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2 pt-3 border-t border-gray-700">
                        <Link
                            :href="route('superadmin.announcements.show', announcement.id)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <EyeIcon class="h-4 w-4" />
                            Voir
                        </Link>
                        <Link
                            :href="route('superadmin.announcements.edit', announcement.id)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Modifier
                        </Link>
                        <button
                            @click="deleteAnnouncement(announcement)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Supprimer
                        </button>
                    </div>
                </div>

                <div v-if="announcements.data.length === 0" class="bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                    <MegaphoneIcon class="mx-auto h-12 w-12 text-gray-500" />
                    <h3 class="mt-2 text-sm font-medium text-gray-300">Aucune annonce</h3>
                    <p class="mt-1 text-sm text-gray-500">Créez votre première annonce.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('superadmin.announcements.create')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Nouvelle Annonce
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="announcements.links && announcements.links.length > 3" class="flex justify-center gap-1">
                <Link
                    v-for="link in announcements.links"
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
