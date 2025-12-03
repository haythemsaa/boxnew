<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    boxes: {
        type: Array,
        required: true
    },
    configuration: {
        type: Object,
        default: () => ({
            canvas_width: 1200,
            canvas_height: 800,
            grid_size: 20,
            show_grid: true
        })
    },
    readonly: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['box-click', 'box-hover']);

// SVG viewport state
const svgRef = ref(null);
const viewBox = ref({ x: 0, y: 0, width: 1200, height: 800 });
const scale = ref(1);
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });

// Tooltip state
const tooltipVisible = ref(false);
const tooltipPosition = ref({ x: 0, y: 0 });
const hoveredBox = ref(null);

// Status colors (inspired by Buxida)
const statusColors = {
    available: {
        fill: '#22c55e',      // Green
        stroke: '#16a34a',
        label: 'Libre',
        icon: '✓'
    },
    occupied: {
        fill: '#3b82f6',      // Blue
        stroke: '#2563eb',
        label: 'Occupé',
        icon: '●'
    },
    reserved: {
        fill: '#f97316',      // Orange
        stroke: '#ea580c',
        label: 'Réservé',
        icon: '◐'
    },
    maintenance: {
        fill: '#ef4444',      // Red
        stroke: '#dc2626',
        label: 'Maintenance',
        icon: '⚠'
    },
    ending: {
        fill: '#eab308',      // Yellow
        stroke: '#ca8a04',
        label: 'Fin de contrat',
        icon: '⏱'
    },
    litigation: {
        fill: '#a855f7',      // Purple
        stroke: '#9333ea',
        label: 'Litige',
        icon: '!'
    },
    unavailable: {
        fill: '#6b7280',      // Gray
        stroke: '#4b5563',
        label: 'Indisponible',
        icon: '✗'
    }
};

// Calculate box position based on index if not set
const getBoxPosition = (box, index) => {
    if (box.position && box.position.x !== undefined && box.position.y !== undefined) {
        return {
            x: box.position.x,
            y: box.position.y,
            width: box.position.width || 80,
            height: box.position.height || 60
        };
    }

    // Auto-grid layout
    const cols = Math.floor(props.configuration.canvas_width / 100);
    const col = index % cols;
    const row = Math.floor(index / cols);

    return {
        x: col * 95 + 20,
        y: row * 75 + 20,
        width: 85,
        height: 65
    };
};

// Get status color
const getBoxColor = (status) => {
    return statusColors[status] || statusColors.unavailable;
};

// Mouse handlers for panning
const handleMouseDown = (e) => {
    if (e.target === svgRef.value || e.target.classList.contains('plan-background')) {
        isPanning.value = true;
        panStart.value = { x: e.clientX, y: e.clientY };
        e.preventDefault();
    }
};

const handleMouseMove = (e) => {
    if (isPanning.value) {
        const dx = (e.clientX - panStart.value.x) / scale.value;
        const dy = (e.clientY - panStart.value.y) / scale.value;

        viewBox.value.x -= dx;
        viewBox.value.y -= dy;

        panStart.value = { x: e.clientX, y: e.clientY };
    }
};

const handleMouseUp = () => {
    isPanning.value = false;
};

// Zoom with mouse wheel
const handleWheel = (e) => {
    e.preventDefault();

    const rect = svgRef.value.getBoundingClientRect();
    const mouseX = e.clientX - rect.left;
    const mouseY = e.clientY - rect.top;

    const zoomFactor = e.deltaY > 0 ? 1.1 : 0.9;
    const newScale = Math.max(0.25, Math.min(4, scale.value * (1 / zoomFactor)));

    // Adjust viewBox for zoom towards mouse position
    const svgX = viewBox.value.x + (mouseX / rect.width) * viewBox.value.width;
    const svgY = viewBox.value.y + (mouseY / rect.height) * viewBox.value.height;

    const newWidth = props.configuration.canvas_width / newScale;
    const newHeight = props.configuration.canvas_height / newScale;

    viewBox.value.width = newWidth;
    viewBox.value.height = newHeight;
    viewBox.value.x = svgX - (mouseX / rect.width) * newWidth;
    viewBox.value.y = svgY - (mouseY / rect.height) * newHeight;

    scale.value = newScale;
};

// Box hover handlers
const handleBoxMouseEnter = (box, event) => {
    if (props.readonly) return;

    hoveredBox.value = box;
    tooltipVisible.value = true;

    const rect = svgRef.value.getBoundingClientRect();
    tooltipPosition.value = {
        x: event.clientX - rect.left + 15,
        y: event.clientY - rect.top - 10
    };

    emit('box-hover', box);
};

