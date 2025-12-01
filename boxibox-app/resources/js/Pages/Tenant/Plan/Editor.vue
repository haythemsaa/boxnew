<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import {
    CubeIcon,
    Square2StackIcon,
    ArrowsPointingOutIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    LockClosedIcon,
    LockOpenIcon,
    EyeIcon,
    EyeSlashIcon,
    ArrowUturnLeftIcon,
    ArrowUturnRightIcon,
    CheckIcon,
    XMarkIcon,
    Cog6ToothIcon,
    PlusIcon,
    MagnifyingGlassMinusIcon,
    MagnifyingGlassPlusIcon,
    Bars3Icon,
    ArrowDownTrayIcon,
    ArrowUpTrayIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    elements: Array,
    configuration: Object,
    boxes: Array,
    unplacedBoxes: Array,
    floors: Array,
});

// State
const selectedSite = ref(props.currentSite?.id);
const localElements = ref([...props.elements].map(el => ({ ...el, id: el.id || generateId() })));
const selectedElements = ref([]);
const tool = ref('select'); // select, box, wall, door, separator, lift, label, zone
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const isResizing = ref(false);
const isDrawing = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const resizeHandle = ref(null);
const drawStart = ref({ x: 0, y: 0 });
const history = ref([]);
const historyIndex = ref(-1);
const showSettings = ref(false);
const showBoxList = ref(false);
const isSaving = ref(false);

// Configuration
const config = ref({
    canvas_width: props.configuration?.canvas_width || 1920,
    canvas_height: props.configuration?.canvas_height || 1080,
    show_grid: props.configuration?.show_grid ?? true,
    grid_size: props.configuration?.grid_size || 20,
    snap_to_grid: props.configuration?.snap_to_grid ?? true,
    default_box_available_color: props.configuration?.default_box_available_color || '#22c55e',
    default_box_occupied_color: props.configuration?.default_box_occupied_color || '#3b82f6',
    default_wall_color: props.configuration?.default_wall_color || '#1e3a5f',
    default_door_color: props.configuration?.default_door_color || '#ffffff',
});

const canvasRef = ref(null);

// Generate unique ID
function generateId() {
    return 'el_' + Math.random().toString(36).substr(2, 9);
}

// Tools configuration
const tools = [
    { id: 'select', icon: Bars3Icon, label: 'Sélection', shortcut: 'V' },
    { id: 'box', icon: CubeIcon, label: 'Box', shortcut: 'B' },
    { id: 'wall', icon: Square2StackIcon, label: 'Mur', shortcut: 'W' },
    { id: 'door', icon: Square2StackIcon, label: 'Porte', shortcut: 'D' },
    { id: 'separator', icon: Square2StackIcon, label: 'Séparateur', shortcut: 'S' },
    { id: 'lift', icon: ArrowsPointingOutIcon, label: 'Ascenseur', shortcut: 'L' },
    { id: 'label', icon: DocumentDuplicateIcon, label: 'Étiquette', shortcut: 'T' },
];

// Snap to grid
const snapToGrid = (value) => {
    if (!config.value.snap_to_grid) return value;
    return Math.round(value / config.value.grid_size) * config.value.grid_size;
};

// Get mouse position on canvas
const getCanvasPosition = (e) => {
    const rect = canvasRef.value.getBoundingClientRect();
    return {
        x: (e.clientX - rect.left - panX.value) / zoom.value,
        y: (e.clientY - rect.top - panY.value) / zoom.value,
    };
};

// Save to history
const saveHistory = () => {
    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(JSON.stringify(localElements.value));
    historyIndex.value = history.value.length - 1;
};

// Undo
const undo = () => {
    if (historyIndex.value > 0) {
        historyIndex.value--;
        localElements.value = JSON.parse(history.value[historyIndex.value]);
        selectedElements.value = [];
    }
};

// Redo
const redo = () => {
    if (historyIndex.value < history.value.length - 1) {
        historyIndex.value++;
        localElements.value = JSON.parse(history.value[historyIndex.value]);
        selectedElements.value = [];
    }
};

