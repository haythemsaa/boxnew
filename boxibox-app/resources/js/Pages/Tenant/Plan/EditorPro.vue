<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    elements: Array,
    configuration: Object,
    boxes: Array,
    unplacedBoxes: Array,
});

// ==================== STATE ====================
const selectedSite = ref(props.currentSite?.id);
const localElements = ref([...(props.elements || [])].map(el => ({ ...el, id: el.id || generateId(), rotation: el.rotation || 0, layer: el.layer || 'main' })));
const selectedElements = ref([]);
const tool = ref('select');
const zoom = ref(0.85);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const isDrawing = ref(false);
const isSelecting = ref(false);
const isResizing = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const drawStart = ref({ x: 0, y: 0 });
const selectionRect = ref({ x: 0, y: 0, w: 0, h: 0, visible: false });
const history = ref([]);
const historyIndex = ref(-1);
const isSaving = ref(false);
const clipboard = ref([]);
const resizeHandle = ref(null);
const resizeStart = ref({ x: 0, y: 0, w: 0, h: 0 });

// UI State
const showMenu = ref(false);
const showQuickCreate = ref(false);
const showAutoNumber = ref(false);
const showBoxList = ref(false);
const showLayers = ref(true);
const showRulers = ref(true);
const showMeasure = ref(false);
const showSettings = ref(false);
const darkMode = ref(false);
const snapEnabled = ref(true);
const snapStrength = ref(5);
const showGrid = ref(true);
const gridSize = ref(20);

// Layers
const layers = ref([
    { id: 'background', name: 'Fond', visible: true, locked: false, color: '#e3f2fd' },
    { id: 'zones', name: 'Zones', visible: true, locked: false, color: '#fff3e0' },
    { id: 'structure', name: 'Structure', visible: true, locked: false, color: '#fce4ec' },
    { id: 'main', name: 'Boxes', visible: true, locked: false, color: '#e8f5e9' },
    { id: 'overlay', name: 'Annotations', visible: true, locked: false, color: '#f3e5f5' },
]);
const activeLayer = ref('main');

// Background image
const backgroundImage = ref(null);
const bgOpacity = ref(0.5);
const bgScale = ref(1);
const bgOffsetX = ref(0);
const bgOffsetY = ref(0);

// Measure tool
const measurePoints = ref([]);
const measureDistance = ref(0);

// Guides
const guides = ref({ horizontal: [], vertical: [] });
const showGuides = ref(true);

// Box templates
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
];

const selectedTemplate = ref(null);

// SVG dimensions
const svgWidth = 1600;
const svgHeight = 1000;

// Status colors
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

const svgRef = ref(null);

// ==================== UTILITIES ====================
function generateId() {
    return 'el_' + Math.random().toString(36).substr(2, 9);
}

function getSvgPoint(e) {
    if (!svgRef.value) return { x: 0, y: 0 };
    const svg = svgRef.value;
    const pt = svg.createSVGPoint();
    pt.x = e.clientX;
    pt.y = e.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
    return { x: Math.round(svgP.x), y: Math.round(svgP.y) };
}

function snap(val, size = null) {
    if (!snapEnabled.value) return val;
    const grid = size || snapStrength.value;
    return Math.round(val / grid) * grid;
}

// Magnetic snap to nearby elements
function magneticSnap(el, dx, dy) {
    if (!snapEnabled.value) return { dx, dy };

    const threshold = 8;
    let snapX = null, snapY = null;
    const newX = el.x + dx;
    const newY = el.y + dy;

    localElements.value.forEach(other => {
        if (other.id === el.id || !other.visible) return;

        // Snap to left edge
        if (Math.abs(newX - other.x) < threshold) snapX = other.x;
        if (Math.abs(newX - (other.x + other.w)) < threshold) snapX = other.x + other.w;
        // Snap right edge
        if (Math.abs((newX + el.w) - other.x) < threshold) snapX = other.x - el.w;
        if (Math.abs((newX + el.w) - (other.x + other.w)) < threshold) snapX = other.x + other.w - el.w;
        // Center X
        if (Math.abs((newX + el.w/2) - (other.x + other.w/2)) < threshold) snapX = other.x + other.w/2 - el.w/2;

        // Snap to top edge
        if (Math.abs(newY - other.y) < threshold) snapY = other.y;
        if (Math.abs(newY - (other.y + other.h)) < threshold) snapY = other.y + other.h;
        // Snap bottom edge
        if (Math.abs((newY + el.h) - other.y) < threshold) snapY = other.y - el.h;
        if (Math.abs((newY + el.h) - (other.y + other.h)) < threshold) snapY = other.y + other.h - el.h;
        // Center Y
        if (Math.abs((newY + el.h/2) - (other.y + other.h/2)) < threshold) snapY = other.y + other.h/2 - el.h/2;
    });

    return {
        dx: snapX !== null ? snapX - el.x : dx,
        dy: snapY !== null ? snapY - el.y : dy
    };
}

