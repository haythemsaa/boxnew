<script setup>
import { ref, computed, reactive } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    widget: Object,
    sites: Array,
    itemsByCategory: Object,
    boxSizes: Array,
})

const selectedItems = reactive({})
const selectedSite = ref('')
const isCalculating = ref(false)
const result = ref(null)
const activeCategory = ref('living_room')

// Initialize all items with quantity 0
Object.keys(props.itemsByCategory).forEach(category => {
    Object.keys(props.itemsByCategory[category].items).forEach(itemId => {
        selectedItems[itemId] = 0
    })
})

const totalItems = computed(() => {
    return Object.values(selectedItems).reduce((sum, qty) => sum + qty, 0)
})

const estimatedVolume = computed(() => {
    let volume = 0
    Object.entries(selectedItems).forEach(([itemId, quantity]) => {
        if (quantity > 0) {
            const category = Object.keys(props.itemsByCategory).find(cat =>
                props.itemsByCategory[cat].items[itemId]
            )
            if (category) {
                volume += props.itemsByCategory[category].items[itemId].volume * quantity
            }
        }
    })
    return volume
})

const recommendedSize = computed(() => {
    const volume = estimatedVolume.value * 1.2 // 20% buffer
    for (const size of props.boxSizes) {
        if (volume >= size.min && volume < size.max) {
            return size
        }
    }
    return props.boxSizes[props.boxSizes.length - 1]
})

const incrementItem = (itemId) => {
    selectedItems[itemId]++
}

const decrementItem = (itemId) => {
    if (selectedItems[itemId] > 0) {
        selectedItems[itemId]--
    }
}

const clearAll = () => {
    Object.keys(selectedItems).forEach(key => {
        selectedItems[key] = 0
    })
}

const styleConfig = computed(() => props.widget?.style_config || {})
const primaryColor = computed(() => styleConfig.value.primary_color || '#4f46e5')
</script>

<template>
    <Head :title="widget?.name || 'Calculateur de taille'" />

    <div class="min-h-screen bg-gray-50 p-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Calculateur de taille de box</h1>
                <p class="text-gray-600 mt-1">Selectionnez vos affaires pour trouver la taille ideale</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Categories & Items -->
                <div class="md:col-span-2 bg-white rounded-lg shadow p-4">
                    <!-- Category Tabs -->
                    <div class="flex flex-wrap gap-2 mb-4 border-b pb-3">
                        <button
                            v-for="(category, key) in itemsByCategory"
                            :key="key"
                            @click="activeCategory = key"
                            :class="[
                                'px-3 py-1.5 text-sm rounded-full transition',
                                activeCategory === key
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]"
                        >
                            {{ category.name }}
                        </button>
                    </div>

                    <!-- Items Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <div
                            v-for="(item, itemId) in itemsByCategory[activeCategory]?.items"
                            :key="itemId"
                            class="border rounded-lg p-3 hover:border-indigo-300 transition"
                        >
                            <div class="text-sm font-medium text-gray-900 mb-2">{{ item.name }}</div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500">{{ item.volume }} m³</span>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="decrementItem(itemId)"
                                        class="w-7 h-7 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                                        :disabled="selectedItems[itemId] === 0"
                                    >
                                        -
                                    </button>
                                    <span class="w-8 text-center text-sm font-medium">{{ selectedItems[itemId] }}</span>
                                    <button
                                        @click="incrementItem(itemId)"
                                        class="w-7 h-7 rounded-full bg-indigo-100 hover:bg-indigo-200 text-indigo-700 flex items-center justify-center"
                                    >
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Resultat</h3>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">Objets selectionnes</span>
                            <span class="font-semibold">{{ totalItems }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-gray-600">Volume estime</span>
                            <span class="font-semibold">{{ estimatedVolume.toFixed(1) }} m³</span>
                        </div>

                        <div v-if="totalItems > 0" class="mt-4 p-4 bg-indigo-50 rounded-lg">
                            <div class="text-sm text-indigo-700 font-medium mb-1">Taille recommandee</div>
                            <div class="text-2xl font-bold text-indigo-900">{{ recommendedSize?.size }}</div>
                            <div class="text-sm text-indigo-600 mt-1">{{ recommendedSize?.description }}</div>
                        </div>

                        <div v-else class="mt-4 p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-gray-500 text-sm">Selectionnez des objets pour voir la recommandation</p>
                        </div>

                        <button
                            v-if="totalItems > 0"
                            @click="clearAll"
                            class="w-full mt-4 px-4 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
                        >
                            Tout effacer
                        </button>

                        <a
                            v-if="totalItems > 0 && widget?.enable_booking"
                            :href="widget?.redirect_url || '/booking'"
                            class="block w-full mt-2 px-4 py-3 bg-indigo-600 text-white text-center font-medium rounded-lg hover:bg-indigo-700 transition"
                        >
                            Reserver maintenant
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom CSS -->
    <component :is="'style'" v-if="widget?.custom_css">
        {{ widget.custom_css }}
    </component>
</template>

<style scoped>
/* Widget-specific styles */
</style>