// Create new element
const createElement = (type, x, y, width = 100, height = 80) => {
    const defaults = {
        box: { fill_color: config.value.default_box_available_color, stroke_color: '#1e3a5f', width: 80, height: 60 },
        wall: { fill_color: config.value.default_wall_color, stroke_color: '#000000', width: 200, height: 10 },
        door: { fill_color: config.value.default_door_color, stroke_color: '#666666', width: 60, height: 10 },
        separator: { fill_color: '#94a3b8', stroke_color: '#64748b', width: 150, height: 5 },
        lift: { fill_color: '#e5e7eb', stroke_color: '#9ca3af', width: 80, height: 80 },
        label: { fill_color: 'transparent', stroke_color: 'transparent', width: 100, height: 30 },
    };

    const def = defaults[type] || defaults.box;

    return {
        id: generateId(),
        element_type: type,
        box_id: null,
        x: snapToGrid(x),
        y: snapToGrid(y),
        width: width || def.width,
        height: height || def.height,
        rotation: 0,
        z_index: localElements.value.length,
        fill_color: def.fill_color,
        stroke_color: def.stroke_color,
        stroke_width: 2,
        opacity: 1,
        label: type === 'lift' ? 'LIFT' : '',
        is_locked: false,
        is_visible: true,
    };
};

// Mouse down on canvas
const onCanvasMouseDown = (e) => {
    if (e.target !== canvasRef.value && !e.target.classList.contains('canvas-background')) {
        return;
    }

    const pos = getCanvasPosition(e);

    if (tool.value === 'select') {
        // Start panning
        isDragging.value = true;
        dragStart.value = { x: e.clientX - panX.value, y: e.clientY - panY.value };
        selectedElements.value = [];
    } else {
        // Start drawing new element
        isDrawing.value = true;
        drawStart.value = pos;
    }
};

// Mouse move on canvas
const onCanvasMouseMove = (e) => {
    if (isDragging.value) {
        panX.value = e.clientX - dragStart.value.x;
        panY.value = e.clientY - dragStart.value.y;
    } else if (isDrawing.value) {
        // Preview drawing
    } else if (isResizing.value && selectedElements.value.length === 1) {
        const pos = getCanvasPosition(e);
        const element = localElements.value.find(el => el.id === selectedElements.value[0]);
        if (element && !element.is_locked) {
            const handle = resizeHandle.value;
            if (handle.includes('e')) {
                element.width = Math.max(20, snapToGrid(pos.x - element.x));
            }
            if (handle.includes('w')) {
                const newX = snapToGrid(pos.x);
                element.width = Math.max(20, element.width + (element.x - newX));
                element.x = newX;
            }
            if (handle.includes('s')) {
                element.height = Math.max(20, snapToGrid(pos.y - element.y));
            }
            if (handle.includes('n')) {
                const newY = snapToGrid(pos.y);
                element.height = Math.max(20, element.height + (element.y - newY));
                element.y = newY;
            }
        }
    }
};

// Mouse up on canvas
const onCanvasMouseUp = (e) => {
    if (isDrawing.value && tool.value !== 'select') {
        const pos = getCanvasPosition(e);
        const width = Math.abs(pos.x - drawStart.value.x);
        const height = Math.abs(pos.y - drawStart.value.y);
        const x = Math.min(pos.x, drawStart.value.x);
        const y = Math.min(pos.y, drawStart.value.y);

        if (width > 10 || height > 10) {
            const newElement = createElement(tool.value, x, y, width, height);
            localElements.value.push(newElement);
            selectedElements.value = [newElement.id];
            saveHistory();
        } else {
            // Single click - create with default size
            const newElement = createElement(tool.value, drawStart.value.x, drawStart.value.y);
            localElements.value.push(newElement);
            selectedElements.value = [newElement.id];
            saveHistory();
        }
    }

    if (isResizing.value) {
        saveHistory();
    }

    isDragging.value = false;
    isDrawing.value = false;
    isResizing.value = false;
    resizeHandle.value = null;
};

// Select element
const selectElement = (e, element) => {
    e.stopPropagation();

    if (e.shiftKey) {
        // Multi-select
        const index = selectedElements.value.indexOf(element.id);
        if (index > -1) {
            selectedElements.value.splice(index, 1);
        } else {
            selectedElements.value.push(element.id);
        }
    } else {
        selectedElements.value = [element.id];
    }
};