// ==================== HISTORY ====================
function saveHistory() {
    history.value = history.value.slice(0, historyIndex.value + 1);
    history.value.push(JSON.stringify(localElements.value));
    historyIndex.value++;
    if (history.value.length > 100) {
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

// ==================== ELEMENT CREATION ====================
const elementDefaults = {
    box: { w: 35, h: 30, fill: '#4CAF50', z: 100, layer: 'main' },
    wall: { w: 100, h: 5, fill: '#1e3a5f', z: 50, layer: 'structure' },
    corridor: { w: 40, h: 150, fill: '#f5f5f5', z: 5, layer: 'structure' },
    door: { w: 30, h: 5, fill: '#ffffff', z: 55, layer: 'structure' },
    separator: { w: 80, h: 3, fill: '#94a3b8', z: 40, layer: 'structure' },
    zone: { w: 100, h: 80, fill: '#e3f2fd', z: 1, layer: 'zones' },
    label: { w: 50, h: 20, fill: 'transparent', z: 200, layer: 'overlay' },
    lift: { w: 50, h: 40, fill: '#ffffff', z: 30, layer: 'structure' },
    stairs: { w: 60, h: 50, fill: '#f5f5f5', z: 30, layer: 'structure' },
    parking: { w: 80, h: 120, fill: '#e8e8e8', z: 2, layer: 'zones' },
};

function createElement(type, x, y, w, h) {
    const def = elementDefaults[type] || elementDefaults.box;
    return {
        id: generateId(),
        type,
        x: snap(x),
        y: snap(y),
        w: w || def.w,
        h: h || def.h,
        fill: def.fill,
        z: def.z,
        layer: activeLayer.value || def.layer,
        rotation: 0,
        name: type === 'lift' ? 'LIFT' : type === 'stairs' ? 'ESCALIER' : '',
        vol: type === 'box' ? 6 : 0,
        status: 'available',
        locked: false,
        visible: true,
    };
}

function selectTemplate(template) {
    selectedTemplate.value = template;
    tool.value = 'template';
}

function placeTemplateAt(x, y) {
    const t = selectedTemplate.value;
    if (!t) return;

    const layer = layers.value.find(l => l.id === activeLayer.value);
    if (layer?.locked) {
        alert('Ce calque est verrouillé');
        return;
    }

    const el = {
        id: generateId(),
        type: 'box',
        x: snap(x - t.w / 2),
        y: snap(y - t.h / 2),
        w: t.w,
        h: t.h,
        fill: t.color,
        z: 100,
        layer: activeLayer.value,
        rotation: 0,
        name: '',
        vol: t.vol,
        status: 'available',
        locked: false,
        visible: true,
    };

    localElements.value.push(el);
    selectedElements.value = [el.id];
    saveHistory();
}

// ==================== MOUSE HANDLERS ====================
function onSvgMouseDown(e) {
    if (e.target.closest('.element-group') || e.target.closest('.resize-handle')) return;

    const pt = getSvgPoint(e);

    if (tool.value === 'measure') {
        measurePoints.value.push({ x: pt.x, y: pt.y });
        if (measurePoints.value.length === 2) {
            const [p1, p2] = measurePoints.value;
            measureDistance.value = Math.sqrt(Math.pow(p2.x - p1.x, 2) + Math.pow(p2.y - p1.y, 2)).toFixed(1);
        } else if (measurePoints.value.length > 2) {
            measurePoints.value = [{ x: pt.x, y: pt.y }];
            measureDistance.value = 0;
        }
        return;
    }

    if (tool.value === 'select') {
        if (e.shiftKey) {
            // Start selection rectangle
            isSelecting.value = true;
            selectionRect.value = { x: pt.x, y: pt.y, w: 0, h: 0, visible: true };
        } else {
            // Pan
            isDragging.value = true;
            dragStart.value = { x: e.clientX, y: e.clientY, px: panX.value, py: panY.value };
            selectedElements.value = [];
        }
    } else if (tool.value === 'template' && selectedTemplate.value) {
        placeTemplateAt(pt.x, pt.y);
    } else if (tool.value === 'guide-h') {
        guides.value.horizontal.push(pt.y);
        tool.value = 'select';
    } else if (tool.value === 'guide-v') {
        guides.value.vertical.push(pt.x);
        tool.value = 'select';
    } else {
        isDrawing.value = true;
        drawStart.value = pt;
    }
}

function onSvgMouseMove(e) {
    const pt = getSvgPoint(e);

    if (isDragging.value && tool.value === 'select') {
        const dx = e.clientX - dragStart.value.x;
        const dy = e.clientY - dragStart.value.y;
        panX.value = dragStart.value.px + dx;
        panY.value = dragStart.value.py + dy;
    }

    if (isSelecting.value) {
        selectionRect.value.w = pt.x - selectionRect.value.x;
        selectionRect.value.h = pt.y - selectionRect.value.y;
    }
}

function onSvgMouseUp(e) {
    const pt = getSvgPoint(e);

    if (isSelecting.value) {
        // Select elements in rectangle
        const rect = normalizeRect(selectionRect.value);
        const selected = localElements.value.filter(el => {
            if (!el.visible) return false;
            const layer = layers.value.find(l => l.id === el.layer);
            if (layer && !layer.visible) return false;
            return el.x >= rect.x && el.x + el.w <= rect.x + rect.w &&
                   el.y >= rect.y && el.y + el.h <= rect.y + rect.h;
        });
        selectedElements.value = selected.map(el => el.id);
        selectionRect.value.visible = false;
    }

    if (isDrawing.value && tool.value !== 'select' && tool.value !== 'template' && tool.value !== 'measure') {
        const layer = layers.value.find(l => l.id === activeLayer.value);
        if (layer?.locked) {
            alert('Ce calque est verrouillé');
        } else {
            const x = Math.min(pt.x, drawStart.value.x);
            const y = Math.min(pt.y, drawStart.value.y);
            const w = Math.abs(pt.x - drawStart.value.x);
            const h = Math.abs(pt.y - drawStart.value.y);
            const useDefaultSize = w < 10 && h < 10;
            const el = createElement(tool.value, useDefaultSize ? drawStart.value.x : x, useDefaultSize ? drawStart.value.y : y, useDefaultSize ? null : w, useDefaultSize ? null : h);
            localElements.value.push(el);
            selectedElements.value = [el.id];
            saveHistory();
        }
    }

    isDragging.value = false;
    isDrawing.value = false;
    isSelecting.value = false;
}

function normalizeRect(rect) {
    return {
        x: rect.w < 0 ? rect.x + rect.w : rect.x,
        y: rect.h < 0 ? rect.y + rect.h : rect.y,
        w: Math.abs(rect.w),
        h: Math.abs(rect.h)
    };
}

// ==================== ELEMENT INTERACTIONS ====================
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

function startDrag(e, el) {
    const layer = layers.value.find(l => l.id === el.layer);
    if (el.locked || layer?.locked || tool.value !== 'select') return;
    e.stopPropagation();

    const startPt = getSvgPoint(e);
    const draggedIds = selectedElements.value.includes(el.id) ? selectedElements.value : [el.id];
    const startPositions = draggedIds.map(id => {
        const elem = localElements.value.find(x => x.id === id);
        return { id, x: elem.x, y: elem.y };
    });

    const onMove = (moveE) => {
        const movePt = getSvgPoint(moveE);
        let dx = movePt.x - startPt.x;
        let dy = movePt.y - startPt.y;

        // Apply magnetic snap to first element
        if (startPositions.length === 1) {
            const elem = localElements.value.find(x => x.id === startPositions[0].id);
            if (elem) {
                const snapped = magneticSnap(elem, dx, dy);
                dx = snapped.dx;
                dy = snapped.dy;
            }
        }

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

// ==================== RESIZE ====================
function startResize(e, el, handle) {
    e.stopPropagation();
    const layer = layers.value.find(l => l.id === el.layer);
    if (el.locked || layer?.locked) return;

    isResizing.value = true;
    resizeHandle.value = handle;
    resizeStart.value = { x: el.x, y: el.y, w: el.w, h: el.h, mouseX: e.clientX, mouseY: e.clientY };

    const onMove = (moveE) => {
        const dx = (moveE.clientX - resizeStart.value.mouseX) / zoom.value;
        const dy = (moveE.clientY - resizeStart.value.mouseY) / zoom.value;

        switch (handle) {
            case 'e':
                el.w = snap(Math.max(10, resizeStart.value.w + dx));
                break;
            case 'w':
                el.x = snap(resizeStart.value.x + dx);
                el.w = snap(Math.max(10, resizeStart.value.w - dx));
                break;
            case 's':
                el.h = snap(Math.max(10, resizeStart.value.h + dy));
                break;
            case 'n':
                el.y = snap(resizeStart.value.y + dy);
                el.h = snap(Math.max(10, resizeStart.value.h - dy));
                break;
            case 'se':
                el.w = snap(Math.max(10, resizeStart.value.w + dx));
                el.h = snap(Math.max(10, resizeStart.value.h + dy));
                break;
            case 'sw':
                el.x = snap(resizeStart.value.x + dx);
                el.w = snap(Math.max(10, resizeStart.value.w - dx));
                el.h = snap(Math.max(10, resizeStart.value.h + dy));
                break;
            case 'ne':
                el.y = snap(resizeStart.value.y + dy);
                el.w = snap(Math.max(10, resizeStart.value.w + dx));
                el.h = snap(Math.max(10, resizeStart.value.h - dy));
                break;
            case 'nw':
                el.x = snap(resizeStart.value.x + dx);
                el.y = snap(resizeStart.value.y + dy);
                el.w = snap(Math.max(10, resizeStart.value.w - dx));
                el.h = snap(Math.max(10, resizeStart.value.h - dy));
                break;
        }
    };

    const onUp = () => {
        isResizing.value = false;
        resizeHandle.value = null;
        saveHistory();
        window.removeEventListener('mousemove', onMove);
        window.removeEventListener('mouseup', onUp);
    };

    window.addEventListener('mousemove', onMove);
    window.addEventListener('mouseup', onUp);
}

// ==================== ACTIONS ====================
function deleteSelected() {
    if (selectedElements.value.length === 0) return;
    localElements.value = localElements.value.filter(el => !selectedElements.value.includes(el.id));
    selectedElements.value = [];
    saveHistory();
}

function duplicateSelected() {
    if (selectedElements.value.length === 0) return;
    const newEls = [];
    selectedElements.value.forEach(id => {
        const orig = localElements.value.find(el => el.id === id);
        if (orig) {
            newEls.push({ ...orig, id: generateId(), x: orig.x + 15, y: orig.y + 15 });
        }
    });
    localElements.value.push(...newEls);
    selectedElements.value = newEls.map(el => el.id);
    saveHistory();
}

function copySelected() {
    if (selectedElements.value.length === 0) return;
    clipboard.value = selectedElements.value.map(id => {
        const el = localElements.value.find(x => x.id === id);
        return el ? { ...el } : null;
    }).filter(Boolean);
}

function paste() {
    if (clipboard.value.length === 0) return;
    const newEls = clipboard.value.map(el => ({
        ...el,
        id: generateId(),
        x: el.x + 20,
        y: el.y + 20
    }));
    localElements.value.push(...newEls);
    selectedElements.value = newEls.map(el => el.id);
    saveHistory();
}

function rotateSelected(angle) {
    selectedElements.value.forEach(id => {
        const el = localElements.value.find(x => x.id === id);
        if (el) {
            el.rotation = ((el.rotation || 0) + angle) % 360;
        }
    });
    saveHistory();
}

// ==================== ALIGNMENT ====================
function alignElements(direction) {
    if (selectedElements.value.length < 2) return;

    const selected = localElements.value.filter(el => selectedElements.value.includes(el.id));

    switch (direction) {
        case 'left':
            const minX = Math.min(...selected.map(el => el.x));
            selected.forEach(el => el.x = minX);
            break;
        case 'right':
            const maxX = Math.max(...selected.map(el => el.x + el.w));
            selected.forEach(el => el.x = maxX - el.w);
            break;
        case 'top':
            const minY = Math.min(...selected.map(el => el.y));
            selected.forEach(el => el.y = minY);
            break;
        case 'bottom':
            const maxY = Math.max(...selected.map(el => el.y + el.h));
            selected.forEach(el => el.y = maxY - el.h);
            break;
        case 'center-h':
            const avgX = selected.reduce((sum, el) => sum + el.x + el.w/2, 0) / selected.length;
            selected.forEach(el => el.x = avgX - el.w/2);
            break;
        case 'center-v':
            const avgY = selected.reduce((sum, el) => sum + el.y + el.h/2, 0) / selected.length;
            selected.forEach(el => el.y = avgY - el.h/2);
            break;
    }
    saveHistory();
}

function distributeElements(direction) {
    if (selectedElements.value.length < 3) return;

    const selected = localElements.value
        .filter(el => selectedElements.value.includes(el.id))
        .sort((a, b) => direction === 'horizontal' ? a.x - b.x : a.y - b.y);

    if (direction === 'horizontal') {
        const totalWidth = selected.reduce((sum, el) => sum + el.w, 0);
        const minX = selected[0].x;
        const maxX = selected[selected.length - 1].x + selected[selected.length - 1].w;
        const gap = (maxX - minX - totalWidth) / (selected.length - 1);

        let currentX = minX;
        selected.forEach(el => {
            el.x = currentX;
            currentX += el.w + gap;
        });
    } else {
        const totalHeight = selected.reduce((sum, el) => sum + el.h, 0);
        const minY = selected[0].y;
        const maxY = selected[selected.length - 1].y + selected[selected.length - 1].h;
        const gap = (maxY - minY - totalHeight) / (selected.length - 1);

        let currentY = minY;
        selected.forEach(el => {
            el.y = currentY;
            currentY += el.h + gap;
        });
    }
    saveHistory();
}

// ==================== LAYERS ====================
function toggleLayerVisibility(layerId) {
    const layer = layers.value.find(l => l.id === layerId);
    if (layer) layer.visible = !layer.visible;
}

function toggleLayerLock(layerId) {
    const layer = layers.value.find(l => l.id === layerId);
    if (layer) layer.locked = !layer.locked;
}

function moveToLayer(layerId) {
    selectedElements.value.forEach(id => {
        const el = localElements.value.find(x => x.id === id);
        if (el) el.layer = layerId;
    });
    saveHistory();
}

// ==================== BACKGROUND IMAGE ====================
function uploadBackground(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
        backgroundImage.value = event.target.result;
    };
    reader.readAsDataURL(file);
}

function removeBackground() {
    backgroundImage.value = null;
}

// ==================== QUICK CREATE ====================
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

// ==================== ZOOM & VIEW ====================
function zoomIn() { zoom.value = Math.min(zoom.value * 1.15, 4); }
function zoomOut() { zoom.value = Math.max(zoom.value / 1.15, 0.1); }
function resetView() { zoom.value = 0.85; panX.value = 0; panY.value = 0; }
function fitToScreen() {
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
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.1), 4);
}

// ==================== SAVE & EXPORT ====================
function savePlan() {
    if (!props.currentSite?.id) {
        alert('Erreur: Aucun site sélectionné.');
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
        onError: () => {
            isSaving.value = false;
            alert('Erreur lors de la sauvegarde.');
        },
    });
}

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

