<script setup>
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
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

const selectedItemsList = computed(() => {
    const items = []
    Object.entries(selectedItems).forEach(([itemId, quantity]) => {
        if (quantity > 0) {
            const category = Object.keys(props.itemsByCategory).find(cat =>
                props.itemsByCategory[cat].items[itemId]
            )
            if (category) {
                items.push({
                    id: itemId,
                    name: props.itemsByCategory[category].items[itemId].name,
                    quantity,
                    volume: props.itemsByCategory[category].items[itemId].volume * quantity,
                })
            }
        }
    })
    return items
})

const incrementItem = (itemId) => {
    selectedItems[itemId]++
}

const decrementItem = (itemId) => {
    if (selectedItems[itemId] > 0) {
        selectedItems[itemId]--
    }
}

const setItemQuantity = (itemId, value) => {
    selectedItems[itemId] = Math.max(0, parseInt(value) || 0)
}

const clearAll = () => {
    Object.keys(selectedItems).forEach(key => {
        selectedItems[key] = 0
    })
    result.value = null
}

const calculate = async () => {
    if (totalItems.value === 0) return

    isCalculating.value = true

    try {
        const items = Object.entries(selectedItems)
            .filter(([_, qty]) => qty > 0)
            .map(([id, quantity]) => ({ id, quantity }))

        const response = await fetch(route('calculator.calculate'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                items,
                site_id: selectedSite.value || null,
            }),
        })

        const data = await response.json()
        if (data.success) {
            result.value = data
        }
    } catch (err) {
        console.error('Calculation error:', err)
    } finally {
        isCalculating.value = false
    }
}