// Start dragging element
const startElementDrag = (e, element) => {
    if (element.is_locked || tool.value !== 'select') return;

    e.stopPropagation();
    isDragging.value = true;

    const pos = getCanvasPosition(e);
    dragStart.value = {
        x: pos.x - element.x,
        y: pos.y - element.y,
        elements: selectedElements.value.map(id => {
            const el = localElements.value.find(e => e.id === id);
            return { id, x: el.x, y: el.y };
        }),
    };

    const onMove = (moveE) => {
        const movePos = getCanvasPosition(moveE);
        const dx = movePos.x - pos.x;
        const dy = movePos.y - pos.y;

        dragStart.value.elements.forEach(({ id, x, y }) => {
            const el = localElements.value.find(e => e.id === id);
            if (el && !el.is_locked) {
                el.x = snapToGrid(x + dx);
                el.y = snapToGrid(y + dy);
            }
        });
    };

    const onUp = () => {
        isDragging.value = false;
        saveHistory();
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
};

// Start resizing element
const startResize = (e, handle) => {
    e.stopPropagation();
    isResizing.value = true;
    resizeHandle.value = handle;
};

// Delete selected elements
const deleteSelected = () => {
    if (selectedElements.value.length === 0) return;

    localElements.value = localElements.value.filter(
        el => !selectedElements.value.includes(el.id)
    );
    selectedElements.value = [];
    saveHistory();
};

// Duplicate selected elements
const duplicateSelected = () => {
    if (selectedElements.value.length === 0) return;

    const newElements = [];
    selectedElements.value.forEach(id => {
        const original = localElements.value.find(el => el.id === id);
        if (original) {
            const copy = {
                ...original,
                id: generateId(),
                x: original.x + 20,
                y: original.y + 20,
            };
            newElements.push(copy);
        }
    });

    localElements.value.push(...newElements);
    selectedElements.value = newElements.map(el => el.id);
    saveHistory();
};

// Toggle lock
const toggleLock = () => {
    selectedElements.value.forEach(id => {
        const el = localElements.value.find(e => e.id === id);
        if (el) el.is_locked = !el.is_locked;
    });
};

// Link box to element
const linkBox = (boxId) => {
    if (selectedElements.value.length !== 1) return;

    const element = localElements.value.find(el => el.id === selectedElements.value[0]);
    if (element && element.element_type === 'box') {
        const box = props.boxes.find(b => b.id === boxId);
        if (box) {
            element.box_id = boxId;
            element.label = box.code;
        }
    }
    saveHistory();
};

// Add box from list
const addBoxFromList = (box) => {
    const newElement = createElement('box', 100, 100);
    newElement.box_id = box.id;
    newElement.label = box.code;
    newElement.width = Math.max(60, Math.min(150, box.size_m3 * 8));
    newElement.height = Math.max(50, Math.min(120, box.size_m3 * 6));
    localElements.value.push(newElement);
    selectedElements.value = [newElement.id];
    saveHistory();
};

// Auto generate plan
const autoGenerate = () => {
    if (confirm('Cela va remplacer le plan actuel. Continuer ?')) {
        router.post(route('tenant.plan.auto-generate', props.currentSite.id), {}, {
            onSuccess: () => {
                window.location.reload();
            },
        });
    }
};

// Save plan
const savePlan = () => {
    isSaving.value = true;

    router.post(route('tenant.plan.save-elements', props.currentSite.id), {
        elements: localElements.value,
    }, {
        onSuccess: () => {
            isSaving.value = false;
        },
        onError: () => {
            isSaving.value = false;
        },
    });
};

// Save configuration
const saveConfiguration = () => {
    router.post(route('tenant.plan.save-configuration', props.currentSite.id), config.value, {
        onSuccess: () => {
            showSettings.value = false;
        },
    });
};

// Zoom controls
const zoomIn = () => zoom.value = Math.min(zoom.value * 1.2, 3);
const zoomOut = () => zoom.value = Math.max(zoom.value / 1.2, 0.3);
const resetView = () => { zoom.value = 1; panX.value = 0; panY.value = 0; };

// Mouse wheel zoom
const onWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.3), 3);
};