function exportPNG() {
    const svg = svgRef.value;
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    const data = new XMLSerializer().serializeToString(svg);
    const img = new Image();

    canvas.width = svgWidth * 2;
    canvas.height = svgHeight * 2;

    img.onload = () => {
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

        const a = document.createElement('a');
        a.download = `plan-${props.currentSite?.name || 'site'}.png`;
        a.href = canvas.toDataURL('image/png');
        a.click();
    };

    img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(data)));
}

// ==================== KEYBOARD ====================
function onKeyDown(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    const ctrl = e.ctrlKey || e.metaKey;

    if (e.key === 'Delete' || e.key === 'Backspace') deleteSelected();
    else if (e.key === 'd' && ctrl) { e.preventDefault(); duplicateSelected(); }
    else if (e.key === 'c' && ctrl) { e.preventDefault(); copySelected(); }
    else if (e.key === 'v' && ctrl) { e.preventDefault(); paste(); }
    else if (e.key === 'z' && ctrl) { e.preventDefault(); e.shiftKey ? redo() : undo(); }
    else if (e.key === 'y' && ctrl) { e.preventDefault(); redo(); }
    else if (e.key === 's' && ctrl) { e.preventDefault(); savePlan(); }
    else if (e.key === 'a' && ctrl) {
        e.preventDefault();
        selectedElements.value = localElements.value.filter(el => {
            const layer = layers.value.find(l => l.id === el.layer);
            return el.visible && layer?.visible;
        }).map(el => el.id);
    }
    else if (e.key === 'Escape') { selectedElements.value = []; tool.value = 'select'; selectedTemplate.value = null; }
    else if (e.key === 'v' && !ctrl) tool.value = 'select';
    else if (e.key === 'b') tool.value = 'box';
    else if (e.key === 'w') tool.value = 'wall';
    else if (e.key === 'c' && !ctrl) tool.value = 'corridor';
    else if (e.key === 'm') tool.value = 'measure';
    else if (e.key === 'g') showGrid.value = !showGrid.value;
    else if (e.key === 'l') showLayers.value = !showLayers.value;
    else if (e.key === 'r' && !ctrl) resetView();
    else if (e.key === 'f') fitToScreen();
    else if (e.key === '+' || e.key === '=') zoomIn();
    else if (e.key === '-') zoomOut();
    else if (e.key === '[') rotateSelected(-15);
    else if (e.key === ']') rotateSelected(15);

    // Arrow keys for moving
    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key) && selectedElements.value.length > 0) {
        e.preventDefault();
        const step = e.shiftKey ? 10 : 1;
        selectedElements.value.forEach(id => {
            const el = localElements.value.find(x => x.id === id);
            if (el && !el.locked) {
                if (e.key === 'ArrowUp') el.y -= step;
                if (e.key === 'ArrowDown') el.y += step;
                if (e.key === 'ArrowLeft') el.x -= step;
                if (e.key === 'ArrowRight') el.x += step;
            }
        });
        saveHistory();
    }
}

