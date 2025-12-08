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
const zoom = ref(0.85); // Zoom initial réduit pour voir tout
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
const showBoxPalette = ref(true); // Palette de boxes visible par défaut

// Palette de boxes prédéfinis par volume
const boxTemplates = [
    { name: '1m³', vol: 1, w: 20, h: 20, color: '#4CAF50' },
    { name: '2m³', vol: 2, w: 25, h: 22, color: '#4CAF50' },
    { name: '3m³', vol: 3, w: 28, h: 25, color: '#4CAF50' },
    { name: '4m³', vol: 4, w: 30, h: 28, color: '#4CAF50' },
    { name: '5m³', vol: 5, w: 32, h: 30, color: '#4CAF50' },
    { name: '6m³', vol: 6, w: 35, h: 30, color: '#4CAF50' },
    { name: '9m³', vol: 9, w: 38, h: 32, color: '#4CAF50' },
    { name: '12m³', vol: 12, w: 42, h: 35, color: '#4CAF50' },
    { name: '15m³', vol: 15, w: 45, h: 38, color: '#4CAF50' },
    { name: '18m³', vol: 18, w: 48, h: 40, color: '#4CAF50' },
    { name: '25m³', vol: 25, w: 55, h: 45, color: '#4CAF50' },
    { name: '30m³', vol: 30, w: 60, h: 48, color: '#4CAF50' },
    { name: '50m³', vol: 50, w: 70, h: 55, color: '#4CAF50' },
    { name: '75m³', vol: 75, w: 80, h: 65, color: '#4CAF50' },
    { name: '100m³', vol: 100, w: 90, h: 70, color: '#4CAF50' },
];

// Template sélectionné pour placement
const selectedTemplate = ref(null);

// SVG viewBox dimensions - Plus grand pour voir tout
const svgWidth = 1400;
const svgHeight = 800;

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

// Sélectionner un template de box
function selectTemplate(template) {
    selectedTemplate.value = template;
    tool.value = 'template'; // Mode template
}

// SVG click handler
function onSvgMouseDown(e) {
    if (e.target.closest('.element-group')) return;

    const pt = getSvgPoint(e);

    if (tool.value === 'select') {
        isDragging.value = true;
        dragStart.value = { x: e.clientX, y: e.clientY, px: panX.value, py: panY.value };
        selectedElements.value = [];
    } else if (tool.value === 'template' && selectedTemplate.value) {
        // Placer le template sélectionné directement au clic
        placeTemplateAt(pt.x, pt.y);
    } else {
        // Mode dessin - créer un élément directement au clic
        isDrawing.value = true;
        drawStart.value = pt;
    }
}

// Placer un template de box à une position
function placeTemplateAt(x, y) {
    const t = selectedTemplate.value;
    if (!t) return;

    const el = {
        id: generateId(),
        type: 'box',
        x: snap(x - t.w / 2), // Centrer sur le clic
        y: snap(y - t.h / 2),
        w: t.w,
        h: t.h,
        fill: t.color,
        z: 100,
        name: '', // L'utilisateur peut le renommer
        vol: t.vol,
        status: 'available',
        locked: false,
        visible: true,
    };

    localElements.value.push(el);
    selectedElements.value = [el.id];
    saveHistory();
}

// Créer un élément au clic (pas en drag)
function createElementAtClick(x, y) {
    const el = createElement(tool.value, x, y);
    localElements.value.push(el);
    selectedElements.value = [el.id];
    saveHistory();
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

        // Si l'utilisateur a juste cliqué (pas de drag), utiliser les dimensions par défaut
        const useDefaultSize = w < 10 && h < 10;
        const el = createElement(tool.value, useDefaultSize ? drawStart.value.x : x, useDefaultSize ? drawStart.value.y : y, useDefaultSize ? null : w, useDefaultSize ? null : h);
        localElements.value.push(el);
        selectedElements.value = [el.id];
        saveHistory();
        console.log('Élément créé:', el.type, 'à', el.x, el.y, 'taille', el.w, 'x', el.h);
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
function zoomIn() { zoom.value = Math.min(zoom.value * 1.15, 3); }
function zoomOut() { zoom.value = Math.max(zoom.value / 1.15, 0.2); }
function resetView() { zoom.value = 0.85; panX.value = 0; panY.value = 0; }
function fitToScreen() {
    // Calcul du zoom pour que le canvas rentre dans l'écran
    const wrapper = document.querySelector('.canvas-wrapper');
    if (wrapper) {
        const wrapperWidth = wrapper.clientWidth - 20;
        const wrapperHeight = wrapper.clientHeight - 20;
        const scaleX = wrapperWidth / svgWidth;
        const scaleY = wrapperHeight / svgHeight;
        zoom.value = Math.min(scaleX, scaleY, 1);
        panX.value = 0;
        panY.value = 0;
    }
}

function onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.92 : 1.08;
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.2), 3);
}

