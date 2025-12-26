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

// ============================================
// STATE MANAGEMENT
// ============================================

// Core state
const selectedSite = ref(props.currentSite?.id);
const selectedFloor = ref(null);
const showSidebar = ref(false);
const isFullscreen = ref(false);
const isLoading = ref(false);

// View modes
const viewMode = ref('normal'); // normal, heatmap-revenue, heatmap-occupation, 3d
const kioskMode = ref(false);
const kioskInterval = ref(null);
const darkMode = ref(false);

// Quick-Edit Mode (light editing without full editor)
const quickEditMode = ref(false);
const isDraggingBox = ref(false);
const draggedBox = ref(null);
const dragOffset = ref({ x: 0, y: 0 });

// Multi-selection for batch operations
const selectedBoxes = ref([]);
const isMultiSelecting = ref(false);
const selectionStart = ref({ x: 0, y: 0 });
const selectionRect = ref({ x: 0, y: 0, w: 0, h: 0 });

// Quick status change
const showStatusModal = ref(false);
const statusChangeTarget = ref(null); // single box or 'batch'

// Saving state
const isSaving = ref(false);
const hasUnsavedChanges = ref(false);

// Zoom & Pan state
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const isPinching = ref(false);
const dragStart = ref({ x: 0, y: 0, px: 0, py: 0 });
const lastPinchDistance = ref(0);

// Search state
const searchQuery = ref('');
const searchResults = ref([]);
const showSearchResults = ref(false);
const highlightedBoxId = ref(null);

// Filter state
const showFilters = ref(false);
const filters = ref({
    status: [],
    sizeRange: [0, 200],
    priceRange: [0, 1000],
    hasContract: null,
    hasAlert: null,
});

// Popup & Modal state
const showPopup = ref(false);
const popupBox = ref(null);
const popupPosition = ref({ x: 0, y: 0 });
const showModal = ref(false);
const modalBox = ref(null);

// Annotations state
const annotations = ref([]);
const showAnnotationModal = ref(false);
const editingAnnotation = ref(null);

// Stats panel
const showStatsPanel = ref(false);

// Real-time state
const liveUpdates = ref(true);
const lastUpdate = ref(new Date());
const recentChanges = ref([]);

// Minimap
const showMinimap = ref(true);

// Export state
const isExporting = ref(false);

// Touch support
const touchStartTime = ref(0);
const touchStartPos = ref({ x: 0, y: 0 });

// SVG dimensions
const svgWidth = 1200;
const svgHeight = 700;

// ============================================
// STATUS CONFIGURATION
// ============================================

const statusConfig = {
    available: { color: '#10B981', darkColor: '#059669', label: 'Libre', icon: 'check-circle' },
    occupied: { color: '#3B82F6', darkColor: '#2563EB', label: 'Occupe', icon: 'user' },
    reserved: { color: '#F59E0B', darkColor: '#D97706', label: 'Reserve', icon: 'clock' },
    ending: { color: '#FBBF24', darkColor: '#F59E0B', label: 'Fin de contrat', icon: 'calendar' },
    litigation: { color: '#8B5CF6', darkColor: '#7C3AED', label: 'Litige', icon: 'alert-triangle' },
    maintenance: { color: '#EF4444', darkColor: '#DC2626', label: 'Maintenance', icon: 'tool' },
    unavailable: { color: '#6B7280', darkColor: '#4B5563', label: 'Indisponible', icon: 'x-circle' },
};

// ============================================
// COMPUTED PROPERTIES
// ============================================

// Box layout with filters applied
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
            hasOverdue: el.hasOverdue || false,
            daysUntilEnd: el.daysUntilEnd || null,
            monthlyRevenue: el.price || el.box?.current_price || 0,
        }));
    } else {
        boxes = generateDemoLayout();
    }

    // Apply floor filter
    if (selectedFloor.value !== null) {
        boxes = boxes.filter(b => b.floor === selectedFloor.value);
    }

    // Apply status filters
    if (filters.value.status.length > 0) {
        boxes = boxes.filter(b => filters.value.status.includes(b.status));
    }

    // Apply size filter
    if (filters.value.sizeRange[0] > 0 || filters.value.sizeRange[1] < 200) {
        boxes = boxes.filter(b => b.vol >= filters.value.sizeRange[0] && b.vol <= filters.value.sizeRange[1]);
    }

    // Apply contract filter
    if (filters.value.hasContract === true) {
        boxes = boxes.filter(b => b.contract);
    } else if (filters.value.hasContract === false) {
        boxes = boxes.filter(b => !b.contract);
    }

    // Apply alert filter
    if (filters.value.hasAlert === true) {
        boxes = boxes.filter(b => b.hasOverdue || b.status === 'ending' || b.status === 'litigation');
    }

    return boxes;
});

// Statistics
const stats = computed(() => {
    const all = boxLayout.value.filter(b => !b.isLift);
    const occupied = all.filter(b => ['occupied', 'reserved', 'ending'].includes(b.status));

    return {
        total: all.length,
        available: all.filter(b => b.status === 'available').length,
        occupied: all.filter(b => b.status === 'occupied').length,
        reserved: all.filter(b => b.status === 'reserved').length,
        ending: all.filter(b => b.status === 'ending').length,
        maintenance: all.filter(b => b.status === 'maintenance').length,
        litigation: all.filter(b => b.status === 'litigation').length,
        occupancyRate: all.length > 0 ? Math.round((occupied.length / all.length) * 100) : 0,
        totalVolume: all.reduce((sum, b) => sum + (b.vol || 0), 0),
        availableVolume: all.filter(b => b.status === 'available').reduce((sum, b) => sum + (b.vol || 0), 0),
        monthlyRevenue: occupied.reduce((sum, b) => sum + (b.monthlyRevenue || 0), 0),
        potentialRevenue: all.reduce((sum, b) => sum + (b.monthlyRevenue || 0), 0),
        alertsCount: all.filter(b => b.hasOverdue || b.status === 'ending' || b.status === 'litigation').length,
        overdueCount: all.filter(b => b.hasOverdue).length,
        endingSoonCount: all.filter(b => b.status === 'ending').length,
    };
});

// Revenue heatmap colors
const revenueHeatmapColors = computed(() => {
    const boxes = boxLayout.value.filter(b => !b.isLift);
    const maxRevenue = Math.max(...boxes.map(b => b.monthlyRevenue || 0), 1);

    return boxes.reduce((acc, box) => {
        const ratio = (box.monthlyRevenue || 0) / maxRevenue;
        // Green (high) to Red (low)
        const r = Math.round(255 * (1 - ratio));
        const g = Math.round(255 * ratio);
        acc[box.id] = `rgb(${r}, ${g}, 100)`;
        return acc;
    }, {});
});

// Occupation heatmap (based on days occupied in last year)
const occupationHeatmapColors = computed(() => {
    const boxes = boxLayout.value.filter(b => !b.isLift);

    return boxes.reduce((acc, box) => {
        // Simulate occupation rate for demo
        const occupationRate = box.status === 'available' ? 0.3 :
                              box.status === 'occupied' ? 0.9 :
                              box.status === 'reserved' ? 0.7 : 0.5;

        // Blue gradient based on occupation
        const intensity = Math.round(200 * occupationRate + 55);
        acc[box.id] = `rgb(${255 - intensity}, ${255 - intensity}, 255)`;
        return acc;
    }, {});
});

// Search results computed
const filteredSearchResults = computed(() => {
    if (!searchQuery.value || searchQuery.value.length < 1) return [];

    const query = searchQuery.value.toLowerCase();
    return boxLayout.value
        .filter(b => !b.isLift && (
            b.name.toLowerCase().includes(query) ||
            b.customer?.name?.toLowerCase().includes(query) ||
            b.contract?.contract_number?.toLowerCase().includes(query)
        ))
        .slice(0, 10);
});

