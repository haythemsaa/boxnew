<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    boxes: Array,
    elements: Array,
    configuration: Object,
    statistics: Object,
    floors: Array,
});

// Core state
const selectedSite = ref(props.currentSite?.id);
const selectedFloor = ref(null);
const showSidebar = ref(false);
const isFullscreen = ref(false);
const isLoading = ref(false);

// Zoom & Pan state
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const isPinching = ref(false);
const dragStart = ref({ x: 0, y: 0, px: 0, py: 0 });
const lastPinchDistance = ref(0);

// Filter state
const showFilters = ref(false);
const filters = ref({
    status: [],
    sizeRange: [0, 200],
    priceRange: [0, 1000],
    search: '',
    hasContract: null,
});

// Popup & Modal state
const showPopup = ref(false);
const popupBox = ref(null);
const popupPosition = ref({ x: 0, y: 0 });
const showModal = ref(false);
const modalBox = ref(null);
const showQuickActions = ref(false);

// Real-time state
const liveUpdates = ref(true);
const lastUpdate = ref(new Date());
const recentChanges = ref([]);

// Minimap
const showMinimap = ref(true);
const minimapScale = 0.1;

// Touch support
const touchStartTime = ref(0);
const touchStartPos = ref({ x: 0, y: 0 });

// SVG dimensions
const svgWidth = 1200;
const svgHeight = 700;

// Status configuration
const statusConfig = {
    available: { color: '#10B981', label: 'Libre', icon: 'check-circle' },
    occupied: { color: '#3B82F6', label: 'Occupé', icon: 'user' },
    reserved: { color: '#F59E0B', label: 'Réservé', icon: 'clock' },
    ending: { color: '#FBBF24', label: 'Fin de contrat', icon: 'calendar-x' },
    litigation: { color: '#8B5CF6', label: 'Litige', icon: 'alert-triangle' },
    maintenance: { color: '#EF4444', label: 'Maintenance', icon: 'tool' },
    unavailable: { color: '#6B7280', label: 'Indisponible', icon: 'x-circle' },
};

// Computed: Box layout with filters applied
const boxLayout = computed(() => {
    let boxes = [];

    if (props.elements && props.elements.length > 0) {
        boxes = props.elements.map(el => ({
            id: el.id,
            name: el.name || el.label || el.box?.number || '',
            x: el.x,
            y: el.y,
            w: el.w || el.width || 35,
            h: el.h || el.height || 30,
            vol: el.vol || el.volume || el.box?.volume || 0,
            price: el.price || el.box?.current_price || 0,
            status: el.status || 'available',
            type: el.type || 'box',
            isLift: el.type === 'lift',
            boxId: el.boxId || el.box?.id,
            floor: el.floor || 0,
            contract: el.contract,
            customer: el.contract?.customer,
            endDate: el.contract?.end_date,
            features: el.features || [],
        }));
    } else {
        boxes = generateDemoLayout();
    }

    // Apply floor filter
    if (selectedFloor.value !== null) {
        boxes = boxes.filter(b => b.floor === selectedFloor.value);
    }

    // Apply filters
    if (filters.value.status.length > 0) {
        boxes = boxes.filter(b => filters.value.status.includes(b.status));
    }

    if (filters.value.search) {
        const search = filters.value.search.toLowerCase();
        boxes = boxes.filter(b =>
            b.name.toLowerCase().includes(search) ||
            b.customer?.name?.toLowerCase().includes(search)
        );
    }

    if (filters.value.sizeRange[0] > 0 || filters.value.sizeRange[1] < 200) {
        boxes = boxes.filter(b => b.vol >= filters.value.sizeRange[0] && b.vol <= filters.value.sizeRange[1]);
    }

    if (filters.value.hasContract === true) {
        boxes = boxes.filter(b => b.contract);
    } else if (filters.value.hasContract === false) {
        boxes = boxes.filter(b => !b.contract);
    }

    return boxes;
});

// Statistics
const stats = computed(() => {
    const all = boxLayout.value.filter(b => !b.isLift);
    return {
        total: all.length,
        available: all.filter(b => b.status === 'available').length,
        occupied: all.filter(b => b.status === 'occupied').length,
        reserved: all.filter(b => b.status === 'reserved').length,
        ending: all.filter(b => b.status === 'ending').length,
        maintenance: all.filter(b => b.status === 'maintenance').length,
        occupancyRate: all.length > 0
            ? Math.round((all.filter(b => ['occupied', 'reserved', 'ending'].includes(b.status)).length / all.length) * 100)
            : 0,
        totalVolume: all.reduce((sum, b) => sum + (b.vol || 0), 0),
        availableVolume: all.filter(b => b.status === 'available').reduce((sum, b) => sum + (b.vol || 0), 0),
    };
});

