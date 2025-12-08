<script setup>
/**
 * Size Calculator Widget
 *
 * Interactive tool to help customers determine the right storage size
 * Features:
 * - Room/item based calculation
 * - Visual 3D representation
 * - Recommended box size with pricing
 * - Embeddable widget version
 */
import { ref, computed, watch } from 'vue'
import {
    CubeIcon,
    HomeIcon,
    TruckIcon,
    ArchiveBoxIcon,
    ComputerDesktopIcon,
    SparklesIcon,
    CheckIcon,
    ArrowRightIcon,
    PlusIcon,
    MinusIcon,
    InformationCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    // Widget mode (embedded vs full page)
    embedded: {
        type: Boolean,
        default: false,
    },
    // Tenant settings for branding
    primaryColor: {
        type: String,
        default: '#3B82F6',
    },
    // Available box sizes for recommendations
    availableBoxes: {
        type: Array,
        default: () => [],
    },
    // Booking URL
    bookingUrl: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['recommend', 'book'])

// Calculation mode
const calculationMode = ref('rooms') // 'rooms' or 'items'

// Room-based items with volumes (in m³)
const rooms = ref({
    studio: { count: 0, volume: 12, label: 'Studio/Chambre', icon: '🛏️' },
    t2: { count: 0, volume: 20, label: 'Appartement T2', icon: '🏠' },
    t3: { count: 0, volume: 30, label: 'Appartement T3', icon: '🏡' },
    t4: { count: 0, volume: 40, label: 'Appartement T4+', icon: '🏘️' },
    house: { count: 0, volume: 60, label: 'Maison', icon: '🏰' },
    office: { count: 0, volume: 15, label: 'Bureau', icon: '🏢' },
    garage: { count: 0, volume: 20, label: 'Garage/Cave', icon: '🚗' },
})

// Item-based calculation
const furnitureCategories = [
    {
        name: 'Salon',
        icon: '🛋️',
        items: [
            { id: 'sofa_2', name: 'Canapé 2 places', volume: 1.5, count: 0 },
            { id: 'sofa_3', name: 'Canapé 3 places', volume: 2.5, count: 0 },
            { id: 'armchair', name: 'Fauteuil', volume: 0.8, count: 0 },
            { id: 'coffee_table', name: 'Table basse', volume: 0.3, count: 0 },
            { id: 'tv_stand', name: 'Meuble TV', volume: 0.5, count: 0 },
            { id: 'bookshelf', name: 'Bibliothèque', volume: 0.8, count: 0 },
        ],
    },
    {
        name: 'Chambre',
        icon: '🛏️',
        items: [
            { id: 'bed_single', name: 'Lit 1 place', volume: 1.5, count: 0 },
            { id: 'bed_double', name: 'Lit 2 places', volume: 2.5, count: 0 },
            { id: 'mattress', name: 'Matelas', volume: 0.5, count: 0 },
            { id: 'wardrobe', name: 'Armoire', volume: 1.5, count: 0 },
            { id: 'dresser', name: 'Commode', volume: 0.6, count: 0 },
            { id: 'nightstand', name: 'Table de chevet', volume: 0.2, count: 0 },
        ],
    },
    {
        name: 'Cuisine/Salle à manger',
        icon: '🍽️',
        items: [
            { id: 'dining_table', name: 'Table à manger', volume: 0.8, count: 0 },
            { id: 'dining_chairs', name: 'Chaises (x4)', volume: 0.5, count: 0 },
            { id: 'fridge', name: 'Réfrigérateur', volume: 1.2, count: 0 },
            { id: 'washing_machine', name: 'Lave-linge', volume: 0.8, count: 0 },
            { id: 'dishwasher', name: 'Lave-vaisselle', volume: 0.6, count: 0 },
            { id: 'microwave', name: 'Micro-ondes', volume: 0.1, count: 0 },
        ],
    },
    {
        name: 'Bureau',
        icon: '💼',
        items: [
            { id: 'desk', name: 'Bureau', volume: 0.8, count: 0 },
            { id: 'office_chair', name: 'Chaise de bureau', volume: 0.4, count: 0 },
            { id: 'filing_cabinet', name: 'Classeur', volume: 0.3, count: 0 },
            { id: 'computer', name: 'Ordinateur', volume: 0.1, count: 0 },
        ],
    },
    {
        name: 'Cartons & Divers',
        icon: '📦',
        items: [
            { id: 'small_box', name: 'Petit carton', volume: 0.03, count: 0 },
            { id: 'medium_box', name: 'Carton moyen', volume: 0.06, count: 0 },
            { id: 'large_box', name: 'Grand carton', volume: 0.1, count: 0 },
            { id: 'suitcase', name: 'Valise', volume: 0.1, count: 0 },
            { id: 'bike', name: 'Vélo', volume: 0.5, count: 0 },
            { id: 'skis', name: 'Skis/Snowboard', volume: 0.2, count: 0 },
        ],
    },
]

