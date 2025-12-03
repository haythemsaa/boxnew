<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    elements: Array,
    configuration: Object,
    boxes: Array,
    unplacedBoxes: Array,
});

// State
const selectedSite = ref(props.currentSite?.id);
const localElements = ref([...(props.elements || [])].map(el => ({ ...el, id: el.id || generateId() })));
const selectedElements = ref([]);
const tool = ref('select');
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const isDrawing = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const drawStart = ref({ x: 0, y: 0 });
const history = ref([]);
const historyIndex = ref(-1);
const isSaving = ref(false);

// UI
const showMenu = ref(false);
const showQuickCreate = ref(false);
const showAutoNumber = ref(false);
const showBoxList = ref(false);

// SVG viewBox dimensions
const svgWidth = 950;
const svgHeight = 580;

// Status colors - Exact Buxida colors
const statusColors = {
    available: '#4CAF50',
    occupied: '#2196F3',
    reserved: '#FF9800',
    ending: '#FFEB3B',
    litigation: '#9C27B0',
    maintenance: '#f44336',
    unavailable: '#9E9E9E',
};

// Quick create settings
const quickCreate = ref({
    columns: 4,
    rows: 10,
    boxWidth: 35,
    boxHeight: 30,
    gapX: 2,
    gapY: 2,
    startX: 100,
    startY: 100,
    prefix: 'A',
    startNumber: 1,
    volume: 6,
});

// Auto numbering
const autoNumber = ref({
    mode: 'column',
    prefix: '',
    start: 1,
    padding: 2,
});

// Refs
const svgRef = ref(null);

function generateId() {
    return 'el_' + Math.random().toString(36).substr(2, 9);
}

// Get SVG coordinates from mouse event
function getSvgPoint(e) {
    if (!svgRef.value) return { x: 0, y: 0 };
    const svg = svgRef.value;
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
    return { x: Math.round(svgP.x), y: Math.round(svgP.y) };
}

// Snap to grid
function snap(val, size = 5) {
    return Math.round(val / size) * size;
}

// Save history
function saveHistory() {
    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(JSON.stringify(localElements.value));
    historyIndex.value++;
    if (history.value.length > 50) {
        history.value.shift();
        historyIndex.value--;
    }
}

function undo() {
    if (historyIndex.value > 0) {
        historyIndex.value--;
        localElements.value = JSON.parse(history.value[historyIndex.value]);
        selectedElements.value = [];
    }
}

function redo() {
    if (historyIndex.value < history.value.length - 1) {
        historyIndex.value++;
        localElements.value = JSON.parse(history.value[historyIndex.value]);
        selectedElements.value = [];
    }
}

// Element defaults
const elementDefaults = {
    box: { w: 35, h: 30, fill: '#4CAF50', z: 100 },
    wall: { w: 100, h: 5, fill: '#1e3a5f', z: 50 },
    corridor: { w: 40, h: 150, fill: '#f5f5f5', z: 5 },
    door: { w: 30, h: 5, fill: '#ffffff', z: 55 },
    separator: { w: 80, h: 3, fill: '#94a3b8', z: 40 },
    zone: { w: 100, h: 80, fill: '#e3f2fd', z: 1 },
    label: { w: 50, h: 20, fill: 'transparent', z: 200 },
    lift: { w: 50, h: 40, fill: '#ffffff', z: 30 },
};

// Create element
function createElement(type, x, y, w, h) {
    const def = elementDefaults[type] || elementDefaults.box;
    return {
        id: generateId(),
        type: type,
        x: snap(x),
        y: snap(y),
        w: w || def.w,
        h: h || def.h,
        fill: def.fill,
        z: def.z,
        name: type === 'lift' ? 'LIFT' : '',
        vol: type === 'box' ? 6 : 0,
        status: 'available',
        locked: false,
        visible: true,
    };
}

// SVG click handler
function onSvgMouseDown(e) {
    if (e.target.closest('.element-group')) return;

    const pt = getSvgPoint(e);

    if (tool.value === 'select') {
        isDragging.value = true;
        dragStart.value = { x: e.clientX, y: e.clientY, px: panX.value, py: panY.value };
        selectedElements.value = [];
    } else {
        isDrawing.value = true;
        drawStart.value = pt;
    }
}