// Generate demo layout
function generateDemoLayout() {
    const boxes = [];
    const statuses = ['available', 'occupied', 'occupied', 'occupied', 'reserved', 'ending', 'occupied'];
    let id = 0;

    const getStatus = () => statuses[Math.floor(Math.random() * statuses.length)];
    const getContract = (status) => status === 'occupied' || status === 'ending' ? {
        id: Math.floor(Math.random() * 1000) + 1,
        contract_number: `CO${2024}${String(Math.floor(Math.random() * 100000)).padStart(5, '0')}`,
        start_date: `2024-${String(Math.floor(Math.random() * 12) + 1).padStart(2, '0')}-01`,
        end_date: status === 'ending' ? `2025-${String(Math.floor(Math.random() * 2) + 1).padStart(2, '0')}-15` : null,
        customer: {
            id: Math.floor(Math.random() * 500) + 1,
            name: ['Martin Dupont', 'Marie Dubois', 'Jean Lefebvre', 'Sophie Bernard', 'Pierre Moreau'][Math.floor(Math.random() * 5)]
        }
    } : null;

    // Create grid layout
    const columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    const rowsPerColumn = 10;
    const boxW = 45, boxH = 38, gapX = 4, gapY = 4;
    let startX = 80;

    columns.forEach((col, colIdx) => {
        for (let row = 0; row < rowsPerColumn; row++) {
            const status = getStatus();
            const x = startX + colIdx * (boxW + gapX + (colIdx % 2 === 0 ? 0 : 20));
            const y = 100 + row * (boxH + gapY);

            boxes.push({
                id: id++,
                name: `${col}${String(row + 1).padStart(2, '0')}`,
                x,
                y,
                w: boxW,
                h: boxH,
                vol: [6, 9, 12, 18, 25][Math.floor(Math.random() * 5)],
                price: [89, 129, 179, 249, 299][Math.floor(Math.random() * 5)],
                status,
                contract: getContract(status),
                customer: getContract(status)?.customer,
                floor: 0,
            });
        }

        // Add corridor every 2 columns
        if ((colIdx + 1) % 2 === 0 && colIdx < columns.length - 1) {
            startX += 25;
        }
    });

    // Add lifts
    boxes.push({ id: id++, name: 'LIFT', x: 80, y: 520, w: 60, h: 45, vol: 0, status: 'unavailable', isLift: true });
    boxes.push({ id: id++, name: 'LIFT', x: 550, y: 520, w: 60, h: 45, vol: 0, status: 'unavailable', isLift: true });

    return boxes;
}

// Get box color with optional highlighting
function getBoxColor(box, highlight = false) {
    if (box.isLift) return '#F3F4F6';
    const baseColor = statusConfig[box.status]?.color || '#6B7280';
    if (highlight) return baseColor;
    return baseColor;
}

// Get text color based on status
function getTextColor(status) {
    return ['ending'].includes(status) ? '#1F2937' : '#FFFFFF';
}

// Event handlers
function onSvgMouseDown(e) {
    if (e.target.closest('.box-group')) return;
    isDragging.value = true;
    dragStart.value = {
        x: e.clientX,
        y: e.clientY,
        px: panX.value,
        py: panY.value
    };
}

function onSvgMouseMove(e) {
    if (isDragging.value) {
        const dx = e.clientX - dragStart.value.x;
        const dy = e.clientY - dragStart.value.y;
        panX.value = dragStart.value.px + dx;
        panY.value = dragStart.value.py + dy;
    }

    if (showPopup.value && popupBox.value) {
        updatePopupPosition(e);
    }
}

function onSvgMouseUp() {
    isDragging.value = false;
}

// Touch handlers
function onTouchStart(e) {
    if (e.touches.length === 1) {
        touchStartTime.value = Date.now();
        touchStartPos.value = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        dragStart.value = {
            x: e.touches[0].clientX,
            y: e.touches[0].clientY,
            px: panX.value,
            py: panY.value
        };
        isDragging.value = true;
    } else if (e.touches.length === 2) {
        isDragging.value = false;
        isPinching.value = true;
        lastPinchDistance.value = getPinchDistance(e.touches);
    }
}

function onTouchMove(e) {
    if (isDragging.value && e.touches.length === 1) {
        const dx = e.touches[0].clientX - dragStart.value.x;
        const dy = e.touches[0].clientY - dragStart.value.y;
        panX.value = dragStart.value.px + dx;
        panY.value = dragStart.value.py + dy;
    } else if (isPinching.value && e.touches.length === 2) {
        const distance = getPinchDistance(e.touches);
        const scale = distance / lastPinchDistance.value;
        zoom.value = Math.min(Math.max(zoom.value * scale, 0.3), 3);
        lastPinchDistance.value = distance;
    }
}

function onTouchEnd(e) {
    if (e.touches.length === 0) {
        const duration = Date.now() - touchStartTime.value;
        if (duration < 200) {
            // Tap - could trigger popup/modal
        }
        isDragging.value = false;
        isPinching.value = false;
    }
}

function getPinchDistance(touches) {
    const dx = touches[0].clientX - touches[1].clientX;
    const dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
}

// Wheel zoom
function onWheel(e) {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.3), 3);
}

// Box interaction handlers
function handleBoxMouseEnter(box, event) {
    if (box.isLift || isDragging.value) return;
    popupBox.value = box;
    updatePopupPosition(event);
    showPopup.value = true;
}

function handleBoxMouseLeave() {
    showPopup.value = false;
    popupBox.value = null;
}

function handleBoxClick(box) {
    if (box.isLift) return;
    modalBox.value = box;
    showModal.value = true;
    showPopup.value = false;
}

