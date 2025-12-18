<script setup>
/**
 * Vehicle Loading Simulator 3D
 *
 * Interactive 3D tool to visualize vehicle/truck loading capacity
 * Features:
 * - Multiple vehicle types (car, van, truck)
 * - Drag & drop items into vehicle
 * - Real-time fill percentage calculation
 * - 3D visualization with Three.js
 * - Editable item dimensions
 */
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import {
    TruckIcon,
    CubeIcon,
    PlusIcon,
    MinusIcon,
    TrashIcon,
    PencilIcon,
    ArrowPathIcon,
    ArrowsPointingOutIcon,
    EyeIcon,
    CheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    // Initial vehicle type
    initialVehicle: {
        type: String,
        default: 'van',
    },
    // Allow editing items
    editable: {
        type: Boolean,
        default: true,
    },
    // Theme color
    primaryColor: {
        type: String,
        default: '#3B82F6',
    },
})

const emit = defineEmits(['update:fillPercentage', 'itemsChanged'])

// Vehicle types with dimensions (in meters)
const vehicleTypes = ref([
    {
        id: 'car',
        name: 'Voiture (coffre)',
        icon: '🚗',
        dimensions: { length: 1.2, width: 1.0, height: 0.5 },
        volume: 0.6,
        color: '#6366F1',
        description: 'Coffre de voiture standard',
    },
    {
        id: 'suv',
        name: 'SUV / Break',
        icon: '🚙',
        dimensions: { length: 1.8, width: 1.2, height: 0.8 },
        volume: 1.7,
        color: '#8B5CF6',
        description: 'Coffre SUV ou break',
    },
    {
        id: 'van',
        name: 'Camionnette',
        icon: '🚐',
        dimensions: { length: 2.5, width: 1.6, height: 1.5 },
        volume: 6.0,
        color: '#EC4899',
        description: 'Petit utilitaire type Kangoo',
    },
    {
        id: 'large_van',
        name: 'Fourgon',
        icon: '🚚',
        dimensions: { length: 3.5, width: 1.8, height: 1.8 },
        volume: 11.3,
        color: '#F59E0B',
        description: 'Fourgon type Master/Ducato',
    },
    {
        id: 'truck_small',
        name: 'Camion 12m³',
        icon: '🚛',
        dimensions: { length: 3.0, width: 2.0, height: 2.0 },
        volume: 12.0,
        color: '#10B981',
        description: 'Petit camion de demenagement',
    },
    {
        id: 'truck_medium',
        name: 'Camion 20m³',
        icon: '🚛',
        dimensions: { length: 4.0, width: 2.2, height: 2.3 },
        volume: 20.0,
        color: '#3B82F6',
        description: 'Camion moyen',
    },
    {
        id: 'truck_large',
        name: 'Camion 30m³',
        icon: '🚛',
        dimensions: { length: 5.0, width: 2.4, height: 2.5 },
        volume: 30.0,
        color: '#EF4444',
        description: 'Grand camion de demenagement',
    },
])

// Selected vehicle
const selectedVehicleId = ref(props.initialVehicle)
const selectedVehicle = computed(() =>
    vehicleTypes.value.find(v => v.id === selectedVehicleId.value) || vehicleTypes.value[2]
)