function onSvgMouseMove(e) {
    if (isDragging.value && tool.value === 'select') {
        const dx = e.clientX - dragStart.value.x;
        const dy = e.clientY - dragStart.value.y;
        panX.value = dragStart.value.px + dx;
        panY.value = dragStart.value.py + dy;
    }
}

function onSvgMouseUp(e) {
    if (isDrawing.value && tool.value !== 'select') {
        const pt = getSvgPoint(e);
        const x = Math.min(pt.x, drawStart.value.x);
        const y = Math.min(pt.y, drawStart.value.y);
        const w = Math.abs(pt.x - drawStart.value.x);
        const h = Math.abs(pt.y - drawStart.value.y);

        const el = createElement(tool.value, x, y, w > 10 ? w : null, h > 10 ? h : null);
        localElements.value.push(el);
        selectedElements.value = [el.id];
        saveHistory();
    }

    isDragging.value = false;
    isDrawing.value = false;
}

// Select element
function selectElement(e, el) {
    e.stopPropagation();
    if (e.shiftKey) {
        const idx = selectedElements.value.indexOf(el.id);
        if (idx > -1) selectedElements.value.splice(idx, 1);
        else selectedElements.value.push(el.id);
    } else {
        selectedElements.value = [el.id];
    }
}

// Drag element
function startDrag(e, el) {
    if (el.locked || tool.value !== 'select') return;
    e.stopPropagation();

    const startPt = getSvgPoint(e);
    const startPositions = selectedElements.value.map(id => {
        const elem = localElements.value.find(x => x.id === id);
        return { id, x: elem.x, y: elem.y };
    });

    const onMove = (moveE) => {
        const movePt = getSvgPoint(moveE);
        const dx = movePt.x - startPt.x;
        const dy = movePt.y - startPt.y;

        startPositions.forEach(({ id, x, y }) => {
            const elem = localElements.value.find(x => x.id === id);
            if (elem && !elem.locked) {
                elem.x = snap(x + dx);
                elem.y = snap(y + dy);
            }
        });
    };

    const onUp = () => {
        saveHistory();
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
}

// Delete selected
function deleteSelected() {
    if (selectedElements.value.length === 0) return;
    localElements.value = localElements.value.filter(el => !selectedElements.value.includes(el.id));
    selectedElements.value = [];
    saveHistory();
}

// Duplicate selected
function duplicateSelected() {
    if (selectedElements.value.length === 0) return;
    const newEls = [];
    selectedElements.value.forEach(id => {
        const orig = localElements.value.find(el => el.id === id);
        if (orig) {
            newEls.push({ ...orig, id: generateId(), x: orig.x + 10, y: orig.y + 10 });
        }
    });
    localElements.value.push(...newEls);
    selectedElements.value = newEls.map(el => el.id);
    saveHistory();
}

// Quick create boxes grid
function createBoxGrid() {
    const { columns, rows, boxWidth, boxHeight, gapX, gapY, startX, startY, prefix, startNumber, volume } = quickCreate.value;

    let num = startNumber;
    for (let col = 0; col < columns; col++) {
        const letter = String.fromCharCode(prefix.charCodeAt(0) + col);
        for (let row = 0; row < rows; row++) {
            const x = startX + col * (boxWidth + gapX);
            const y = startY + row * (boxHeight + gapY);
            const el = createElement('box', x, y, boxWidth, boxHeight);
            el.name = `${letter}${String(num).padStart(2, '0')}`;
            el.vol = volume;
            localElements.value.push(el);
            num++;
        }
    }
    saveHistory();
    showQuickCreate.value = false;
}

// Auto number boxes
function applyAutoNumber() {
    const { mode, prefix, start, padding } = autoNumber.value;

    const boxes = localElements.value.filter(el => el.type === 'box');
    boxes.sort((a, b) => {
        if (mode === 'column') return (a.x - b.x) || (a.y - b.y);
        if (mode === 'row') return (a.y - b.y) || (a.x - b.x);
        return 0;
    });

    boxes.forEach((box, i) => {
        const num = start + i;
        box.name = prefix + String(num).padStart(padding, '0');
    });

    saveHistory();
    showAutoNumber.value = false;
}

// Generate Buxida-style plan
function generateBuxidaPlan() {
    if (!confirm('Générer un plan style Buxida ? Cela va ajouter des éléments.')) return;

    const cols = ['M', 'K', 'J', 'I', 'H', 'G', 'F', 'E', 'D', 'C'];
    const boxW = 35, boxH = 30, gap = 2;
    let currentX = 60;

    cols.forEach((colName, colIdx) => {
        for (let i = 0; i < 12; i++) {
            const y = 95 + i * (boxH + gap);
            const box = createElement('box', currentX, y, boxW, boxH);
            box.name = `${colName}${String(i + 1).padStart(2, '0')}`;
            box.vol = 6;
            box.status = Math.random() > 0.3 ? 'occupied' : 'available';
            box.fill = statusColors[box.status];
            localElements.value.push(box);
        }

        currentX += boxW + gap;

        if ((colIdx + 1) % 4 === 0 && colIdx < cols.length - 1) {
            const corridor = createElement('corridor', currentX, 95, 30, 12 * (boxH + gap) - gap);
            localElements.value.push(corridor);
            currentX += 35;
        }
    });

    const lift = createElement('lift', 105, 460, 55, 40);
    lift.name = 'LIFT';
    lift.status = 'unavailable';
    localElements.value.push(lift);

    saveHistory();
}

// Add box from list
function addBoxFromList(box) {
    const el = createElement('box', 100 + Math.random() * 50, 100 + Math.random() * 50);
    el.boxId = box.id;
    el.name = box.number || box.name;
    el.vol = box.volume || 6;
    localElements.value.push(el);
    selectedElements.value = [el.id];
    saveHistory();
}

// Zoom
function zoomIn() { zoom.value = Math.min(zoom.value * 1.2, 3); }
function zoomOut() { zoom.value = Math.max(zoom.value / 1.2, 0.3); }
function resetView() { zoom.value = 1; panX.value = 0; panY.value = 0; }

function onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.3), 3);
}