// Save
function savePlan() {
    if (!props.currentSite?.id) {
        alert('Erreur: Aucun site sélectionné. Veuillez sélectionner un site.');
        return;
    }
    isSaving.value = true;
    router.post(route('tenant.plan.save-elements', props.currentSite.id), {
        elements: localElements.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            isSaving.value = false;
            alert('Plan sauvegardé avec succès!');
        },
        onError: (errors) => {
            isSaving.value = false;
            console.error('Erreurs de sauvegarde:', errors);
            alert('Erreur lors de la sauvegarde du plan.');
        },
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
    else if (e.key === 'f') fitToScreen();
    else if (e.key === 'r') resetView();
    else if (e.key === '+' || e.key === '=') zoomIn();
    else if (e.key === '-') zoomOut();
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
    // Ajuster automatiquement au chargement
    setTimeout(() => fitToScreen(), 100);
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
                    <button @click="zoomOut" class="zoom-btn" title="Zoom - (-)">−</button>
                    <span class="zoom-level">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="zoom-btn" title="Zoom + (+)">+</button>
                    <button @click="fitToScreen" class="zoom-btn" title="Ajuster (F)">⊡</button>
                    <button @click="resetView" class="zoom-btn" title="Reset (R)">⟲</button>
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
            <!-- Box Palette - Left sidebar -->
            <aside class="box-palette">
                <div class="palette-header">
                    <h3>📦 Boxes</h3>
                    <span class="palette-hint">Cliquez pour sélectionner, puis cliquez sur le plan</span>
                </div>
                <div class="palette-grid">
                    <div
                        v-for="t in boxTemplates"
                        :key="t.vol"
                        :class="['palette-item', { selected: selectedTemplate?.vol === t.vol }]"
                        @click="selectTemplate(t)"
                        :title="`Box ${t.vol}m³ - Cliquez puis placez sur le plan`"
                    >
                        <div class="palette-box" :style="{ width: Math.min(t.w, 50) + 'px', height: Math.min(t.h, 40) + 'px', background: t.color }">
                            {{ t.vol }}
                        </div>
                        <span class="palette-label">{{ t.name }}</span>
                    </div>
                </div>
                <div class="palette-actions">
                    <button @click="selectedTemplate = null; tool = 'select'" class="palette-btn" v-if="selectedTemplate">
                        ✕ Annuler sélection
                    </button>
                </div>
                <div class="palette-divider"></div>
                <div class="palette-section">
                    <h4>Autres éléments</h4>
                    <div class="other-tools">
                        <button @click="tool = 'wall'; selectedTemplate = null" :class="['tool-item', { active: tool === 'wall' }]">
                            <span class="tool-icon">▬</span> Mur
                        </button>
                        <button @click="tool = 'corridor'; selectedTemplate = null" :class="['tool-item', { active: tool === 'corridor' }]">
                            <span class="tool-icon">▭</span> Couloir
                        </button>
                        <button @click="tool = 'door'; selectedTemplate = null" :class="['tool-item', { active: tool === 'door' }]">
                            <span class="tool-icon">🚪</span> Porte
                        </button>
                        <button @click="tool = 'lift'; selectedTemplate = null" :class="['tool-item', { active: tool === 'lift' }]">
                            <span class="tool-icon">🛗</span> Ascenseur
                        </button>
                        <button @click="tool = 'zone'; selectedTemplate = null" :class="['tool-item', { active: tool === 'zone' }]">
                            <span class="tool-icon">⬜</span> Zone
                        </button>
                        <button @click="tool = 'label'; selectedTemplate = null" :class="['tool-item', { active: tool === 'label' }]">
                            <span class="tool-icon">T</span> Texte
                        </button>
                    </div>
                </div>
            </aside>

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
            <div :class="['canvas-wrapper', { 'template-mode': tool === 'template', 'select-mode': tool === 'select' }]" @wheel="onWheel">
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
                    <rect x="20" y="20" :width="svgWidth - 40" :height="svgHeight - 40" fill="none" stroke="#333" stroke-width="2" rx="3"/>

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
    padding: 6px 10px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    gap: 8px;
    flex-shrink: 0;
    flex-wrap: wrap;
}
.header-left, .header-center, .header-right {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.menu-btn {
    background: rgba(255,255,255,0.1);
    border: none; color: white;
    padding: 8px 12px; border-radius: 6px;
    cursor: pointer; font-size: 16px;
}
.menu-btn:hover { background: rgba(255,255,255,0.2); }
.editor-header h1 { font-size: 14px; font-weight: 600; margin: 0; white-space: nowrap; }
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
    padding: 4px 6px; border: none;
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-size: 10px; border-radius: 4px;
    cursor: pointer; transition: all 0.15s;
}
.tool-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.tool-btn.active { background: #4CAF50; color: white; }

/* Stats */
.stats { display: none; } /* Masquer sur petits écrans */
@media (min-width: 1200px) {
    .stats { display: flex; gap: 8px; font-size: 11px; }
}
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
    padding: 4px 8px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 4px;
    cursor: pointer; font-size: 11px;
    text-decoration: none;
    transition: all 0.15s;
    white-space: nowrap;
}
.header-btn:hover, .header-btn.active { background: rgba(255,255,255,0.2); }
.save-btn {
    padding: 4px 10px; border: none;
    background: #4CAF50; color: white;
    border-radius: 4px; cursor: pointer;
    font-size: 11px; font-weight: 500;
}
.save-btn:hover:not(:disabled) { background: #43A047; }
.save-btn:disabled { opacity: 0.7; }

/* Legend */
.legend-bar {
    display: flex;
    justify-content: center;
    padding: 4px 10px;
    background: #fff;
    border-bottom: 1px solid #ddd;
}
.legend-status {
    display: flex; gap: 12px; align-items: center; flex-wrap: wrap;
}
.status-item { display: flex; align-items: center; gap: 4px; font-size: 11px; }
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

/* Box Palette */
.box-palette {
    width: 160px;
    background: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
}
.palette-header {
    padding: 12px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #1e3a5f, #2d5a87);
    color: white;
}
.palette-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}
.palette-hint {
    display: block;
    font-size: 10px;
    opacity: 0.8;
    margin-top: 4px;
}
.palette-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
    padding: 10px;
}
.palette-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 6px 4px;
    border: 2px solid transparent;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s;
    background: #f8f9fa;
}
.palette-item:hover {
    background: #e3f2fd;
    border-color: #90caf9;
}
.palette-item.selected {
    background: #bbdefb;
    border-color: #2196F3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}
.palette-box {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #333;
    border-radius: 2px;
    color: white;
    font-size: 9px;
    font-weight: bold;
    min-width: 20px;
    min-height: 18px;
}
.palette-label {
    font-size: 9px;
    color: #666;
    margin-top: 3px;
    font-weight: 500;
}
.palette-actions {
    padding: 8px;
}
.palette-btn {
    width: 100%;
    padding: 8px;
    background: #ef5350;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
}
.palette-btn:hover {
    background: #e53935;
}
.palette-divider {
    height: 1px;
    background: #ddd;
    margin: 8px 12px;
}
.palette-section {
    padding: 8px 12px;
}
.palette-section h4 {
    margin: 0 0 8px;
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
}
.other-tools {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.tool-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
    font-size: 11px;
    transition: all 0.15s;
}
.tool-item:hover {
    background: #e8e8e8;
}
.tool-item.active {
    background: #2196F3;
    color: white;
    border-color: #1976D2;
}
.tool-icon {
    font-size: 14px;
    width: 18px;
    text-align: center;
}

/* Properties panel */
.properties-panel {
    width: 180px;
    background: #fff;
    border-right: 1px solid #ddd;
    padding: 12px;
    overflow-y: auto;
    flex-shrink: 0;
    font-size: 12px;
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
    padding: 5px;
}
.canvas-wrapper.template-mode {
    cursor: copy;
}
.canvas-wrapper.select-mode {
    cursor: default;
}
.plan-svg {
    width: 100%;
    height: 100%;
    max-height: calc(100vh - 100px);
    background: #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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