const items = ref(
    furnitureCategories.flatMap(cat =>
        cat.items.map(item => ({ ...item, category: cat.name }))
    )
)

// Computed total volume
const totalVolume = computed(() => {
    if (calculationMode.value === 'rooms') {
        return Object.values(rooms.value).reduce(
            (sum, room) => sum + room.count * room.volume,
            0
        )
    } else {
        return items.value.reduce(
            (sum, item) => sum + item.count * item.volume,
            0
        )
    }
})

// Add 20% buffer for movement space
const recommendedVolume = computed(() => {
    return Math.ceil(totalVolume.value * 1.2)
})

// Size categories with descriptions
const sizeCategories = [
    { min: 0, max: 2, name: 'Casier', icon: '📦', desc: '10-20 cartons' },
    { min: 2, max: 5, name: 'Petit', icon: '🗄️', desc: 'Studio / Chambre' },
    { min: 5, max: 10, name: 'Moyen', icon: '🏠', desc: 'Appartement T2' },
    { min: 10, max: 20, name: 'Grand', icon: '🏡', desc: 'Appartement T3/T4' },
    { min: 20, max: 40, name: 'Très Grand', icon: '🏘️', desc: 'Maison' },
    { min: 40, max: Infinity, name: 'XXL', icon: '🏢', desc: 'Grande maison / Entreprise' },
]

const recommendedCategory = computed(() => {
    const vol = recommendedVolume.value
    return sizeCategories.find(cat => vol >= cat.min && vol < cat.max) || sizeCategories[0]
})

// Find matching box from available boxes
const recommendedBox = computed(() => {
    if (!props.availableBoxes.length) return null

    const vol = recommendedVolume.value
    // Find the smallest box that fits
    const sorted = [...props.availableBoxes].sort((a, b) => a.volume - b.volume)
    return sorted.find(box => box.volume >= vol) || sorted[sorted.length - 1]
})

// Item count helpers
const incrementRoom = (key) => {
    rooms.value[key].count++
}

const decrementRoom = (key) => {
    if (rooms.value[key].count > 0) {
        rooms.value[key].count--
    }
}

const incrementItem = (itemId) => {
    const item = items.value.find(i => i.id === itemId)
    if (item) item.count++
}

const decrementItem = (itemId) => {
    const item = items.value.find(i => i.id === itemId)
    if (item && item.count > 0) item.count--
}

const resetAll = () => {
    Object.keys(rooms.value).forEach(key => {
        rooms.value[key].count = 0
    })
    items.value.forEach(item => {
        item.count = 0
    })
}

// Format volume
const formatVolume = (vol) => {
    if (vol < 1) return `${Math.round(vol * 100) / 100} m³`
    return `${Math.round(vol)} m³`
}

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount || 0)
}