// Keyboard shortcuts
const onKeyDown = (e) => {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    if (e.key === 'Delete' || e.key === 'Backspace') {
        deleteSelected();
    } else if (e.key === 'd' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        duplicateSelected();
    } else if (e.key === 'z' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        if (e.shiftKey) {
            redo();
        } else {
            undo();
        }
    } else if (e.key === 's' && (e.ctrlKey || e.metaKey)) {
        e.preventDefault();
        savePlan();
    } else if (e.key === 'v') {
        tool.value = 'select';
    } else if (e.key === 'b') {
        tool.value = 'box';
    } else if (e.key === 'w') {
        tool.value = 'wall';
    }
};

// Get element style
const getElementStyle = (element) => ({
    left: `${element.x}px`,
    top: `${element.y}px`,
    width: `${element.width}px`,
    height: `${element.height}px`,
    transform: `rotate(${element.rotation}deg)`,
    backgroundColor: element.fill_color || '#cccccc',
    borderColor: element.stroke_color || '#1e3a5f',
    borderWidth: `${element.stroke_width || 1}px`,
    opacity: element.opacity,
    zIndex: element.z_index,
});

// Transform style
const transformStyle = computed(() => ({
    transform: `translate(${panX.value}px, ${panY.value}px) scale(${zoom.value})`,
    transformOrigin: '0 0',
}));

// Selected element for editing
const selectedElement = computed(() => {
    if (selectedElements.value.length !== 1) return null;
    return localElements.value.find(el => el.id === selectedElements.value[0]);
});

// Is element selected
const isSelected = (element) => selectedElements.value.includes(element.id);

// Initialize history
onMounted(() => {
    saveHistory();
    window.addEventListener('keydown', onKeyDown);
    window.addEventListener('mouseup', onCanvasMouseUp);
    window.addEventListener('mousemove', onCanvasMouseMove);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    window.removeEventListener('mouseup', onCanvasMouseUp);
    window.removeEventListener('mousemove', onCanvasMouseMove);
});

// Change site
const changeSite = () => {
    router.get(route('tenant.plan.editor'), { site_id: selectedSite.value });
};
</script>

