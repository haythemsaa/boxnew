<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    ArrowLeftIcon,
    PencilSquareIcon,
    TrashIcon,
    MegaphoneIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    announcement: Object,
})

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
        all: 'Tous les utilisateurs',
        tenants: 'Tenants uniquement',
        specific: 'Tenants spécifiques',
    }
    return labels[t] || t
}

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const deleteAnnouncement = () => {
    if (confirm(`Supprimer l'annonce "${props.announcement.title}" ?`)) {
        router.delete(route('superadmin.announcements.destroy', props.announcement.id))
    }
}

const toggleStatus = () => {
    router.post(route('superadmin.announcements.toggle', props.announcement.id))
}
</script>

<template>
    <Head :title="`Annonce - ${announcement.title}`" />

    <SuperAdminLayout :title="`Annonce - ${announcement.title}`">
        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link
                        :href="route('superadmin.announcements.index')"
                        class="p-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="h-5 w-5 text-gray-300" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-white">{{ announcement.title }}</h1>
                        <p class="mt-1 text-sm text-gray-400">Créée le {{ formatDate(announcement.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('superadmin.announcements.edit', announcement.id)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                    >
                        <PencilSquareIcon class="h-5 w-5" />
                        Modifier
                    </Link>
                    <button
                        @click="deleteAnnouncement"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                    >
                        <TrashIcon class="h-5 w-5" />
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Preview -->
            <div :class="[getTypeColor(announcement.type), 'rounded-xl border p-4']">
                <div class="flex items-start gap-3">
                    <MegaphoneIcon class="h-6 w-6 flex-shrink-0" />
                    <div>
                        <h3 class="font-medium">{{ announcement.title }}</h3>
                        <p class="mt-1 text-sm opacity-90">{{ announcement.content }}</p>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Détails</h2>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <div class="text-sm text-gray-400">Type</div>
                        <span :class="[getTypeColor(announcement.type), 'mt-1 inline-flex px-2 py-1 text-sm rounded-full border capitalize']">
                            {{ announcement.type }}
                        </span>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Statut</div>
                        <div class="mt-1 flex items-center gap-2">
                            <span :class="[
                                announcement.is_active ? 'bg-green-500/10 text-green-400' : 'bg-gray-500/10 text-gray-400',
                                'px-2 py-1 text-sm rounded-full'
                            ]">
                                {{ announcement.is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <button
                                @click="toggleStatus"
                                class="text-xs text-purple-400 hover:text-purple-300"
                            >
                                {{ announcement.is_active ? 'Désactiver' : 'Activer' }}
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Cible</div>
                        <div class="mt-1 text-white">{{ getTargetLabel(announcement.target) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Fermable</div>
                        <div class="mt-1 text-white">{{ announcement.is_dismissible ? 'Oui' : 'Non' }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Date de début</div>
                        <div class="mt-1 text-white">{{ formatDate(announcement.starts_at) }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-400">Date de fin</div>
                        <div class="mt-1 text-white">{{ formatDate(announcement.ends_at) }}</div>
                    </div>
                </div>

                <div v-if="announcement.target === 'specific' && announcement.target_tenant_ids?.length" class="mt-6 pt-6 border-t border-gray-700">
                    <div class="text-sm text-gray-400 mb-2">Tenants ciblés</div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="tenantId in announcement.target_tenant_ids"
                            :key="tenantId"
                            class="px-2 py-1 text-sm bg-gray-700 text-gray-300 rounded"
                        >
                            Tenant #{{ tenantId }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Creator -->
            <div v-if="announcement.creator" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Créateur</h2>
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-purple-600 rounded-full flex items-center justify-center text-white font-medium">
                        {{ announcement.creator.name?.charAt(0) }}
                    </div>
                    <div>
                        <div class="text-white font-medium">{{ announcement.creator.name }}</div>
                        <div class="text-sm text-gray-400">{{ announcement.creator.email }}</div>
                    </div>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