// ==================== COMPUTED ====================
const sortedElements = computed(() => {
    return [...localElements.value]
        .filter(el => {
            const layer = layers.value.find(l => l.id === el.layer);
            return layer ? layer.visible : true;
        })
        .sort((a, b) => (a.z || 0) - (b.z || 0));
});

const selectedElement = computed(() =>
    selectedElements.value.length === 1
        ? localElements.value.find(el => el.id === selectedElements.value[0])
        : null
);

const isSelected = (el) => selectedElements.value.includes(el.id);

const stats = computed(() => {
    const boxes = localElements.value.filter(el => el.type === 'box');
    return {
        total: boxes.length,
        available: boxes.filter(b => b.status === 'available').length,
        occupied: boxes.filter(b => b.status === 'occupied').length,
    };
});

const tools = [
    { id: 'select', label: 'Sélection', icon: '↖', key: 'V' },
    { id: 'box', label: 'Box', icon: '▢', key: 'B' },
    { id: 'wall', label: 'Mur', icon: '▬', key: 'W' },
    { id: 'corridor', label: 'Couloir', icon: '▭', key: 'C' },
    { id: 'door', label: 'Porte', icon: '🚪', key: 'D' },
    { id: 'zone', label: 'Zone', icon: '⬜', key: 'Z' },
    { id: 'lift', label: 'Ascenseur', icon: '🛗', key: 'L' },
    { id: 'stairs', label: 'Escalier', icon: '⚡', key: 'S' },
    { id: 'label', label: 'Texte', icon: 'T', key: 'T' },
    { id: 'measure', label: 'Mesure', icon: '📏', key: 'M' },
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

function getTransform(el) {
    if (!el.rotation) return '';
    const cx = el.x + el.w / 2;
    const cy = el.y + el.h / 2;
    return `rotate(${el.rotation} ${cx} ${cy})`;
}

onMounted(() => {
    saveHistory();
    window.addEventListener('keydown', onKeyDown);
    setTimeout(() => fitToScreen(), 100);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
});
</script>

<template>
    <div :class="['editor-fullscreen', { 'dark-mode': darkMode }]">
        <!-- Sidebar Menu -->
        <div class="sidebar" :class="{ open: showMenu }">
            <div class="sidebar-header">
                <span class="logo">BoxiBox Pro</span>
                <button @click="showMenu = false" class="close-menu">&times;</button>
            </div>
            <nav class="sidebar-nav">
                <Link :href="route('tenant.dashboard')" class="nav-item">
                    <i class="fas fa-home"></i><span>Dashboard</span>
                </Link>
                <Link :href="route('tenant.boxes.index')" class="nav-item">
                    <i class="fas fa-box"></i><span>Boxes</span>
                </Link>
                <Link :href="route('tenant.plan.interactive')" class="nav-item">
                    <i class="fas fa-map"></i><span>Plan Interactif</span>
                </Link>
                <Link :href="route('tenant.plan.interactive-pro')" class="nav-item">
                    <i class="fas fa-star"></i><span>Plan Pro</span>
                </Link>
            </nav>
        </div>
        <div v-if="showMenu" class="overlay" @click="showMenu = false"></div>

        <!-- Header -->
        <header class="editor-header">
            <div class="header-left">
                <button @click="showMenu = true" class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Éditeur Pro</h1>
                <select v-model="selectedSite" @change="changeSite" class="site-select">
                    <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.name }}</option>
                </select>
            </div>

            <div class="header-center">
                <div class="tools-bar">
                    <button
                        v-for="t in tools"
                        :key="t.id"
                        @click="tool = t.id; selectedTemplate = null"
                        :class="['tool-btn', { active: tool === t.id }]"
                        :title="`${t.label} (${t.key})`"
                    >
                        <span class="tool-icon">{{ t.icon }}</span>
                        <span class="tool-label">{{ t.label }}</span>
                    </button>
                </div>
            </div>

            <div class="header-right">
                <div class="actions">
                    <button @click="undo" :disabled="historyIndex <= 0" class="action-btn" title="Annuler (Ctrl+Z)">↩</button>
                    <button @click="redo" :disabled="historyIndex >= history.length - 1" class="action-btn" title="Rétablir (Ctrl+Y)">↪</button>
                    <div class="divider"></div>
                    <button @click="copySelected" :disabled="selectedElements.length === 0" class="action-btn" title="Copier (Ctrl+C)">📋</button>
                    <button @click="paste" :disabled="clipboard.length === 0" class="action-btn" title="Coller (Ctrl+V)">📌</button>
                    <button @click="duplicateSelected" :disabled="selectedElements.length === 0" class="action-btn" title="Dupliquer (Ctrl+D)">⧉</button>
                    <button @click="deleteSelected" :disabled="selectedElements.length === 0" class="action-btn danger" title="Supprimer">🗑</button>
                </div>

                <div class="divider"></div>

                <!-- Alignment buttons -->
                <div class="align-controls" v-if="selectedElements.length >= 2">
                    <button @click="alignElements('left')" class="align-btn" title="Aligner à gauche">⬅</button>
                    <button @click="alignElements('center-h')" class="align-btn" title="Centrer horizontalement">↔</button>
                    <button @click="alignElements('right')" class="align-btn" title="Aligner à droite">➡</button>
                    <button @click="alignElements('top')" class="align-btn" title="Aligner en haut">⬆</button>
                    <button @click="alignElements('center-v')" class="align-btn" title="Centrer verticalement">↕</button>
                    <button @click="alignElements('bottom')" class="align-btn" title="Aligner en bas">⬇</button>
                    <div class="divider"></div>
                    <button @click="distributeElements('horizontal')" class="align-btn" title="Distribuer horizontalement" v-if="selectedElements.length >= 3">⇔</button>
                    <button @click="distributeElements('vertical')" class="align-btn" title="Distribuer verticalement" v-if="selectedElements.length >= 3">⇕</button>
                </div>

                <!-- Rotation -->
                <div class="rotate-controls" v-if="selectedElements.length > 0">
                    <button @click="rotateSelected(-15)" class="action-btn" title="Rotation -15° ([)">↶</button>
                    <button @click="rotateSelected(15)" class="action-btn" title="Rotation +15° (])">↷</button>
                </div>

                <div class="divider"></div>

                <div class="zoom-controls">
                    <button @click="zoomOut" class="zoom-btn" title="Zoom -">−</button>
                    <span class="zoom-level">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="zoom-btn" title="Zoom +">+</button>
                    <button @click="fitToScreen" class="zoom-btn" title="Ajuster (F)">⊡</button>
                    <button @click="resetView" class="zoom-btn" title="Reset (R)">⟲</button>
                </div>

                <div class="divider"></div>

                <div class="toggle-controls">
                    <button @click="showGrid = !showGrid" :class="['toggle-btn', { active: showGrid }]" title="Grille (G)">⊞</button>
                    <button @click="snapEnabled = !snapEnabled" :class="['toggle-btn', { active: snapEnabled }]" title="Magnétisme">🧲</button>
                    <button @click="showRulers = !showRulers" :class="['toggle-btn', { active: showRulers }]" title="Règles">📏</button>
                    <button @click="showLayers = !showLayers" :class="['toggle-btn', { active: showLayers }]" title="Calques (L)">📚</button>
                    <button @click="darkMode = !darkMode" :class="['toggle-btn', { active: darkMode }]" title="Mode sombre">🌙</button>
                </div>

                <div class="divider"></div>

                <button @click="showQuickCreate = true" class="header-btn">Création Rapide</button>
                <button @click="showAutoNumber = true" class="header-btn">Numéroter</button>
                <button @click="exportPNG" class="header-btn">PNG</button>
                <button @click="exportSVG" class="header-btn">SVG</button>
                <Link :href="route('tenant.plan.interactive-pro')" class="header-btn">Aperçu</Link>
                <button @click="savePlan" :disabled="isSaving" class="save-btn">
                    {{ isSaving ? '...' : '💾 Sauver' }}
                </button>
            </div>
        </header>

        <!-- Measure display -->
        <div v-if="tool === 'measure' && measureDistance > 0" class="measure-display">
            Distance: {{ measureDistance }} px ({{ (measureDistance / 10).toFixed(1) }} unités)
        </div>

        <!-- Main content -->
        <div class="editor-body">
            <!-- Layers Panel -->
            <aside v-if="showLayers" class="layers-panel">
                <div class="panel-header">
                    <h3>📚 Calques</h3>
                </div>
                <div class="layer-list">
                    <div
                        v-for="layer in layers"
                        :key="layer.id"
                        :class="['layer-item', { active: activeLayer === layer.id }]"
                        @click="activeLayer = layer.id"
                    >
                        <div class="layer-color" :style="{ background: layer.color }"></div>
                        <span class="layer-name">{{ layer.name }}</span>
                        <div class="layer-actions">
                            <button @click.stop="toggleLayerVisibility(layer.id)" :class="{ active: layer.visible }">
                                {{ layer.visible ? '👁' : '👁‍🗨' }}
                            </button>
                            <button @click.stop="toggleLayerLock(layer.id)" :class="{ active: layer.locked }">
                                {{ layer.locked ? '🔒' : '🔓' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="layer-actions-bar" v-if="selectedElements.length > 0">
                    <label>Déplacer vers:</label>
                    <select @change="moveToLayer($event.target.value)">
                        <option value="">-- Choisir --</option>
                        <option v-for="layer in layers" :key="layer.id" :value="layer.id">{{ layer.name }}</option>
                    </select>
                </div>
            </aside>

            <!-- Box Palette -->
            <aside class="box-palette">
                <div class="palette-header">
                    <h3>📦 Boxes</h3>
                </div>
                <div class="palette-grid">
                    <div
                        v-for="t in boxTemplates"
                        :key="t.vol"
                        :class="['palette-item', { selected: selectedTemplate?.vol === t.vol }]"
                        @click="selectTemplate(t)"
                    >
                        <div class="palette-box" :style="{ width: Math.min(t.w, 45) + 'px', height: Math.min(t.h, 35) + 'px', background: t.color }">
                            {{ t.vol }}
                        </div>
                        <span class="palette-label">{{ t.name }}</span>
                    </div>
                </div>
                <div class="palette-actions" v-if="selectedTemplate">
                    <button @click="selectedTemplate = null; tool = 'select'" class="palette-btn">✕ Annuler</button>
                </div>

                <!-- Background image -->
                <div class="palette-section">
                    <h4>🖼 Image de fond</h4>
                    <input type="file" accept="image/*" @change="uploadBackground" class="file-input" />
                    <div v-if="backgroundImage" class="bg-controls">
                        <label>Opacité: {{ Math.round(bgOpacity * 100) }}%</label>
                        <input type="range" v-model.number="bgOpacity" min="0" max="1" step="0.1" />
                        <label>Échelle: {{ bgScale.toFixed(1) }}x</label>
                        <input type="range" v-model.number="bgScale" min="0.1" max="3" step="0.1" />
                        <button @click="removeBackground" class="btn-small danger">Supprimer</button>
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
                <div class="prop-group">
                    <label>Rotation</label>
                    <input type="number" v-model.number="selectedElement.rotation" step="15" />
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
                    <label>Calque</label>
                    <select v-model="selectedElement.layer">
                        <option v-for="layer in layers" :key="layer.id" :value="layer.id">{{ layer.name }}</option>
                    </select>
                </div>
                <div class="prop-group">
                    <label>Couleur</label>
                    <input type="color" v-model="selectedElement.fill" />
                </div>
                <div class="prop-group">
                    <label>Z-Index</label>
                    <input type="number" v-model.number="selectedElement.z" />
                </div>
                <div class="prop-actions">
                    <button @click="selectedElement.locked = !selectedElement.locked" :class="{ active: selectedElement.locked }">
                        {{ selectedElement.locked ? '🔒 Verrouillé' : '🔓 Déverrouillé' }}
                    </button>
                </div>
            </aside>

            <!-- SVG Canvas -->
            <div :class="['canvas-wrapper', { 'template-mode': tool === 'template', 'measure-mode': tool === 'measure' }]" @wheel="onWheel">
                <!-- Rulers -->
                <div v-if="showRulers" class="ruler ruler-h">
                    <svg width="100%" height="20">
                        <g v-for="i in Math.ceil(svgWidth / 50)" :key="'h'+i">
                            <line :x1="i * 50 * zoom + panX" y1="15" :x2="i * 50 * zoom + panX" y2="20" stroke="#666" />
                            <text :x="i * 50 * zoom + panX" y="12" font-size="9" fill="#666">{{ i * 50 }}</text>
                        </g>
                    </svg>
                </div>
                <div v-if="showRulers" class="ruler ruler-v">
                    <svg width="20" height="100%">
                        <g v-for="i in Math.ceil(svgHeight / 50)" :key="'v'+i">
                            <line x1="15" :y1="i * 50 * zoom + panY" x2="20" :y2="i * 50 * zoom + panY" stroke="#666" />
                            <text x="2" :y="i * 50 * zoom + panY + 3" font-size="9" fill="#666">{{ i * 50 }}</text>
                        </g>
                    </svg>
                </div>

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
                    <rect x="0" y="0" :width="svgWidth" :height="svgHeight" :fill="darkMode ? '#2d2d2d' : '#fff'"/>

                    <!-- Background image -->
                    <image
                        v-if="backgroundImage"
                        :href="backgroundImage"
                        :x="bgOffsetX"
                        :y="bgOffsetY"
                        :width="svgWidth * bgScale"
                        :height="svgHeight * bgScale"
                        :opacity="bgOpacity"
                        preserveAspectRatio="xMidYMid meet"
                    />

                    <!-- Grid -->
                    <defs>
                        <pattern id="grid" :width="gridSize" :height="gridSize" patternUnits="userSpaceOnUse">
                            <path :d="`M ${gridSize} 0 L 0 0 0 ${gridSize}`" fill="none" :stroke="darkMode ? '#444' : '#e0e0e0'" stroke-width="0.5"/>
                        </pattern>
                        <pattern id="grid-major" :width="gridSize * 5" :height="gridSize * 5" patternUnits="userSpaceOnUse">
                            <path :d="`M ${gridSize * 5} 0 L 0 0 0 ${gridSize * 5}`" fill="none" :stroke="darkMode ? '#555' : '#ccc'" stroke-width="1"/>
                        </pattern>
                    </defs>
                    <rect v-if="showGrid" x="0" y="0" :width="svgWidth" :height="svgHeight" fill="url(#grid)"/>
                    <rect v-if="showGrid" x="0" y="0" :width="svgWidth" :height="svgHeight" fill="url(#grid-major)"/>

                    <!-- Guides -->
                    <g v-if="showGuides">
                        <line v-for="(y, i) in guides.horizontal" :key="'gh'+i"
                            x1="0" :y1="y" :x2="svgWidth" :y2="y"
                            stroke="#00bcd4" stroke-width="1" stroke-dasharray="5,5" />
                        <line v-for="(x, i) in guides.vertical" :key="'gv'+i"
                            :x1="x" y1="0" :x2="x" :y2="svgHeight"
                            stroke="#00bcd4" stroke-width="1" stroke-dasharray="5,5" />
                    </g>

                    <!-- Border -->
                    <rect x="20" y="20" :width="svgWidth - 40" :height="svgHeight - 40" fill="none" :stroke="darkMode ? '#666' : '#333'" stroke-width="2" rx="3"/>

                    <!-- Elements -->
                    <g v-for="el in sortedElements" :key="el.id"
                       class="element-group"
                       :class="{ selected: isSelected(el), locked: el.locked }"
                       :transform="getTransform(el)"
                       @mousedown="(e) => { selectElement(e, el); startDrag(e, el); }">

                        <!-- Box element -->
                        <template v-if="el.type === 'box'">
                            <rect
                                :x="el.x" :y="el.y" :width="el.w" :height="el.h"
                                :fill="getElementFill(el)"
                                :stroke="darkMode ? '#fff' : '#333'"
                                stroke-width="1" rx="1"
                                class="element-rect"
                            />
                            <text :x="el.x + el.w/2" :y="el.y + el.h/2 - (el.h > 25 && el.vol ? 4 : 0)" :fill="getTextColor(el)" class="box-name">
                                {{ el.name }}
                            </text>
                            <text v-if="el.vol && el.h > 25" :x="el.x + el.w/2" :y="el.y + el.h/2 + 8" :fill="getTextColor(el)" class="box-vol">
                                {{ el.vol }}m³
                            </text>
                        </template>

                        <!-- Wall -->
                        <template v-else-if="el.type === 'wall'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#1e3a5f'" stroke="#000" stroke-width="1"/>
                        </template>

                        <!-- Corridor -->
                        <template v-else-if="el.type === 'corridor'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#f5f5f5'" stroke="#ddd" stroke-width="1"/>
                        </template>

                        <!-- Door -->
                        <template v-else-if="el.type === 'door'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" fill="#fff" stroke="#666" stroke-width="1"/>
                            <line :x1="el.x" :y1="el.y + el.h/2" :x2="el.x + el.w" :y2="el.y + el.h/2" stroke="#999" stroke-width="1" stroke-dasharray="3,2"/>
                        </template>

                        <!-- Lift -->
                        <template v-else-if="el.type === 'lift'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" fill="#fff" stroke="#333" stroke-width="1" rx="1"/>
                            <text :x="el.x + el.w/2" :y="el.y + el.h/2" fill="#333" class="box-name">{{ el.name || 'LIFT' }}</text>
                        </template>

                        <!-- Stairs -->
                        <template v-else-if="el.type === 'stairs'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" fill="#f5f5f5" stroke="#333" stroke-width="1"/>
                            <g v-for="i in 5" :key="i">
                                <line :x1="el.x" :y1="el.y + (el.h / 5) * i" :x2="el.x + el.w" :y2="el.y + (el.h / 5) * i" stroke="#999" stroke-width="1"/>
                            </g>
                            <text :x="el.x + el.w/2" :y="el.y + el.h/2" fill="#333" class="box-name">{{ el.name || 'ESC' }}</text>
                        </template>

                        <!-- Zone -->
                        <template v-else-if="el.type === 'zone'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#e3f2fd'" stroke="#90caf9" stroke-width="1" stroke-dasharray="5,5" rx="5"/>
                            <text v-if="el.name" :x="el.x + 5" :y="el.y + 15" fill="#1976d2" font-size="11">{{ el.name }}</text>
                        </template>

                        <!-- Label -->
                        <template v-else-if="el.type === 'label'">
                            <text :x="el.x" :y="el.y + 15" :fill="darkMode ? '#fff' : '#333'" font-size="14" font-weight="bold">{{ el.name }}</text>
                        </template>

                        <!-- Separator -->
                        <template v-else-if="el.type === 'separator'">
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#94a3b8'"/>
                        </template>

                        <!-- Generic -->
                        <template v-else>
                            <rect :x="el.x" :y="el.y" :width="el.w" :height="el.h" :fill="el.fill || '#ccc'" stroke="#999" stroke-width="1"/>
                        </template>

                        <!-- Selection & resize handles -->
                        <g v-if="isSelected(el)" class="selection-overlay">
                            <rect :x="el.x - 2" :y="el.y - 2" :width="el.w + 4" :height="el.h + 4" fill="none" stroke="#3b82f6" stroke-width="2" stroke-dasharray="4,2"/>

                            <!-- Resize handles -->
                            <rect class="resize-handle" :x="el.x - 4" :y="el.y + el.h/2 - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'w')" style="cursor: ew-resize"/>
                            <rect class="resize-handle" :x="el.x + el.w - 4" :y="el.y + el.h/2 - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'e')" style="cursor: ew-resize"/>
                            <rect class="resize-handle" :x="el.x + el.w/2 - 4" :y="el.y - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'n')" style="cursor: ns-resize"/>
                            <rect class="resize-handle" :x="el.x + el.w/2 - 4" :y="el.y + el.h - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 's')" style="cursor: ns-resize"/>
                            <rect class="resize-handle" :x="el.x - 4" :y="el.y - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'nw')" style="cursor: nwse-resize"/>
                            <rect class="resize-handle" :x="el.x + el.w - 4" :y="el.y - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'ne')" style="cursor: nesw-resize"/>
                            <rect class="resize-handle" :x="el.x - 4" :y="el.y + el.h - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'sw')" style="cursor: nesw-resize"/>
                            <rect class="resize-handle" :x="el.x + el.w - 4" :y="el.y + el.h - 4" width="8" height="8" fill="#3b82f6" @mousedown.stop="startResize($event, el, 'se')" style="cursor: nwse-resize"/>
                        </g>
                    </g>

                    <!-- Selection rectangle -->
                    <rect
                        v-if="selectionRect.visible"
                        :x="selectionRect.w < 0 ? selectionRect.x + selectionRect.w : selectionRect.x"
                        :y="selectionRect.h < 0 ? selectionRect.y + selectionRect.h : selectionRect.y"
                        :width="Math.abs(selectionRect.w)"
                        :height="Math.abs(selectionRect.h)"
                        fill="rgba(59, 130, 246, 0.2)"
                        stroke="#3b82f6"
                        stroke-width="1"
                        stroke-dasharray="5,5"
                    />

                    <!-- Measure line -->
                    <g v-if="tool === 'measure' && measurePoints.length >= 1">
                        <circle :cx="measurePoints[0].x" :cy="measurePoints[0].y" r="5" fill="#f44336"/>
                        <circle v-if="measurePoints.length === 2" :cx="measurePoints[1].x" :cy="measurePoints[1].y" r="5" fill="#f44336"/>
                        <line v-if="measurePoints.length === 2"
                            :x1="measurePoints[0].x" :y1="measurePoints[0].y"
                            :x2="measurePoints[1].x" :y2="measurePoints[1].y"
                            stroke="#f44336" stroke-width="2" stroke-dasharray="5,5"/>
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

        <!-- Stats bar -->
        <footer class="stats-bar">
            <div class="stat"><span class="dot available"></span>{{ stats.available }} Libre</div>
            <div class="stat"><span class="dot occupied"></span>{{ stats.occupied }} Occupé</div>
            <div class="stat-total">Total: {{ stats.total }} boxes</div>
            <div class="divider"></div>
            <div class="stat">Calque actif: {{ layers.find(l => l.id === activeLayer)?.name }}</div>
            <div class="stat">{{ selectedElements.length }} sélectionné(s)</div>
            <div class="stat">Historique: {{ historyIndex + 1 }}/{{ history.length }}</div>
        </footer>

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

        <!-- Keyboard shortcuts help -->
        <div class="shortcuts-hint">
            <small>V: Sélection | B: Box | W: Mur | M: Mesure | G: Grille | L: Calques | [/]: Rotation | Shift+Drag: Multi-sélection</small>
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
    font-family: 'Open Sans', -apple-system, sans-serif;
    z-index: 9999;
}

.editor-fullscreen.dark-mode {
    background: #1a1a1a;
    color: #e0e0e0;
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
.sidebar-header .logo { color: #3498db; font-size: 22px; font-weight: 700; }
.close-menu { background: none; border: none; color: #fff; font-size: 28px; cursor: pointer; }
.sidebar-nav { padding: 20px 0; }
.nav-item {
    display: flex; align-items: center; gap: 15px;
    padding: 15px 25px; color: #ecf0f1;
    text-decoration: none; transition: background 0.2s;
}
.nav-item:hover { background: #34495e; }
.overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; }

/* Header */
.editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 8px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: white;
    gap: 6px;
    flex-shrink: 0;
    flex-wrap: wrap;
}

.dark-mode .editor-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
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
    padding: 6px 10px; border-radius: 4px;
    cursor: pointer; font-size: 14px;
}

.editor-header h1 { font-size: 13px; font-weight: 600; margin: 0; }

.site-select {
    padding: 4px 8px; border-radius: 4px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white; font-size: 12px;
}

.divider {
    width: 1px;
    height: 20px;
    background: rgba(255,255,255,0.3);
    margin: 0 4px;
}

/* Tools */
.tools-bar {
    display: flex; gap: 2px;
    background: rgba(0,0,0,0.2);
    padding: 3px; border-radius: 6px;
}

.tool-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 3px 6px; border: none;
    background: transparent;
    color: rgba(255,255,255,0.7);
    font-size: 9px; border-radius: 4px;
    cursor: pointer; transition: all 0.15s;
    gap: 1px;
}
.tool-btn:hover { background: rgba(255,255,255,0.1); color: white; }
.tool-btn.active { background: #4CAF50; color: white; }
.tool-icon { font-size: 12px; }
.tool-label { font-size: 8px; }

/* Actions */
.actions { display: flex; gap: 2px; }
.action-btn {
    padding: 4px 8px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 3px;
    cursor: pointer; font-size: 12px;
}
.action-btn:hover:not(:disabled) { background: rgba(255,255,255,0.2); }
.action-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.action-btn.danger:hover:not(:disabled) { background: #e53935; }

/* Alignment */
.align-controls, .rotate-controls {
    display: flex; gap: 2px;
}
.align-btn {
    padding: 4px 6px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 3px;
    cursor: pointer; font-size: 11px;
}
.align-btn:hover { background: rgba(255,255,255,0.2); }

/* Zoom */
.zoom-controls {
    display: flex; align-items: center; gap: 3px;
    background: rgba(0,0,0,0.2);
    padding: 3px 6px; border-radius: 4px;
}
.zoom-btn {
    width: 22px; height: 22px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 3px;
    cursor: pointer; font-size: 12px;
}
.zoom-level { width: 40px; text-align: center; font-size: 10px; }

/* Toggle buttons */
.toggle-controls { display: flex; gap: 2px; }
.toggle-btn {
    padding: 4px 6px; border: none;
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 3px;
    cursor: pointer; font-size: 11px;
    opacity: 0.6;
}
.toggle-btn.active { opacity: 1; background: rgba(255,255,255,0.2); }

/* Header buttons */
.header-btn {
    padding: 3px 6px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white; border-radius: 3px;
    cursor: pointer; font-size: 10px;
    text-decoration: none;
}
.header-btn:hover { background: rgba(255,255,255,0.2); }

.save-btn {
    padding: 4px 10px; border: none;
    background: #4CAF50; color: white;
    border-radius: 4px; cursor: pointer;
    font-size: 11px; font-weight: 600;
}
.save-btn:hover:not(:disabled) { background: #43A047; }

/* Measure display */
.measure-display {
    position: fixed;
    top: 60px;
    left: 50%;
    transform: translateX(-50%);
    background: #f44336;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

/* Body */
.editor-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    position: relative;
}

/* Layers Panel */
.layers-panel {
    width: 160px;
    background: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

.dark-mode .layers-panel {
    background: #2d2d2d;
    border-color: #444;
}

.panel-header {
    padding: 10px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #1e3a5f, #2d5a87);
    color: white;
}
.panel-header h3 { margin: 0; font-size: 13px; }

.layer-list { flex: 1; overflow-y: auto; }

.layer-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    cursor: pointer;
    border-bottom: 1px solid #eee;
    transition: background 0.15s;
}
.layer-item:hover { background: #f5f5f5; }
.layer-item.active { background: #e3f2fd; }

.dark-mode .layer-item { border-color: #444; }
.dark-mode .layer-item:hover { background: #3d3d3d; }
.dark-mode .layer-item.active { background: #1e3a5f; }

.layer-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
    border: 1px solid #999;
}

.layer-name {
    flex: 1;
    font-size: 11px;
    font-weight: 500;
}

.layer-actions {
    display: flex;
    gap: 4px;
}
.layer-actions button {
    background: none;
    border: none;
    cursor: pointer;
    font-size: 12px;
    opacity: 0.5;
    padding: 2px;
}
.layer-actions button.active { opacity: 1; }

.layer-actions-bar {
    padding: 8px;
    border-top: 1px solid #eee;
    font-size: 11px;
}
.layer-actions-bar select {
    width: 100%;
    padding: 4px;
    margin-top: 4px;
    font-size: 11px;
}

/* Box Palette */
.box-palette {
    width: 150px;
    background: #fff;
    border-right: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
}

.dark-mode .box-palette {
    background: #2d2d2d;
    border-color: #444;
}

.palette-header {
    padding: 10px;
    border-bottom: 1px solid #eee;
    background: linear-gradient(135deg, #1e3a5f, #2d5a87);
    color: white;
}
.palette-header h3 { margin: 0; font-size: 13px; }

.palette-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
    padding: 8px;
}

.palette-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 4px;
    border: 2px solid transparent;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.15s;
    background: #f8f9fa;
}
.palette-item:hover { background: #e3f2fd; border-color: #90caf9; }
.palette-item.selected { background: #bbdefb; border-color: #2196F3; }

.dark-mode .palette-item { background: #3d3d3d; }

.palette-box {
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #333;
    border-radius: 2px;
    color: white;
    font-size: 8px;
    font-weight: bold;
}

.palette-label { font-size: 8px; color: #666; margin-top: 2px; }
.dark-mode .palette-label { color: #aaa; }

.palette-actions { padding: 6px; }
.palette-btn {
    width: 100%;
    padding: 6px;
    background: #ef5350;
    color: white;
    border: none;
    border-radius: 3px;
    font-size: 10px;
    cursor: pointer;
}

.palette-section {
    padding: 8px;
    border-top: 1px solid #eee;
}
.palette-section h4 { margin: 0 0 8px; font-size: 11px; color: #666; }

.file-input {
    width: 100%;
    font-size: 10px;
    margin-bottom: 8px;
}

.bg-controls {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.bg-controls label { font-size: 10px; }
.bg-controls input[type="range"] { width: 100%; }
.btn-small {
    padding: 4px 8px;
    font-size: 10px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
}
.btn-small.danger { background: #ef5350; color: white; }

/* Properties panel */
.properties-panel {
    width: 170px;
    background: #fff;
    border-left: 1px solid #ddd;
    padding: 10px;
    overflow-y: auto;
    flex-shrink: 0;
    font-size: 11px;
}

.dark-mode .properties-panel {
    background: #2d2d2d;
    border-color: #444;
}

.properties-panel h3 { margin: 0 0 12px; font-size: 13px; color: #1e3a5f; }
.dark-mode .properties-panel h3 { color: #90caf9; }

.prop-group { margin-bottom: 10px; }
.prop-group label {
    display: block; font-size: 10px;
    color: #666; margin-bottom: 3px;
    text-transform: uppercase;
}
.prop-group input, .prop-group select {
    width: 100%; padding: 5px 6px;
    border: 1px solid #ddd; border-radius: 3px;
    font-size: 12px;
}
.prop-group input[type="color"] { height: 28px; padding: 2px; }
.prop-value { font-size: 12px; font-weight: 500; text-transform: capitalize; }
.prop-row { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }

.prop-actions {
    margin-top: 10px;
}
.prop-actions button {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    background: #f5f5f5;
    border-radius: 3px;
    font-size: 11px;
    cursor: pointer;
}
.prop-actions button.active { background: #fff3e0; border-color: #ff9800; }

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
    position: relative;
}

.dark-mode .canvas-wrapper { background: #1a1a1a; }

.canvas-wrapper.template-mode { cursor: copy; }
.canvas-wrapper.measure-mode { cursor: crosshair; }

/* Rulers */
.ruler {
    position: absolute;
    background: #f5f5f5;
    z-index: 10;
}
.ruler-h {
    top: 0;
    left: 20px;
    right: 0;
    height: 20px;
    border-bottom: 1px solid #ddd;
}
.ruler-v {
    top: 20px;
    left: 0;
    bottom: 0;
    width: 20px;
    border-right: 1px solid #ddd;
}

.plan-svg {
    width: 100%;
    height: 100%;
    max-height: calc(100vh - 120px);
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

.resize-handle {
    fill: #3b82f6;
    stroke: white;
    stroke-width: 1;
}

/* Stats bar */
.stats-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 6px 16px;
    background: #fff;
    border-top: 1px solid #ddd;
    font-size: 11px;
}

.dark-mode .stats-bar {
    background: #2d2d2d;
    border-color: #444;
}

.stat { display: flex; align-items: center; gap: 4px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot.available { background: #4CAF50; }
.dot.occupied { background: #2196F3; }
.stat-total { font-weight: 600; }

/* Shortcuts hint */
.shortcuts-hint {
    position: fixed;
    bottom: 40px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 10px;
    opacity: 0.6;
    z-index: 100;
}

/* Box list panel */
.box-list-panel {
    width: 200px;
    background: #fff;
    border-left: 1px solid #ddd;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
}

/* Modals */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex; align-items: center; justify-content: center;
    z-index: 10002;
}

.modal {
    background: #fff;
    border-radius: 10px;
    width: 100%; max-width: 400px;
    max-height: 90vh; overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 18px;
    border-bottom: 1px solid #eee;
}
.modal-header h2 { margin: 0; font-size: 16px; }
.modal-header button { background: none; border: none; font-size: 18px; cursor: pointer; }

.modal-body { padding: 16px; overflow-y: auto; }
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 18px;
    border-top: 1px solid #eee;
}

.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; margin-bottom: 4px; }
.form-group input, .form-group select {
    width: 100%; padding: 6px 10px;
    border: 1px solid #ddd; border-radius: 4px;
    font-size: 13px;
}
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

.preview-text {
    margin-top: 14px; padding: 10px;
    background: #f5f5f5; border-radius: 4px;
    font-size: 13px; text-align: center;
}

.btn-cancel {
    padding: 8px 16px;
    border: 1px solid #ddd; background: #fff;
    border-radius: 4px; cursor: pointer; font-size: 13px;
}

.btn-primary {
    padding: 8px 16px;
    border: none; background: #2563eb;
    color: #fff; border-radius: 4px;
    cursor: pointer; font-size: 13px; font-weight: 500;
}
.btn-primary:hover { background: #1d4ed8; }
</style>