<template>
    <TenantLayout title="Éditeur de plan">
        <div class="editor-container">
            <!-- Toolbar -->
            <div class="editor-toolbar">
                <div class="toolbar-section">
                    <!-- Site selector -->
                    <select
                        v-model="selectedSite"
                        @change="changeSite"
                        class="toolbar-select"
                    >
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
                    </select>
                </div>

                <div class="toolbar-section">
                    <!-- Tools -->
                    <button
                        v-for="t in tools"
                        :key="t.id"
                        @click="tool = t.id"
                        :class="['toolbar-btn', { active: tool === t.id }]"
                        :title="`${t.label} (${t.shortcut})`"
                    >
                        <component :is="t.icon" class="w-5 h-5" />
                    </button>
                </div>

                <div class="toolbar-divider"></div>

                <div class="toolbar-section">
                    <!-- Actions -->
                    <button @click="undo" :disabled="historyIndex <= 0" class="toolbar-btn" title="Annuler (Ctrl+Z)">
                        <ArrowUturnLeftIcon class="w-5 h-5" />
                    </button>
                    <button @click="redo" :disabled="historyIndex >= history.length - 1" class="toolbar-btn" title="Rétablir (Ctrl+Shift+Z)">
                        <ArrowUturnRightIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="toolbar-divider"></div>

                <div class="toolbar-section">
                    <button @click="deleteSelected" :disabled="selectedElements.length === 0" class="toolbar-btn" title="Supprimer">
                        <TrashIcon class="w-5 h-5" />
                    </button>
                    <button @click="duplicateSelected" :disabled="selectedElements.length === 0" class="toolbar-btn" title="Dupliquer (Ctrl+D)">
                        <DocumentDuplicateIcon class="w-5 h-5" />
                    </button>
                    <button @click="toggleLock" :disabled="selectedElements.length === 0" class="toolbar-btn" title="Verrouiller/Déverrouiller">
                        <LockClosedIcon v-if="selectedElement?.is_locked" class="w-5 h-5" />
                        <LockOpenIcon v-else class="w-5 h-5" />
                    </button>
                </div>

                <div class="toolbar-divider"></div>

                <div class="toolbar-section">
                    <!-- Zoom -->
                    <button @click="zoomOut" class="toolbar-btn">
                        <MagnifyingGlassMinusIcon class="w-5 h-5" />
                    </button>
                    <span class="text-sm font-medium w-12 text-center">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="toolbar-btn">
                        <MagnifyingGlassPlusIcon class="w-5 h-5" />
                    </button>
                    <button @click="resetView" class="toolbar-btn">
                        <ArrowsPointingOutIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1"></div>

                <div class="toolbar-section">
                    <button @click="autoGenerate" class="toolbar-btn" title="Génération automatique">
                        <SparklesIcon class="w-5 h-5" />
                    </button>
                    <button @click="showBoxList = !showBoxList" class="toolbar-btn" title="Liste des boxes">
                        <CubeIcon class="w-5 h-5" />
                    </button>
                    <button @click="showSettings = true" class="toolbar-btn" title="Paramètres">
                        <Cog6ToothIcon class="w-5 h-5" />
                    </button>
                </div>

                <div class="toolbar-divider"></div>

                <div class="toolbar-section">
                    <Link :href="route('tenant.plan.index', { site_id: currentSite?.id })" class="toolbar-btn">
                        <EyeIcon class="w-5 h-5" />
                        <span class="ml-1">Aperçu</span>
                    </Link>
                    <button @click="savePlan" :disabled="isSaving" class="toolbar-btn-primary">
                        <CheckIcon class="w-5 h-5" />
                        <span class="ml-1">{{ isSaving ? 'Sauvegarde...' : 'Sauvegarder' }}</span>
                    </button>
                </div>
            </div>

            <div class="editor-content">
                <!-- Left panel - Properties -->
                <div class="editor-panel-left">
                    <div v-if="selectedElement" class="properties-panel">
                        <h3 class="panel-title">Propriétés</h3>

                        <div class="property-group">
                            <label class="property-label">Type</label>
                            <div class="property-value">{{ selectedElement.element_type }}</div>
                        </div>

                        <div class="property-group">
                            <label class="property-label">Position X</label>
                            <input type="number" v-model.number="selectedElement.x" class="property-input" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Position Y</label>
                            <input type="number" v-model.number="selectedElement.y" class="property-input" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Largeur</label>
                            <input type="number" v-model.number="selectedElement.width" class="property-input" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Hauteur</label>
                            <input type="number" v-model.number="selectedElement.height" class="property-input" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Rotation</label>
                            <input type="number" v-model.number="selectedElement.rotation" class="property-input" step="15" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Étiquette</label>
                            <input type="text" v-model="selectedElement.label" class="property-input" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Couleur fond</label>
                            <input type="color" v-model="selectedElement.fill_color" class="property-input-color" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Couleur bordure</label>
                            <input type="color" v-model="selectedElement.stroke_color" class="property-input-color" />
                        </div>

                        <div class="property-group">
                            <label class="property-label">Épaisseur bordure</label>
                            <input type="number" v-model.number="selectedElement.stroke_width" class="property-input" min="0" max="10" />
                        </div>

                        <!-- Box linking -->
                        <div v-if="selectedElement.element_type === 'box'" class="property-group">
                            <label class="property-label">Lier à un box</label>
                            <select v-model="selectedElement.box_id" @change="linkBox(selectedElement.box_id)" class="property-input">
                                <option :value="null">-- Aucun --</option>
                                <option v-for="box in boxes" :key="box.id" :value="box.id">
                                    {{ box.code }} ({{ box.size_m3 }}m³)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-else class="empty-panel">
                        <p class="text-gray-500 text-sm">Sélectionnez un élément pour voir ses propriétés</p>
                    </div>
                </div>

                <!-- Canvas -->
                <div
                    class="editor-canvas-container"
                    @mousedown="onCanvasMouseDown"
                    @wheel="onWheel"
                    ref="canvasRef"
                >
                    <div
                        class="editor-canvas canvas-background"
                        :style="{
                            width: `${config.canvas_width}px`,
                            height: `${config.canvas_height}px`,
                            ...transformStyle,
                            backgroundSize: config.show_grid ? `${config.grid_size}px ${config.grid_size}px` : 'none',
                        }"
                    >
                        <!-- Elements -->
                        <div
                            v-for="element in localElements"
                            :key="element.id"
                            :class="['editor-element', `element-${element.element_type}`, { selected: isSelected(element), locked: element.is_locked }]"
                            :style="getElementStyle(element)"
                            @mousedown="(e) => { selectElement(e, element); startElementDrag(e, element); }"
                        >
                            <div class="element-label">{{ element.label }}</div>

                            <!-- Resize handles (only for selected) -->
                            <template v-if="isSelected(element) && !element.is_locked">
                                <div class="resize-handle nw" @mousedown.stop="startResize($event, 'nw')"></div>
                                <div class="resize-handle n" @mousedown.stop="startResize($event, 'n')"></div>
                                <div class="resize-handle ne" @mousedown.stop="startResize($event, 'ne')"></div>
                                <div class="resize-handle e" @mousedown.stop="startResize($event, 'e')"></div>
                                <div class="resize-handle se" @mousedown.stop="startResize($event, 'se')"></div>
                                <div class="resize-handle s" @mousedown.stop="startResize($event, 's')"></div>
                                <div class="resize-handle sw" @mousedown.stop="startResize($event, 'sw')"></div>
                                <div class="resize-handle w" @mousedown.stop="startResize($event, 'w')"></div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right panel - Box list -->
                <div v-if="showBoxList" class="editor-panel-right">
                    <div class="panel-header">
                        <h3 class="panel-title">Boxes non placés</h3>
                        <button @click="showBoxList = false" class="text-gray-400 hover:text-gray-600">
                            <XMarkIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="box-list">
                        <div
                            v-for="box in unplacedBoxes"
                            :key="box.id"
                            class="box-list-item"
                            @click="addBoxFromList(box)"
                        >
                            <CubeIcon class="w-5 h-5 text-primary-600" />
                            <div>
                                <div class="font-medium">{{ box.code }}</div>
                                <div class="text-xs text-gray-500">{{ box.size_m3 }}m³ - {{ box.monthly_price }}€/mois</div>
                            </div>
                            <PlusIcon class="w-4 h-4 text-gray-400" />
                        </div>

                        <div v-if="unplacedBoxes.length === 0" class="text-center text-gray-500 py-4">
                            Tous les boxes sont placés
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings modal -->
            <div v-if="showSettings" class="modal-overlay" @click="showSettings = false">
                <div class="modal-content" @click.stop>
                    <div class="modal-header">
                        <h3 class="text-lg font-semibold">Paramètres du plan</h3>
                        <button @click="showSettings = false" class="text-gray-400 hover:text-gray-600">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="settings-group">
                            <h4 class="settings-title">Dimensions du canvas</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="settings-label">Largeur (px)</label>
                                    <input type="number" v-model.number="config.canvas_width" class="settings-input" />
                                </div>
                                <div>
                                    <label class="settings-label">Hauteur (px)</label>
                                    <input type="number" v-model.number="config.canvas_height" class="settings-input" />
                                </div>
                            </div>
                        </div>

                        <div class="settings-group">
                            <h4 class="settings-title">Grille</h4>
                            <div class="space-y-3">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="config.show_grid" class="rounded" />
                                    Afficher la grille
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" v-model="config.snap_to_grid" class="rounded" />
                                    Magnétisme à la grille
                                </label>
                                <div>
                                    <label class="settings-label">Taille de la grille (px)</label>
                                    <input type="number" v-model.number="config.grid_size" class="settings-input" />
                                </div>
                            </div>
                        </div>

                        <div class="settings-group">
                            <h4 class="settings-title">Couleurs par défaut</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="settings-label">Box disponible</label>
                                    <input type="color" v-model="config.default_box_available_color" class="settings-input-color" />
                                </div>
                                <div>
                                    <label class="settings-label">Box occupé</label>
                                    <input type="color" v-model="config.default_box_occupied_color" class="settings-input-color" />
                                </div>
                                <div>
                                    <label class="settings-label">Mur</label>
                                    <input type="color" v-model="config.default_wall_color" class="settings-input-color" />
                                </div>
                                <div>
                                    <label class="settings-label">Porte</label>
                                    <input type="color" v-model="config.default_door_color" class="settings-input-color" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="showSettings = false" class="btn-secondary">Annuler</button>
                        <button @click="saveConfiguration" class="btn-primary">Sauvegarder</button>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