// Default items that can be loaded
const defaultItems = [
    { id: 'box_small', name: 'Carton petit', dimensions: { l: 0.4, w: 0.3, h: 0.3 }, volume: 0.036, color: '#D4A574', icon: '📦' },
    { id: 'box_medium', name: 'Carton moyen', dimensions: { l: 0.5, w: 0.4, h: 0.4 }, volume: 0.08, color: '#C4956A', icon: '📦' },
    { id: 'box_large', name: 'Carton grand', dimensions: { l: 0.6, w: 0.5, h: 0.5 }, volume: 0.15, color: '#B4855A', icon: '📦' },
    { id: 'sofa', name: 'Canape 2 places', dimensions: { l: 1.8, w: 0.9, h: 0.85 }, volume: 1.38, color: '#8B5CF6', icon: '🛋️' },
    { id: 'sofa_3', name: 'Canape 3 places', dimensions: { l: 2.2, w: 0.95, h: 0.85 }, volume: 1.78, color: '#7C3AED', icon: '🛋️' },
    { id: 'armchair', name: 'Fauteuil', dimensions: { l: 0.9, w: 0.85, h: 0.9 }, volume: 0.69, color: '#A78BFA', icon: '🪑' },
    { id: 'bed_single', name: 'Lit 1 place', dimensions: { l: 2.0, w: 0.9, h: 0.5 }, volume: 0.9, color: '#F472B6', icon: '🛏️' },
    { id: 'bed_double', name: 'Lit 2 places', dimensions: { l: 2.0, w: 1.4, h: 0.5 }, volume: 1.4, color: '#EC4899', icon: '🛏️' },
    { id: 'mattress', name: 'Matelas', dimensions: { l: 2.0, w: 1.4, h: 0.25 }, volume: 0.7, color: '#FBBF24', icon: '🛏️' },
    { id: 'wardrobe', name: 'Armoire', dimensions: { l: 1.2, w: 0.6, h: 2.0 }, volume: 1.44, color: '#92400E', icon: '🚪' },
    { id: 'wardrobe_large', name: 'Grande armoire', dimensions: { l: 1.8, w: 0.6, h: 2.2 }, volume: 2.38, color: '#78350F', icon: '🚪' },
    { id: 'dresser', name: 'Commode', dimensions: { l: 1.0, w: 0.5, h: 0.9 }, volume: 0.45, color: '#A16207', icon: '🗄️' },
    { id: 'desk', name: 'Bureau', dimensions: { l: 1.4, w: 0.7, h: 0.75 }, volume: 0.74, color: '#CA8A04', icon: '🖥️' },
    { id: 'dining_table', name: 'Table a manger', dimensions: { l: 1.6, w: 0.9, h: 0.75 }, volume: 1.08, color: '#B45309', icon: '🍽️' },
    { id: 'chairs_4', name: 'Chaises (x4)', dimensions: { l: 0.8, w: 0.8, h: 0.9 }, volume: 0.58, color: '#D97706', icon: '🪑' },
    { id: 'fridge', name: 'Refrigerateur', dimensions: { l: 0.6, w: 0.65, h: 1.8 }, volume: 0.7, color: '#E5E7EB', icon: '🧊' },
    { id: 'washing_machine', name: 'Lave-linge', dimensions: { l: 0.6, w: 0.6, h: 0.85 }, volume: 0.31, color: '#9CA3AF', icon: '🧺' },
    { id: 'tv', name: 'TV (emballee)', dimensions: { l: 1.2, w: 0.15, h: 0.8 }, volume: 0.14, color: '#1F2937', icon: '📺' },
    { id: 'bike', name: 'Velo', dimensions: { l: 1.8, w: 0.6, h: 1.1 }, volume: 1.19, color: '#059669', icon: '🚲' },
    { id: 'plant', name: 'Plante (grande)', dimensions: { l: 0.5, w: 0.5, h: 1.2 }, volume: 0.3, color: '#10B981', icon: '🌿' },
]

// Items in the vehicle
const loadedItems = ref([])

// Custom item being edited
const showCustomItemModal = ref(false)
const editingItem = ref(null)
const customItem = ref({
    name: '',
    dimensions: { l: 0.5, w: 0.5, h: 0.5 },
    color: '#6366F1',
})

// Computed values
const totalLoadedVolume = computed(() => {
    return loadedItems.value.reduce((sum, item) => sum + item.volume * item.quantity, 0)
})

const fillPercentage = computed(() => {
    if (!selectedVehicle.value) return 0
    const percent = (totalLoadedVolume.value / selectedVehicle.value.volume) * 100
    return Math.min(100, Math.round(percent * 10) / 10)
})