function updatePopupPosition(event) {
    const container = document.getElementById('plan-container');
    if (!container) return;
    const rect = container.getBoundingClientRect();
    let x = event.clientX - rect.left + 15;
    let y = event.clientY - rect.top + 15;
    if (x + 340 > rect.width) x = event.clientX - rect.left - 340;
    if (y + 200 > rect.height) y = event.clientY - rect.top - 200;
    popupPosition.value = { x: Math.max(10, x), y: Math.max(10, y) };
}

// Navigation
function changeSite() {
    isLoading.value = true;
    router.get(route('tenant.plan.interactive-enhanced'), { site_id: selectedSite.value }, {
        onFinish: () => { isLoading.value = false; }
    });
}

function changeFloor(floor) {
    selectedFloor.value = floor === selectedFloor.value ? null : floor;
}

function goToCustomer(customerId) {
    if (customerId) router.visit(route('tenant.customers.show', customerId));
}

function goToContract(contractId) {
    if (contractId) router.visit(route('tenant.contracts.show', contractId));
}

function goToBox(boxId) {
    if (boxId) router.visit(route('tenant.boxes.show', boxId));
}

function createContract(boxId) {
    if (boxId) router.visit(route('tenant.contracts.create', { box_id: boxId }));
}

// Zoom controls
function zoomIn() { zoom.value = Math.min(zoom.value * 1.2, 3); }
function zoomOut() { zoom.value = Math.max(zoom.value / 1.2, 0.3); }
function resetView() { zoom.value = 1; panX.value = 0; panY.value = 0; }

function fitToScreen() {
    const container = document.getElementById('plan-container');
    if (container) {
        const scaleX = (container.clientWidth - 40) / svgWidth;
        const scaleY = (container.clientHeight - 40) / svgHeight;
        zoom.value = Math.min(scaleX, scaleY, 1.2);
        panX.value = 0;
        panY.value = 0;
    }
}

// Fullscreen
function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    if (isFullscreen.value) {
        document.documentElement.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
}

// Filter management
function toggleStatusFilter(status) {
    const idx = filters.value.status.indexOf(status);
    if (idx > -1) {
        filters.value.status.splice(idx, 1);
    } else {
        filters.value.status.push(status);
    }
}

function clearFilters() {
    filters.value = {
        status: [],
        sizeRange: [0, 200],
        priceRange: [0, 1000],
        search: '',
        hasContract: null,
    };
}

// Format helpers
function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price || 0);
}

function formatVolume(vol) {
    return `${vol || 0} m³`;
}

// Minimap click navigation
function onMinimapClick(e) {
    const minimap = e.currentTarget;
    const rect = minimap.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;

    // Convert to pan values
    const container = document.getElementById('plan-container');
    if (container) {
        panX.value = -(x * svgWidth * zoom.value - container.clientWidth / 2);
        panY.value = -(y * svgHeight * zoom.value - container.clientHeight / 2);
    }
}

// Highlight box by name
function highlightBox(name) {
    const box = boxLayout.value.find(b => b.name === name);
    if (box) {
        // Center view on box
        const container = document.getElementById('plan-container');
        if (container) {
            panX.value = -(box.x * zoom.value - container.clientWidth / 2 + box.w * zoom.value / 2);
            panY.value = -(box.y * zoom.value - container.clientHeight / 2 + box.h * zoom.value / 2);
        }
        // Open modal
        modalBox.value = box;
        showModal.value = true;
    }
}

// Keyboard shortcuts
function onKeyDown(e) {
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

    switch(e.key) {
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
        case '0':
            resetView();
            break;
        case 'f':
            fitToScreen();
            break;
        case 'Escape':
            showModal.value = false;
            showFilters.value = false;
            break;
        case 'm':
            showMinimap.value = !showMinimap.value;
            break;
    }
}

// Real-time updates simulation
let updateInterval = null;

function startRealTimeUpdates() {
    if (updateInterval) return;
    updateInterval = setInterval(() => {
        if (liveUpdates.value) {
            lastUpdate.value = new Date();
            // In production, this would fetch real updates via WebSocket or polling
        }
    }, 30000);
}

function stopRealTimeUpdates() {
    if (updateInterval) {
        clearInterval(updateInterval);
        updateInterval = null;
    }
}

// Lifecycle
onMounted(() => {
    window.addEventListener('keydown', onKeyDown);
    startRealTimeUpdates();
    nextTick(() => fitToScreen());
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    stopRealTimeUpdates();
});

// Watch for fullscreen changes
watch(isFullscreen, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});
</script>

