<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
    FlagIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    flags: Object,
    stats: Object,
})

const deleteFlag = (flag) => {
    if (confirm(`Supprimer le flag "${flag.name}" ?`)) {
        router.delete(route('superadmin.feature-flags.destroy', flag.id))
    }
}

const toggleFlag = (flag) => {
    router.post(route('superadmin.feature-flags.toggle', flag.id))
}
</script>

<template>
    <Head title="Feature Flags" />

    <SuperAdminLayout title="Feature Flags">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Feature Flags</h1>
                    <p class="mt-1 text-sm text-gray-400">Contrôlez le déploiement des fonctionnalités</p>
                </div>
                <Link
                    :href="route('superadmin.feature-flags.create')"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                >
                    <PlusIcon class="h-5 w-5" />
                    Nouveau Flag
                </Link>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Activés</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.enabled }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Désactivés</div>
                    <div class="mt-1 text-2xl font-semibold text-red-400">{{ stats.disabled }}</div>
                </div>
            </div>

            <!-- Flags Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    v-for="flag in flags.data"
                    :key="flag.id"
                    class="bg-gray-800 rounded-xl border border-gray-700 p-4 hover:border-gray-600 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div :class="[
                                flag.is_enabled ? 'bg-green-600/20 text-green-400' : 'bg-gray-600/20 text-gray-400',
                                'p-2 rounded-lg'
                            ]">
                                <FlagIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <h3 class="font-medium text-white">{{ flag.name }}</h3>
                                <p class="text-sm text-gray-500 font-mono">{{ flag.key }}</p>
                            </div>
                        </div>
                        <button
                            @click="toggleFlag(flag)"
                            :class="[
                                flag.is_enabled ? 'bg-green-600' : 'bg-gray-600',
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors'
                            ]"
                        >
                            <span
                                :class="[
                                    flag.is_enabled ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition mt-0.5 ml-0.5'
                                ]"
                            />
                        </button>
                    </div>

                    <p v-if="flag.description" class="mt-3 text-sm text-gray-400">
                        {{ flag.description }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <div v-if="flag.enabled_for_plans?.length" class="flex flex-wrap gap-1">
                            <span
                                v-for="plan in flag.enabled_for_plans"
                                :key="plan"
                                class="px-2 py-0.5 text-xs bg-purple-500/10 text-purple-400 rounded-full capitalize"
                            >
                                {{ plan }}
                            </span>
                        </div>
                        <span v-if="flag.enabled_for_tenants?.length" class="text-xs text-gray-500">
                            + {{ flag.enabled_for_tenants.length }} tenant(s) spécifique(s)
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2 pt-3 border-t border-gray-700">
                        <Link
                            :href="route('superadmin.feature-flags.edit', flag.id)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Modifier
                        </Link>
                        <button
                            @click="deleteFlag(flag)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Supprimer
                        </button>
                    </div>
                </div>

                <div v-if="flags.data.length === 0" class="col-span-full bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                    <FlagIcon class="mx-auto h-12 w-12 text-gray-500" />
                    <h3 class="mt-2 text-sm font-medium text-gray-300">Aucun feature flag</h3>
                    <p class="mt-1 text-sm text-gray-500">Créez votre premier feature flag.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('superadmin.feature-flags.create')"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                        >
                            <PlusIcon class="h-5 w-5" />
                            Nouveau Flag
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="flags.links && flags.links.length > 3" class="flex justify-center gap-1">
                <Link
                    v-for="link in flags.links"
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