// Emit recommendation when volume changes
watch(recommendedVolume, (newVol) => {
    emit('recommend', {
        volume: newVol,
        category: recommendedCategory.value,
        box: recommendedBox.value,
    })
})

// Book action
const handleBook = () => {
    if (props.bookingUrl) {
        window.location.href = props.bookingUrl
    }
    emit('book', {
        volume: recommendedVolume.value,
        box: recommendedBox.value,
    })
}

// Visual representation helpers
const volumeBarWidth = computed(() => {
    const maxVol = 50 // Max display volume
    return Math.min(100, (recommendedVolume.value / maxVol) * 100)
})

const activeItemsCount = computed(() => {
    if (calculationMode.value === 'rooms') {
        return Object.values(rooms.value).reduce((sum, r) => sum + r.count, 0)
    }
    return items.value.reduce((sum, i) => sum + i.count, 0)
})
</script>

<template>
    <div
        :class="[
            'bg-white rounded-2xl shadow-lg overflow-hidden',
            embedded ? 'max-w-md' : 'max-w-4xl mx-auto'
        ]"
    >
        <!-- Header -->
        <div
            class="p-6 text-white"
            :style="{ backgroundColor: primaryColor }"
        >
            <div class="flex items-center gap-3">
                <CubeIcon class="h-8 w-8" />
                <div>
                    <h2 class="text-xl font-bold">Calculateur de taille</h2>
                    <p class="text-white/80 text-sm">Estimez la taille de box dont vous avez besoin</p>
                </div>
            </div>
        </div>

        <!-- Mode Toggle -->
        <div class="p-4 border-b border-gray-100">
            <div class="flex gap-2">
                <button
                    @click="calculationMode = 'rooms'"
                    :class="[
                        'flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all',
                        calculationMode === 'rooms'
                            ? 'text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                    :style="calculationMode === 'rooms' ? { backgroundColor: primaryColor } : {}"
                >
                    <HomeIcon class="h-5 w-5 inline mr-2" />
                    Par pièce
                </button>
                <button
                    @click="calculationMode = 'items'"
                    :class="[
                        'flex-1 py-3 px-4 rounded-xl font-medium text-sm transition-all',
                        calculationMode === 'items'
                            ? 'text-white shadow-md'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                    :style="calculationMode === 'items' ? { backgroundColor: primaryColor } : {}"
                >
                    <ArchiveBoxIcon class="h-5 w-5 inline mr-2" />
                    Par meuble
                </button>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4">
            <!-- Room Mode -->
            <div v-if="calculationMode === 'rooms'" class="space-y-3">
                <div
                    v-for="(room, key) in rooms"
                    :key="key"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-xl"
                >
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ room.icon }}</span>
                        <div>
                            <p class="font-medium text-gray-900">{{ room.label }}</p>
                            <p class="text-xs text-gray-500">~{{ room.volume }} m³</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="decrementRoom(key)"
                            class="w-10 h-10 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors"
                            :disabled="room.count === 0"
                        >
                            <MinusIcon class="h-5 w-5" />
                        </button>
                        <span class="w-8 text-center font-bold text-lg">{{ room.count }}</span>
                        <button
                            @click="incrementRoom(key)"
                            class="w-10 h-10 rounded-xl flex items-center justify-center text-white transition-colors"
                            :style="{ backgroundColor: primaryColor }"
                        >
                            <PlusIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Items Mode -->
            <div v-else class="space-y-4 max-h-80 overflow-y-auto">
                <div v-for="category in furnitureCategories" :key="category.name">
                    <h3 class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
                        <span>{{ category.icon }}</span>
                        {{ category.name }}
                    </h3>
                    <div class="grid grid-cols-2 gap-2">
                        <div
                            v-for="item in items.filter(i => i.category === category.name)"
                            :key="item.id"
                            class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
                                <p class="text-xs text-gray-500">{{ item.volume }} m³</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    @click="decrementItem(item.id)"
                                    class="w-7 h-7 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-100"
                                    :disabled="item.count === 0"
                                >
                                    <MinusIcon class="h-4 w-4" />
                                </button>
                                <span class="w-6 text-center font-medium text-sm">{{ item.count }}</span>
                                <button
                                    @click="incrementItem(item.id)"
                                    class="w-7 h-7 rounded-lg flex items-center justify-center text-white"
                                    :style="{ backgroundColor: primaryColor }"
                                >
                                    <PlusIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reset Button -->
            <div v-if="activeItemsCount > 0" class="mt-4 text-center">
                <button
                    @click="resetAll"
                    class="text-sm text-gray-500 hover:text-gray-700 underline"
                >
                    Réinitialiser
                </button>
            </div>
        </div>

        <!-- Result Section -->
        <div class="border-t border-gray-100 p-4 bg-gray-50">
            <!-- Volume Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>Volume estimé</span>
                    <span class="font-bold" :style="{ color: primaryColor }">
                        {{ formatVolume(recommendedVolume) }}
                    </span>
                </div>
                <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :style="{
                            width: volumeBarWidth + '%',
                            backgroundColor: primaryColor
                        }"
                    ></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-1">
                    <span>0 m³</span>
                    <span>50+ m³</span>
                </div>
            </div>

            <!-- Recommendation Card -->
            <div
                v-if="recommendedVolume > 0"
                class="bg-white rounded-xl p-4 border-2 transition-all"
                :style="{ borderColor: primaryColor }"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="w-16 h-16 rounded-xl flex items-center justify-center text-3xl"
                        :style="{ backgroundColor: primaryColor + '20' }"
                    >
                        {{ recommendedCategory.icon }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <SparklesIcon class="h-5 w-5" :style="{ color: primaryColor }" />
                            <span class="text-sm font-medium text-gray-500">Recommandation</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">
                            Box {{ recommendedCategory.name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ recommendedCategory.desc }}</p>
                    </div>
                    <div v-if="recommendedBox" class="text-right">
                        <p class="text-2xl font-bold" :style="{ color: primaryColor }">
                            {{ formatCurrency(recommendedBox.current_price) }}
                        </p>
                        <p class="text-sm text-gray-500">/mois</p>
                    </div>
                </div>

                <!-- Book Button -->
                <button
                    v-if="bookingUrl || $attrs.onBook"
                    @click="handleBook"
                    class="mt-4 w-full py-3 rounded-xl text-white font-semibold flex items-center justify-center gap-2 transition-all hover:opacity-90"
                    :style="{ backgroundColor: primaryColor }"
                >
                    Réserver ce box
                    <ArrowRightIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-6 text-gray-500">
                <InformationCircleIcon class="h-12 w-12 mx-auto mb-2 text-gray-300" />
                <p>Ajoutez des éléments pour obtenir une estimation</p>
            </div>
        </div>

        <!-- Size Guide -->
        <div class="border-t border-gray-100 p-4">
            <details class="group">
                <summary class="flex items-center justify-between cursor-pointer text-sm font-medium text-gray-600 hover:text-gray-900">
                    <span class="flex items-center gap-2">
                        <InformationCircleIcon class="h-5 w-5" />
                        Guide des tailles
                    </span>
                    <span class="group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                    <div
                        v-for="cat in sizeCategories.slice(0, 6)"
                        :key="cat.name"
                        class="p-2 bg-gray-50 rounded-lg"
                    >
                        <div class="text-xl">{{ cat.icon }}</div>
                        <p class="font-medium text-sm">{{ cat.min }}-{{ cat.max === Infinity ? '50+' : cat.max }} m³</p>
                        <p class="text-xs text-gray-500">{{ cat.name }}</p>
                    </div>
                </div>
            </details>
        </div>
    </div>
</template>

<style scoped>
/* Smooth scrollbar for items mode */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}
</style>
