<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue'
import {
    PlusIcon,
    PencilSquareIcon,
    CubeIcon,
    CurrencyEuroIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    RocketLaunchIcon,
    PuzzlePieceIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    modules: Array,
    categories: Object,
    stats: Object,
})

const selectedCategory = ref('all')

const filteredModules = computed(() => {
    if (selectedCategory.value === 'all') {
        return props.modules
    }
    return props.modules.filter(m => m.category === selectedCategory.value)
})

const getCategoryIcon = (category) => {
    const icons = {
        core: CubeIcon,
        marketing: RocketLaunchIcon,
        operations: Cog6ToothIcon,
        integrations: PuzzlePieceIcon,
        analytics: ChartBarIcon,
        premium: CurrencyEuroIcon,
    }
    return icons[category] || CubeIcon
}

const getCategoryColor = (category) => {
    const colors = {
        core: 'blue',
        marketing: 'green',
        operations: 'orange',
        integrations: 'purple',
        analytics: 'cyan',
        premium: 'yellow',
    }
    return colors[category] || 'gray'
}
</script>

<template>
    <Head title="Gestion des Modules" />

    <SuperAdminLayout title="Modules & Plans">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Gestion des Modules</h1>
                    <p class="mt-1 text-sm text-gray-400">Configurez les modules et les fonctionnalites de la plateforme</p>
                </div>
                <div class="flex gap-3">
                    <Link
                        :href="route('superadmin.modules.plans')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
                    >
                        <CurrencyEuroIcon class="h-5 w-5" />
                        Gerer les Plans
                    </Link>
                    <Link
                        :href="route('superadmin.modules.demos')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
                    >
                        <RocketLaunchIcon class="h-5 w-5" />
                        Historique Demos
                    </Link>
                    <Link
                        :href="route('superadmin.modules.create')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nouveau Module
                    </Link>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Total Modules</div>
                    <div class="mt-1 text-2xl font-semibold text-white">{{ stats.total_modules }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Modules Core</div>
                    <div class="mt-1 text-2xl font-semibold text-blue-400">{{ stats.core_modules }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Abonnements Actifs</div>
                    <div class="mt-1 text-2xl font-semibold text-green-400">{{ stats.active_subscriptions }}</div>
                </div>
                <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                    <div class="text-sm text-gray-400">Periodes d'Essai</div>
                    <div class="mt-1 text-2xl font-semibold text-yellow-400">{{ stats.trial_subscriptions }}</div>
                </div>
            </div>

            <!-- Category Filter -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button
                    @click="selectedCategory = 'all'"
                    :class="[
                        selectedCategory === 'all' ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap'
                    ]"
                >
                    Tous ({{ modules.length }})
                </button>
                <button
                    v-for="(cat, key) in categories"
                    :key="key"
                    @click="selectedCategory = key"
                    :class="[
                        selectedCategory === key ? 'bg-purple-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
                        'px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap flex items-center gap-2'
                    ]"
                >
                    <component :is="getCategoryIcon(key)" class="h-4 w-4" />
                    {{ cat.name }} ({{ cat.count }})
                </button>
            </div>

            <!-- Modules Grid -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="module in filteredModules"
                    :key="module.id"
                    class="bg-gray-800 rounded-xl border border-gray-700 p-5 hover:border-gray-600 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3">
                            <div :class="[
                                `bg-${module.color}-500/20 text-${module.color}-400`,
                                'p-3 rounded-xl'
                            ]">
                                <component :is="getCategoryIcon(module.category)" class="h-6 w-6" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-white">{{ module.name }}</h3>
                                <p class="text-sm text-gray-500 font-mono">{{ module.code }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="module.is_core" class="px-2 py-0.5 text-xs bg-blue-500/20 text-blue-400 rounded-full">
                                Core
                            </span>
                            <span :class="[
                                module.is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400',
                                'px-2 py-0.5 text-xs rounded-full'
                            ]">
                                {{ module.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <p class="mt-3 text-sm text-gray-400 line-clamp-2">
                        {{ module.description }}
                    </p>

                    <!-- Prix -->
                    <div class="mt-4 flex items-center gap-4">
                        <div v-if="module.monthly_price > 0" class="text-sm">
                            <span class="text-gray-500">Mensuel:</span>
                            <span class="ml-1 text-white font-medium">{{ module.monthly_price }} EUR</span>
                        </div>
                        <div v-if="module.yearly_price > 0" class="text-sm">
                            <span class="text-gray-500">Annuel:</span>
                            <span class="ml-1 text-white font-medium">{{ module.yearly_price }} EUR</span>
                        </div>
                        <div v-if="module.is_core" class="text-sm text-green-400">
                            Gratuit (inclus)
                        </div>
                    </div>

                    <!-- Usage Stats -->
                    <div class="mt-3 text-sm text-gray-500">
                        <span class="text-purple-400 font-medium">{{ module.active_count || 0 }}</span> tenant(s) utilisent ce module
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex items-center gap-2 pt-4 border-t border-gray-700">
                        <Link
                            :href="route('superadmin.modules.edit', module.id)"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-lg transition-colors"
                        >
                            <PencilSquareIcon class="h-4 w-4" />
                            Modifier
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredModules.length === 0" class="bg-gray-800 rounded-xl border border-gray-700 p-8 text-center">
                <CubeIcon class="mx-auto h-12 w-12 text-gray-500" />
                <h3 class="mt-2 text-sm font-medium text-gray-300">Aucun module dans cette categorie</h3>
            </div>
        </div>
    </SuperAdminLayout>
</template>
