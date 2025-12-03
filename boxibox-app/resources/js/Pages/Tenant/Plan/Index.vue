<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import TenantLayout from '@/Layouts/TenantLayout.vue';
import {
    MapIcon,
    CubeIcon,
    UserIcon,
    DocumentTextIcon,
    PencilSquareIcon,
    XMarkIcon,
    MagnifyingGlassMinusIcon,
    MagnifyingGlassPlusIcon,
    ArrowsPointingOutIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    sites: Array,
    currentSite: Object,
    elements: Array,
    configuration: Object,
    statistics: Object,
    boxTypes: Array,
    floors: Array,
});

const selectedSite = ref(props.currentSite?.id);
const zoom = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const dragStart = ref({ x: 0, y: 0 });
const selectedElement = ref(null);
const showBoxModal = ref(false);
const boxDetails = ref(null);
const loadingBoxDetails = ref(false);

const canvasRef = ref(null);

// Change site
const changeSite = () => {
    router.get(route('tenant.plan.index'), { site_id: selectedSite.value });
};

// Zoom controls
const zoomIn = () => {
    zoom.value = Math.min(zoom.value * 1.2, 3);
};

const zoomOut = () => {
    zoom.value = Math.max(zoom.value / 1.2, 0.3);
};

const resetView = () => {
    zoom.value = 1;
    panX.value = 0;
    panY.value = 0;
};

// Pan controls
const startDrag = (e) => {
    if (e.target === canvasRef.value || e.target.classList.contains('canvas-background')) {
        isDragging.value = true;
        dragStart.value = { x: e.clientX - panX.value, y: e.clientY - panY.value };
    }
};

const onDrag = (e) => {
    if (isDragging.value) {
        panX.value = e.clientX - dragStart.value.x;
        panY.value = e.clientY - dragStart.value.y;
    }
};

const stopDrag = () => {
    isDragging.value = false;
};

// Mouse wheel zoom
const onWheel = (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? 0.9 : 1.1;
    zoom.value = Math.min(Math.max(zoom.value * delta, 0.3), 3);
};

// Click on element
const onElementClick = async (element) => {
    if (element.element_type === 'box' && element.box_id) {
        selectedElement.value = element;
        showBoxModal.value = true;
        loadingBoxDetails.value = true;

        try {
            const response = await fetch(route('tenant.plan.box-details', element.box_id));
            boxDetails.value = await response.json();
        } catch (error) {
            console.error('Error loading box details:', error);
        } finally {
            loadingBoxDetails.value = false;
        }
    }
};

const closeModal = () => {
    showBoxModal.value = false;
    selectedElement.value = null;
    boxDetails.value = null;
};

// Get element style
const getElementStyle = (element) => {
    let backgroundColor = element.fill_color || element.status_color || '#cccccc';

    // For boxes, use status color
    if (element.element_type === 'box') {
        backgroundColor = element.status_color;
    }

    return {
        left: `${element.x}px`,
        top: `${element.y}px`,
        width: `${element.width}px`,
        height: `${element.height}px`,
        transform: `rotate(${element.rotation}deg)`,
        backgroundColor,
        borderColor: element.stroke_color || '#1e3a5f',
        borderWidth: `${element.stroke_width || 1}px`,
        opacity: element.opacity,
        zIndex: element.z_index,
    };
};

// Get element class
const getElementClass = (element) => {
    const classes = ['plan-element', `element-${element.element_type}`];

    if (element.element_type === 'box') {
        const isOccupied = element.box_info?.status === 'occupied';
        classes.push(isOccupied ? 'box-occupied' : 'box-available');
    }

    return classes.join(' ');
};

// Transform style for canvas
const transformStyle = computed(() => ({
    transform: `translate(${panX.value}px, ${panY.value}px) scale(${zoom.value})`,
    transformOrigin: '0 0',
}));

onMounted(() => {
    window.addEventListener('mouseup', stopDrag);
    window.addEventListener('mousemove', onDrag);
});

onUnmounted(() => {
    window.removeEventListener('mouseup', stopDrag);
    window.removeEventListener('mousemove', onDrag);
});
</script>