const handleBoxMouseLeave = () => {
    tooltipVisible.value = false;
    hoveredBox.value = null;
};

const handleBoxMouseMove = (event) => {
    if (!tooltipVisible.value) return;

    const rect = svgRef.value.getBoundingClientRect();
    tooltipPosition.value = {
        x: event.clientX - rect.left + 15,
        y: event.clientY - rect.top - 10
    };
};

// Box click handler
const handleBoxClick = (box) => {
    emit('box-click', box);
};

// Zoom controls
const zoomIn = () => {
    const newScale = Math.min(4, scale.value * 1.25);
    const centerX = viewBox.value.x + viewBox.value.width / 2;
    const centerY = viewBox.value.y + viewBox.value.height / 2;

    viewBox.value.width = props.configuration.canvas_width / newScale;
    viewBox.value.height = props.configuration.canvas_height / newScale;
    viewBox.value.x = centerX - viewBox.value.width / 2;
    viewBox.value.y = centerY - viewBox.value.height / 2;

    scale.value = newScale;
};

const zoomOut = () => {
    const newScale = Math.max(0.25, scale.value / 1.25);
    const centerX = viewBox.value.x + viewBox.value.width / 2;
    const centerY = viewBox.value.y + viewBox.value.height / 2;

    viewBox.value.width = props.configuration.canvas_width / newScale;
    viewBox.value.height = props.configuration.canvas_height / newScale;
    viewBox.value.x = centerX - viewBox.value.width / 2;
    viewBox.value.y = centerY - viewBox.value.height / 2;

    scale.value = newScale;
};

const resetView = () => {
    scale.value = 1;
    viewBox.value = {
        x: 0,
        y: 0,
        width: props.configuration.canvas_width,
        height: props.configuration.canvas_height
    };
};

// Computed viewBox string
const viewBoxString = computed(() => {
    return `${viewBox.value.x} ${viewBox.value.y} ${viewBox.value.width} ${viewBox.value.height}`;
});

// Statistics
const statistics = computed(() => {
    const stats = {
        total: props.boxes.length,
        available: 0,
        occupied: 0,
        reserved: 0,
        maintenance: 0,
        ending: 0,
        litigation: 0
    };

    props.boxes.forEach(box => {
        if (stats[box.status] !== undefined) {
            stats[box.status]++;
        }
    });

    stats.occupancy_rate = stats.total > 0
        ? Math.round((stats.occupied / stats.total) * 100)
        : 0;

    return stats;
});

// Initialize viewBox
onMounted(() => {
    viewBox.value = {
        x: 0,
        y: 0,
        width: props.configuration.canvas_width,
        height: props.configuration.canvas_height
    };

    window.addEventListener('mouseup', handleMouseUp);
    window.addEventListener('mousemove', handleMouseMove);
});

onUnmounted(() => {
    window.removeEventListener('mouseup', handleMouseUp);
    window.removeEventListener('mousemove', handleMouseMove);
});
</script>