// ============================================
// DEMO DATA GENERATOR
// ============================================

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
            name: ['Martin Dupont', 'Marie Dubois', 'Jean Lefebvre', 'Sophie Bernard', 'Pierre Moreau', 'Alice Petit', 'Thomas Robert'][Math.floor(Math.random() * 7)]
        }
    } : null;

    // Create grid layout
    const columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
    const rowsPerColumn = 12;
    const boxW = 42, boxH = 35, gapX = 3, gapY = 3;
    let startX = 60;

    columns.forEach((col, colIdx) => {
        for (let row = 0; row < rowsPerColumn; row++) {
            const status = getStatus();
            const contract = getContract(status);
            const x = startX + colIdx * (boxW + gapX + (colIdx % 2 === 0 ? 0 : 15));
            const y = 80 + row * (boxH + gapY);
            const price = [89, 129, 149, 179, 199, 249, 299][Math.floor(Math.random() * 7)];

            boxes.push({
                id: id++,
                name: `${col}${String(row + 1).padStart(2, '0')}`,
                x,
                y,
                w: boxW,
                h: boxH,
                vol: [6, 9, 12, 18, 25][Math.floor(Math.random() * 5)],
                price,
                monthlyRevenue: status !== 'available' ? price : 0,
                status,
                contract,
                customer: contract?.customer,
                floor: 0,
                hasOverdue: status === 'occupied' && Math.random() > 0.85,
                daysUntilEnd: status === 'ending' ? Math.floor(Math.random() * 30) + 1 : null,
            });
        }

        // Add corridor every 2 columns
        if ((colIdx + 1) % 2 === 0 && colIdx < columns.length - 1) {
            startX += 20;
        }
    });

    // Add lifts
    boxes.push({ id: id++, name: 'ASCENSEUR', x: 60, y: 540, w: 70, h: 50, vol: 0, status: 'unavailable', isLift: true });
    boxes.push({ id: id++, name: 'ASCENSEUR', x: 480, y: 540, w: 70, h: 50, vol: 0, status: 'unavailable', isLift: true });

    // Add some larger boxes
    boxes.push({ id: id++, name: 'XL-01', x: 700, y: 80, w: 80, h: 70, vol: 50, price: 499, monthlyRevenue: 499, status: 'occupied', contract: getContract('occupied'), floor: 0 });
    boxes.push({ id: id++, name: 'XL-02', x: 700, y: 160, w: 80, h: 70, vol: 50, price: 499, monthlyRevenue: 0, status: 'available', floor: 0 });
    boxes.push({ id: id++, name: 'XL-03', x: 700, y: 240, w: 80, h: 70, vol: 50, price: 499, monthlyRevenue: 499, status: 'reserved', floor: 0 });

    return boxes;
}

// ============================================
// COLOR FUNCTIONS
// ============================================

function getBoxColor(box) {
    if (box.isLift) return darkMode.value ? '#374151' : '#F3F4F6';

    // Heatmap modes
    if (viewMode.value === 'heatmap-revenue') {
        return revenueHeatmapColors.value[box.id] || '#6B7280';
    }
    if (viewMode.value === 'heatmap-occupation') {
        return occupationHeatmapColors.value[box.id] || '#6B7280';
    }

    // Normal mode
    const config = statusConfig[box.status];
    return darkMode.value ? config?.darkColor : config?.color || '#6B7280';
}

function getTextColor(status) {
    if (viewMode.value !== 'normal') return '#1F2937';
    return ['ending'].includes(status) ? '#1F2937' : '#FFFFFF';
}

function getBoxStroke(box) {
    if (highlightedBoxId.value === box.id) return '#FBBF24';
    if (box.hasOverdue) return '#EF4444';
    if (box.status === 'ending' && box.daysUntilEnd && box.daysUntilEnd <= 7) return '#F97316';
    return darkMode.value ? '#4B5563' : '#374151';
}

function getBoxStrokeWidth(box) {
    if (highlightedBoxId.value === box.id) return 3;
    if (box.hasOverdue || (box.status === 'ending' && box.daysUntilEnd && box.daysUntilEnd <= 7)) return 2;
    return 1;
}

// ============================================
// EVENT HANDLERS
// ============================================

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
    if (box.isLift || isDragging.value || isDraggingBox.value || kioskMode.value) return;
    popupBox.value = box;
    updatePopupPosition(event);
    showPopup.value = true;
}

function handleBoxMouseLeave() {
    if (!isDraggingBox.value) {
        showPopup.value = false;
        popupBox.value = null;
    }
}

function handleBoxClick(box, event) {
    if (box.isLift || kioskMode.value) return;

    // Multi-select with Ctrl/Cmd key
    if (quickEditMode.value && (event.ctrlKey || event.metaKey)) {
        toggleBoxSelection(box);
        return;
    }

    // If in quick-edit mode and already selected, don't open modal
    if (quickEditMode.value && selectedBoxes.value.includes(box.id)) {
        return;
    }

    // Clear selection and open modal
    selectedBoxes.value = [];
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
    if (y + 220 > rect.height) y = event.clientY - rect.top - 220;
    popupPosition.value = { x: Math.max(10, x), y: Math.max(10, y) };
}

// ============================================
// SEARCH FUNCTIONS
// ============================================

function onSearchInput() {
    showSearchResults.value = searchQuery.value.length > 0;
    highlightedBoxId.value = null;
}

function selectSearchResult(box) {
    centerOnBox(box);
    highlightedBoxId.value = box.id;
    showSearchResults.value = false;
    searchQuery.value = box.name;

    // Clear highlight after 3 seconds
    setTimeout(() => {
        highlightedBoxId.value = null;
    }, 3000);
}

function centerOnBox(box) {
    const container = document.getElementById('plan-container');
    if (!container) return;

    // Set zoom to comfortable level
    zoom.value = 1.5;

    // Calculate pan to center on box
    panX.value = -(box.x * zoom.value - container.clientWidth / 2 + box.w * zoom.value / 2);
    panY.value = -(box.y * zoom.value - container.clientHeight / 2 + box.h * zoom.value / 2);
}

function clearSearch() {
    searchQuery.value = '';
    showSearchResults.value = false;
    highlightedBoxId.value = null;
}

// ============================================
// NAVIGATION FUNCTIONS
// ============================================