<template>
    <TenantLayout title="Plan des boxes">
        <!-- Gradient Header -->
        <div class="relative overflow-hidden bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 -mt-6 pt-10 pb-32 px-4 sm:px-6 lg:px-8">
            <!-- Decorative circles -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white/10 rounded-full -ml-48 mb-0 blur-3xl"></div>

            <div class="max-w-7xl mx-auto relative z-10 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <MapIcon class="w-10 h-10 text-white" />
                    <div>
                        <h1 class="text-4xl font-bold text-white">Plan des boxes</h1>
                        <p class="mt-2 text-indigo-100">État des boxes en temps réel</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Site selector -->
                    <select
                        v-model="selectedSite"
                        @change="changeSite"
                        class="rounded-xl border-0 shadow-lg text-sm font-medium px-4 py-2 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
                    >
                        <option v-for="site in sites" :key="site.id" :value="site.id">
                            {{ site.name }}
                        </option>
                    </select>

                    <!-- Edit button -->
                    <Link
                        :href="route('tenant.plan.editor', { site_id: currentSite?.id })"
                        class="inline-flex items-center px-4 py-2 bg-white/20 text-white rounded-xl hover:bg-white/30 backdrop-blur-sm transition-colors shadow-lg border border-white/30 font-medium text-sm"
                    >
                        <PencilSquareIcon class="w-4 h-4 mr-2" />
                        Éditer le plan
                    </Link>
                </div>
            </div>
        </div>

        <div class="plan-container">

            <!-- Statistics bar -->
            <div v-if="statistics" class="stats-bar">
                <div class="stat-item">
                    <span class="stat-label">NB BOX :</span>
                    <span class="stat-value">{{ statistics.total }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">OCCUPÉ :</span>
                    <span class="stat-value text-blue-600">{{ statistics.occupied }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">LIBRE :</span>
                    <span class="stat-value text-green-600">{{ statistics.available }}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">TAUX :</span>
                    <span class="stat-value">{{ statistics.occupancy_rate }}%</span>
                </div>
            </div>

            <div class="plan-content">
                <!-- Legend -->
                <div class="plan-legend">
                    <h3 class="font-semibold text-gray-700 mb-3">Légende</h3>

                    <div class="legend-item">
                        <div class="legend-color bg-green-500"></div>
                        <span>Disponible</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color bg-blue-500"></div>
                        <span>Occupé</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color bg-orange-500"></div>
                        <span>Réservé</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color bg-red-500"></div>
                        <span>Maintenance</span>
                    </div>

                    <hr class="my-4" />

                    <h3 class="font-semibold text-gray-700 mb-3">Types de boxes</h3>
                    <div v-for="boxType in boxTypes" :key="boxType.size" class="legend-box-type">
                        <span class="font-medium">{{ boxType.size }}</span>
                        <div class="flex gap-2 text-sm">
                            <span class="text-blue-600">{{ boxType.occupied }}</span>
                            <span>/</span>
                            <span>{{ boxType.total }}</span>
                        </div>
                    </div>

                    <!-- Zoom controls -->
                    <hr class="my-4" />
                    <h3 class="font-semibold text-gray-700 mb-3">Zoom</h3>
                    <div class="flex items-center gap-2">
                        <button @click="zoomOut" class="zoom-btn">
                            <MagnifyingGlassMinusIcon class="w-5 h-5" />
                        </button>
                        <span class="text-sm font-medium">{{ Math.round(zoom * 100) }}%</span>
                        <button @click="zoomIn" class="zoom-btn">
                            <MagnifyingGlassPlusIcon class="w-5 h-5" />
                        </button>
                        <button @click="resetView" class="zoom-btn">
                            <ArrowsPointingOutIcon class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Canvas area -->
                <div
                    class="plan-canvas-container"
                    @mousedown="startDrag"
                    @wheel="onWheel"
                    ref="canvasRef"
                >
                    <div
                        class="plan-canvas canvas-background"
                        :style="{
                            width: `${configuration?.canvas_width || 1920}px`,
                            height: `${configuration?.canvas_height || 1080}px`,
                            ...transformStyle
                        }"
                    >
                        <!-- Elements -->
                        <div
                            v-for="element in elements"
                            :key="element.id"
                            :class="getElementClass(element)"
                            :style="getElementStyle(element)"
                            @click.stop="onElementClick(element)"
                        >
                            <div class="element-label" v-if="element.label">
                                {{ element.label }}
                            </div>
                            <div class="element-size" v-if="element.box_info?.size">
                                {{ element.box_info.size }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Box details modal -->
            <div v-if="showBoxModal" class="modal-overlay" @click="closeModal">
                <div class="modal-content" @click.stop>
                    <div class="modal-header">
                        <h3 class="text-lg font-semibold">
                            Box {{ boxDetails?.box?.number || selectedElement?.label }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <XMarkIcon class="w-6 h-6" />
                        </button>
                    </div>

                    <div v-if="loadingBoxDetails" class="p-8 text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                        <p class="mt-2 text-gray-500">Chargement...</p>
                    </div>

                    <div v-else-if="boxDetails" class="modal-body">
                        <!-- Box info -->
                        <div class="info-section">
                            <h4 class="info-title">
                                <CubeIcon class="w-5 h-5" />
                                Informations du box
                            </h4>
                            <div class="info-grid">
                                <div>
                                    <span class="info-label">Code</span>
                                    <span class="info-value">{{ boxDetails.box.number }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Taille</span>
                                    <span class="info-value">{{ boxDetails.box.volume }} m³</span>
                                </div>
                                <div>
                                    <span class="info-label">Dimensions</span>
                                    <span class="info-value">{{ boxDetails.box.dimensions }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Prix mensuel</span>
                                    <span class="info-value">{{ boxDetails.box.price }} €</span>
                                </div>
                                <div>
                                    <span class="info-label">Statut</span>
                                    <span :class="['status-badge', boxDetails.contract ? 'status-occupied' : 'status-available']">
                                        {{ boxDetails.contract ? 'Occupé' : 'Disponible' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer info -->
                        <div v-if="boxDetails.customer" class="info-section">
                            <h4 class="info-title">
                                <UserIcon class="w-5 h-5" />
                                Client
                            </h4>
                            <div class="info-grid">
                                <div>
                                    <span class="info-label">Nom</span>
                                    <span class="info-value">{{ boxDetails.customer.name }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Email</span>
                                    <span class="info-value">{{ boxDetails.customer.email }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Téléphone</span>
                                    <span class="info-value">{{ boxDetails.customer.phone || '-' }}</span>
                                </div>
                            </div>
                            <Link
                                :href="route('tenant.customers.show', boxDetails.customer.id)"
                                class="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium inline-flex items-center"
                            >
                                Voir la fiche client →
                            </Link>
                        </div>

                        <!-- Contract info -->
                        <div v-if="boxDetails.contract" class="info-section">
                            <h4 class="info-title">
                                <DocumentTextIcon class="w-5 h-5" />
                                Contrat
                            </h4>
                            <div class="info-grid">
                                <div>
                                    <span class="info-label">N° Contrat</span>
                                    <span class="info-value">{{ boxDetails.contract.number }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Début</span>
                                    <span class="info-value">{{ boxDetails.contract.start_date }}</span>
                                </div>
                                <div>
                                    <span class="info-label">Loyer</span>
                                    <span class="info-value">{{ boxDetails.contract.monthly_price }} €/mois</span>
                                </div>
                            </div>
                            <Link
                                :href="route('tenant.contracts.show', boxDetails.contract.id)"
                                class="mt-3 text-primary-600 hover:text-primary-700 text-sm font-medium inline-flex items-center"
                            >
                                Voir le contrat →
                            </Link>
                        </div>

                        <!-- Actions for available boxes -->
                        <div v-if="!boxDetails.contract" class="info-section">
                            <Link
                                :href="route('tenant.contracts.create', { box_id: boxDetails.box.id })"
                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium"
                            >
                                Créer un contrat pour ce box
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantLayout>
</template>

<style scoped>
.plan-container {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.plan-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.stats-bar {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 0.75rem 1rem;
    background: linear-gradient(to right, #eff6ff, white);
    border-bottom: 1px solid #e5e7eb;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stat-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
}

.stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
}

.plan-content {
    display: flex;
    flex: 1;
    overflow: hidden;
}

.plan-legend {
    width: 16rem;
    flex-shrink: 0;
    padding: 1rem;
    background: white;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.legend-color {
    width: 1.5rem;
    height: 1.5rem;
    border-radius: 0.25rem;
    border: 1px solid #d1d5db;
}

.legend-box-type {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.zoom-btn {
    padding: 0.5rem;
    border-radius: 0.5rem;
    background: #f3f4f6;
    cursor: pointer;
    transition: background 0.15s;
}

.zoom-btn:hover {
    background: #e5e7eb;
}

.plan-canvas-container {
    flex: 1;
    overflow: hidden;
    background: #f3f4f6;
    cursor: grab;
    position: relative;
}

.plan-canvas-container:active {
    cursor: grabbing;
}

.plan-canvas {
    position: relative;
    background: white;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.03) 1px, transparent 1px);
    background-size: 20px 20px;
}

.plan-element {
    position: absolute;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s;
    border-style: solid;
}

.plan-element:hover {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    outline: 2px solid #60a5fa;
    outline-offset: 2px;
    transform: scale(1.02);
}

.element-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #1f2937;
    text-align: center;
    line-height: 1.25;
}

.element-size {
    font-size: 0.75rem;
    color: #4b5563;
}

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
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    position: sticky;
    top: 0;
    background: white;
}

.modal-body {
    padding: 1rem;
}

.modal-body > * + * {
    margin-top: 1rem;
}

.info-section {
    background: #f9fafb;
    border-radius: 0.5rem;
    padding: 1rem;
}

.info-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.75rem;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.info-label {
    display: block;
    font-size: 0.75rem;
    color: #6b7280;
}

.info-value {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
}

.status-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-available {
    background: #dcfce7;
    color: #15803d;
}

.status-occupied {
    background: #dbeafe;
    color: #1d4ed8;
}
</style>