<template>
    <div class="interactive-plan-container">
        <!-- Statistics Bar -->
        <div class="stats-bar">
            <div class="stat-group">
                <div class="stat-item">
                    <span class="stat-icon">📦</span>
                    <span class="stat-label">TOTAL</span>
                    <span class="stat-value">{{ statistics.total }}</span>
                </div>
                <div class="stat-item stat-occupied">
                    <span class="stat-dot" style="background: #3b82f6"></span>
                    <span class="stat-label">OCCUPÉ</span>
                    <span class="stat-value">{{ statistics.occupied }}</span>
                </div>
                <div class="stat-item stat-available">
                    <span class="stat-dot" style="background: #22c55e"></span>
                    <span class="stat-label">LIBRE</span>
                    <span class="stat-value">{{ statistics.available }}</span>
                </div>
                <div class="stat-item stat-reserved">
                    <span class="stat-dot" style="background: #f97316"></span>
                    <span class="stat-label">RÉSERVÉ</span>
                    <span class="stat-value">{{ statistics.reserved }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-icon">📊</span>
                    <span class="stat-label">TAUX</span>
                    <span class="stat-value stat-rate">{{ statistics.occupancy_rate }}%</span>
                </div>
            </div>

            <!-- Zoom Controls -->
            <div class="zoom-controls">
                <button @click="zoomOut" class="zoom-btn" title="Zoom arrière">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/>
                    </svg>
                </button>
                <span class="zoom-level">{{ Math.round(scale * 100) }}%</span>
                <button @click="zoomIn" class="zoom-btn" title="Zoom avant">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/>
                    </svg>
                </button>
                <button @click="resetView" class="zoom-btn" title="Réinitialiser la vue">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend-bar">
            <span class="legend-title">Légende :</span>
            <div v-for="(color, status) in statusColors" :key="status" class="legend-item">
                <span class="legend-color" :style="{ background: color.fill }"></span>
                <span class="legend-label">{{ color.label }}</span>
            </div>
        </div>

        <!-- SVG Plan -->
        <div class="plan-wrapper">
            <svg
                ref="svgRef"
                :viewBox="viewBoxString"
                class="plan-svg"
                @mousedown="handleMouseDown"
                @wheel="handleWheel"
                preserveAspectRatio="xMidYMid meet"
            >
                <!-- Definitions for filters and gradients -->
                <defs>
                    <!-- Shadow filter -->
                    <filter id="boxShadow" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="2" dy="2" stdDeviation="3" flood-opacity="0.3"/>
                    </filter>

                    <!-- Hover glow filter -->
                    <filter id="hoverGlow" x="-50%" y="-50%" width="200%" height="200%">
                        <feGaussianBlur stdDeviation="4" result="blur"/>
                        <feMerge>
                            <feMergeNode in="blur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>

                    <!-- Grid pattern -->
                    <pattern id="grid" width="20" height="20" patternUnits="userSpaceOnUse">
                        <path d="M 20 0 L 0 0 0 20" fill="none" stroke="#e5e7eb" stroke-width="0.5"/>
                    </pattern>
                </defs>

                <!-- Background with grid -->
                <rect
                    class="plan-background"
                    x="0"
                    y="0"
                    :width="configuration.canvas_width"
                    :height="configuration.canvas_height"
                    fill="#f8fafc"
                />
                <rect
                    v-if="configuration.show_grid"
                    x="0"
                    y="0"
                    :width="configuration.canvas_width"
                    :height="configuration.canvas_height"
                    fill="url(#grid)"
                />

                <!-- Boxes -->
                <g class="boxes-layer">
                    <g
                        v-for="(box, index) in boxes"
                        :key="box.id"
                        class="box-group"
                        :class="{ 'box-hovered': hoveredBox?.id === box.id }"
                        @mouseenter="handleBoxMouseEnter(box, $event)"
                        @mouseleave="handleBoxMouseLeave"
                        @mousemove="handleBoxMouseMove"
                        @click="handleBoxClick(box)"
                    >
                        <!-- Box rectangle -->
                        <rect
                            :x="getBoxPosition(box, index).x"
                            :y="getBoxPosition(box, index).y"
                            :width="getBoxPosition(box, index).width"
                            :height="getBoxPosition(box, index).height"
                            :fill="getBoxColor(box.status).fill"
                            :stroke="getBoxColor(box.status).stroke"
                            stroke-width="2"
                            rx="4"
                            ry="4"
                            class="box-rect"
                            :filter="hoveredBox?.id === box.id ? 'url(#hoverGlow)' : 'url(#boxShadow)'"
                        />

                        <!-- Box number -->
                        <text
                            :x="getBoxPosition(box, index).x + getBoxPosition(box, index).width / 2"
                            :y="getBoxPosition(box, index).y + getBoxPosition(box, index).height / 2 - 6"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            class="box-number"
                            fill="white"
                            font-weight="bold"
                            font-size="12"
                        >
                            {{ box.number }}
                        </text>

                        <!-- Box volume -->
                        <text
                            :x="getBoxPosition(box, index).x + getBoxPosition(box, index).width / 2"
                            :y="getBoxPosition(box, index).y + getBoxPosition(box, index).height / 2 + 10"
                            text-anchor="middle"
                            dominant-baseline="middle"
                            class="box-volume"
                            fill="rgba(255,255,255,0.9)"
                            font-size="10"
                        >
                            {{ box.volume }}m³
                        </text>

                        <!-- Status indicator -->
                        <circle
                            v-if="box.status === 'ending' || box.status === 'litigation'"
                            :cx="getBoxPosition(box, index).x + getBoxPosition(box, index).width - 8"
                            :cy="getBoxPosition(box, index).y + 8"
                            r="6"
                            fill="#ef4444"
                            stroke="white"
                            stroke-width="1.5"
                        />
                    </g>
                </g>
            </svg>

            <!-- Tooltip -->
            <div
                v-if="tooltipVisible && hoveredBox"
                class="box-tooltip"
                :style="{ left: tooltipPosition.x + 'px', top: tooltipPosition.y + 'px' }"
            >
                <div class="tooltip-header" :style="{ background: getBoxColor(hoveredBox.status).fill }">
                    <span class="tooltip-number">{{ hoveredBox.number }}</span>
                    <span class="tooltip-status">{{ getBoxColor(hoveredBox.status).label }}</span>
                </div>
                <div class="tooltip-body">
                    <div class="tooltip-row">
                        <span class="tooltip-label">Volume :</span>
                        <span class="tooltip-value">{{ hoveredBox.volume }} m³</span>
                    </div>
                    <div class="tooltip-row" v-if="hoveredBox.dimensions">
                        <span class="tooltip-label">Dimensions :</span>
                        <span class="tooltip-value">{{ hoveredBox.dimensions }}</span>
                    </div>
                    <div class="tooltip-row">
                        <span class="tooltip-label">Prix :</span>
                        <span class="tooltip-value tooltip-price">{{ hoveredBox.current_price || hoveredBox.price }} €/mois</span>
                    </div>
                    <div v-if="hoveredBox.contract" class="tooltip-contract">
                        <div class="tooltip-row">
                            <span class="tooltip-label">Contrat :</span>
                            <span class="tooltip-value">{{ hoveredBox.contract.contract_number }}</span>
                        </div>
                        <div v-if="hoveredBox.contract.customer" class="tooltip-row">
                            <span class="tooltip-label">Client :</span>
                            <span class="tooltip-value">{{ hoveredBox.contract.customer.name }}</span>
                        </div>
                    </div>
                </div>
                <div class="tooltip-footer">
                    Cliquez pour voir les détails
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.interactive-plan-container {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 600px;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Statistics Bar */
.stats-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
    color: white;
}

.stat-group {
    display: flex;
    align-items: center;
    gap: 24px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stat-icon {
    font-size: 18px;
}

.stat-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid rgba(255,255,255,0.5);
}

.stat-label {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    opacity: 0.9;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 18px;
    font-weight: 700;
}

.stat-rate {
    color: #fbbf24;
}

/* Zoom Controls */
.zoom-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.15);
    padding: 6px 12px;
    border-radius: 8px;
}