const remainingVolume = computed(() => {
    if (!selectedVehicle.value) return 0
    return Math.max(0, selectedVehicle.value.volume - totalLoadedVolume.value)
})

const isOverloaded = computed(() => fillPercentage.value > 100)

// Watch fill percentage and emit
watch(fillPercentage, (newVal) => {
    emit('update:fillPercentage', newVal)
})

// Methods
const addItem = (item) => {
    const existingItem = loadedItems.value.find(i => i.id === item.id)
    if (existingItem) {
        existingItem.quantity++
    } else {
        loadedItems.value.push({
            ...item,
            quantity: 1,
            instanceId: Date.now(),
        })
    }
    emit('itemsChanged', loadedItems.value)
}

const removeItem = (item) => {
    const index = loadedItems.value.findIndex(i => i.instanceId === item.instanceId)
    if (index !== -1) {
        if (loadedItems.value[index].quantity > 1) {
            loadedItems.value[index].quantity--
        } else {
            loadedItems.value.splice(index, 1)
        }
    }
    emit('itemsChanged', loadedItems.value)
}

const deleteItem = (item) => {
    const index = loadedItems.value.findIndex(i => i.instanceId === item.instanceId)
    if (index !== -1) {
        loadedItems.value.splice(index, 1)
    }
    emit('itemsChanged', loadedItems.value)
}

const clearAll = () => {
    loadedItems.value = []
    emit('itemsChanged', loadedItems.value)
}

const openCustomItemModal = (item = null) => {
    if (item) {
        editingItem.value = item
        customItem.value = {
            name: item.name,
            dimensions: { ...item.dimensions },
            color: item.color,
        }
    } else {
        editingItem.value = null
        customItem.value = {
            name: '',
            dimensions: { l: 0.5, w: 0.5, h: 0.5 },
            color: '#6366F1',
        }
    }
    showCustomItemModal.value = true
}

const saveCustomItem = () => {
    const volume = customItem.value.dimensions.l * customItem.value.dimensions.w * customItem.value.dimensions.h

    if (editingItem.value) {
        // Update existing
        const index = loadedItems.value.findIndex(i => i.instanceId === editingItem.value.instanceId)
        if (index !== -1) {
            loadedItems.value[index] = {
                ...loadedItems.value[index],
                name: customItem.value.name,
                dimensions: { ...customItem.value.dimensions },
                volume: volume,
                color: customItem.value.color,
            }
        }
    } else {
        // Add new custom item
        addItem({
            id: 'custom_' + Date.now(),
            name: customItem.value.name || 'Objet personnalise',
            dimensions: { ...customItem.value.dimensions },
            volume: volume,
            color: customItem.value.color,
            icon: '📦',
            isCustom: true,
        })
    }

    showCustomItemModal.value = false
    emit('itemsChanged', loadedItems.value)
}

const formatVolume = (vol) => {
    if (vol < 1) {
        return (vol * 1000).toFixed(0) + ' L'
    }
    return vol.toFixed(2) + ' m³'
}

const getFillColor = (percent) => {
    if (percent > 100) return '#EF4444'
    if (percent > 80) return '#F59E0B'
    if (percent > 50) return '#3B82F6'
    return '#10B981'
}

// 3D Canvas
const canvasRef = ref(null)
let animationId = null

// Simple 3D visualization using CSS transforms
const vehicleRotation = ref({ x: -20, y: -30 })
const isDragging = ref(false)
const lastMousePos = ref({ x: 0, y: 0 })

const startDrag = (e) => {
    isDragging.value = true
    lastMousePos.value = { x: e.clientX || e.touches?.[0]?.clientX, y: e.clientY || e.touches?.[0]?.clientY }
}