const getCategoryIcon = (icon) => {
    const icons = {
        home: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
        bed: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />`,
        utensils: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />`,
        kitchen: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />`,
        briefcase: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        box: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />`,
        dumbbell: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h4m12 0h-4m-4 0V4m0 2v2m0 10v2m0-2v-2m-8 0H4m16 0h-4" />`,
    }
    return icons[icon] || icons.box
}

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount)
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-lg border-b border-gray-200 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="/" class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <span class="text-xl font-bold text-gray-900">Boxibox</span>
                        </a>
                    </div>
                    <h1 class="text-lg font-semibold text-gray-700">Calculateur de taille</h1>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 py-8">
            <!-- Hero Section -->
            <div class="text-center mb-10">
                <h1 class="text-4xl font-bold text-gray-900 mb-4">
                    Quelle taille de box vous faut-il ?
                </h1>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Selectionnez vos affaires et nous vous recommanderons la taille ideale pour votre stockage.
                </p>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Left Panel - Item Selection -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <!-- Category Tabs -->
                        <div class="border-b border-gray-200 overflow-x-auto">
                            <div class="flex">
                                <button
                                    v-for="(category, key) in itemsByCategory"
                                    :key="key"
                                    @click="activeCategory = key"
                                    class="flex-shrink-0 px-6 py-4 text-sm font-medium transition-colors border-b-2 -mb-px"
                                    :class="activeCategory === key
                                        ? 'text-blue-600 border-blue-600 bg-blue-50/50'
                                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50'"
                                >
                                    {{ category.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Items Grid -->
                        <div class="p-6">
                            <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div
                                    v-for="(item, itemId) in itemsByCategory[activeCategory].items"
                                    :key="itemId"
                                    class="border rounded-xl p-4 transition-all"
                                    :class="selectedItems[itemId] > 0 ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-medium text-gray-900">{{ item.name }}</h4>
                                            <p class="text-xs text-gray-500">{{ item.volume }} m3</p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-center space-x-3">
                                        <button
                                            @click="decrementItem(itemId)"
                                            class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
                                            :class="selectedItems[itemId] === 0 ? 'opacity-50 cursor-not-allowed' : ''"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                            </svg>
                                        </button>
                                        <input
                                            type="number"
                                            :value="selectedItems[itemId]"
                                            @input="setItemQuantity(itemId, $event.target.value)"
                                            class="w-16 text-center border border-gray-200 rounded-lg py-1 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            min="0"
                                        />
                                        <button
                                            @click="incrementItem(itemId)"
                                            class="w-8 h-8 rounded-lg bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center transition-colors"
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

                <!-- Right Panel - Summary & Results -->
                <div class="space-y-6">
                    <!-- Live Summary -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resume</h3>

                        <div class="space-y-4">
                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-bold text-blue-600">{{ totalItems }}</p>
                                    <p class="text-sm text-gray-500">Articles</p>
                                </div>
                                <div class="bg-gray-50 rounded-xl p-4 text-center">
                                    <p class="text-3xl font-bold text-blue-600">{{ estimatedVolume.toFixed(1) }}</p>
                                    <p class="text-sm text-gray-500">m3 estimes</p>
                                </div>
                            </div>

                            <!-- Selected Items List -->
                            <div v-if="selectedItemsList.length > 0" class="border-t border-gray-100 pt-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Articles selectionnes</h4>
                                <div class="max-h-48 overflow-y-auto space-y-2">
                                    <div
                                        v-for="item in selectedItemsList"
                                        :key="item.id"
                                        class="flex justify-between items-center text-sm"
                                    >
                                        <span class="text-gray-600">{{ item.name }} x{{ item.quantity }}</span>
                                        <span class="text-gray-500">{{ item.volume.toFixed(2) }} m3</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Site Selection -->
                            <div class="border-t border-gray-100 pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Site prefere (optionnel)
                                </label>
                                <select
                                    v-model="selectedSite"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                >
                                    <option value="">Tous les sites</option>
                                    <option v-for="site in sites" :key="site.id" :value="site.id">
                                        {{ site.name }} - {{ site.city }}
                                    </option>
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3 pt-4">
                                <button
                                    @click="calculate"
                                    :disabled="totalItems === 0 || isCalculating"
                                    class="w-full py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                                >
                                    <svg v-if="isCalculating" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isCalculating ? 'Calcul en cours...' : 'Calculer ma taille' }}
                                </button>
                                <button
                                    @click="clearAll"
                                    class="w-full py-2 px-4 text-gray-600 hover:text-gray-900 text-sm transition-colors"
                                >
                                    Tout effacer
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div v-if="result" class="bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl shadow-lg p-6 text-white">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-white/20 rounded-full mx-auto flex items-center justify-center mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold mb-1">Nous vous recommandons</h3>
                            <p class="text-5xl font-black">{{ result.recommendation.size }}</p>
                            <p class="text-blue-100 mt-2">{{ result.recommendation.description }}</p>
                        </div>

                        <div class="bg-white/10 rounded-xl p-4 mb-6">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-blue-200">Volume estime</p>
                                    <p class="text-xl font-bold">{{ result.calculation.total_volume }} m3</p>
                                </div>
                                <div>
                                    <p class="text-blue-200">Avec marge</p>
                                    <p class="text-xl font-bold">{{ result.calculation.recommended_volume }} m3</p>
                                </div>
                            </div>
                        </div>

                        <!-- Available Boxes -->
                        <div v-if="result.available_boxes && result.available_boxes.length > 0">
                            <h4 class="font-semibold mb-3">Box disponibles</h4>
                            <div class="space-y-2">
                                <a
                                    v-for="box in result.available_boxes"
                                    :key="box.id"
                                    :href="`/booking/box/${box.id}`"
                                    class="block bg-white/10 hover:bg-white/20 rounded-xl p-3 transition-colors"
                                >
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <p class="font-medium">{{ box.name }}</p>
                                            <p class="text-sm text-blue-200">{{ box.dimensions }} - {{ box.volume }} m3</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold">{{ formatCurrency(box.price) }}/mois</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <a
                            href="/booking"
                            class="block mt-6 w-full py-3 px-6 bg-white text-blue-600 font-semibold rounded-xl text-center hover:bg-blue-50 transition-colors"
                        >
                            Voir tous les box disponibles
                        </a>
                    </div>

                    <!-- Size Guide -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Guide des tailles</h3>
                        <div class="space-y-3">
                            <div
                                v-for="size in boxSizes.slice(0, 6)"
                                :key="size.size"
                                class="flex justify-between items-center text-sm py-2 border-b border-gray-100 last:border-0"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">{{ size.size }}</p>
                                    <p class="text-xs text-gray-500">{{ size.description }}</p>
                                </div>
                                <span class="text-gray-500">~{{ size.volume }} m3</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-16">
            <div class="max-w-7xl mx-auto px-4 py-8 text-center">
                <p class="text-gray-500 text-sm">
                    Besoin d'aide ? Contactez-nous au <a href="tel:+33100000000" class="text-blue-600 hover:underline">01 00 00 00 00</a>
                </p>
            </div>
        </footer>
    </div>
</template>