// Save
function savePlan() {
    isSaving.value = true;
    router.post(route('tenant.plan.save-elements', props.currentSite.id), {
        elements: localElements.value,
    }, {
        onSuccess: () => { isSaving.value = false; },
        onError: () => { isSaving.value = false; },
    });
}

// Export SVG
function exportSVG() {
    const svg = svgRef.value.outerHTML;
    const blob = new Blob([svg], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `plan-${props.currentSite?.name || 'site'}.svg`;
    a.click();
    URL.revokeObjectURL(url);
}

// Keyboard shortcuts
function onKeyDown(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
    else if (e.key === 'd' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); duplicateSelected(); }
    else if (e.key === 'z' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); e.shiftKey ? redo() : undo(); }
    else if (e.key === 's' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); savePlan(); }
    else if (e.key === 'Escape') { selectedElements.value = []; tool.value = 'select'; }
    else if (e.key === 'v') tool.value = 'select';
    else if (e.key === 'b') tool.value = 'box';
    else if (e.key === 'w') tool.value = 'wall';
    else if (e.key === 'c') tool.value = 'corridor';
}

// Computed
const sortedElements = computed(() => [...localElements.value].sort((a, b) => (a.z || 0) - (b.z || 0)));
const selectedElement = computed(() => selectedElements.value.length === 1 ? localElements.value.find(el => el.id === selectedElements.value[0]) : null);
const isSelected = (el) => selectedElements.value.includes(el.id);

const stats = computed(() => {
    const boxes = localElements.value.filter(el => el.type === 'box');
    return {
        total: boxes.length,
        available: boxes.filter(b => b.status === 'available' || b.fill === '#4CAF50').length,
        occupied: boxes.filter(b => b.status === 'occupied' || b.fill === '#2196F3').length,
    };
});

const tools = [
    { id: 'select', label: 'Sélection', key: 'V' },
    { id: 'box', label: 'Box', key: 'B' },
    { id: 'wall', label: 'Mur', key: 'W' },
    { id: 'corridor', label: 'Couloir', key: 'C' },
    { id: 'door', label: 'Porte', key: 'D' },
    { id: 'separator', label: 'Séparateur', key: 'S' },
    { id: 'zone', label: 'Zone', key: 'Z' },
    { id: 'label', label: 'Texte', key: 'T' },
    { id: 'lift', label: 'Ascenseur', key: 'L' },
];

function changeSite() {
    router.get(route('tenant.plan.editor'), { site_id: selectedSite.value });
}

function getTextColor(el) {
    return el.status === 'ending' || el.fill === '#FFEB3B' ? '#333' : '#fff';
}