<template>
    <div :class="['plan-enhanced', { fullscreen: isFullscreen }]">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ open: showSidebar }">
            <div class="sidebar-header">
                <span class="logo">
                    <span class="logo-icon">📦</span>
                    BoxiBox
                </span>
                <button @click="showSidebar = false" class="close-btn">&times;</button>
            </div>
            <nav class="sidebar-nav">
                <Link :href="route('tenant.dashboard')" class="nav-item">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </Link>
                <Link :href="route('tenant.boxes.index')" class="nav-item">
                    <i class="fas fa-box"></i>
                    <span>Boxes</span>
                </Link>
                <Link :href="route('tenant.contracts.index')" class="nav-item">
                    <i class="fas fa-file-contract"></i>
                    <span>Contrats</span>
                </Link>
                <Link :href="route('tenant.customers.index')" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Clients</span>
                </Link>
                <Link :href="route('tenant.plan.editor')" class="nav-item editor-link">
                    <i class="fas fa-pencil-ruler"></i>
                    <span>Éditeur de Plan</span>
                </Link>
            </nav>
        </aside>
        <div v-if="showSidebar" class="sidebar-overlay" @click="showSidebar = false"></div>

        <!-- Header -->
        <header class="plan-header">
            <div class="header-left">
                <button @click="showSidebar = true" class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Plan Interactif</h1>
                <select v-model="selectedSite" @change="changeSite" class="site-select">
                    <option v-for="site in sites" :key="site.id" :value="site.id">
                        {{ site.name }}
                    </option>
                </select>
            </div>

            <div class="header-stats">
                <div class="stat-pill available">
                    <span class="stat-dot"></span>
                    <span class="stat-value">{{ stats.available }}</span>
                    <span class="stat-label">Libres</span>
                </div>
                <div class="stat-pill occupied">
                    <span class="stat-dot"></span>
                    <span class="stat-value">{{ stats.occupied }}</span>
                    <span class="stat-label">Occupés</span>
                </div>
                <div class="stat-pill occupancy">
                    <span class="stat-value">{{ stats.occupancyRate }}%</span>
                    <span class="stat-label">Taux</span>
                </div>
            </div>

            <div class="header-actions">
                <button @click="showFilters = !showFilters" :class="['action-btn', { active: showFilters || filters.status.length > 0 }]">
                    <i class="fas fa-filter"></i>
                    <span class="btn-text">Filtres</span>
                    <span v-if="filters.status.length > 0" class="filter-badge">{{ filters.status.length }}</span>
                </button>

                <div class="zoom-controls">
                    <button @click="zoomOut" class="zoom-btn" title="Zoom arrière (-)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="zoom-level">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="zoom-btn" title="Zoom avant (+)">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button @click="fitToScreen" class="zoom-btn" title="Ajuster à l'écran (F)">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                    <button @click="resetView" class="zoom-btn" title="Réinitialiser (0)">
                        <i class="fas fa-redo"></i>
                    </button>
                </div>

                <button @click="toggleFullscreen" class="action-btn" :title="isFullscreen ? 'Quitter plein écran' : 'Plein écran'">
                    <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>

                <Link :href="route('tenant.plan.editor')" class="editor-btn">
                    <i class="fas fa-pencil-ruler"></i>
                    <span class="btn-text">Éditer</span>
                </Link>
            </div>
        </header>

        <!-- Filter Panel -->
        <div v-if="showFilters" class="filter-panel">
            <div class="filter-header">
                <h3><i class="fas fa-filter"></i> Filtres</h3>
                <button @click="clearFilters" class="clear-btn" v-if="filters.status.length > 0">
                    Effacer tout
                </button>
            </div>

            <div class="filter-section">
                <label>Rechercher</label>
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Nom du box ou client..."
                    />
                </div>
            </div>

            <div class="filter-section">
                <label>Statut</label>
                <div class="status-filters">
                    <button
                        v-for="(config, status) in statusConfig"
                        :key="status"
                        @click="toggleStatusFilter(status)"
                        :class="['status-filter-btn', { active: filters.status.includes(status) }]"
                        :style="{
                            '--status-color': config.color,
                            borderColor: filters.status.includes(status) ? config.color : 'transparent'
                        }"
                    >
                        <span class="status-dot" :style="{ background: config.color }"></span>
                        {{ config.label }}
                    </button>
                </div>
            </div>

            <div class="filter-section">
                <label>Avec contrat</label>
                <div class="contract-filters">
                    <button
                        @click="filters.hasContract = filters.hasContract === true ? null : true"
                        :class="['contract-filter-btn', { active: filters.hasContract === true }]"
                    >
                        <i class="fas fa-check"></i> Oui
                    </button>
                    <button
                        @click="filters.hasContract = filters.hasContract === false ? null : false"
                        :class="['contract-filter-btn', { active: filters.hasContract === false }]"
                    >
                        <i class="fas fa-times"></i> Non
                    </button>
                </div>
            </div>

            <div class="filter-results">
                <span>{{ boxLayout.filter(b => !b.isLift).length }} boxes affichés</span>
            </div>
        </div>

        <!-- Floor selector -->
        <div v-if="floors && floors.length > 1" class="floor-selector">
            <button
                v-for="floor in floors"
                :key="floor.id"
                @click="changeFloor(floor.id)"
                :class="['floor-btn', { active: selectedFloor === floor.id }]"
            >
                {{ floor.name || `Étage ${floor.level}` }}
            </button>
            <button
                @click="selectedFloor = null"
                :class="['floor-btn', { active: selectedFloor === null }]"
            >
                Tous
            </button>
        </div>

        <!-- Legend -->
        <div class="legend-bar">
            <div class="legend-item" v-for="(config, status) in statusConfig" :key="status">
                <span class="legend-dot" :style="{ background: config.color }"></span>
                <span class="legend-label">{{ config.label }}</span>
            </div>
        </div>

        <!-- Main Plan Container -->
        <div
            id="plan-container"
            class="plan-container"
            @mousedown="onSvgMouseDown"
            @mousemove="onSvgMouseMove"
            @mouseup="onSvgMouseUp"
            @mouseleave="onSvgMouseUp"
            @wheel="onWheel"
            @touchstart="onTouchStart"
            @touchmove="onTouchMove"
            @touchend="onTouchEnd"
        >
            <!-- Loading overlay -->
            <div v-if="isLoading" class="loading-overlay">
                <div class="spinner"></div>
                <span>Chargement...</span>
            </div>

            <svg
                id="plan-svg"
                :viewBox="`0 0 ${svgWidth} ${svgHeight}`"
                preserveAspectRatio="xMidYMid meet"
                class="plan-svg"
                :style="{
                    transform: `translate(${panX}px, ${panY}px) scale(${zoom})`,
                    cursor: isDragging ? 'grabbing' : 'grab'
                }"
            >
                <!-- Background -->
                <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="#F9FAFB"/>

                <!-- Grid pattern -->
                <defs>
                    <pattern id="smallGrid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#E5E7EB" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="url(#smallGrid)"/>

                <!-- Border -->
                <rect x="40" y="60" :width="svgWidth - 80" :height="svgHeight - 100"
                      fill="none" stroke="#D1D5DB" stroke-width="2" rx="8"/>

                <!-- Boxes -->
                <g
                    v-for="box in boxLayout"
                    :key="box.id"
                    class="box-group"
                    @mouseenter="handleBoxMouseEnter(box, $event)"
                    @mouseleave="handleBoxMouseLeave"
                    @click="handleBoxClick(box)"
                >
                    <!-- Box rect -->
                    <rect
                        :x="box.x"
                        :y="box.y"
                        :width="box.w"
                        :height="box.h"
                        :fill="getBoxColor(box)"
                        :stroke="box.isLift ? '#9CA3AF' : '#374151'"
                        stroke-width="1"
                        rx="3"
                        class="box-rect"
                        :class="{ lift: box.isLift, 'ending-soon': box.status === 'ending' }"
                    />

                    <!-- Box name -->
                    <text
                        :x="box.x + box.w/2"
                        :y="box.y + box.h/2 - (box.h > 28 && box.vol ? 5 : 0)"
                        :fill="getTextColor(box.status)"
                        class="box-name"
                    >
                        {{ box.name }}
                    </text>

                    <!-- Volume -->
                    <text
                        v-if="box.vol && box.h > 28 && !box.isLift"
                        :x="box.x + box.w/2"
                        :y="box.y + box.h/2 + 8"
                        :fill="getTextColor(box.status)"
                        class="box-vol"
                    >
                        {{ box.vol }}m³
                    </text>

                    <!-- Ending indicator -->
                    <circle
                        v-if="box.status === 'ending'"
                        :cx="box.x + box.w - 6"
                        :cy="box.y + 6"
                        r="4"
                        fill="#EF4444"
                        class="ending-indicator"
                    />
                </g>
            </svg>

            <!-- Hover Popup -->
            <Transition name="popup">
                <div
                    v-if="showPopup && popupBox"
                    class="popup"
                    :style="{ left: popupPosition.x + 'px', top: popupPosition.y + 'px' }"
                >
                    <div class="popup-header" :style="{ background: getBoxColor(popupBox) }">
                        <span class="popup-title">Box {{ popupBox.name }}</span>
                        <span class="popup-status">{{ statusConfig[popupBox.status]?.label }}</span>
                    </div>
                    <div class="popup-body">
                        <div class="popup-row">
                            <span class="popup-label">Volume</span>
                            <span class="popup-value">{{ formatVolume(popupBox.vol) }}</span>
                        </div>
                        <div class="popup-row" v-if="popupBox.price">
                            <span class="popup-label">Prix</span>
                            <span class="popup-value">{{ formatPrice(popupBox.price) }}/mois</span>
                        </div>
                        <template v-if="popupBox.contract">
                            <div class="popup-divider"></div>
                            <div class="popup-row">
                                <span class="popup-label">Contrat</span>
                                <span class="popup-value link">{{ popupBox.contract.contract_number }}</span>
                            </div>
                            <div class="popup-row" v-if="popupBox.customer">
                                <span class="popup-label">Client</span>
                                <span class="popup-value link">{{ popupBox.customer.name }}</span>
                            </div>
                            <div class="popup-row" v-if="popupBox.endDate">
                                <span class="popup-label">Fin</span>
                                <span class="popup-value warning">{{ formatDate(popupBox.endDate) }}</span>
                            </div>
                        </template>
                    </div>
                    <div class="popup-footer">
                        Cliquez pour plus de détails
                    </div>
                </div>
            </Transition>

            <!-- Minimap -->
            <div v-if="showMinimap" class="minimap" @click="onMinimapClick">
                <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" preserveAspectRatio="xMidYMid meet">
                    <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="#F3F4F6"/>
                    <rect
                        v-for="box in boxLayout"
                        :key="'mini-' + box.id"
                        :x="box.x"
                        :y="box.y"
                        :width="box.w"
                        :height="box.h"
                        :fill="getBoxColor(box)"
                    />
                </svg>
                <div
                    class="minimap-viewport"
                    :style="{
                        left: (-panX / zoom / svgWidth * 100) + '%',
                        top: (-panY / zoom / svgHeight * 100) + '%',
                        width: (100 / zoom) + '%',
                        height: (100 / zoom) + '%'
                    }"
                ></div>
                <button @click.stop="showMinimap = false" class="minimap-close">×</button>
            </div>

            <!-- Minimap toggle button (when hidden) -->
            <button v-if="!showMinimap" @click="showMinimap = true" class="minimap-toggle">
                <i class="fas fa-map"></i>
            </button>
        </div>

        <!-- Detail Modal -->
        <Transition name="modal">
            <div v-if="showModal && modalBox" class="modal-overlay" @click.self="showModal = false">
                <div class="modal">
                    <div class="modal-header" :style="{ background: getBoxColor(modalBox) }">
                        <div class="modal-title-group">
                            <h2>Box {{ modalBox.name }}</h2>
                            <span class="modal-status-badge">
                                {{ statusConfig[modalBox.status]?.label }}
                            </span>
                        </div>
                        <button @click="showModal = false" class="modal-close">&times;</button>
                    </div>

                    <div class="modal-body">
                        <!-- Box Info -->
                        <div class="modal-section">
                            <h3>Informations</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Volume</span>
                                    <span class="info-value">{{ formatVolume(modalBox.vol) }}</span>
                                </div>
                                <div class="info-item" v-if="modalBox.price">
                                    <span class="info-label">Prix mensuel</span>
                                    <span class="info-value">{{ formatPrice(modalBox.price) }}</span>
                                </div>
                                <div class="info-item" v-if="modalBox.floor !== undefined">
                                    <span class="info-label">Étage</span>
                                    <span class="info-value">{{ modalBox.floor }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Contract Info -->
                        <div class="modal-section" v-if="modalBox.contract">
                            <h3>Contrat actif</h3>
                            <div class="contract-card" @click="goToContract(modalBox.contract.id)">
                                <div class="contract-number">
                                    <i class="fas fa-file-contract"></i>
                                    {{ modalBox.contract.contract_number }}
                                </div>
                                <div class="contract-dates">
                                    <span>Début: {{ formatDate(modalBox.contract.start_date) }}</span>
                                    <span v-if="modalBox.endDate" class="end-date">
                                        Fin: {{ formatDate(modalBox.endDate) }}
                                    </span>
                                </div>
                            </div>

                            <div class="customer-card" v-if="modalBox.customer" @click="goToCustomer(modalBox.customer.id)">
                                <div class="customer-avatar">
                                    {{ modalBox.customer.name?.charAt(0) || '?' }}
                                </div>
                                <div class="customer-info">
                                    <span class="customer-name">{{ modalBox.customer.name }}</span>
                                    <span class="customer-action">Voir le profil →</span>
                                </div>
                            </div>
                        </div>

                        <!-- Available Box -->
                        <div class="modal-section available-section" v-if="modalBox.status === 'available'">
                            <div class="available-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Ce box est disponible</span>
                            </div>
                            <p class="available-text">
                                Vous pouvez créer un nouveau contrat pour ce box.
                            </p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button v-if="modalBox.boxId" @click="goToBox(modalBox.boxId)" class="btn btn-secondary">
                            <i class="fas fa-box"></i> Détails du box
                        </button>
                        <template v-if="modalBox.status === 'available'">
                            <button @click="createContract(modalBox.boxId)" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Créer un contrat
                            </button>
                        </template>
                        <template v-else-if="modalBox.contract">
                            <button @click="goToContract(modalBox.contract.id)" class="btn btn-primary">
                                <i class="fas fa-file-contract"></i> Voir le contrat
                            </button>
                        </template>
                        <button @click="showModal = false" class="btn btn-ghost">Fermer</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Real-time indicator -->
        <div class="realtime-indicator" v-if="liveUpdates">
            <span class="pulse"></span>
            <span class="realtime-text">Temps réel</span>
        </div>

        <!-- Keyboard shortcuts hint -->
        <div class="shortcuts-hint">
            <span><kbd>+</kbd>/<kbd>-</kbd> Zoom</span>
            <span><kbd>F</kbd> Ajuster</span>
            <span><kbd>M</kbd> Minimap</span>
            <span><kbd>0</kbd> Reset</span>
        </div>
    </div>
</template>

<style scoped>
/* Base Layout */
.plan-enhanced {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    background: #F3F4F6;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    z-index: 9999;
    overflow: hidden;
}

.plan-enhanced.fullscreen {
    z-index: 99999;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: -300px;
    width: 300px;
    height: 100%;
    background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);
    z-index: 10001;
    transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 4px 0 20px rgba(0,0,0,0.3);
}