const onDrag = (e) => {
    if (!isDragging.value) return
    const x = e.clientX || e.touches?.[0]?.clientX
    const y = e.clientY || e.touches?.[0]?.clientY
    const deltaX = x - lastMousePos.value.x
    const deltaY = y - lastMousePos.value.y

    vehicleRotation.value.y += deltaX * 0.5
    vehicleRotation.value.x = Math.max(-60, Math.min(10, vehicleRotation.value.x + deltaY * 0.5))

    lastMousePos.value = { x, y }
}

const stopDrag = () => {
    isDragging.value = false
}

const resetView = () => {
    vehicleRotation.value = { x: -20, y: -30 }
}

// Generate 3D boxes for loaded items
const generateLoadedBoxes = computed(() => {
    const boxes = []
    const vehicle = selectedVehicle.value
    if (!vehicle) return boxes

    const { length, width, height } = vehicle.dimensions
    const scale = 100 // Scale factor for CSS

    let currentX = 0
    let currentY = 0
    let currentZ = 0
    let rowMaxHeight = 0
    let layerMaxDepth = 0

    loadedItems.value.forEach(item => {
        for (let q = 0; q < item.quantity; q++) {
            const itemL = item.dimensions.l * scale
            const itemW = item.dimensions.w * scale
            const itemH = item.dimensions.h * scale

            // Simple stacking algorithm
            if (currentX + itemL > length * scale) {
                currentX = 0
                currentZ += rowMaxHeight
                rowMaxHeight = 0
            }

            if (currentZ + itemH > height * scale) {
                currentZ = 0
                currentY += layerMaxDepth
                layerMaxDepth = 0
                currentX = 0
            }

            if (currentY + itemW <= width * scale) {
                boxes.push({
                    id: `${item.instanceId}_${q}`,
                    x: currentX,
                    y: currentY,
                    z: currentZ,
                    l: itemL,
                    w: itemW,
                    h: itemH,
                    color: item.color,
                    name: item.name,
                })

                rowMaxHeight = Math.max(rowMaxHeight, itemH)
                layerMaxDepth = Math.max(layerMaxDepth, itemW)
                currentX += itemL + 2
            }
        }
    })

    return boxes
})
</script>