function changeSite() {
    isLoading.value = true;
    router.get(route('tenant.plan.interactive'), { site_id: selectedSite.value }, {
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

// ============================================
// ZOOM CONTROLS
// ============================================

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

// ============================================
// FULLSCREEN & KIOSK MODE
// ============================================

function toggleFullscreen() {
    isFullscreen.value = !isFullscreen.value;
    if (isFullscreen.value) {
        document.documentElement.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
}

function toggleKioskMode() {
    kioskMode.value = !kioskMode.value;

    if (kioskMode.value) {
        isFullscreen.value = true;
        document.documentElement.requestFullscreen?.();
        showSidebar.value = false;
        showFilters.value = false;
        showStatsPanel.value = false;

        // Auto-rotate sites if multiple
        if (props.sites && props.sites.length > 1) {
            let currentIndex = props.sites.findIndex(s => s.id === selectedSite.value);
            kioskInterval.value = setInterval(() => {
                currentIndex = (currentIndex + 1) % props.sites.length;
                selectedSite.value = props.sites[currentIndex].id;
                changeSite();
            }, 30000); // Change every 30 seconds
        }
    } else {
        if (kioskInterval.value) {
            clearInterval(kioskInterval.value);
            kioskInterval.value = null;
        }
    }
}

// ============================================
// FILTER MANAGEMENT
// ============================================

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
        hasContract: null,
        hasAlert: null,
    };
}

function showOnlyAlerts() {
    clearFilters();
    filters.value.hasAlert = true;
}

// ============================================
// EXPORT FUNCTIONS
// ============================================

async function exportToPNG() {
    isExporting.value = true;

    try {
        const svg = document.getElementById('plan-svg');
        const svgData = new XMLSerializer().serializeToString(svg);
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.width = svgWidth * 2;
        canvas.height = svgHeight * 2;

        const img = new Image();
        img.onload = () => {
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            const link = document.createElement('a');
            link.download = `plan-${props.currentSite?.name || 'site'}-${new Date().toISOString().split('T')[0]}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();

            isExporting.value = false;
        };

        img.src = 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svgData)));
    } catch (error) {
        console.error('Export error:', error);
        isExporting.value = false;
    }
}

function printPlan() {
    window.print();
}

// ============================================
// QUICK-EDIT MODE FUNCTIONS
// ============================================

function toggleQuickEditMode() {
    quickEditMode.value = !quickEditMode.value;
    if (!quickEditMode.value) {
        selectedBoxes.value = [];
        isDraggingBox.value = false;
        draggedBox.value = null;
    }
}

function toggleBoxSelection(box) {
    const idx = selectedBoxes.value.indexOf(box.id);
    if (idx > -1) {
        selectedBoxes.value.splice(idx, 1);
    } else {
        selectedBoxes.value.push(box.id);
    }
}

function selectAllBoxes() {
    selectedBoxes.value = boxLayout.value.filter(b => !b.isLift).map(b => b.id);
}

function clearSelection() {
    selectedBoxes.value = [];
}

function isBoxSelected(box) {
    return selectedBoxes.value.includes(box.id);
}

// Box drag handlers for quick-edit mode
function handleBoxMouseDown(box, event) {
    if (!quickEditMode.value || box.isLift) return;

    event.stopPropagation();
    event.preventDefault();

    // If not already selected, select this box
    if (!selectedBoxes.value.includes(box.id)) {
        if (!(event.ctrlKey || event.metaKey)) {
            selectedBoxes.value = [];
        }
        selectedBoxes.value.push(box.id);
    }

    isDraggingBox.value = true;
    draggedBox.value = box;
    showPopup.value = false;

    // Calculate offset from mouse to box position
    const svg = document.getElementById('plan-svg');
    const pt = svg.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());

    dragOffset.value = {
        x: svgP.x - box.x,
        y: svgP.y - box.y
    };

    // Store initial positions of all selected boxes
    selectedBoxes.value.forEach(id => {
        const b = boxLayout.value.find(bx => bx.id === id);
        if (b) {
            b._startX = b.x;
            b._startY = b.y;
        }
    });

    window.addEventListener('mousemove', onBoxDrag);
    window.addEventListener('mouseup', onBoxDragEnd);
}

function onBoxDrag(event) {
    if (!isDraggingBox.value || !draggedBox.value) return;

    const svg = document.getElementById('plan-svg');
    const pt = svg.createSVGPoint();
    pt.x = event.clientX;
    pt.y = event.clientY;
    const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());

    const newX = svgP.x - dragOffset.value.x;
    const newY = svgP.y - dragOffset.value.y;

    const dx = newX - draggedBox.value._startX;
    const dy = newY - draggedBox.value._startY;

    // Move all selected boxes
    selectedBoxes.value.forEach(id => {
        const box = boxLayout.value.find(b => b.id === id);
        if (box) {
            box.x = Math.round(box._startX + dx);
            box.y = Math.round(box._startY + dy);
        }
    });

    hasUnsavedChanges.value = true;
}

function onBoxDragEnd() {
    isDraggingBox.value = false;
    draggedBox.value = null;
    window.removeEventListener('mousemove', onBoxDrag);
    window.removeEventListener('mouseup', onBoxDragEnd);

    // Clean up temp properties
    boxLayout.value.forEach(b => {
        delete b._startX;
        delete b._startY;
    });
}

// ============================================
// QUICK STATUS CHANGE
// ============================================

function openStatusChange(target = 'single') {
    statusChangeTarget.value = target;
    showStatusModal.value = true;
}

function changeBoxStatus(newStatus) {
    if (statusChangeTarget.value === 'batch') {
        // Change status for all selected boxes
        selectedBoxes.value.forEach(id => {
            const box = boxLayout.value.find(b => b.id === id);
            if (box) {
                box.status = newStatus;
            }
        });
    } else if (modalBox.value) {
        // Change status for single box
        modalBox.value.status = newStatus;
    }

    hasUnsavedChanges.value = true;
    showStatusModal.value = false;
}

async function saveChanges() {
    if (!hasUnsavedChanges.value) return;

    isSaving.value = true;

    try {
        // Prepare data for saving
        const elementsToSave = boxLayout.value.map(box => ({
            id: box.id,
            x: box.x,
            y: box.y,
            w: box.w,
            h: box.h,
            status: box.status,
            boxId: box.boxId,
        }));

        await router.post(route('tenant.plan.save'), {
            site_id: selectedSite.value,
            elements: elementsToSave,
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                hasUnsavedChanges.value = false;
                // Show success toast
                showToast('Modifications enregistrees');
            },
            onError: () => {
                showToast('Erreur lors de l\'enregistrement', 'error');
            }
        });
    } catch (error) {
        console.error('Save error:', error);
    } finally {
        isSaving.value = false;
    }
}

function discardChanges() {
    if (confirm('Annuler toutes les modifications non enregistrees ?')) {
        router.reload();
        hasUnsavedChanges.value = false;
    }
}

// Simple toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `quick-toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ============================================
// ANNOTATION FUNCTIONS
// ============================================

function addAnnotation(box) {
    editingAnnotation.value = {
        boxId: box.id,
        boxName: box.name,
        type: 'note',
        text: '',
        priority: 'normal',
    };
    showAnnotationModal.value = true;
}

function saveAnnotation() {
    if (!editingAnnotation.value) return;

    const existing = annotations.value.findIndex(a => a.boxId === editingAnnotation.value.boxId);
    if (existing > -1) {
        annotations.value[existing] = { ...editingAnnotation.value };
    } else {
        annotations.value.push({ ...editingAnnotation.value });
    }

    showAnnotationModal.value = false;
    editingAnnotation.value = null;

    // Save to localStorage
    localStorage.setItem('plan-annotations', JSON.stringify(annotations.value));
}

function getBoxAnnotation(boxId) {
    return annotations.value.find(a => a.boxId === boxId);
}

function removeAnnotation(boxId) {
    annotations.value = annotations.value.filter(a => a.boxId !== boxId);
    localStorage.setItem('plan-annotations', JSON.stringify(annotations.value));
}

// ============================================
// FORMAT HELPERS
// ============================================

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR' }).format(price || 0);
}

function formatVolume(vol) {
    return `${vol || 0} m3`;
}

// ============================================
// MINIMAP
// ============================================

function onMinimapClick(e) {
    const minimap = e.currentTarget;
    const rect = minimap.getBoundingClientRect();
    const x = (e.clientX - rect.left) / rect.width;
    const y = (e.clientY - rect.top) / rect.height;

    const container = document.getElementById('plan-container');
    if (container) {
        panX.value = -(x * svgWidth * zoom.value - container.clientWidth / 2);
        panY.value = -(y * svgHeight * zoom.value - container.clientHeight / 2);
    }
}

// ============================================
// KEYBOARD SHORTCUTS
// ============================================

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
            showStatsPanel.value = false;
            showSearchResults.value = false;
            showStatusModal.value = false;
            if (quickEditMode.value) {
                selectedBoxes.value = [];
            }
            if (kioskMode.value) toggleKioskMode();
            break;
        case 'e':
            if (!kioskMode.value) toggleQuickEditMode();
            break;
        case 'a':
            if (quickEditMode.value && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                selectAllBoxes();
            }
            break;
        case 'Delete':
        case 'Backspace':
            if (quickEditMode.value && selectedBoxes.value.length > 0) {
                e.preventDefault();
                clearSelection();
            }
            break;
        case 'm':
            showMinimap.value = !showMinimap.value;
            break;
        case 'k':
            toggleKioskMode();
            break;
        case 's':
            showStatsPanel.value = !showStatsPanel.value;
            break;
        case 'd':
            darkMode.value = !darkMode.value;
            break;
        case '/':
            e.preventDefault();
            document.getElementById('search-input')?.focus();
            break;
    }
}

// ============================================
// LIFECYCLE
// ============================================

onMounted(() => {
    window.addEventListener('keydown', onKeyDown);

    // Load annotations from localStorage
    const saved = localStorage.getItem('plan-annotations');
    if (saved) {
        try {
            annotations.value = JSON.parse(saved);
        } catch (e) {}
    }

    nextTick(() => fitToScreen());
});

onUnmounted(() => {
    window.removeEventListener('keydown', onKeyDown);
    if (kioskInterval.value) {
        clearInterval(kioskInterval.value);
    }
});

watch(isFullscreen, (val) => {
    document.body.style.overflow = val ? 'hidden' : '';
});

watch(darkMode, (val) => {
    document.body.classList.toggle('dark-mode', val);
});
</script>

<template>
    <div :class="['plan-pro', { fullscreen: isFullscreen, dark: darkMode, kiosk: kioskMode }]">
        <!-- Sidebar -->
        <aside class="sidebar" :class="{ open: showSidebar }" v-if="!kioskMode">
            <div class="sidebar-header">
                <span class="logo">
                    <span class="logo-icon">📦</span>
                    BoxiBox Pro
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
                <div class="nav-divider"></div>
                <Link :href="route('tenant.plan.editor-pro', { site_id: currentSite?.id })" class="nav-item editor-link">
                    <i class="fas fa-pencil-ruler"></i>
                    <span>Editeur Pro</span>
                </Link>
            </nav>
        </aside>
        <div v-if="showSidebar && !kioskMode" class="sidebar-overlay" @click="showSidebar = false"></div>

        <!-- Header -->
        <header class="plan-header" v-if="!kioskMode">
            <div class="header-left">
                <button @click="showSidebar = true" class="menu-btn">
                    <i class="fas fa-bars"></i>
                </button>

                <!-- Search -->
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input
                        id="search-input"
                        v-model="searchQuery"
                        @input="onSearchInput"
                        @focus="showSearchResults = searchQuery.length > 0"
                        type="text"
                        placeholder="Rechercher un box, client... (/)"
                        class="search-input"
                    />
                    <button v-if="searchQuery" @click="clearSearch" class="search-clear">
                        <i class="fas fa-times"></i>
                    </button>

                    <!-- Search Results Dropdown -->
                    <div v-if="showSearchResults && filteredSearchResults.length > 0" class="search-results">
                        <div
                            v-for="result in filteredSearchResults"
                            :key="result.id"
                            @click="selectSearchResult(result)"
                            class="search-result-item"
                        >
                            <span class="result-name">{{ result.name }}</span>
                            <span class="result-status" :style="{ color: statusConfig[result.status]?.color }">
                                {{ statusConfig[result.status]?.label }}
                            </span>
                            <span v-if="result.customer" class="result-customer">
                                {{ result.customer.name }}
                            </span>
                        </div>
                    </div>
                </div>

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
                    <span class="stat-label">Occupes</span>
                </div>
                <div class="stat-pill occupancy">
                    <span class="stat-value">{{ stats.occupancyRate }}%</span>
                    <span class="stat-label">Taux</span>
                </div>
                <div v-if="stats.alertsCount > 0" class="stat-pill alerts" @click="showOnlyAlerts">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span class="stat-value">{{ stats.alertsCount }}</span>
                    <span class="stat-label">Alertes</span>
                </div>
            </div>

            <div class="header-actions">
                <!-- View Mode Selector -->
                <div class="view-mode-selector">
                    <button
                        @click="viewMode = 'normal'"
                        :class="['view-btn', { active: viewMode === 'normal' }]"
                        title="Vue normale"
                    >
                        <i class="fas fa-th"></i>
                    </button>
                    <button
                        @click="viewMode = 'heatmap-revenue'"
                        :class="['view-btn', { active: viewMode === 'heatmap-revenue' }]"
                        title="Heatmap Revenus"
                    >
                        <i class="fas fa-euro-sign"></i>
                    </button>
                    <button
                        @click="viewMode = 'heatmap-occupation'"
                        :class="['view-btn', { active: viewMode === 'heatmap-occupation' }]"
                        title="Heatmap Occupation"
                    >
                        <i class="fas fa-chart-pie"></i>
                    </button>
                </div>

                <button @click="showFilters = !showFilters" :class="['action-btn', { active: showFilters || filters.status.length > 0 }]">
                    <i class="fas fa-filter"></i>
                    <span v-if="filters.status.length > 0" class="filter-badge">{{ filters.status.length }}</span>
                </button>

                <button @click="showStatsPanel = !showStatsPanel" :class="['action-btn', { active: showStatsPanel }]" title="Statistiques (S)">
                    <i class="fas fa-chart-bar"></i>
                </button>

                <div class="zoom-controls">
                    <button @click="zoomOut" class="zoom-btn" title="Zoom arriere (-)">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="zoom-level">{{ Math.round(zoom * 100) }}%</span>
                    <button @click="zoomIn" class="zoom-btn" title="Zoom avant (+)">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button @click="fitToScreen" class="zoom-btn" title="Ajuster (F)">
                        <i class="fas fa-expand-arrows-alt"></i>
                    </button>
                </div>

                <!-- Quick-Edit Mode Toggle -->
                <button
                    @click="toggleQuickEditMode"
                    :class="['action-btn quick-edit-btn', { active: quickEditMode }]"
                    :title="quickEditMode ? 'Quitter edition rapide' : 'Edition rapide (E)'"
                >
                    <i class="fas fa-edit"></i>
                    <span v-if="quickEditMode" class="btn-label">Edition</span>
                </button>

                <button @click="darkMode = !darkMode" :class="['action-btn', { active: darkMode }]" title="Mode sombre (D)">
                    <i :class="darkMode ? 'fas fa-sun' : 'fas fa-moon'"></i>
                </button>

                <button @click="toggleKioskMode" class="action-btn" title="Mode Kiosque (K)">
                    <i class="fas fa-tv"></i>
                </button>

                <div class="dropdown">
                    <button class="action-btn" title="Exporter">
                        <i class="fas fa-download"></i>
                    </button>
                    <div class="dropdown-menu">
                        <button @click="exportToPNG" :disabled="isExporting">
                            <i class="fas fa-image"></i> Export PNG
                        </button>
                        <button @click="printPlan">
                            <i class="fas fa-print"></i> Imprimer
                        </button>
                    </div>
                </div>

                <button @click="toggleFullscreen" class="action-btn">
                    <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>

                <Link :href="route('tenant.plan.editor-pro', { site_id: currentSite?.id })" class="editor-btn">
                    <i class="fas fa-pencil-ruler"></i>
                    <span class="btn-text">Editer</span>
                </Link>
            </div>
        </header>

        <!-- Kiosk Header -->
        <header v-if="kioskMode" class="kiosk-header">
            <div class="kiosk-logo">
                <span class="logo-icon">📦</span>
                BoxiBox - {{ currentSite?.name }}
            </div>
            <div class="kiosk-stats">
                <div class="kiosk-stat">
                    <span class="kiosk-stat-value">{{ stats.available }}</span>
                    <span class="kiosk-stat-label">Boxes Libres</span>
                </div>
                <div class="kiosk-stat">
                    <span class="kiosk-stat-value">{{ stats.occupancyRate }}%</span>
                    <span class="kiosk-stat-label">Taux d'occupation</span>
                </div>
            </div>
            <div class="kiosk-time">
                {{ new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
            </div>
            <button @click="toggleKioskMode" class="kiosk-exit">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <!-- Filter Panel -->
        <Transition name="slide">
            <div v-if="showFilters && !kioskMode" class="filter-panel">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Filtres</h3>
                    <button @click="clearFilters" class="clear-btn">Effacer</button>
                </div>

                <div class="filter-section">
                    <label>Statut</label>
                    <div class="status-filters">
                        <button
                            v-for="(config, status) in statusConfig"
                            :key="status"
                            @click="toggleStatusFilter(status)"
                            :class="['status-filter-btn', { active: filters.status.includes(status) }]"
                        >
                            <span class="status-dot" :style="{ background: config.color }"></span>
                            {{ config.label }}
                        </button>
                    </div>
                </div>

                <div class="filter-section">
                    <label>Options</label>
                    <div class="option-filters">
                        <button
                            @click="filters.hasContract = filters.hasContract === true ? null : true"
                            :class="['option-btn', { active: filters.hasContract === true }]"
                        >
                            <i class="fas fa-file-contract"></i> Avec contrat
                        </button>
                        <button
                            @click="filters.hasAlert = filters.hasAlert === true ? null : true"
                            :class="['option-btn', { active: filters.hasAlert === true }]"
                        >
                            <i class="fas fa-exclamation-triangle"></i> Avec alertes
                        </button>
                    </div>
                </div>

                <div class="filter-results">
                    {{ boxLayout.filter(b => !b.isLift).length }} boxes affiches
                </div>
            </div>
        </Transition>

        <!-- Stats Panel -->
        <Transition name="slide-right">
            <div v-if="showStatsPanel && !kioskMode" class="stats-panel">
                <div class="stats-header">
                    <h3><i class="fas fa-chart-bar"></i> Statistiques</h3>
                    <button @click="showStatsPanel = false" class="close-btn">&times;</button>
                </div>

                <div class="stats-content">
                    <!-- Occupation Chart -->
                    <div class="stats-card">
                        <h4>Occupation</h4>
                        <div class="occupation-chart">
                            <div class="occupation-ring">
                                <svg viewBox="0 0 36 36">
                                    <path
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        fill="none"
                                        stroke="#E5E7EB"
                                        stroke-width="3"
                                    />
                                    <path
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        fill="none"
                                        stroke="#3B82F6"
                                        stroke-width="3"
                                        :stroke-dasharray="`${stats.occupancyRate}, 100`"
                                    />
                                </svg>
                                <span class="occupation-value">{{ stats.occupancyRate }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Stats -->
                    <div class="stats-card">
                        <h4>Revenus mensuels</h4>
                        <div class="revenue-stats">
                            <div class="revenue-item">
                                <span class="revenue-label">Actuel</span>
                                <span class="revenue-value">{{ formatPrice(stats.monthlyRevenue) }}</span>
                            </div>
                            <div class="revenue-item">
                                <span class="revenue-label">Potentiel</span>
                                <span class="revenue-value potential">{{ formatPrice(stats.potentialRevenue) }}</span>
                            </div>
                            <div class="revenue-bar">
                                <div class="revenue-fill" :style="{ width: (stats.monthlyRevenue / stats.potentialRevenue * 100) + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Breakdown -->
                    <div class="stats-card">
                        <h4>Repartition</h4>
                        <div class="status-breakdown">
                            <div v-for="(config, status) in statusConfig" :key="status" class="breakdown-item">
                                <span class="breakdown-dot" :style="{ background: config.color }"></span>
                                <span class="breakdown-label">{{ config.label }}</span>
                                <span class="breakdown-value">{{ boxLayout.filter(b => b.status === status).length }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="stats-card alerts-card" v-if="stats.alertsCount > 0">
                        <h4><i class="fas fa-exclamation-triangle"></i> Alertes</h4>
                        <div class="alerts-list">
                            <div class="alert-item" v-if="stats.overdueCount > 0">
                                <i class="fas fa-euro-sign text-red"></i>
                                <span>{{ stats.overdueCount }} impayes</span>
                            </div>
                            <div class="alert-item" v-if="stats.endingSoonCount > 0">
                                <i class="fas fa-calendar-times text-orange"></i>
                                <span>{{ stats.endingSoonCount }} fin de contrat</span>
                            </div>
                            <div class="alert-item" v-if="stats.litigation > 0">
                                <i class="fas fa-gavel text-purple"></i>
                                <span>{{ stats.litigation }} en litige</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Legend -->
        <div class="legend-bar" v-if="!kioskMode && viewMode === 'normal'">
            <div class="legend-item" v-for="(config, status) in statusConfig" :key="status">
                <span class="legend-dot" :style="{ background: config.color }"></span>
                <span class="legend-label">{{ config.label }}</span>
            </div>
        </div>

        <!-- Heatmap Legend -->
        <div class="heatmap-legend" v-if="viewMode !== 'normal'">
            <span class="heatmap-title">
                {{ viewMode === 'heatmap-revenue' ? 'Revenus' : 'Taux occupation' }}
            </span>
            <div class="heatmap-gradient">
                <span>Faible</span>
                <div class="gradient-bar" :class="viewMode"></div>
                <span>Eleve</span>
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
                <rect x="0" y="0" :width="svgWidth" :height="svgHeight" :fill="darkMode ? '#1F2937' : '#F9FAFB'"/>

                <!-- Grid pattern -->
                <defs>
                    <pattern id="smallGrid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" :stroke="darkMode ? '#374151' : '#E5E7EB'" stroke-width="0.5"/>
                    </pattern>
                </defs>
                <rect x="0" y="0" :width="svgWidth" :height="svgHeight" fill="url(#smallGrid)"/>

                <!-- Border -->
                <rect x="40" y="50" :width="svgWidth - 80" :height="svgHeight - 90"
                      fill="none" :stroke="darkMode ? '#4B5563' : '#D1D5DB'" stroke-width="2" rx="8"/>

                <!-- Boxes -->
                <g
                    v-for="box in boxLayout"
                    :key="box.id"
                    class="box-group"
                    :class="{
                        'quick-edit-mode': quickEditMode,
                        'selected': isBoxSelected(box),
                        'dragging': isDraggingBox && draggedBox?.id === box.id
                    }"
                    @mouseenter="handleBoxMouseEnter(box, $event)"
                    @mouseleave="handleBoxMouseLeave"
                    @click="handleBoxClick(box, $event)"
                    @mousedown="handleBoxMouseDown(box, $event)"
                    :style="{ cursor: quickEditMode && !box.isLift ? 'move' : 'pointer' }"
                >
                    <!-- Selection highlight (behind box) -->
                    <rect
                        v-if="isBoxSelected(box)"
                        :x="box.x - 3"
                        :y="box.y - 3"
                        :width="box.w + 6"
                        :height="box.h + 6"
                        fill="none"
                        stroke="#8B5CF6"
                        stroke-width="2"
                        stroke-dasharray="4,2"
                        rx="5"
                        class="selection-highlight"
                    />

                    <!-- Box rect -->
                    <rect
                        :x="box.x"
                        :y="box.y"
                        :width="box.w"
                        :height="box.h"
                        :fill="getBoxColor(box)"
                        :stroke="isBoxSelected(box) ? '#8B5CF6' : getBoxStroke(box)"
                        :stroke-width="isBoxSelected(box) ? 2 : getBoxStrokeWidth(box)"
                        rx="3"
                        class="box-rect"
                        :class="{
                            lift: box.isLift,
                            highlighted: highlightedBoxId === box.id,
                            'has-alert': box.hasOverdue || box.status === 'litigation'
                        }"
                    />

                    <!-- Box name -->
                    <text
                        :x="box.x + box.w/2"
                        :y="box.y + box.h/2 - (box.h > 30 && box.vol ? 5 : 0)"
                        :fill="getTextColor(box.status)"
                        class="box-name"
                    >
                        {{ box.name }}
                    </text>

                    <!-- Volume -->
                    <text
                        v-if="box.vol && box.h > 30 && !box.isLift"
                        :x="box.x + box.w/2"
                        :y="box.y + box.h/2 + 8"
                        :fill="getTextColor(box.status)"
                        class="box-vol"
                    >
                        {{ box.vol }}m3
                    </text>

                    <!-- Alert indicator -->
                    <g v-if="box.hasOverdue || (box.status === 'ending' && box.daysUntilEnd && box.daysUntilEnd <= 7)">
                        <circle
                            :cx="box.x + box.w - 6"
                            :cy="box.y + 6"
                            r="5"
                            :fill="box.hasOverdue ? '#EF4444' : '#F97316'"
                            class="alert-indicator"
                        />
                        <text
                            :x="box.x + box.w - 6"
                            :y="box.y + 9"
                            fill="#fff"
                            class="alert-icon"
                        >!</text>
                    </g>

                    <!-- Annotation indicator -->
                    <circle
                        v-if="getBoxAnnotation(box.id)"
                        :cx="box.x + 6"
                        :cy="box.y + 6"
                        r="4"
                        fill="#8B5CF6"
                        class="annotation-indicator"
                    />
                </g>
            </svg>

            <!-- Hover Popup -->
            <Transition name="popup">
                <div
                    v-if="showPopup && popupBox && !kioskMode"
                    class="popup"
                    :style="{ left: popupPosition.x + 'px', top: popupPosition.y + 'px' }"
                >
                    <div class="popup-header" :style="{ background: statusConfig[popupBox.status]?.color }">
                        <span class="popup-title">Box {{ popupBox.name }}</span>
                        <span class="popup-status">{{ statusConfig[popupBox.status]?.label }}</span>
                    </div>
                    <div class="popup-body">
                        <div class="popup-row">
                            <span class="popup-label">Volume</span>
                            <span class="popup-value">{{ formatVolume(popupBox.vol) }}</span>
                        </div>
                        <div class="popup-row">
                            <span class="popup-label">Prix</span>
                            <span class="popup-value">{{ formatPrice(popupBox.price) }}/mois</span>
                        </div>

                        <!-- Alerts -->
                        <div v-if="popupBox.hasOverdue" class="popup-alert red">
                            <i class="fas fa-exclamation-circle"></i> Impaye
                        </div>
                        <div v-if="popupBox.status === 'ending' && popupBox.daysUntilEnd" class="popup-alert orange">
                            <i class="fas fa-calendar"></i> Fin dans {{ popupBox.daysUntilEnd }} jours
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
                        </template>

                        <!-- Annotation -->
                        <div v-if="getBoxAnnotation(popupBox.id)" class="popup-annotation">
                            <i class="fas fa-sticky-note"></i>
                            {{ getBoxAnnotation(popupBox.id).text }}
                        </div>
                    </div>
                    <div class="popup-footer">
                        Cliquez pour plus de details
                    </div>
                </div>
            </Transition>

            <!-- Minimap -->
            <div v-if="showMinimap && !kioskMode" class="minimap" @click="onMinimapClick">
                <svg :viewBox="`0 0 ${svgWidth} ${svgHeight}`" preserveAspectRatio="xMidYMid meet">
                    <rect x="0" y="0" :width="svgWidth" :height="svgHeight" :fill="darkMode ? '#374151' : '#F3F4F6'"/>
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
                <button @click.stop="showMinimap = false" class="minimap-close">&times;</button>
            </div>

            <button v-if="!showMinimap && !kioskMode" @click="showMinimap = true" class="minimap-toggle">
                <i class="fas fa-map"></i>
            </button>
        </div>

        <!-- Detail Modal -->
        <Transition name="modal">
            <div v-if="showModal && modalBox" class="modal-overlay" @click.self="showModal = false">
                <div class="modal" :class="{ dark: darkMode }">
                    <div class="modal-header" :style="{ background: statusConfig[modalBox.status]?.color }">
                        <div class="modal-title-group">
                            <h2>Box {{ modalBox.name }}</h2>
                            <span class="modal-status-badge">
                                {{ statusConfig[modalBox.status]?.label }}
                            </span>
                        </div>
                        <button @click="showModal = false" class="modal-close">&times;</button>
                    </div>

                    <div class="modal-body">
                        <!-- Alerts -->
                        <div v-if="modalBox.hasOverdue" class="modal-alert red">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Ce box a des factures impayees</span>
                        </div>
                        <div v-if="modalBox.status === 'ending' && modalBox.daysUntilEnd" class="modal-alert orange">
                            <i class="fas fa-calendar-times"></i>
                            <span>Contrat se termine dans {{ modalBox.daysUntilEnd }} jours</span>
                        </div>

                        <!-- Box Info -->
                        <div class="modal-section">
                            <h3>Informations</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Volume</span>
                                    <span class="info-value">{{ formatVolume(modalBox.vol) }}</span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Prix mensuel</span>
                                    <span class="info-value">{{ formatPrice(modalBox.price) }}</span>
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
                                    <span>Debut: {{ formatDate(modalBox.contract.start_date) }}</span>
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
                                    <span class="customer-action">Voir le profil &rarr;</span>
                                </div>
                            </div>
                        </div>

                        <!-- Available Box -->
                        <div class="modal-section available-section" v-if="modalBox.status === 'available'">
                            <div class="available-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Ce box est disponible</span>
                            </div>
                        </div>

                        <!-- Annotation Section -->
                        <div class="modal-section">
                            <h3>Notes</h3>
                            <div v-if="getBoxAnnotation(modalBox.id)" class="annotation-display">
                                <p>{{ getBoxAnnotation(modalBox.id).text }}</p>
                                <button @click="removeAnnotation(modalBox.id)" class="btn-text-danger">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                            <button v-else @click="addAnnotation(modalBox)" class="btn btn-secondary btn-sm">
                                <i class="fas fa-plus"></i> Ajouter une note
                            </button>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button v-if="modalBox.boxId" @click="goToBox(modalBox.boxId)" class="btn btn-secondary">
                            <i class="fas fa-box"></i> Details
                        </button>
                        <template v-if="modalBox.status === 'available'">
                            <button @click="createContract(modalBox.boxId)" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Creer contrat
                            </button>
                        </template>
                        <template v-else-if="modalBox.contract">
                            <button @click="goToContract(modalBox.contract.id)" class="btn btn-primary">
                                <i class="fas fa-file-contract"></i> Voir contrat
                            </button>
                        </template>
                        <button @click="showModal = false" class="btn btn-ghost">Fermer</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Annotation Modal -->
        <Transition name="modal">
            <div v-if="showAnnotationModal" class="modal-overlay" @click.self="showAnnotationModal = false">
                <div class="modal modal-sm" :class="{ dark: darkMode }">
                    <div class="modal-header">
                        <h2>Note - Box {{ editingAnnotation?.boxName }}</h2>
                        <button @click="showAnnotationModal = false" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Note</label>
                            <textarea
                                v-model="editingAnnotation.text"
                                rows="4"
                                placeholder="Entrez votre note..."
                                class="form-textarea"
                            ></textarea>
                        </div>
                        <div class="form-group">
                            <label>Priorite</label>
                            <select v-model="editingAnnotation.priority" class="form-select">
                                <option value="low">Basse</option>
                                <option value="normal">Normale</option>
                                <option value="high">Haute</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showAnnotationModal = false" class="btn btn-ghost">Annuler</button>
                        <button @click="saveAnnotation" class="btn btn-primary">Enregistrer</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Real-time indicator -->
        <div class="realtime-indicator" v-if="liveUpdates && !kioskMode">
            <span class="pulse"></span>
            <span class="realtime-text">Temps reel</span>
        </div>

        <!-- Keyboard shortcuts hint -->
        <div class="shortcuts-hint" v-if="!kioskMode && !quickEditMode">
            <span><kbd>/</kbd> Rechercher</span>
            <span><kbd>K</kbd> Kiosque</span>
            <span><kbd>S</kbd> Stats</span>
            <span><kbd>D</kbd> Sombre</span>
            <span><kbd>E</kbd> Editer</span>
        </div>

        <!-- Quick-Edit Toolbar (appears at bottom when in edit mode) -->
        <Transition name="slide-up">
            <div v-if="quickEditMode && !kioskMode" class="quick-edit-toolbar">
                <div class="edit-toolbar-content">
                    <!-- Left: Selection info -->
                    <div class="selection-info">
                        <span v-if="selectedBoxes.length === 0" class="selection-hint">
                            <i class="fas fa-info-circle"></i>
                            Cliquez sur un box pour le selectionner, ou Ctrl+clic pour multi-selection
                        </span>
                        <span v-else class="selection-count">
                            <i class="fas fa-check-square"></i>
                            {{ selectedBoxes.length }} box{{ selectedBoxes.length > 1 ? 'es' : '' }} selectionne{{ selectedBoxes.length > 1 ? 's' : '' }}
                        </span>
                    </div>

                    <!-- Center: Actions -->
                    <div class="edit-actions" v-if="selectedBoxes.length > 0">
                        <button @click="openStatusChange('batch')" class="edit-action-btn">
                            <i class="fas fa-exchange-alt"></i>
                            Changer statut
                        </button>
                        <button @click="selectAllBoxes" class="edit-action-btn">
                            <i class="fas fa-check-double"></i>
                            Tout selectionner
                        </button>
                        <button @click="clearSelection" class="edit-action-btn">
                            <i class="fas fa-times"></i>
                            Deselectionner
                        </button>
                    </div>

                    <!-- Right: Save/Cancel -->
                    <div class="edit-controls">
                        <span v-if="hasUnsavedChanges" class="unsaved-badge">
                            <i class="fas fa-exclamation-circle"></i>
                            Non enregistre
                        </span>
                        <button
                            v-if="hasUnsavedChanges"
                            @click="discardChanges"
                            class="edit-control-btn cancel"
                        >
                            <i class="fas fa-undo"></i>
                            Annuler
                        </button>
                        <button
                            @click="saveChanges"
                            :disabled="!hasUnsavedChanges || isSaving"
                            class="edit-control-btn save"
                            :class="{ 'has-changes': hasUnsavedChanges }"
                        >
                            <i :class="isSaving ? 'fas fa-spinner fa-spin' : 'fas fa-save'"></i>
                            {{ isSaving ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                        <button @click="toggleQuickEditMode" class="edit-control-btn exit">
                            <i class="fas fa-times"></i>
                            Quitter
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Status Change Modal -->
        <Transition name="modal">
            <div v-if="showStatusModal" class="modal-overlay" @click.self="showStatusModal = false">
                <div class="modal status-modal" :class="{ dark: darkMode }">
                    <div class="modal-header">
                        <h2>
                            <i class="fas fa-exchange-alt"></i>
                            Changer le statut
                        </h2>
                        <button @click="showStatusModal = false" class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p class="status-modal-info">
                            <template v-if="statusChangeTarget === 'batch'">
                                Changer le statut de <strong>{{ selectedBoxes.length }} boxes</strong>
                            </template>
                            <template v-else>
                                Changer le statut du box <strong>{{ modalBox?.name }}</strong>
                            </template>
                        </p>
                        <div class="status-grid">
                            <button
                                v-for="(config, status) in statusConfig"
                                :key="status"
                                @click="changeBoxStatus(status)"
                                class="status-option"
                                :style="{ '--status-color': config.color }"
                            >
                                <span class="status-dot" :style="{ background: config.color }"></span>
                                <span class="status-label">{{ config.label }}</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button @click="showStatusModal = false" class="btn btn-ghost">Annuler</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

/* ============================================
   BASE LAYOUT
   ============================================ */
.plan-pro {
    position: fixed;
    inset: 0;
    display: flex;
    flex-direction: column;
    background: #F3F4F6;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    z-index: 9999;
    overflow: hidden;
    transition: background 0.3s;
}

.plan-pro.dark {
    background: #111827;
}

.plan-pro.fullscreen {
    z-index: 99999;
}

.plan-pro.kiosk {
    cursor: none;
}

/* ============================================
   SIDEBAR
   ============================================ */
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
    font-size: 20px;
    font-weight: 700;
}

.logo-icon { font-size: 26px; }

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

.nav-divider {
    height: 1px;
    background: rgba(255,255,255,0.1);
    margin: 16px 24px;
}

.editor-link {
    margin: 16px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    border-radius: 12px;
    justify-content: center;
    padding: 16px;
    color: #fff !important;
}

/* ============================================
   HEADER
   ============================================ */
.plan-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    gap: 12px;
    flex-wrap: wrap;
    z-index: 100;
}

.dark .plan-header {
    background: #1F2937;
    border-color: #374151;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
    min-width: 300px;
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
    flex-shrink: 0;
}

.dark .menu-btn {
    background: #374151;
    color: #E5E7EB;
}

.menu-btn:hover { background: #E5E7EB; }

/* Search */
.search-container {
    position: relative;
    flex: 1;
    max-width: 350px;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF;
}

.search-input {
    width: 100%;
    padding: 10px 36px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 14px;
    background: #F9FAFB;
    transition: all 0.2s;
}

.dark .search-input {
    background: #374151;
    border-color: #4B5563;
    color: #fff;
}

.search-input:focus {
    outline: none;
    border-color: #3B82F6;
    background: #fff;
}

.search-clear {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #9CA3AF;
    cursor: pointer;
    padding: 4px;
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    margin-top: 4px;
    max-height: 300px;
    overflow-y: auto;
    z-index: 1000;
}

.dark .search-results {
    background: #1F2937;
    border-color: #374151;
}

.search-result-item {
    padding: 12px 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #F3F4F6;
    transition: background 0.2s;
}

.dark .search-result-item {
    border-color: #374151;
}

.search-result-item:hover {
    background: #F3F4F6;
}

.dark .search-result-item:hover {
    background: #374151;
}

.result-name {
    font-weight: 600;
    color: #111827;
}

.dark .result-name {
    color: #F9FAFB;
}

.result-status {
    font-size: 12px;
    font-weight: 500;
}

.result-customer {
    margin-left: auto;
    font-size: 12px;
    color: #6B7280;
}

.site-select {
    padding: 10px 14px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    font-size: 14px;
    background: #fff;
    cursor: pointer;
    flex-shrink: 0;
}

.dark .site-select {
    background: #374151;
    border-color: #4B5563;
    color: #fff;
}

/* Stats */
.header-stats {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.stat-pill {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #F9FAFB;
    border-radius: 20px;
    font-size: 13px;
}

.dark .stat-pill {
    background: #374151;
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

.dark .stat-value {
    color: #F9FAFB;
}

.stat-label {
    color: #6B7280;
    display: none;
}

@media (min-width: 1024px) {
    .stat-label { display: inline; }
}

.stat-pill.occupancy {
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #fff;
}

.stat-pill.occupancy .stat-value,
.stat-pill.occupancy .stat-label { color: #fff; }

.stat-pill.alerts {
    background: #FEF2F2;
    color: #DC2626;
    cursor: pointer;
}

.stat-pill.alerts:hover {
    background: #FEE2E2;
}

/* Actions */
.header-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.view-mode-selector {
    display: flex;
    background: #F3F4F6;
    border-radius: 8px;
    padding: 3px;
}

.dark .view-mode-selector {
    background: #374151;
}

.view-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    color: #6B7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.view-btn:hover { color: #374151; }
.view-btn.active { background: #fff; color: #3B82F6; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dark .view-btn.active { background: #4B5563; }

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px;
    background: #F3F4F6;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: #374151;
    font-size: 14px;
    transition: all 0.2s;
    position: relative;
}

.dark .action-btn {
    background: #374151;
    color: #E5E7EB;
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
    width: 16px;
    height: 16px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Zoom controls */
.zoom-controls {
    display: none;
    align-items: center;
    gap: 4px;
    background: #F3F4F6;
    padding: 3px;
    border-radius: 8px;
}

@media (min-width: 768px) {
    .zoom-controls { display: flex; }
}

.dark .zoom-controls {
    background: #374151;
}

.zoom-btn {
    width: 30px;
    height: 30px;
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

.dark .zoom-btn {
    color: #E5E7EB;
}

.zoom-btn:hover { background: #E5E7EB; }

.zoom-level {
    padding: 0 8px;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    min-width: 45px;
    text-align: center;
}

.dark .zoom-level {
    color: #E5E7EB;
}

/* Dropdown */
.dropdown {
    position: relative;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: #fff;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    padding: 4px;
    display: none;
    z-index: 1000;
}

.dropdown:hover .dropdown-menu {
    display: block;
}

.dropdown-menu button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    color: #374151;
    border-radius: 6px;
    white-space: nowrap;
}

.dropdown-menu button:hover {
    background: #F3F4F6;
}

.editor-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.editor-btn:hover { opacity: 0.9; }

.btn-text {
    display: none;
}

@media (min-width: 1024px) {
    .btn-text { display: inline; }
}

/* ============================================
   KIOSK HEADER
   ============================================ */
.kiosk-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 40px;
    background: linear-gradient(135deg, #1E293B, #0F172A);
    color: #fff;
}

.kiosk-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 28px;
    font-weight: 700;
}

.kiosk-stats {
    display: flex;
    gap: 60px;
}

.kiosk-stat {
    text-align: center;
}

.kiosk-stat-value {
    display: block;
    font-size: 48px;
    font-weight: 700;
    color: #10B981;
}

.kiosk-stat-label {
    font-size: 16px;
    color: rgba(255,255,255,0.7);
}

.kiosk-time {
    font-size: 32px;
    font-weight: 300;
}

.kiosk-exit {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.1);
    border: none;
    border-radius: 50%;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s;
}

.kiosk-header:hover .kiosk-exit {
    opacity: 1;
}

/* ============================================
   FILTER PANEL
   ============================================ */
.filter-panel {
    position: absolute;
    top: 65px;
    right: 16px;
    width: 300px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    z-index: 200;
    padding: 16px;
}

.dark .filter-panel {
    background: #1F2937;
}

.slide-enter-active,
.slide-leave-active {
    transition: all 0.2s ease;
}

.slide-enter-from,
.slide-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.filter-header h3 {
    margin: 0;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #111827;
}

.dark .filter-header h3 {
    color: #F9FAFB;
}

.clear-btn {
    background: none;
    border: none;
    color: #3B82F6;
    font-size: 13px;
    cursor: pointer;
}

.filter-section {
    margin-bottom: 16px;
}

.filter-section label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
    margin-bottom: 8px;
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

.dark .status-filter-btn {
    background: #374151;
    color: #E5E7EB;
}

.status-filter-btn:hover { background: #F3F4F6; }
.status-filter-btn.active { border-color: #3B82F6; background: #EFF6FF; }

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.option-filters {
    display: flex;
    gap: 8px;
}

.option-btn {
    flex: 1;
    padding: 8px;
    background: #F9FAFB;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.dark .option-btn {
    background: #374151;
    border-color: #4B5563;
    color: #E5E7EB;
}

.option-btn.active {
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

.dark .filter-results {
    border-color: #374151;
}

/* ============================================
   STATS PANEL
   ============================================ */
.stats-panel {
    position: absolute;
    top: 65px;
    right: 16px;
    width: 320px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    z-index: 200;
    max-height: calc(100vh - 120px);
    overflow-y: auto;
}

.dark .stats-panel {
    background: #1F2937;
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.2s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    opacity: 0;
    transform: translateX(10px);
}

.stats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #E5E7EB;
}

.dark .stats-header {
    border-color: #374151;
}

.stats-header h3 {
    margin: 0;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #111827;
}

.dark .stats-header h3 {
    color: #F9FAFB;
}

.stats-content {
    padding: 16px;
}

.stats-card {
    background: #F9FAFB;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 12px;
}

.dark .stats-card {
    background: #374151;
}

.stats-card h4 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
}

.occupation-chart {
    display: flex;
    justify-content: center;
}

.occupation-ring {
    position: relative;
    width: 100px;
    height: 100px;
}

.occupation-ring svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.occupation-value {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    color: #3B82F6;
}

.revenue-stats {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.revenue-item {
    display: flex;
    justify-content: space-between;
}

.revenue-label {
    color: #6B7280;
    font-size: 13px;
}

.revenue-value {
    font-weight: 600;
    color: #10B981;
}

.revenue-value.potential {
    color: #6B7280;
}

.dark .revenue-value {
    color: #34D399;
}

.revenue-bar {
    height: 8px;
    background: #E5E7EB;
    border-radius: 4px;
    overflow: hidden;
}

.dark .revenue-bar {
    background: #4B5563;
}

.revenue-fill {
    height: 100%;
    background: linear-gradient(90deg, #10B981, #3B82F6);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.status-breakdown {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.breakdown-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.breakdown-dot {
    width: 10px;
    height: 10px;
    border-radius: 3px;
}

.breakdown-label {
    flex: 1;
    font-size: 13px;
    color: #374151;
}

.dark .breakdown-label {
    color: #E5E7EB;
}

.breakdown-value {
    font-weight: 600;
    color: #111827;
}

.dark .breakdown-value {
    color: #F9FAFB;
}

.alerts-card {
    background: #FEF2F2;
}

.dark .alerts-card {
    background: #7F1D1D;
}

.alerts-card h4 {
    color: #DC2626;
}

.alerts-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.alert-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #DC2626;
}

.text-red { color: #EF4444; }
.text-orange { color: #F97316; }
.text-purple { color: #8B5CF6; }

/* ============================================
   LEGEND
   ============================================ */
.legend-bar {
    display: flex;
    justify-content: center;
    gap: 14px;
    padding: 8px 16px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
    flex-wrap: wrap;
}

.dark .legend-bar {
    background: #1F2937;
    border-color: #374151;
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

.heatmap-legend {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 10px 16px;
    background: #fff;
    border-bottom: 1px solid #E5E7EB;
}

.dark .heatmap-legend {
    background: #1F2937;
    border-color: #374151;
}

.heatmap-title {
    font-weight: 600;
    color: #374151;
}

.dark .heatmap-title {
    color: #E5E7EB;
}

.heatmap-gradient {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6B7280;
}

.gradient-bar {
    width: 120px;
    height: 12px;
    border-radius: 6px;
}

.gradient-bar.heatmap-revenue {
    background: linear-gradient(90deg, #EF4444, #FBBF24, #10B981);
}

.gradient-bar.heatmap-occupation {
    background: linear-gradient(90deg, #E0E7FF, #3B82F6, #1E3A8A);
}

/* ============================================
   PLAN CONTAINER
   ============================================ */
.plan-container {
    flex: 1;
    position: relative;
    overflow: hidden;
    background: #E5E7EB;
}

.dark .plan-container {
    background: #0F172A;
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

.dark .loading-overlay {
    background: rgba(17,24,39,0.9);
    color: #fff;
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

.dark .plan-svg {
    background: #1F2937;
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
}

.box-rect.lift {
    cursor: default;
}

.box-rect.highlighted {
    animation: highlight-pulse 1s ease-in-out infinite;
}

@keyframes highlight-pulse {
    0%, 100% { filter: brightness(1); }
    50% { filter: brightness(1.3); }
}

.box-rect.has-alert {
    animation: alert-pulse 2s ease-in-out infinite;
}

@keyframes alert-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
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

.alert-indicator {
    animation: blink 1s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.alert-icon {
    font-size: 8px;
    font-weight: bold;
    text-anchor: middle;
    dominant-baseline: middle;
}

.annotation-indicator {
    cursor: pointer;
}

/* ============================================
   POPUP
   ============================================ */
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

.dark .popup {
    background: #1F2937;
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
    padding: 5px 0;
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

.dark .popup-value {
    color: #F9FAFB;
}

.popup-value.link { color: #3B82F6; }

.popup-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    margin: 8px 0;
}

.popup-alert.red {
    background: #FEF2F2;
    color: #DC2626;
}

.popup-alert.orange {
    background: #FFF7ED;
    color: #EA580C;
}

.popup-divider {
    height: 1px;
    background: #E5E7EB;
    margin: 8px 0;
}

.dark .popup-divider {
    background: #374151;
}

.popup-annotation {
    background: #F5F3FF;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 12px;
    color: #7C3AED;
    margin-top: 8px;
}

.popup-footer {
    padding: 10px 16px;
    background: #F9FAFB;
    font-size: 12px;
    color: #6B7280;
    text-align: center;
}

.dark .popup-footer {
    background: #374151;
}

/* ============================================
   MINIMAP
   ============================================ */
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
    cursor: crosshair;
}

.dark .minimap {
    background: #374151;
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

.dark .minimap-toggle {
    background: #374151;
    color: #E5E7EB;
}

/* ============================================
   MODAL
   ============================================ */
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

.modal.dark {
    background: #1F2937;
}

.modal.modal-sm {
    max-width: 400px;
}

.modal-header {
    padding: 20px 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: #3B82F6;
}

.modal-title-group h2 {
    margin: 0 0 6px;
    font-size: 22px;
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

.modal-alert {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 16px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 500;
}

.modal-alert.red {
    background: #FEF2F2;
    color: #DC2626;
}

.modal-alert.orange {
    background: #FFF7ED;
    color: #EA580C;
}

.modal-section {
    margin-bottom: 20px;
}

.modal-section:last-child { margin-bottom: 0; }

.modal-section h3 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    color: #6B7280;
    text-transform: uppercase;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.info-item {
    padding: 12px;
    background: #F9FAFB;
    border-radius: 8px;
}

.dark .info-item {
    background: #374151;
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

.dark .info-value {
    color: #F9FAFB;
}

.contract-card {
    padding: 14px;
    background: #F9FAFB;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 12px;
}

.dark .contract-card {
    background: #374151;
}

.contract-card:hover { background: #F3F4F6; }

.contract-number {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #3B82F6;
    margin-bottom: 6px;
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

.dark .customer-card {
    background: #374151;
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

.dark .customer-name {
    color: #F9FAFB;
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

.dark .available-section {
    background: #064E3B;
}

.available-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #059669;
    font-size: 18px;
    font-weight: 600;
}

.dark .available-badge {
    color: #34D399;
}

.annotation-display {
    background: #F5F3FF;
    padding: 12px;
    border-radius: 8px;
}

.dark .annotation-display {
    background: #4C1D95;
}

.annotation-display p {
    margin: 0 0 8px;
    font-size: 14px;
    color: #5B21B6;
}

.dark .annotation-display p {
    color: #DDD6FE;
}

.btn-text-danger {
    background: none;
    border: none;
    color: #DC2626;
    font-size: 12px;
    cursor: pointer;
}

.modal-footer {
    padding: 16px 24px;
    background: #F9FAFB;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    flex-wrap: wrap;
}

.dark .modal-footer {
    background: #374151;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.btn-sm {
    padding: 8px 14px;
    font-size: 13px;
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

/* Form elements */
.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.dark .form-group label {
    color: #E5E7EB;
}

.form-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
    resize: vertical;
}

.dark .form-textarea {
    background: #374151;
    border-color: #4B5563;
    color: #fff;
}

.form-select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    font-size: 14px;
}

.dark .form-select {
    background: #374151;
    border-color: #4B5563;
    color: #fff;
}

/* ============================================
   REAL-TIME INDICATOR
   ============================================ */
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

.dark .realtime-indicator {
    background: #374151;
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

/* ============================================
   KEYBOARD SHORTCUTS
   ============================================ */
.shortcuts-hint {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: none;
    gap: 16px;
    padding: 8px 16px;
    background: rgba(255,255,255,0.95);
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-size: 11px;
    color: #6B7280;
    z-index: 100;
}

@media (min-width: 1024px) {
    .shortcuts-hint { display: flex; }
}

.dark .shortcuts-hint {
    background: rgba(55, 65, 81, 0.95);
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

.dark .shortcuts-hint kbd {
    background: #4B5563;
    border-color: #6B7280;
}

/* ============================================
   PRINT STYLES
   ============================================ */
@media print {
    .plan-header,
    .sidebar,
    .filter-panel,
    .stats-panel,
    .minimap,
    .realtime-indicator,
    .shortcuts-hint,
    .popup,
    .modal-overlay {
        display: none !important;
    }

    .plan-pro {
        position: static;
        background: #fff;
    }

    .plan-container {
        overflow: visible;
    }

    .plan-svg {
        transform: none !important;
        width: 100%;
        height: auto;
    }

    .legend-bar {
        position: static;
        border: 1px solid #ccc;
        margin-bottom: 20px;
    }
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 768px) {
    .header-stats {
        display: none;
    }

    .view-mode-selector {
        display: none;
    }

    .filter-panel,
    .stats-panel {
        right: 10px;
        left: 10px;
        width: auto;
    }

    .legend-bar {
        display: none;
    }

    .minimap {
        width: 120px;
        height: 80px;
    }

    .kiosk-header {
        flex-direction: column;
        gap: 20px;
        padding: 20px;
    }

    .kiosk-stats {
        gap: 30px;
    }

    .kiosk-stat-value {
        font-size: 36px;
    }

    .quick-edit-toolbar {
        padding: 8px 12px;
    }

    .edit-toolbar-content {
        flex-direction: column;
        gap: 10px;
    }

    .edit-actions {
        flex-wrap: wrap;
    }
}

/* ============================================
   QUICK-EDIT MODE
   ============================================ */
.quick-edit-btn.active {
    background: #8B5CF6 !important;
    color: #fff !important;
}

.quick-edit-btn .btn-label {
    margin-left: 6px;
    font-size: 11px;
    font-weight: 600;
}

/* Selection highlight animation */
.selection-highlight {
    animation: selectionPulse 1.5s infinite ease-in-out;
}

@keyframes selectionPulse {
    0%, 100% { opacity: 1; stroke-dasharray: 4,2; }
    50% { opacity: 0.6; stroke-dasharray: 2,4; }
}

/* Box group in quick-edit mode */
.box-group.quick-edit-mode {
    cursor: move;
}

.box-group.quick-edit-mode:hover .box-rect {
    filter: brightness(1.1);
}

.box-group.selected .box-rect {
    filter: brightness(1.05);
}

.box-group.dragging {
    opacity: 0.8;
}

.box-group.dragging .box-rect {
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}

/* Quick-Edit Toolbar */
.quick-edit-toolbar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(249,250,251,0.98));
    border-top: 2px solid #8B5CF6;
    padding: 12px 20px;
    z-index: 1000;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}

.dark .quick-edit-toolbar {
    background: linear-gradient(180deg, rgba(31,41,55,0.98), rgba(17,24,39,0.98));
}

.edit-toolbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1400px;
    margin: 0 auto;
    gap: 20px;
}

.selection-info {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 280px;
}

.selection-hint {
    color: #6B7280;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.dark .selection-hint {
    color: #9CA3AF;
}

.selection-count {
    color: #8B5CF6;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.edit-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    justify-content: center;
}

.edit-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    color: #374151;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.dark .edit-action-btn {
    background: #374151;
    border-color: #4B5563;
    color: #E5E7EB;
}

.edit-action-btn:hover {
    background: #E5E7EB;
    border-color: #D1D5DB;
}

.dark .edit-action-btn:hover {
    background: #4B5563;
}

.edit-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.unsaved-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    background: #FEF3C7;
    color: #B45309;
    font-size: 12px;
    font-weight: 500;
    border-radius: 20px;
    animation: unsavedPulse 2s infinite;
}

@keyframes unsavedPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.dark .unsaved-badge {
    background: #78350F;
    color: #FDE68A;
}

.edit-control-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.edit-control-btn.save {
    background: #D1D5DB;
    color: #6B7280;
}

.edit-control-btn.save.has-changes {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
    color: #fff;
}

.edit-control-btn.save.has-changes:hover {
    background: linear-gradient(135deg, #7C3AED, #6D28D9);
}

.edit-control-btn.save:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.edit-control-btn.cancel {
    background: #FEE2E2;
    color: #DC2626;
}

.edit-control-btn.cancel:hover {
    background: #FECACA;
}

.dark .edit-control-btn.cancel {
    background: #7F1D1D;
    color: #FCA5A5;
}

.edit-control-btn.exit {
    background: #F3F4F6;
    color: #6B7280;
}

.edit-control-btn.exit:hover {
    background: #E5E7EB;
}

.dark .edit-control-btn.exit {
    background: #374151;
    color: #9CA3AF;
}

/* Status Change Modal */
.status-modal .modal-body {
    padding: 24px;
}

.status-modal-info {
    margin: 0 0 20px;
    text-align: center;
    color: #6B7280;
    font-size: 14px;
}

.dark .status-modal-info {
    color: #9CA3AF;
}

.status-modal-info strong {
    color: #111827;
}

.dark .status-modal-info strong {
    color: #F9FAFB;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
}

.status-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: #F9FAFB;
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.dark .status-option {
    background: #374151;
}

.status-option:hover {
    background: #fff;
    border-color: var(--status-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.dark .status-option:hover {
    background: #4B5563;
}

.status-dot {
    width: 14px;
    height: 14px;
    border-radius: 4px;
    flex-shrink: 0;
}

.status-label {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
}

.dark .status-label {
    color: #E5E7EB;
}

/* Slide-up transition for toolbar */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

/* Toast Notification */
:global(.quick-toast) {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%) translateY(20px);
    padding: 12px 24px;
    background: #10B981;
    color: #fff;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    z-index: 10000;
    opacity: 0;
    transition: all 0.3s ease;
}

:global(.quick-toast.show) {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

:global(.quick-toast.error) {
    background: #EF4444;
}
</style>
