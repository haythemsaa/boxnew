<script setup>
import { ref, computed, reactive } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import CustomerPortalLayout from '@/Layouts/CustomerPortalLayout.vue'

const props = defineProps({
    items: Array,
    boxSizes: Array,
})

// Item quantities
const quantities = reactive({})
props.items.forEach(item => {
    quantities[item.id] = 0
})

// Calculate total volume
const totalVolume = computed(() => {
    return props.items.reduce((sum, item) => {
        return sum + (item.volume * (quantities[item.id] || 0))
    }, 0)
})

// Recommended box size
const recommendedBox = computed(() => {
    const volumeWithMargin = totalVolume.value * 1.2 // 20% safety margin
    return props.boxSizes.find(box => box.max_volume >= volumeWithMargin) || props.boxSizes[props.boxSizes.length - 1]
})

// Progress percentage for visualization
const volumePercentage = computed(() => {
    if (!recommendedBox.value) return 0
    return Math.min(100, (totalVolume.value / recommendedBox.value.max_volume) * 100)
})

// Item count
const totalItems = computed(() => {
    return Object.values(quantities).reduce((sum, q) => sum + q, 0)
})

const incrementItem = (itemId) => {
    quantities[itemId]++
}

const decrementItem = (itemId) => {
    if (quantities[itemId] > 0) {
        quantities[itemId]--
    }
}

const resetAll = () => {
    Object.keys(quantities).forEach(key => {
        quantities[key] = 0
    })
}

const getIconSvg = (icon) => {
    const icons = {
        box: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />`,
        suitcase: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        chair: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />`,
        table: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z" />`,
        sofa: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
        bed: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
        wardrobe: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM9 4v16M9 8h4" />`,
        dresser: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />`,
        desk: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        bookshelf: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />`,
        tv: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        fridge: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2zm0 7h14M9 3v7" />`,
        washing: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16a2 2 0 002 2h12a2 2 0 002-2V4M8 8h.01M12 14a3 3 0 100-6 3 3 0 000 6z" />`,
        bike: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />`,
        ski: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />`,
        tire: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`,
    }
    return icons[icon] || icons.box
}
</script>

<template>
    <Head title="Calculateur de taille" />

    <CustomerPortalLayout>
        <div class="py-6">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Calculateur de taille de box</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Sélectionnez vos objets pour estimer la taille de box dont vous avez besoin
                    </p>
                </div>

                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Items Selection (Left) -->
                    <div class="lg:col-span-2">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sélectionnez vos objets</h2>
                                <button
                                    v-if="totalItems > 0"
                                    @click="resetAll"
                                    class="text-sm text-red-600 hover:text-red-700 dark:text-red-400"
                                >
                                    Tout effacer
                                </button>
                            </div>

                            <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div
                                    v-for="item in items"
                                    :key="item.id"
                                    :class="[
                                        'p-4 rounded-xl border-2 transition-all duration-200',
                                        quantities[item.id] > 0
                                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                            : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                    ]"
                                >
                                    <div class="flex items-center gap-3 mb-3">
                                        <div :class="[
                                            'p-2 rounded-lg',
                                            quantities[item.id] > 0
                                                ? 'bg-indigo-100 dark:bg-indigo-800'
                                                : 'bg-gray-100 dark:bg-gray-700'
                                        ]">
                                            <svg
                                                :class="[
                                                    'w-5 h-5',
                                                    quantities[item.id] > 0
                                                        ? 'text-indigo-600 dark:text-indigo-400'
                                                        : 'text-gray-500 dark:text-gray-400'
                                                ]"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                                v-html="getIconSvg(item.icon)"
                                            >
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ item.name }}</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ item.volume }} m³</span>
                                        <div class="flex items-center gap-2">
                                            <button
                                                @click="decrementItem(item.id)"
                                                :disabled="quantities[item.id] === 0"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>
                                            <span class="w-8 text-center font-medium text-gray-900 dark:text-white">{{ quantities[item.id] }}</span>
                                            <button
                                                @click="incrementItem(item.id)"
                                                class="w-8 h-8 flex items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-200 dark:hover:bg-indigo-900 transition"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Result Panel (Right) -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-24 space-y-6">
                            <!-- Volume Summary -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Résumé</h3>

                                <div class="space-y-4">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 dark:text-gray-400">Objets sélectionnés</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ totalItems }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600 dark:text-gray-400">Volume total</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ totalVolume.toFixed(1) }} m³</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendation -->
                            <div v-if="totalItems > 0" class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="p-2 bg-white/20 rounded-lg">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-indigo-200 text-sm">Taille recommandée</p>
                                        <p class="text-2xl font-bold">{{ recommendedBox?.name }}</p>
                                    </div>
                                </div>

                                <p class="text-indigo-100 text-sm mb-4">{{ recommendedBox?.description }}</p>

                                <!-- Progress bar -->
                                <div class="mb-4">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-indigo-200">Remplissage estimé</span>
                                        <span class="text-white font-medium">{{ Math.round(volumePercentage) }}%</span>
                                    </div>
                                    <div class="h-3 bg-white/20 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-white rounded-full transition-all duration-500"
                                            :style="{ width: volumePercentage + '%' }"
                                        ></div>
                                    </div>
                                </div>

                                <p class="text-xs text-indigo-200">
                                    * Une marge de 20% est incluse pour faciliter le chargement
                                </p>
                            </div>

                            <!-- Empty state -->
                            <div v-else class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-6 text-center">
                                <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400">
                                    Ajoutez des objets pour voir la taille de box recommandée
                                </p>
                            </div>

                            <!-- Box Sizes Reference -->
                            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Guide des tailles</h3>
                                <div class="space-y-3">
                                    <div
                                        v-for="box in boxSizes"
                                        :key="box.size"
                                        :class="[
                                            'p-3 rounded-lg border transition',
                                            recommendedBox?.size === box.size
                                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30'
                                                : 'border-gray-200 dark:border-gray-700'
                                        ]"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-gray-900 dark:text-white">{{ box.name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ box.description }}</p>
                                            </div>
                                            <span v-if="recommendedBox?.size === box.size" class="px-2 py-1 bg-indigo-500 text-white text-xs rounded-full">
                                                Recommandé
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerPortalLayout>
</template>