<template>
    <div class="vehicle-loading-simulator bg-white rounded-xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-4 text-white">
            <h2 class="text-xl font-bold flex items-center gap-2">
                <TruckIcon class="w-6 h-6" />
                Simulateur de Chargement Vehicule
            </h2>
            <p class="text-blue-100 text-sm mt-1">Visualisez le remplissage de votre vehicule en 3D</p>
        </div>

        <div class="p-4 lg:p-6">
            <!-- Vehicle Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Type de vehicule</label>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2">
                    <button
                        v-for="vehicle in vehicleTypes"
                        :key="vehicle.id"
                        @click="selectedVehicleId = vehicle.id"
                        class="p-3 rounded-lg border-2 transition-all text-center hover:shadow-md"
                        :class="selectedVehicleId === vehicle.id
                            ? 'border-blue-500 bg-blue-50'
                            : 'border-gray-200 hover:border-gray-300'"
                    >
                        <span class="text-2xl">{{ vehicle.icon }}</span>
                        <p class="text-xs font-medium mt-1 truncate">{{ vehicle.name }}</p>
                        <p class="text-xs text-gray-500">{{ vehicle.volume }} m³</p>
                    </button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left: 3D Visualization -->
                <div class="lg:col-span-2">
                    <!-- Fill Stats -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-700">Remplissage</span>
                            <span class="text-2xl font-bold" :style="{ color: getFillColor(fillPercentage) }">
                                {{ fillPercentage }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{
                                    width: Math.min(fillPercentage, 100) + '%',
                                    backgroundColor: getFillColor(fillPercentage)
                                }"
                            ></div>
                        </div>
                        <div class="flex justify-between text-sm mt-2 text-gray-600">
                            <span>Charge: {{ formatVolume(totalLoadedVolume) }}</span>
                            <span>Capacite: {{ formatVolume(selectedVehicle?.volume || 0) }}</span>
                            <span>Restant: {{ formatVolume(remainingVolume) }}</span>
                        </div>
                        <div v-if="isOverloaded" class="mt-2 text-red-600 text-sm font-medium flex items-center gap-1">
                            <XMarkIcon class="w-4 h-4" />
                            Attention: Capacite depassee!
                        </div>
                    </div>

                    <!-- 3D View -->
                    <div
                        class="relative bg-gradient-to-b from-gray-100 to-gray-200 rounded-lg overflow-hidden"
                        style="height: 400px; perspective: 1000px;"
                        @mousedown="startDrag"
                        @mousemove="onDrag"
                        @mouseup="stopDrag"
                        @mouseleave="stopDrag"
                        @touchstart="startDrag"
                        @touchmove="onDrag"
                        @touchend="stopDrag"
                    >
                        <!-- Controls -->
                        <div class="absolute top-2 right-2 flex gap-2 z-10">
                            <button
                                @click="resetView"
                                class="p-2 bg-white rounded-lg shadow hover:bg-gray-50"
                                title="Reset view"
                            >
                                <ArrowPathIcon class="w-5 h-5 text-gray-600" />
                            </button>
                            <button
                                @click="clearAll"
                                class="p-2 bg-white rounded-lg shadow hover:bg-gray-50"
                                title="Clear all"
                            >
                                <TrashIcon class="w-5 h-5 text-gray-600" />
                            </button>
                        </div>

                        <!-- 3D Scene -->
                        <div
                            class="absolute inset-0 flex items-center justify-center"
                            style="transform-style: preserve-3d;"
                        >
                            <div
                                class="relative"
                                :style="{
                                    transformStyle: 'preserve-3d',
                                    transform: `rotateX(${vehicleRotation.x}deg) rotateY(${vehicleRotation.y}deg)`,
                                    transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                                }"
                            >
                                <!-- Vehicle Container (wireframe) -->
                                <div
                                    class="relative border-2 border-dashed rounded"
                                    :style="{
                                        width: (selectedVehicle?.dimensions.length || 2) * 100 + 'px',
                                        height: (selectedVehicle?.dimensions.height || 1.5) * 100 + 'px',
                                        borderColor: selectedVehicle?.color || '#3B82F6',
                                        transformStyle: 'preserve-3d',
                                        transform: `translateZ(${(selectedVehicle?.dimensions.width || 1) * 50}px)`,
                                    }"
                                >
                                    <!-- Back face -->
                                    <div
                                        class="absolute inset-0 border-2 border-dashed opacity-30 rounded"
                                        :style="{
                                            borderColor: selectedVehicle?.color || '#3B82F6',
                                            transform: `translateZ(-${(selectedVehicle?.dimensions.width || 1) * 100}px)`,
                                        }"
                                    ></div>

                                    <!-- Left face -->
                                    <div
                                        class="absolute top-0 left-0 border-2 border-dashed opacity-30"
                                        :style="{
                                            width: (selectedVehicle?.dimensions.width || 1) * 100 + 'px',
                                            height: (selectedVehicle?.dimensions.height || 1.5) * 100 + 'px',
                                            borderColor: selectedVehicle?.color || '#3B82F6',
                                            transformOrigin: 'left center',
                                            transform: 'rotateY(-90deg)',
                                        }"
                                    ></div>

                                    <!-- Right face -->
                                    <div
                                        class="absolute top-0 right-0 border-2 border-dashed opacity-30"
                                        :style="{
                                            width: (selectedVehicle?.dimensions.width || 1) * 100 + 'px',
                                            height: (selectedVehicle?.dimensions.height || 1.5) * 100 + 'px',
                                            borderColor: selectedVehicle?.color || '#3B82F6',
                                            transformOrigin: 'right center',
                                            transform: 'rotateY(90deg)',
                                        }"
                                    ></div>

                                    <!-- Bottom face -->
                                    <div
                                        class="absolute bottom-0 left-0 border-2 border-dashed opacity-20"
                                        :style="{
                                            width: (selectedVehicle?.dimensions.length || 2) * 100 + 'px',
                                            height: (selectedVehicle?.dimensions.width || 1) * 100 + 'px',
                                            backgroundColor: selectedVehicle?.color || '#3B82F6',
                                            borderColor: selectedVehicle?.color || '#3B82F6',
                                            transformOrigin: 'bottom center',
                                            transform: 'rotateX(90deg)',
                                        }"
                                    ></div>

                                    <!-- Loaded Items -->
                                    <div
                                        v-for="box in generateLoadedBoxes"
                                        :key="box.id"
                                        class="absolute rounded shadow-md transition-all duration-300"
                                        :style="{
                                            left: box.x + 'px',
                                            bottom: box.z + 'px',
                                            width: box.l + 'px',
                                            height: box.h + 'px',
                                            backgroundColor: box.color,
                                            opacity: 0.9,
                                            transformStyle: 'preserve-3d',
                                            transform: `translateZ(${box.y}px)`,
                                        }"
                                        :title="box.name"
                                    >
                                        <!-- 3D depth for box -->
                                        <div
                                            class="absolute top-0 right-0 opacity-70"
                                            :style="{
                                                width: box.w + 'px',
                                                height: box.h + 'px',
                                                backgroundColor: box.color,
                                                filter: 'brightness(0.8)',
                                                transformOrigin: 'right center',
                                                transform: 'rotateY(90deg)',
                                            }"
                                        ></div>
                                        <div
                                            class="absolute top-0 left-0 opacity-80"
                                            :style="{
                                                width: box.l + 'px',
                                                height: box.w + 'px',
                                                backgroundColor: box.color,
                                                filter: 'brightness(1.1)',
                                                transformOrigin: 'top center',
                                                transform: 'rotateX(-90deg)',
                                            }"
                                        ></div>
                                    </div>
                                </div>

                                <!-- Vehicle Label -->
                                <div
                                    class="absolute -bottom-8 left-1/2 transform -translate-x-1/2 text-center"
                                >
                                    <span class="text-lg">{{ selectedVehicle?.icon }}</span>
                                    <p class="text-xs text-gray-600 font-medium">{{ selectedVehicle?.name }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="absolute bottom-2 left-2 text-xs text-gray-500 bg-white/80 px-2 py-1 rounded">
                            Cliquez et glissez pour faire pivoter
                        </div>
                    </div>
                </div>

                <!-- Right: Item Selection & Loaded Items -->
                <div class="space-y-4">
                    <!-- Add Custom Item -->
                    <button
                        v-if="editable"
                        @click="openCustomItemModal()"
                        class="w-full p-3 border-2 border-dashed border-blue-300 rounded-lg text-blue-600 hover:bg-blue-50 transition-colors flex items-center justify-center gap-2"
                    >
                        <PlusIcon class="w-5 h-5" />
                        Ajouter un objet personnalise
                    </button>

                    <!-- Item Categories -->
                    <div class="bg-gray-50 rounded-lg p-3 max-h-[300px] overflow-y-auto">
                        <h4 class="font-medium text-gray-700 mb-2 text-sm">Objets disponibles</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                v-for="item in defaultItems"
                                :key="item.id"
                                @click="addItem(item)"
                                class="p-2 bg-white rounded-lg border hover:border-blue-300 hover:shadow-sm transition-all text-left"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">{{ item.icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium truncate">{{ item.name }}</p>
                                        <p class="text-xs text-gray-500">{{ formatVolume(item.volume) }}</p>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Loaded Items List -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium text-gray-700 text-sm">Objets charges ({{ loadedItems.length }})</h4>
                            <button
                                v-if="loadedItems.length > 0"
                                @click="clearAll"
                                class="text-xs text-red-600 hover:text-red-700"
                            >
                                Tout vider
                            </button>
                        </div>

                        <div v-if="loadedItems.length === 0" class="text-center py-4 text-gray-500 text-sm">
                            Aucun objet charge
                        </div>

                        <div v-else class="space-y-2 max-h-[200px] overflow-y-auto">
                            <div
                                v-for="item in loadedItems"
                                :key="item.instanceId"
                                class="flex items-center gap-2 p-2 bg-white rounded-lg border"
                            >
                                <div
                                    class="w-3 h-3 rounded"
                                    :style="{ backgroundColor: item.color }"
                                ></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium truncate">{{ item.name }}</p>
                                    <p class="text-xs text-gray-500">{{ formatVolume(item.volume) }}</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button
                                        @click="removeItem(item)"
                                        class="p-1 hover:bg-gray-100 rounded"
                                    >
                                        <MinusIcon class="w-4 h-4 text-gray-500" />
                                    </button>
                                    <span class="text-sm font-medium w-6 text-center">{{ item.quantity }}</span>
                                    <button
                                        @click="addItem(item)"
                                        class="p-1 hover:bg-gray-100 rounded"
                                    >
                                        <PlusIcon class="w-4 h-4 text-gray-500" />
                                    </button>
                                    <button
                                        v-if="editable"
                                        @click="openCustomItemModal(item)"
                                        class="p-1 hover:bg-gray-100 rounded ml-1"
                                    >
                                        <PencilIcon class="w-4 h-4 text-blue-500" />
                                    </button>
                                    <button
                                        @click="deleteItem(item)"
                                        class="p-1 hover:bg-gray-100 rounded"
                                    >
                                        <TrashIcon class="w-4 h-4 text-red-500" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <h4 class="font-medium text-blue-900 mb-2">Resume</h4>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-blue-700">Vehicule:</span>
                                <span class="font-medium">{{ selectedVehicle?.name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Capacite:</span>
                                <span class="font-medium">{{ formatVolume(selectedVehicle?.volume || 0) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700">Volume charge:</span>
                                <span class="font-medium">{{ formatVolume(totalLoadedVolume) }}</span>
                            </div>
                            <div class="flex justify-between text-lg pt-2 border-t border-blue-200">
                                <span class="text-blue-800 font-medium">Remplissage:</span>
                                <span class="font-bold" :style="{ color: getFillColor(fillPercentage) }">
                                    {{ fillPercentage }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Item Modal -->
        <div
            v-if="showCustomItemModal"
            class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
            @click.self="showCustomItemModal = false"
        >
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 m-4">
                <h3 class="text-lg font-semibold mb-4">
                    {{ editingItem ? 'Modifier l\'objet' : 'Nouvel objet personnalise' }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom</label>
                        <input
                            v-model="customItem.name"
                            type="text"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            placeholder="ex: Carton livres"
                        >
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Longueur (m)</label>
                            <input
                                v-model.number="customItem.dimensions.l"
                                type="number"
                                step="0.1"
                                min="0.1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Largeur (m)</label>
                            <input
                                v-model.number="customItem.dimensions.w"
                                type="number"
                                step="0.1"
                                min="0.1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hauteur (m)</label>
                            <input
                                v-model.number="customItem.dimensions.h"
                                type="number"
                                step="0.1"
                                min="0.1"
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Couleur</label>
                        <div class="flex gap-2">
                            <input
                                v-model="customItem.color"
                                type="color"
                                class="w-12 h-10 rounded border cursor-pointer"
                            >
                            <input
                                v-model="customItem.color"
                                type="text"
                                class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                            >
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-sm text-gray-600">
                            Volume calcule:
                            <span class="font-bold text-gray-900">
                                {{ formatVolume(customItem.dimensions.l * customItem.dimensions.w * customItem.dimensions.h) }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <button
                        @click="showCustomItemModal = false"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50"
                    >
                        Annuler
                    </button>
                    <button
                        @click="saveCustomItem"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-1" />
                        {{ editingItem ? 'Modifier' : 'Ajouter' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.vehicle-loading-simulator {
    user-select: none;
}
</style>