function getElementFill(el) {
    if (el.type === 'box') {
        return el.fill || statusColors[el.status] || statusColors.available;
    }
    return el.fill || '#ccc';
}

onMounted(() => {
    saveHistory();
    window.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <div class="editor-fullscreen">
        <!-- Sidebar Menu -->
        <div class="sidebar" :class="{ open: showMenu }">
            <div class="sidebar-header">
                <span class="logo">BoxiBox</span>
                <button @click="showMenu = false" class="close-menu">&times;</button>
            </div>
            <nav class="sidebar-nav">
                <Link :href="route('tenant.dashboard')" class="nav-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </Link>
                <Link :href="route('tenant.boxes.index')" class="nav-item">
                    <i class="fas fa-box"></i><span>Boxes</span>
                </Link>
                <Link :href="route('tenant.contracts.index')" class="nav-item">
                    <i class="fas fa-file-contract"></i><span>Contrats</span>
                </Link>
                <Link :href="route('tenant.customers.index')" class="nav-item">
                    <i class="fas fa-users"></i><span>Clients</span>
                </Link>
                <Link :href="route('tenant.plan.interactive')" class="nav-item active">
                    <i class="fas fa-map"></i><span>Plan Interactif</span>
                </Link>
            </nav>
        </div>
        <div v-if="showMenu" class="overlay" @click="showMenu = false"></div>

        <!-- Header -->
        <div class="editor-header">
            <div class="header-left">
                <button @click="showMenu = true" class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Éditeur de Plan</h1>
                <select v-model="selectedSite" @change="changeSite" class="site-select">
                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                </select>
            </div>

            <div class="header-center">
                <div class="tools-bar">
                    <button
                        v-for="t in tools"
                        :key="t.id"
                        @click="tool = t.id"
                        :class="['tool-btn', { active: tool === t.id }]"
                        :title="`${t.label} (${t.key})`"
                    >
                        {{ t.label }}
                    </button>
                </div>
            </div>

            <div class="header-right">
                <div class="stats">
                    <span class="stat"><span class="dot available"></span>{{ stats.available }} Libre</span>
                    <span class="stat"><span class="dot occupied"></span>{{ stats.occupied }} Occupé</span>
                    <span class="stat-total">Total: {{ stats.total }}</span>
                </div>

                <div class="actions">
                    <button @click="undo" :disabled="historyIndex <= 0" class="action-btn" title="Annuler">↩</button>
                    <button @click="redo" :disabled="historyIndex >= history.length - 1" class="action-btn" title="Rétablir">↪</button>
                    <button @click="deleteSelected" :disabled="selectedElements.length === 0" class="action-btn" title="Supprimer">🗑</button>
                    <button @click="duplicateSelected" :disabled="selectedElements.length === 0" class="action-btn" title="Dupliquer">📋</button>
                </div>

                <div class="zoom-controls">
                    <button @click="zoomOut" class="zoom-btn">−</button>
                    <span class="zoom-level">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="zoom-btn">+</button>
                    <button @click="resetView" class="zoom-btn" title="Reset">⟲</button>
                </div>

                <button @click="showQuickCreate = true" class="header-btn">Création Rapide</button>
                <button @click="showAutoNumber = true" class="header-btn">Numéroter</button>
                <button @click="generateBuxidaPlan" class="header-btn">Générer Plan</button>
                <button @click="showBoxList = !showBoxList" :class="['header-btn', { active: showBoxList }]">Boxes</button>
                <button @click="exportSVG" class="header-btn">Export</button>
                <Link :href="route('tenant.plan.interactive')" class="header-btn">Aperçu</Link>
                <button @click="savePlan" :disabled="isSaving" class="save-btn">
                    {{ isSaving ? 'Sauvegarde...' : '💾 Sauvegarder' }}
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend-bar">
            <div class="legend-status">
                <div class="status-item"><div class="status-box libre"></div><span>Libre</span></div>
                <div class="status-item"><div class="status-box occupe"></div><span>Occupé</span></div>
                <div class="status-item"><div class="status-box reserve"></div><span>Réservé</span></div>
                <div class="status-item"><div class="status-box dedite"></div><span>Fin contrat</span></div>
                <div class="status-item"><div class="status-box litige"></div><span>Litige</span></div>
                <div class="status-item"><div class="status-box maintenance"></div><span>Maintenance</span></div>
            </div>
        </div>

        <!-- Main content -->
        <div class="editor-body">
            <!-- Properties panel -->
            <aside v-if="selectedElement" class="properties-panel">
                <h3>Propriétés</h3>
                <div class="prop-group">
                    <label>Type</label>
                    <div class="prop-value">{{ selectedElement.type }}</div>
                </div>
                <div class="prop-row">
                    <div class="prop-group">
                        <label>X</label>
                        <input type="number" v-model.number="selectedElement.x" />
                    </div>
                    <div class="prop-group">
                        <label>Y</label>
                        <input type="number" v-model.number="selectedElement.y" />
                    </div>
                </div>
                <div class="prop-row">
                    <div class="prop-group">
                        <label>Largeur</label>
                        <input type="number" v-model.number="selectedElement.w" />
                    </div>
                    <div class="prop-group">
                        <label>Hauteur</label>
                        <input type="number" v-model.number="selectedElement.h" />
                    </div>
                </div>
                <div class="prop-group" v-if="selectedElement.type === 'box'">
                    <label>Nom</label>
                    <input type="text" v-model="selectedElement.name" />
                </div>
                <div class="prop-group" v-if="selectedElement.type === 'box'">
                    <label>Volume (m³)</label>
                    <input type="number" v-model.number="selectedElement.vol" />
                </div>
                <div class="prop-group" v-if="selectedElement.type === 'box'">
                    <label>Statut</label>
                    <select v-model="selectedElement.status" @change="selectedElement.fill = statusColors[selectedElement.status]">
                        <option value="available">Libre</option>
                        <option value="occupied">Occupé</option>
                        <option value="reserved">Réservé</option>
                        <option value="ending">Fin contrat</option>
                        <option value="litigation">Litige</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="prop-group">
                    <label>Couleur</label>
                    <input type="color" v-model="selectedElement.fill" />
                </div>
            </aside>

            <!-- SVG Canvas -->
            <div class="canvas-wrapper" @wheel="onWheel">
                <svg
                    ref="svgRef"
                    :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
                    preserveAspectRatio="xMidYMid meet"
                    class="plan-svg"
                    :style="{ transform: `translate(${panX}px, ${panY}px) scale(${zoom})` }"
                    @mousedown="onSvgMouseDown"
                    @mousemove="onSvgMouseMove"
                    @mouseup="onSvgMouseUp"
                >
                    <!-- Background -->
                    <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="#fff"/>

                    <!-- Grid -->
                    <defs>
                        <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                            <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#e0e0e0" stroke-width="0.5"/>
                        </pattern>
                    </defs>
                    <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="url(#grid)"/>

                    <!-- Border -->
                    <rect x="50" y="85" width="870" height="480" fill="none" stroke="#333" stroke-width="2" rx="3"/>

                    <!-- Elements -->
                    <g v-for="el in sortedElements" :key="el.id"
                       class="element-group"
                       :class="{ selected: isSelected(el), locked: el.locked }"
                       @mousedown="(e) => { selectElement(e, el); startDrag(e, el); }">

                        <!-- Box element -->
                        <template v-if="el.type === 'box'">
                            <rect
                                :x="el.x"
                                :y="el.y"
                                :width="el.w"
                                :height="el.h"
                                :fill="getElementFill(el)"
                                stroke="#333"
                                stroke-width="1"
                                rx="1"
                                class="element-rect"
                            />
                            <text
                                :x="el.x + el.w/2"
                                :y="el.y + el.h/2 - (el.h > 25 && el.vol ? 4 : 0)"
                                :fill="getTextColor(el)"
                                class="box-name"
                            >
                                {{ el.name }}
                            </text>
                            <text
                                v-if="el.vol && el.h > 25"
                                :x="el.x + el.w/2"
                                :y="el.y + el.h/2 + 8"
                                :fill="getTextColor(el)"
                                class="box-vol"
                            >
                                {{ el.vol }}m³
                            </text>
                        </template>

                        <!-- Wall element -->
                        <template v-else-if="el.type === 'wall'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#1e3a5f'" stroke="#000" stroke-width="1"/>
                        </template>

                        <!-- Corridor element -->
                        <template v-else-if="el.type === 'corridor'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#f5f5f5'" stroke="#ddd" stroke-width="1"/>
                        </template>

                        <!-- Door element -->
                        <template v-else-if="el.type === 'door'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" fill="#fff" stroke="#666" stroke-width="1"/>
                        </template>

                        <!-- Lift element -->
                        <template v-else-if="el.type === 'lift'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" fill="#fff" stroke="#333" stroke-width="1" rx="1"/>
                            <text :x="el.x + el.w/2" :y="el.y + el.h/2" fill="#333" class="box-name">{{ el.name || 'LIFT' }}</text>
                        </template>

                        <!-- Zone element -->
                        <template v-else-if="el.type === 'zone'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#e3f2fd'" stroke="#90caf9" stroke-width="1" stroke-dasharray="5,5" rx="5"/>
                        </template>

                        <!-- Label element -->
                        <template v-else-if="el.type === 'label'">
                            <text :x="el.x" :y="el.y + 15" fill="#333" font-size="14" font-weight="bold">{{ el.name }}</text>
                        </template>

                        <!-- Separator element -->
                        <template v-else-if="el.type === 'separator'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#94a3b8'"/>
                        </template>

                        <!-- Generic element -->
                        <template v-else>
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#ccc'" stroke="#999" stroke-width="1"/>
                        </template>

                        <!-- Selection indicator -->
                        <rect v-if="isSelected(el)" :x="el.x - 2" :y="el.y - 2" :width="el.w + 4" :height="el.h + 4" fill="none" stroke="#3b82f6" stroke-width="2" stroke-dasharray="4,2"/>
                    </g>
                </svg>
            </div>

            <!-- Box list panel -->
            <aside v-if="showBoxList" class="box-list-panel">
                <div class="panel-header">
                    <h3>Boxes disponibles</h3>
                    <button @click="showBoxList = false">✕</button>
                </div>
                <div class="box-list">
                    <div v-for="box in unplacedBoxes" :key="box.id" class="box-item" @click="addBoxFromList(box)">
                        <span class="box-name-list">{{ box.number || box.name }}</span>
                        <span class="box-info">{{ box.volume }}m³</span>
                    </div>
                    <div v-if="!unplacedBoxes?.length" class="empty-msg">Tous les boxes sont placés</div>
                </div>
            </aside>
        </div>

        <!-- Quick Create Modal -->
        <div v-if="showQuickCreate" class="modal-overlay" @click.self="showQuickCreate = false">
            <div class="modal">
                <div class="modal-header">
                    <h2>Création rapide de boxes</h2>
                    <button @click="showQuickCreate = false">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group"><label>Colonnes</label><input type="number" v-model.number="quickCreate.columns" min="1" max="20" /></div>
                        <div class="form-group"><label>Rangées</label><input type="number" v-model.number="quickCreate.rows" min="1" max="30" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Largeur box</label><input type="number" v-model.number="quickCreate.boxWidth" min="20" /></div>
                        <div class="form-group"><label>Hauteur box</label><input type="number" v-model.number="quickCreate.boxHeight" min="15" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Espace X</label><input type="number" v-model.number="quickCreate.gapX" min="0" /></div>
                        <div class="form-group"><label>Espace Y</label><input type="number" v-model.number="quickCreate.gapY" min="0" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Position X</label><input type="number" v-model.number="quickCreate.startX" /></div>
                        <div class="form-group"><label>Position Y</label><input type="number" v-model.number="quickCreate.startY" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Préfixe</label><input type="text" v-model="quickCreate.prefix" maxlength="1" /></div>
                        <div class="form-group"><label>N° départ</label><input type="number" v-model.number="quickCreate.startNumber" min="1" /></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Volume (m³)</label><input type="number" v-model.number="quickCreate.volume" min="1" /></div>
                    </div>
                    <p class="preview-text">{{ quickCreate.columns * quickCreate.rows }} boxes seront créés</p>
                </div>
                <div class="modal-footer">
                    <button @click="showQuickCreate = false" class="btn-cancel">Annuler</button>
                    <button @click="createBoxGrid" class="btn-primary">Créer</button>
                </div>
            </div>
        </div>

        <!-- Auto Number Modal -->
        <div v-if="showAutoNumber" class="modal-overlay" @click.self="showAutoNumber = false">
            <div class="modal">
                <div class="modal-header">
                    <h2>Numérotation automatique</h2>
                    <button @click="showAutoNumber = false">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Mode</label>
                        <select v-model="autoNumber.mode">
                            <option value="column">Par colonne</option>
                            <option value="row">Par rangée</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Préfixe</label><input type="text" v-model="autoNumber.prefix" placeholder="Ex: BOX-" /></div>
                    <div class="form-row">
                        <div class="form-group"><label>N° départ</label><input type="number" v-model.number="autoNumber.start" min="1" /></div>
                        <div class="form-group"><label>Zéros</label><input type="number" v-model.number="autoNumber.padding" min="1" max="5" /></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="showAutoNumber = false" class="btn-cancel">Annuler</button>
                    <button @click="applyAutoNumber" class="btn-primary">Appliquer</button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

* { box-sizing: border-box; }

.editor-fullscreen {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    background: #f5f5f5;
    font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, sans-serif;
    z-index: 9999;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0; left: -280px;
    width: 280px; height: 100%;
    background: #2c3e50;
    z-index: 10001;
    transition: left 0.3s ease;
    box-shadow: 2px 0 10px rgba(0,0,0,0.3);
}
.sidebar.open { left: 0; }
.sidebar-header {
    padding: 20px;
    background: #1a252f;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.sidebar-header .logo { color: #3498db; font-size: 24px; font-weight: 700; }
.close-menu { background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; }
.sidebar-nav { padding: 20px 0; }
.nav-item {
    display: flex; align-items: center; gap: 15px;
    padding: 15px 25px; color: #ecf0f1;
    text-decoration: none; transition: background 0.2s;
}
.nav-item:hover, .nav-item.active { background: #34495e; }
.nav-item i { width: 20px; text-align: center; }
.overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.5); z-index: 10000;
}

/* Header */
.editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    gap: 16px;
    flex-shrink: 0;
}
.header-left, .header-center, .header-right {
    display: flex;
    align-items: center;
    gap: 12px;
}
.menu-btn {
    background: rgba(255,255,255,0.1);
    border: none; color: white;
    padding: 8px 12px; border-radius: 6px;
    cursor: pointer; font-size: 16px;
}
.menu-btn:hover { background: rgba(255,255,255,0.2); }
.editor-header h1 { font-size: 18px; font-weight: 600; margin: 0; }
.site-select {
    padding: 6px 12px; border-radius: 6px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white; font-size: 14px;
}
.site-select option { color: #333; }

/* Tools */
.tools-bar {
    display: flex; gap: 4px;
    background: rgba(0,0,0,0.2);
    padding: 4px; border-radius: 8px;
}
.tool-btn {
    padding: 6px 10px; border: none;
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-size: 11px; border-radius: 4px;
    cursor: pointer; transition: all 0.15s;
}
.tool-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.tool-btn.active { background: #4CAF50; color: white; }

/* Stats */
.stats { display: flex; gap: 12px; font-size: 12px; }
.stat { display: flex; align-items: center; gap: 4px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot.available { background: #4CAF50; }
.dot.occupied { background: #2196F3; }
.stat-total { color: rgba(255,255,255,0.7); }

/* Actions */
.actions { display: flex; gap: 4px; }
.action-btn {
    padding: 6px 10px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 4px;
    cursor: pointer; font-size: 14px;
}
.action-btn:hover:not(:disabled) { background: rgba(255,255,255,0.2); }
.action-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* Zoom */
.zoom-controls {
    display: flex; align-items: center; gap: 4px;
    background: rgba(0,0,0,0.2);
    padding: 4px 8px; border-radius: 6px;
}
.zoom-btn {
    width: 26px; height: 26px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 4px;
    cursor: pointer; font-size: 14px;
}
.zoom-btn:hover { background: rgba(255,255,255,0.2); }
.zoom-level { width: 45px; text-align: center; font-size: 11px; }

/* Header buttons */
.header-btn {
    padding: 6px 10px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 6px;
    cursor: pointer; font-size: 12px;
    text-decoration: none;
    transition: all 0.15s;
}
.header-btn:hover, .header-btn.active { background: rgba(255,255,255,0.2); }
.save-btn {
    padding: 6px 14px; border: none;
    background: #4CAF50; color: white;
    border-radius: 6px; cursor: pointer;
    font-size: 13px; font-weight: 500;
}
.save-btn:hover:not(:disabled) { background: #43A047; }
.save-btn:disabled { opacity: 0.7; }

/* Legend */
.legend-bar {
    display: flex;
    justify-content: center;
    padding: 8px 20px;
    background: #fff;
    border-bottom: 1px solid #ddd;
}
.legend-status {
    display: flex; gap: 20px; align-items: center;
}
.status-item { display: flex; align-items: center; gap: 6px; font-size: 12px; }
.status-box {
    width: 20px; height: 14px;
    border-radius: 2px; border: 1px solid #333;
}
.status-box.libre { background: #4CAF50; }
.status-box.occupe { background: #2196F3; }
.status-box.reserve { background: #FF9800; }
.status-box.dedite { background: #FFEB3B; }
.status-box.litige { background: #9C27B0; }
.status-box.maintenance { background: #f44336; }

/* Body */
.editor-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    position: relative;
}

/* Properties panel */
.properties-panel {
    width: 200px;
    background: #fff;
    border-right: 1px solid #ddd;
    padding: 16px;
    overflow-y: auto;
    flex-shrink: 0;
}
.properties-panel h3 { margin: 0 0 16px; font-size: 14px; color: #1e3a5f; }
.prop-group { margin-bottom: 12px; }
.prop-group label {
    display: block; font-size: 11px;
    color: #666; margin-bottom: 4px;
    text-transform: uppercase;
}
.prop-group input, .prop-group select {
    width: 100%; padding: 6px 8px;
    border: 1px solid #ddd; border-radius: 4px;
    font-size: 13px;
}
.prop-group input[type="color"] { height: 32px; padding: 2px; }
.prop-value { font-size: 13px; font-weight: 500; color: #333; text-transform: capitalize; }
.prop-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }

/* Canvas */
.canvas-wrapper {
    flex: 1;
    overflow: hidden;
    background: #e0e0e0;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: crosshair;
}
.plan-svg {
    width: 100%;
    max-width: 1200px;
    height: auto;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    transform-origin: center center;
}

/* SVG Elements */
.element-group { cursor: move; }
.element-group:hover .element-rect { filter: brightness(1.1); }
.element-group.selected .element-rect { filter: brightness(1.15); }
.element-group.locked { cursor: not-allowed; opacity: 0.7; }

.box-name {
    font-size: 8px;
    font-weight: bold;
    text-anchor: middle;
    dominant-baseline: middle;
    pointer-events: none;
}
.box-vol {
    font-size: 6px;
    text-anchor: middle;
    dominant-baseline: middle;
    pointer-events: none;
}

/* Box list panel */
.box-list-panel {
    width: 220px;
    background: #fff;
    border-left: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}
.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #ddd;
    background: #f8f8f8;
}
.panel-header h3 { margin: 0; font-size: 13px; color: #333; }
.panel-header button { background: none; border: none; font-size: 18px; cursor: pointer; color: #666; }
.box-list { flex: 1; overflow-y: auto; padding: 8px; }
.box-item {
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.15s;
    margin-bottom: 4px;
    border: 1px solid #eee;
}
.box-item:hover { background: #f0f0f0; }
.box-name-list { display: block; font-weight: 600; color: #333; font-size: 13px; }
.box-info { font-size: 11px; color: #666; }
.empty-msg { padding: 20px; text-align: center; color: #999; font-size: 12px; }

/* Modals */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex; align-items: center; justify-content: center;
    z-index: 10002;
}
.modal {
    background: #fff;
    border-radius: 12px;
    width: 100%; max-width: 420px;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
}
.modal-header h2 { margin: 0; font-size: 18px; color: #333; }
.modal-header button { background: none; border: none; font-size: 20px; cursor: pointer; color: #666; }
.modal-body { padding: 20px; overflow-y: auto; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid #eee;
}
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
.form-group input, .form-group select {
    width: 100%; padding: 8px 12px;
    border: 1px solid #ddd; border-radius: 6px;
    font-size: 14px;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.preview-text {
    margin-top: 16px; padding: 12px;
    background: #f5f5f5; border-radius: 6px;
    font-size: 14px; color: #555; text-align: center;
}
.btn-cancel {
    padding: 10px 20px;
    border: 1px solid #ddd; background: #fff;
    border-radius: 6px; cursor: pointer; font-size: 14px;
}
.btn-cancel:hover { background: #f8f8f8; }
.btn-primary {
    padding: 10px 20px;
    border: none; background: #2563eb;
    color: #fff; border-radius: 6px;
    cursor: pointer; font-size: 14px; font-weight: 500;
}
.btn-primary:hover { background: #1d4ed8; }
</style>
