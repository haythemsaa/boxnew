<template>
    <AuthenticatedLayout title="Éditeur de Plan Professionnel">
        <!-- Success Message -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-600">{{ $page.props.flash.success }}</p>
        </div>

        <div class="flex gap-4 h-[calc(100vh-12rem)]">
            <!-- Left Sidebar - Toolbar -->
            <div class="w-64 bg-white rounded-lg shadow-lg p-4 flex flex-col gap-4 overflow-y-auto">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <h3 class="font-bold text-lg">Outils</h3>
                </div>

                <!-- Tool Selection -->
                <div class="space-y-2">
                    <button
                        @click="currentTool = 'select'"
                        :class="currentTool === 'select' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                        </svg>
                        Sélectionner / Déplacer
                    </button>

                    <button
                        @click="currentTool = 'add-box'"
                        :class="currentTool === 'add-box' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        Ajouter Box
                    </button>

                    <button
                        @click="currentTool = 'add-corridor'"
                        :class="currentTool === 'add-corridor' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Ajouter Couloir
                    </button>

                    <button
                        @click="currentTool = 'add-wall'"
                        :class="currentTool === 'add-wall' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        Ajouter Mur
                    </button>

                    <button
                        @click="deletedSelected"
                        class="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-colors bg-red-100 text-red-700 hover:bg-red-200"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Supprimer sélection
                    </button>
                </div>

                <hr class="my-2"/>

                <!-- Options -->
                <div class="space-y-3">
                    <h4 class="font-semibold text-sm text-gray-700">Options</h4>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="snapToGrid"
                            class="rounded text-primary-600 focus:ring-primary-500"
                        />
                        <span class="text-sm text-gray-700">Magnétisme de grille</span>
                    </label>

                    <div>
                        <label class="text-sm text-gray-700 block mb-1">Taille de grille: {{ gridSize }}px</label>
                        <input
                            type="range"
                            v-model.number="gridSize"
                            min="10"
                            max="50"
                            class="w-full"
                        />
                    </div>

                    <div>
                        <label class="text-sm text-gray-700 block mb-1">Zoom: {{ Math.round(zoom * 100) }}%</label>
                        <input
                            type="range"
                            v-model.number="zoom"
                            min="0.5"
                            max="2"
                            step="0.1"
                            class="w-full"
                        />
                    </div>
                </div>

                <hr class="my-2"/>

                <!-- Actions -->
                <div class="space-y-2">
                    <button
                        @click="autoArrange"
                        class="w-full bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded-lg font-medium transition-colors text-sm"
                    >
                        Réorganiser en grille
                    </button>

                    <button
                        @click="clearAll"
                        class="w-full bg-orange-600 hover:bg-orange-700 text-white px-3 py-2 rounded-lg font-medium transition-colors text-sm"
                    >
                        Tout effacer
                    </button>

                    <button
                        @click="savePlan"
                        :disabled="saving"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white px-3 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 text-sm"
                    >
                        {{ saving ? 'Sauvegarde...' : 'Sauvegarder' }}
                    </button>

                    <Link
                        :href="route('tenant.boxes.plan')"
                        class="block w-full text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg font-medium transition-colors text-sm"
                    >
                        Annuler
                    </Link>
                </div>
            </div>

            <!-- Main Canvas -->
            <div class="flex-1 bg-white rounded-lg shadow-lg overflow-hidden flex flex-col">
                <!-- Top Toolbar -->
                <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <h2 class="font-bold text-gray-900">Plan des Boxes</h2>
                        <span class="text-sm text-gray-600">{{ editableBoxes.length }} boxes | {{ planElements.length }} éléments</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <kbd class="px-2 py-1 bg-white border rounded">Clic</kbd> = Placer
                        <kbd class="px-2 py-1 bg-white border rounded">Glisser</kbd> = Déplacer
                        <kbd class="px-2 py-1 bg-white border rounded">Suppr</kbd> = Effacer
                    </div>
                </div>

                <!-- Canvas -->
                <div class="flex-1 overflow-auto bg-gray-100 p-4">
                    <div
                        ref="planContainer"
                        class="relative bg-white border-2 border-gray-300 rounded-lg shadow-inner mx-auto"
                        :style="{
                            width: `${canvasWidth * zoom}px`,
                            height: `${canvasHeight * zoom}px`,
                            transform: `scale(${zoom})`,
                            transformOrigin: 'top left',
                        }"
                        @click="handleCanvasClick"
                        @mousedown="handleCanvasMouseDown"
                    >
                        <!-- Grid background -->
                        <div
                            v-if="snapToGrid"
                            class="absolute inset-0 pointer-events-none"
                            :style="{
                                backgroundImage: `linear-gradient(#e5e7eb 1px, transparent 1px), linear-gradient(90deg, #e5e7eb 1px, transparent 1px)`,
                                backgroundSize: `${gridSize}px ${gridSize}px`
                            }"
                        ></div>

                        <!-- Plan Elements (Corridors, Walls) -->
                        <div
                            v-for="element in planElements"
                            :key="element.id"
                            class="absolute cursor-pointer transition-shadow"
                            :class="[
                                element.type === 'corridor' ? 'bg-gray-200 border-2 border-dashed border-gray-400' : '',
                                element.type === 'wall' ? 'bg-gray-700' : '',
                                selectedElementId === element.id ? 'ring-4 ring-yellow-400 shadow-2xl z-30' : 'z-10'
                            ]"
                            :style="getElementStyle(element)"
                            @click.stop="selectElement(element)"
                            @mousedown.stop="startDragElement(element, $event)"
                        >
                            <div v-if="element.type === 'corridor'" class="flex items-center justify-center h-full text-gray-600 font-semibold text-xs">
                                {{ element.label || 'Couloir' }}
                            </div>
                            <!-- Resize handles for elements -->
                            <template v-if="selectedElementId === element.id">
                                <div class="absolute bottom-0 right-0 w-3 h-3 bg-yellow-500 border border-yellow-600 cursor-se-resize" @mousedown.stop="startResizeElement(element, $event)"></div>
                            </template>
                        </div>

                        <!-- Boxes -->
                        <div
                            v-for="box in editableBoxes"
                            :key="'box-' + box.id"
                            class="absolute cursor-move border-2 rounded flex flex-col items-center justify-center text-xs font-semibold transition-shadow select-none"
                            :class="[
                                getBoxColorClass(box.status),
                                selectedBoxId === box.id ? 'ring-4 ring-yellow-400 border-yellow-500 shadow-2xl z-20' : 'border-gray-700 hover:shadow-lg z-10'
                            ]"
                            :style="getBoxStyle(box)"
                            @mousedown.stop="startDragBox(box, $event)"
                            @click.stop="selectBox(box)"
                        >
                            <div class="text-center pointer-events-none">
                                <div class="font-bold">{{ box.number }}</div>
                                <div class="text-[10px]">{{ box.volume }}m³</div>
                            </div>

                            <!-- Resize handle -->
                            <div
                                v-if="selectedBoxId === box.id"
                                class="absolute bottom-0 right-0 w-3 h-3 bg-yellow-500 border border-yellow-600 cursor-se-resize"
                                @mousedown.stop="startResizeBox(box, $event)"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Properties -->
            <div class="w-80 bg-white rounded-lg shadow-lg p-4 overflow-y-auto">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Propriétés
                </h3>

                <!-- Box Properties -->
                <div v-if="selectedBoxId" class="space-y-4">
                    <div class="bg-cyan-50 p-3 rounded-lg border border-cyan-200">
                        <h4 class="font-semibold text-cyan-900 mb-2 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Box {{ getSelectedBox()?.number }}
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600">Volume</label>
                                <p class="text-sm font-semibold">{{ getSelectedBox()?.volume }} m³</p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600">État</label>
                                <p class="text-sm">
                                    <span :class="getStatusBadgeClass(getSelectedBox()?.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(getSelectedBox()?.status) }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h5 class="font-semibold text-sm text-gray-700">Position & Dimensions</h5>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">X</label>
                                <input
                                    v-model.number="getSelectedBox().position.x"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Y</label>
                                <input
                                    v-model.number="getSelectedBox().position.y"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Largeur</label>
                                <input
                                    v-model.number="getSelectedBox().position.width"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Hauteur</label>
                                <input
                                    v-model.number="getSelectedBox().position.height"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Element Properties -->
                <div v-else-if="selectedElementId" class="space-y-4">
                    <div :class="getSelectedElement()?.type === 'corridor' ? 'bg-gray-100' : 'bg-gray-700'" class="p-3 rounded-lg border border-gray-300">
                        <h4 class="font-semibold mb-2 flex items-center gap-2" :class="getSelectedElement()?.type === 'corridor' ? 'text-gray-900' : 'text-white'">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16M4 9h16M4 13h16M4 17h16"/>
                            </svg>
                            {{ getSelectedElement()?.type === 'corridor' ? 'Couloir' : 'Mur' }}
                        </h4>
                    </div>

                    <div v-if="getSelectedElement()?.type === 'corridor'" class="space-y-3">
                        <div>
                            <label class="text-xs font-medium text-gray-600 block mb-1">Nom du couloir</label>
                            <input
                                v-model="getSelectedElement().label"
                                type="text"
                                placeholder="Ex: Couloir A"
                                class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                            />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h5 class="font-semibold text-sm text-gray-700">Position & Dimensions</h5>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">X</label>
                                <input
                                    v-model.number="getSelectedElement().x"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Y</label>
                                <input
                                    v-model.number="getSelectedElement().y"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Largeur</label>
                                <input
                                    v-model.number="getSelectedElement().width"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-600 block mb-1">Hauteur</label>
                                <input
                                    v-model.number="getSelectedElement().height"
                                    type="number"
                                    class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Selection -->
                <div v-else class="text-center py-8 text-gray-500">
                    <svg class="mx-auto h-12 w-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                    </svg>
                    <p class="text-sm">Sélectionnez un élément pour voir ses propriétés</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    boxes: Array,
    planElements: Array,
})