.zoom-btn {
    padding: 6px;
    background: rgba(255,255,255,0.2);
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.zoom-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: scale(1.05);
}

.zoom-level {
    font-size: 13px;
    font-weight: 600;
    min-width: 50px;
    text-align: center;
}

/* Legend Bar */
.legend-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 10px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
}

.legend-title {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.1);
}

.legend-label {
    font-size: 12px;
    color: #64748b;
}

/* Plan Wrapper */
.plan-wrapper {
    flex: 1;
    position: relative;
    overflow: hidden;
    background: #f1f5f9;
}

.plan-svg {
    width: 100%;
    height: 100%;
    cursor: grab;
}

.plan-svg:active {
    cursor: grabbing;
}

/* Box Styles */
.box-group {
    cursor: pointer;
    transition: all 0.2s ease;
}

.box-rect {
    transition: all 0.2s ease;
}

.box-group:hover .box-rect {
    transform: scale(1.05);
    transform-origin: center;
}

.box-hovered .box-rect {
    stroke-width: 3;
}

.box-number {
    pointer-events: none;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.box-volume {
    pointer-events: none;
}

/* Tooltip */
.box-tooltip {
    position: absolute;
    z-index: 100;
    min-width: 220px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2), 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    pointer-events: none;
    animation: tooltipFadeIn 0.15s ease-out;
}

@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateY(5px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.tooltip-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    color: white;
}

.tooltip-number {
    font-size: 16px;
    font-weight: 700;
}

.tooltip-status {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    background: rgba(255,255,255,0.2);
    padding: 3px 8px;
    border-radius: 4px;
}

.tooltip-body {
    padding: 12px 14px;
}

.tooltip-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
}

.tooltip-label {
    font-size: 12px;
    color: #64748b;
}

.tooltip-value {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
}

.tooltip-price {
    color: #2563eb;
}

.tooltip-contract {
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px dashed #e2e8f0;
}

.tooltip-footer {
    padding: 8px 14px;
    background: #f8fafc;
    font-size: 11px;
    color: #64748b;
    text-align: center;
    border-top: 1px solid #e2e8f0;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .interactive-plan-container {
        background: #1e293b;
    }

    .legend-bar {
        background: #334155;
        border-color: #475569;
    }

    .legend-title {
        color: #cbd5e1;
    }

    .legend-label {
        color: #94a3b8;
    }

    .plan-wrapper {
        background: #0f172a;
    }

    .box-tooltip {
        background: #1e293b;
    }

    .tooltip-body {
        background: #1e293b;
    }

    .tooltip-label {
        color: #94a3b8;
    }

    .tooltip-value {
        color: #f1f5f9;
    }

    .tooltip-footer {
        background: #334155;
        border-color: #475569;
        color: #94a3b8;
    }
}
</style>