.editor-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 4rem);
}

.editor-toolbar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.toolbar-section {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.toolbar-divider {
    width: 1px;
    height: 1.5rem;
    background: #d1d5db;
    margin: 0 0.5rem;
}

.toolbar-btn {
    padding: 0.5rem;
    border-radius: 0.5rem;
    color: #4b5563;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    border: none;
    background: transparent;
}

.toolbar-btn:hover {
    background: #f3f4f6;
    color: #111827;
}

.toolbar-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toolbar-btn.active {
    background: #dbeafe;
    color: #1d4ed8;
}

.toolbar-btn-primary {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #2563eb;
    color: white;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    border: none;
}

.toolbar-btn-primary:hover {
    background: #1d4ed8;
}

.toolbar-btn-primary:disabled {
    opacity: 0.5;
}

.toolbar-select {
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    font-size: 0.875rem;
    padding: 0.5rem;
}

.editor-content {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.editor-panel-left {
    width: 16rem;
    flex-shrink: 0;
    background: white;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
}

.editor-panel-right {
    width: 18rem;
    flex-shrink: 0;
    background: white;
    border-left: 1px solid #e5e7eb;
    overflow-y: auto;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
}

.panel-title {
    font-weight: 600;
    color: #1f2937;
}

.properties-panel {
    padding: 1rem;
}

.properties-panel > * + * {
    margin-top: 0.75rem;
}

.property-group {
    margin-bottom: 0.75rem;
}

.property-group > * + * {
    margin-top: 0.25rem;
}

.property-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
}

.property-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
    text-transform: capitalize;
}