// State
const currentTool = ref('select')
const editableBoxes = ref([])
const planElements = ref([])
const selectedBoxId = ref(null)
const selectedElementId = ref(null)
const saving = ref(false)
const planContainer = ref(null)
const snapToGrid = ref(true)
const gridSize = ref(20)
const zoom = ref(1)
const canvasWidth = ref(1600)
const canvasHeight = ref(1200)

let nextElementId = 1

// Dragging state
const dragState = reactive({
    isDragging: false,
    isResizing: false,
    currentItem: null,
    itemType: null, // 'box' or 'element'
    startX: 0,
    startY: 0,
    startItemX: 0,
    startItemY: 0,
    startWidth: 0,
    startHeight: 0,
})

// Initialize boxes with default positions
onMounted(() => {
    editableBoxes.value = props.boxes.map((box, index) => {
        if (!box.position || !box.position.x) {
            const cols = 15
            const col = index % cols
            const row = Math.floor(index / cols)

            return {
                ...box,
                position: {
                    x: col * 80 + 20,
                    y: row * 70 + 20,
                    width: 70,
                    height: 60,
                }
            }
        }
        return {
            ...box,
            position: {
                x: box.position.x || 0,
                y: box.position.y || 0,
                width: box.position.width || 70,
                height: box.position.height || 60,
            }
        }
    })

    // Load existing plan elements
    if (props.planElements && props.planElements.length > 0) {
        planElements.value = [...props.planElements]
        // Find the highest ID to continue from
        const maxId = Math.max(...props.planElements.map(e => e.id || 0), 0)
        nextElementId = maxId + 1
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyDown)
})