.sidebar.open { left: 0; }

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    backdrop-filter: blur(2px);
}

.sidebar-header {
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #fff;
    font-size: 22px;
    font-weight: 700;
}

.logo-icon { font-size: 28px; }

.close-btn {
    background: rgba(255,255,255,0.1);
    border: none;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    font-size: 24px;
    cursor: pointer;
    transition: background 0.2s;
}

.close-btn:hover { background: rgba(255,255,255,0.2); }

.sidebar-nav { padding: 16px 0; }

.nav-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 24px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.2s;
    font-size: 15px;
}

.nav-item:hover {
    background: rgba(255,255,255,0.1);
    color: #fff;
}

.nav-item i { width: 20px; text-align: center; }

.editor-link {
    margin: 16px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    border-radius: 12px;
    justify-content: center;
    padding: 16px;
    color: #fff !important;
}

.editor-link:hover { opacity: 0.9; }

/* Header */
.plan-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    gap: 16px;
    flex-wrap: wrap;
    z-index: 100;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.menu-btn {
    background: #F3F4F6;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    cursor: pointer;
    color: #374151;
    font-size: 18px;
    transition: all 0.2s;
}

.menu-btn:hover { background: #E5E7EB; }

.plan-header h1 {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.site-select {
    padding: 8px 16px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
}

/* Stats */
.header-stats {
    display: flex;
    gap: 12px;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #F9FAFB;
    border-radius: 20px;
    font-size: 13px;
}

.stat-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.stat-pill.available .stat-dot { background: #10B981; }
.stat-pill.occupied .stat-dot { background: #3B82F6; }

.stat-value {
    font-weight: 600;
    color: #111827;
}

.stat-label {
    color: #6B7280;
}

.stat-pill.occupancy {
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #fff;
}

.stat-pill.occupancy .stat-value,
.stat-pill.occupancy .stat-label { color: #fff; }

/* Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: #F3F4F6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
    position: relative;
}

.action-btn:hover { background: #E5E7EB; }
.action-btn.active { background: #3B82F6; color: #fff; }

.filter-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #EF4444;
    color: #fff;
    font-size: 10px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-text {
    display: none;
}

@media (min-width: 768px) {
    .btn-text { display: inline; }
}

/* Zoom controls */
.zoom-controls {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #F3F4F6;
    padding: 4px;
    border-radius: 8px;
}

.zoom-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.zoom-btn:hover { background: #E5E7EB; }

.zoom-level {
    padding: 0 8px;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    min-width: 50px;
    text-align: center;
}

.editor-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.editor-btn:hover { opacity: 0.9; transform: translateY(-1px); }

/* Filter Panel */
.filter-panel {
    position: absolute;
    top: 70px;
    right: 20px;
    width: 320px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    z-index: 200;
    padding: 20px;
    animation: slideIn 0.2s ease;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.filter-header h3 {
    margin: 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clear-btn {
    background: none;
    border: none;
    color: #3B82F6;
    font-size: 13px;
    cursor: pointer;
}

.filter-section {
    margin-bottom: 20px;
}

.filter-section label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.search-input {
    position: relative;
}

.search-input i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
}

.search-input input {
    width: 100%;
    padding: 10px 12px 10px 38px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
}

.status-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.status-filter-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    background: #F9FAFB;
    border: 2px solid transparent;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.status-filter-btn:hover { background: #F3F4F6; }
.status-filter-btn.active { background: #EFF6FF; }

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.contract-filters {
    display: flex;
    gap: 8px;
}

.contract-filter-btn {
    flex: 1;
    padding: 8px;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.contract-filter-btn.active {
    background: #3B82F6;
    color: #fff;
    border-color: #3B82F6;
}

.filter-results {
    padding-top: 12px;
    border-top: 1px solid #E5E7EB;
    font-size: 13px;
    color: #6B7280;
}

/* Floor Selector */
.floor-selector {
    display: flex;
    gap: 8px;
    padding: 8px 20px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
}

.floor-btn {
    padding: 6px 16px;
    background: #F3F4F6;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.floor-btn:hover { background: #E5E7EB; }
.floor-btn.active { background: #3B82F6; color: #fff; }

/* Legend */
.legend-bar {
    display: flex;
    justify-content: center;
    gap: 16px;
    padding: 8px 20px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6B7280;
}

.legend-dot {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

/* Plan Container */
.plan-container {
    flex: 1;
    position: relative;
    overflow: hidden;
    background: #E5E7EB;
}

.loading-overlay {
    position: absolute;
    inset: 0;
    background: rgba(255,255,255,0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    z-index: 100;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #E5E7EB;
    border-top-color: #3B82F6;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.plan-svg {
    width: 100%;
    height: 100%;
    background: #fff;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform-origin: center center;
    transition: transform 0.05s ease-out;
}

/* Box styles */
.box-group {
    cursor: pointer;
}

.box-rect {
    transition: all 0.15s ease;
}

.box-group:hover .box-rect {
    filter: brightness(1.1);
    stroke-width: 2;
}

.box-rect.lift {
    cursor: default;
}

.box-rect.ending-soon {
    animation: pulse-warning 2s ease-in-out infinite;
}

@keyframes pulse-warning {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.ending-indicator {
    animation: blink 1s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.box-name {
    font-size: 10px;
    font-weight: 600;
    text-anchor: middle;
    dominant-baseline: middle;
    pointer-events: none;
}

.box-vol {
    font-size: 8px;
    text-anchor: middle;
    dominant-baseline: middle;
    pointer-events: none;
    opacity: 0.9;
}

/* Popup */
.popup {
    position: absolute;
    z-index: 150;
    width: 280px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    pointer-events: none;
}

.popup-enter-active,
.popup-leave-active {
    transition: all 0.2s ease;
}

.popup-enter-from,
.popup-leave-to {
    opacity: 0;
    transform: scale(0.95);
}

.popup-header {
    padding: 12px 16px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.popup-title {
    font-weight: 600;
    font-size: 15px;
}

.popup-status {
    font-size: 12px;
    opacity: 0.9;
}

.popup-body {
    padding: 12px 16px;
}

.popup-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
}

.popup-label {
    color: #6B7280;
    font-size: 13px;
}

.popup-value {
    font-weight: 500;
    font-size: 13px;
    color: #111827;
}

.popup-value.link { color: #3B82F6; }
.popup-value.warning { color: #F59E0B; }

.popup-divider {
    height: 1px;
    background: #E5E7EB;
    margin: 8px 0;
}

.popup-footer {
    padding: 10px 16px;
    background: #F9FAFB;
    font-size: 12px;
    color: #6B7280;
    text-align: center;
}

/* Minimap */
.minimap {
    position: absolute;
    bottom: 20px;
    right: 20px;
    width: 180px;
    height: 120px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    overflow: hidden;
    z-index: 100;
}

.minimap svg {
    width: 100%;
    height: 100%;
}

.minimap-viewport {
    position: absolute;
    border: 2px solid #3B82F6;
    background: rgba(59, 130, 246, 0.1);
    pointer-events: none;
}

.minimap-close {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 20px;
    height: 20px;
    background: rgba(0,0,0,0.5);
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1;
}

.minimap-toggle {
    position: absolute;
    bottom: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: #fff;
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    cursor: pointer;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #374151;
}

.minimap-toggle:hover { background: #F3F4F6; }

/* Modal */
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10002;
    backdrop-filter: blur(4px);
    padding: 20px;
}

.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal,
.modal-leave-to .modal {
    transform: scale(0.95) translateY(10px);
}

.modal {
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
}

.modal-header {
    padding: 20px 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.modal-title-group h2 {
    margin: 0 0 6px;
    font-size: 24px;
    font-weight: 600;
}

.modal-status-badge {
    display: inline-block;
    padding: 4px 10px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    font-size: 12px;
}

.modal-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: #fff;
    width: 36px;
    height: 36px;
    border-radius: 8px;
    font-size: 24px;
    cursor: pointer;
    transition: background 0.2s;
}

.modal-close:hover { background: rgba(255,255,255,0.3); }

.modal-body {
    padding: 24px;
    overflow-y: auto;
    max-height: 50vh;
}

.modal-section {
    margin-bottom: 24px;
}

.modal-section:last-child { margin-bottom: 0; }

.modal-section h3 {
    margin: 0 0 16px;
    font-size: 14px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.info-item {
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.info-label {
    display: block;
    font-size: 12px;
    color: #6B7280;
    margin-bottom: 4px;
}

.info-value {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.contract-card {
    padding: 16px;
    background: #F9FAFB;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 12px;
}

.contract-card:hover { background: #F3F4F6; }

.contract-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #3B82F6;
    margin-bottom: 8px;
}

.contract-dates {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: #6B7280;
}

.end-date { color: #F59E0B; }

.customer-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #F9FAFB;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.customer-card:hover { background: #F3F4F6; }

.customer-avatar {
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
}

.customer-info {
    flex: 1;
}

.customer-name {
    display: block;
    font-weight: 600;
    color: #111827;
}

.customer-action {
    font-size: 12px;
    color: #3B82F6;
}

.available-section {
    text-align: center;
    padding: 24px;
    background: #ECFDF5;
    border-radius: 12px;
}

.available-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #059669;
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 8px;
}

.available-badge i { font-size: 24px; }

.available-text {
    margin: 0;
    color: #065F46;
    font-size: 14px;
}

.modal-footer {
    padding: 16px 24px;
    background: #F9FAFB;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-primary {
    background: #3B82F6;
    color: #fff;
}

.btn-primary:hover { background: #2563EB; }

.btn-secondary {
    background: #E5E7EB;
    color: #374151;
}

.btn-secondary:hover { background: #D1D5DB; }

.btn-ghost {
    background: transparent;
    color: #6B7280;
}

.btn-ghost:hover { background: #F3F4F6; }

/* Real-time indicator */
.realtime-indicator {
    position: absolute;
    bottom: 20px;
    left: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 12px;
    color: #6B7280;
    z-index: 100;
}

.pulse {
    width: 8px;
    height: 8px;
    background: #10B981;
    border-radius: 50%;
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

/* Keyboard shortcuts hint */
.shortcuts-hint {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 16px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.95);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 11px;
    color: #6B7280;
    z-index: 100;
}

.shortcuts-hint kbd {
    display: inline-block;
    padding: 2px 6px;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    border-radius: 4px;
    font-family: inherit;
    font-size: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .plan-header {
        padding: 10px 12px;
    }

    .plan-header h1 {
        display: none;
    }

    .header-stats {
        display: none;
    }

    .zoom-controls {
        display: none;
    }

    .legend-bar {
        display: none;
    }

    .shortcuts-hint {
        display: none;
    }

    .minimap {
        width: 120px;
        height: 80px;
    }

    .filter-panel {
        right: 10px;
        left: 10px;
        width: auto;
    }

    .modal {
        margin: 10px;
        max-height: calc(100vh - 20px);
    }
}
</style>