.property-input {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    font-size: 0.875rem;
    padding: 0.5rem;
}

.property-input-color {
    width: 100%;
    height: 2rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
}

.empty-panel {
    padding: 1rem;
    text-align: center;
}

.box-list {
    padding: 0.5rem;
}

.box-list-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.15s;
}

.box-list-item:hover {
    background: #f9fafb;
}

.editor-canvas-container {
    flex: 1;
    overflow: hidden;
    background: #e5e7eb;
    cursor: crosshair;
    position: relative;
}

.editor-canvas {
    position: relative;
    background: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.05) 1px, transparent 1px);
}

.editor-element {
    position: absolute;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: move;
    border-style: solid;
}

.editor-element.selected {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.editor-element.locked {
    cursor: not-allowed;
    opacity: 0.75;
}

.element-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-align: center;
    line-height: 1.25;
    pointer-events: none;
    text-shadow: 0 1px 2px rgba(255,255,255,0.8);
}

/* Resize handles */
.resize-handle {
    position: absolute;
    width: 0.75rem;
    height: 0.75rem;
    background: white;
    border: 2px solid #3b82f6;
    border-radius: 2px;
}

.resize-handle.nw { top: -0.375rem; left: -0.375rem; cursor: nw-resize; }
.resize-handle.n { top: -0.375rem; left: 50%; transform: translateX(-50%); cursor: n-resize; }
.resize-handle.ne { top: -0.375rem; right: -0.375rem; cursor: ne-resize; }
.resize-handle.e { top: 50%; right: -0.375rem; transform: translateY(-50%); cursor: e-resize; }
.resize-handle.se { bottom: -0.375rem; right: -0.375rem; cursor: se-resize; }
.resize-handle.s { bottom: -0.375rem; left: 50%; transform: translateX(-50%); cursor: s-resize; }
.resize-handle.sw { bottom: -0.375rem; left: -0.375rem; cursor: sw-resize; }
.resize-handle.w { top: 50%; left: -0.375rem; transform: translateY(-50%); cursor: w-resize; }

/* Modal styles */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.modal-content {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    width: 100%;
    max-width: 32rem;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-body {
    padding: 1rem;
}

.modal-body > * + * {
    margin-top: 1.5rem;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
}

.settings-group {
    margin-bottom: 1rem;
}

.settings-group > * + * {
    margin-top: 0.75rem;
}

.settings-title {
    font-weight: 500;
    color: #1f2937;
}

.settings-label {
    display: block;
    font-size: 0.875rem;
    color: #4b5563;
    margin-bottom: 0.25rem;
}

.settings-input {
    width: 100%;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    padding: 0.5rem;
}

.settings-input-color {
    width: 100%;
    height: 2.5rem;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    cursor: pointer;
}

.btn-secondary {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    color: #374151;
    background: white;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-secondary:hover {
    background: #f9fafb;
}

.btn-primary {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: #2563eb;
    color: white;
    border: none;
    cursor: pointer;
    transition: background 0.15s;
}

.btn-primary:hover {
    background: #1d4ed8;
}
</style>