// Keyboard shortcuts
const handleKeyDown = (event) => {
    if (event.key === 'Delete') {
        deletedSelected()
    } else if (event.key === 'Escape') {
        deselectAll()
    }
}

// Snap to grid
const snapValue = (value) => {
    if (!snapToGrid.value) return value
    return Math.round(value / gridSize.value) * gridSize.value
}

// Get box color class
const getBoxColorClass = (status) => {
    const colors = {
        occupied: 'bg-cyan-500 text-white',
        available: 'bg-green-500 text-white',
        reserved: 'bg-yellow-400 text-gray-900',
        maintenance: 'bg-orange-400 text-white',
        unavailable: 'bg-gray-400 text-white',
    }
    return colors[status] || 'bg-gray-300 text-gray-900'
}

// Get status badge class
const getStatusBadgeClass = (status) => {
    const colors = {
        occupied: 'bg-cyan-100 text-cyan-800',
        available: 'bg-green-100 text-green-800',
        reserved: 'bg-yellow-100 text-yellow-800',
        maintenance: 'bg-orange-100 text-orange-800',
        unavailable: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

// Get status label
const getStatusLabel = (status) => {
    const labels = {
        occupied: 'Occupé',
        available: 'Libre',
        reserved: 'Réservé',
        maintenance: 'Maintenance',
        unavailable: 'Indisponible',
    }
    return labels[status] || status
}

// Get box style
const getBoxStyle = (box) => {
    return {
        left: `${box.position.x}px`,
        top: `${box.position.y}px`,
        width: `${box.position.width}px`,
        height: `${box.position.height}px`,
    }
}

// Get element style
const getElementStyle = (element) => {
    return {
        left: `${element.x}px`,
        top: `${element.y}px`,
        width: `${element.width}px`,
        height: `${element.height}px`,
    }
}

// Select box
const selectBox = (box) => {
    selectedBoxId.value = box.id
    selectedElementId.value = null
}

// Select element
const selectElement = (element) => {
    selectedElementId.value = element.id
    selectedBoxId.value = null
}

// Deselect all
const deselectAll = () => {
    selectedBoxId.value = null
    selectedElementId.value = null
}

// Get selected box
const getSelectedBox = () => {
    return editableBoxes.value.find(b => b.id === selectedBoxId.value)
}

// Get selected element
const getSelectedElement = () => {
    return planElements.value.find(e => e.id === selectedElementId.value)
}

// Handle canvas click
const handleCanvasClick = (event) => {
    if (currentTool.value === 'select') {
        deselectAll()
    } else if (currentTool.value === 'add-box') {
        // Add new box at click position
        const rect = planContainer.value.getBoundingClientRect()
        const x = snapValue(event.clientX - rect.left)
        const y = snapValue(event.clientY - rect.top)

        alert('Fonctionnalité "Ajouter Box" - Cette fonction nécessite la création d\'une nouvelle box dans la base de données. Veuillez utiliser le menu "Boxes" pour créer de nouvelles boxes.')
    } else if (currentTool.value === 'add-corridor' || currentTool.value === 'add-wall') {
        // Add corridor or wall at click position
        const rect = planContainer.value.getBoundingClientRect()
        const x = snapValue(event.clientX - rect.left)
        const y = snapValue(event.clientY - rect.top)

        const newElement = {
            id: nextElementId++,
            type: currentTool.value === 'add-corridor' ? 'corridor' : 'wall',
            x: x,
            y: y,
            width: currentTool.value === 'add-corridor' ? 120 : 20,
            height: currentTool.value === 'add-corridor' ? 80 : 100,
            label: currentTool.value === 'add-corridor' ? `Couloir ${nextElementId - 1}` : '',
        }

        planElements.value.push(newElement)
        selectElement(newElement)
    }
}

// Handle canvas mouse down
const handleCanvasMouseDown = (event) => {
    if (currentTool.value === 'select') {
        deselectAll()
    }
}

// Start dragging box
const startDragBox = (box, event) => {
    if (currentTool.value !== 'select') return

    dragState.isDragging = true
    dragState.currentItem = box
    dragState.itemType = 'box'
    dragState.startX = event.clientX
    dragState.startY = event.clientY
    dragState.startItemX = box.position.x
    dragState.startItemY = box.position.y

    document.addEventListener('mousemove', onDrag)
    document.addEventListener('mouseup', stopDrag)
}

// Start dragging element
const startDragElement = (element, event) => {
    if (currentTool.value !== 'select') return

    dragState.isDragging = true
    dragState.currentItem = element
    dragState.itemType = 'element'
    dragState.startX = event.clientX
    dragState.startY = event.clientY
    dragState.startItemX = element.x
    dragState.startItemY = element.y

    document.addEventListener('mousemove', onDrag)
    document.addEventListener('mouseup', stopDrag)
}

// Dragging
const onDrag = (event) => {
    if (!dragState.isDragging || !dragState.currentItem) return

    const deltaX = event.clientX - dragState.startX
    const deltaY = event.clientY - dragState.startY

    if (dragState.itemType === 'box') {
        dragState.currentItem.position.x = Math.max(0, snapValue(dragState.startItemX + deltaX))
        dragState.currentItem.position.y = Math.max(0, snapValue(dragState.startItemY + deltaY))
    } else {
        dragState.currentItem.x = Math.max(0, snapValue(dragState.startItemX + deltaX))
        dragState.currentItem.y = Math.max(0, snapValue(dragState.startItemY + deltaY))
    }
}

// Stop dragging
const stopDrag = () => {
    dragState.isDragging = false
    dragState.currentItem = null

    document.removeEventListener('mousemove', onDrag)
    document.removeEventListener('mouseup', stopDrag)
}

// Start resizing box
const startResizeBox = (box, event) => {
    dragState.isResizing = true
    dragState.currentItem = box
    dragState.itemType = 'box'
    dragState.startX = event.clientX
    dragState.startY = event.clientY
    dragState.startWidth = box.position.width
    dragState.startHeight = box.position.height

    document.addEventListener('mousemove', onResize)
    document.addEventListener('mouseup', stopResize)
}

// Start resizing element
const startResizeElement = (element, event) => {
    dragState.isResizing = true
    dragState.currentItem = element
    dragState.itemType = 'element'
    dragState.startX = event.clientX
    dragState.startY = event.clientY
    dragState.startWidth = element.width
    dragState.startHeight = element.height

    document.addEventListener('mousemove', onResize)
    document.addEventListener('mouseup', stopResize)
}

// Resizing
const onResize = (event) => {
    if (!dragState.isResizing || !dragState.currentItem) return

    const deltaX = event.clientX - dragState.startX
    const deltaY = event.clientY - dragState.startY

    if (dragState.itemType === 'box') {
        dragState.currentItem.position.width = Math.max(40, snapValue(dragState.startWidth + deltaX))
        dragState.currentItem.position.height = Math.max(30, snapValue(dragState.startHeight + deltaY))
    } else {
        dragState.currentItem.width = Math.max(20, snapValue(dragState.startWidth + deltaX))
        dragState.currentItem.height = Math.max(20, snapValue(dragState.startHeight + deltaY))
    }
}

// Stop resizing
const stopResize = () => {
    dragState.isResizing = false
    dragState.currentItem = null

    document.removeEventListener('mousemove', onResize)
    document.removeEventListener('mouseup', stopResize)
}

// Delete selected
const deletedSelected = () => {
    if (selectedBoxId.value) {
        if (confirm('Voulez-vous vraiment supprimer cette box du plan? (Elle ne sera pas supprimée de la base de données)')) {
            const index = editableBoxes.value.findIndex(b => b.id === selectedBoxId.value)
            if (index !== -1) {
                // Reset position to null
                editableBoxes.value[index].position = { x: null, y: null, width: 70, height: 60 }
                deselectAll()
            }
        }
    } else if (selectedElementId.value) {
        if (confirm('Voulez-vous vraiment supprimer cet élément?')) {
            const index = planElements.value.findIndex(e => e.id === selectedElementId.value)
            if (index !== -1) {
                planElements.value.splice(index, 1)
                deselectAll()
            }
        }
    }
}

// Auto arrange boxes in grid
const autoArrange = () => {
    if (confirm('Voulez-vous réorganiser toutes les boxes en grille?')) {
        const cols = 15
        editableBoxes.value.forEach((box, index) => {
            const col = index % cols
            const row = Math.floor(index / cols)

            box.position.x = col * 80 + 20
            box.position.y = row * 70 + 20
            box.position.width = 70
            box.position.height = 60
        })
    }
}

// Clear all
const clearAll = () => {
    if (confirm('Voulez-vous vraiment tout effacer? Cette action ne peut pas être annulée.')) {
        editableBoxes.value.forEach(box => {
            box.position = { x: null, y: null, width: 70, height: 60 }
        })
        planElements.value = []
        deselectAll()
    }
}

// Save plan
const savePlan = () => {
    saving.value = true

    const data = {
        boxes: editableBoxes.value.map(box => ({
            id: box.id,
            position: box.position,
        })),
        elements: planElements.value
    }

    router.post(route('tenant.boxes.plan.save'), data, {
        onSuccess: () => {
            saving.value = false
        },
        onError: () => {
            saving.value = false
            alert('Erreur lors de la sauvegarde du plan')
        }
    })
}
</script>
