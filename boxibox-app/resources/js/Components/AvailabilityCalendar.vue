<template>
    <div class="availability-calendar">
        <!-- Header avec navigation -->
        <div class="calendar-header flex items-center justify-between mb-4">
            <button
                @click="previousMonth"
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>

            <h3 class="text-lg font-semibold text-gray-900">
                {{ currentMonthLabel }}
            </h3>

            <button
                @click="nextMonth"
                class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Légende -->
        <div class="calendar-legend flex items-center justify-center gap-4 mb-4 text-sm">
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                <span>Disponible</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span>Limité</span>
            </div>
            <div class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span>Complet</span>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="flex justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        </div>

        <!-- Calendar grid -->
        <div v-else class="calendar-grid">
            <!-- Days header -->
            <div class="grid grid-cols-7 gap-1 mb-2">
                <div
                    v-for="day in weekDays"
                    :key="day"
                    class="text-center text-xs font-medium text-gray-500 py-2"
                >
                    {{ day }}
                </div>
            </div>

            <!-- Calendar days -->
            <div class="grid grid-cols-7 gap-1">
                <!-- Empty cells for offset -->
                <div
                    v-for="n in startDayOffset"
                    :key="'empty-' + n"
                    class="aspect-square"
                ></div>

                <!-- Day cells -->
                <div
                    v-for="day in calendarDays"
                    :key="day.date"
                    :class="getDayClass(day)"
                    @click="selectDate(day)"
                >
                    <div class="day-number text-sm font-medium">{{ day.day }}</div>
                    <div class="day-availability text-xs mt-1">
                        <span v-if="day.status === 'available'" class="text-green-600">
                            {{ day.available }} dispo
                        </span>
                        <span v-else-if="day.status === 'limited'" class="text-yellow-600">
                            {{ day.available }} dispo
                        </span>
                        <span v-else class="text-red-600">
                            Complet
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div v-if="summary" class="calendar-summary mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-primary">{{ summary.total_boxes }}</div>
                    <div class="text-xs text-gray-500">Total boxes</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600">{{ summary.average_availability }}</div>
                    <div class="text-xs text-gray-500">Moy. disponibles</div>
                </div>
                <div v-if="selectedDate">
                    <div class="text-2xl font-bold text-primary-dark">{{ selectedDay?.available || 0 }}</div>
                    <div class="text-xs text-gray-500">Dispo. le {{ formatDate(selectedDate) }}</div>
                </div>
                <div v-if="selectedDate">
                    <div class="text-2xl font-bold text-gray-600">{{ selectedDay?.occupancy_rate || 0 }}%</div>
                    <div class="text-xs text-gray-500">Taux occupation</div>
                </div>
            </div>
        </div>

        <!-- Selected date details -->
        <div v-if="selectedDate && showBoxes" class="selected-date-details mt-4">
            <h4 class="font-medium text-gray-900 mb-3">
                Boxes disponibles le {{ formatDate(selectedDate) }}
            </h4>

            <div v-if="loadingBoxes" class="flex justify-center py-4">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            </div>

            <div v-else-if="availableBoxes.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                <div
                    v-for="box in availableBoxes"
                    :key="box.id"
                    class="box-card p-4 border rounded-lg hover:border-primary hover:shadow-md transition-all cursor-pointer"
                    :class="{ 'border-primary bg-primary/5': selectedBox?.id === box.id }"
                    @click="selectBox(box)"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-medium text-gray-900">{{ box.code }}</div>
                            <div class="text-sm text-gray-500">{{ box.size }} m²</div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-primary">{{ box.price }}€</div>
                            <div class="text-xs text-gray-500">/mois</div>
                        </div>
                    </div>
                    <div v-if="box.features && box.features.length" class="mt-2 flex flex-wrap gap-1">
                        <span
                            v-for="feature in box.features.slice(0, 3)"
                            :key="feature"
                            class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded"
                        >
                            {{ feature }}
                        </span>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-4 text-gray-500">
                Aucune box disponible pour cette date
            </div>
        </div>

        <!-- Action button -->
        <div v-if="selectedBox" class="mt-4">
            <button
                @click="$emit('book', { date: selectedDate, box: selectedBox })"
                class="w-full py-3 px-4 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark transition-colors"
            >
                Réserver {{ selectedBox.code }} - {{ selectedBox.price }}€/mois
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    siteId: {
        type: [Number, String],
        required: true,
    },
    showBoxes: {
        type: Boolean,
        default: true,
    },
    boxType: {
        type: String,
        default: null,
    },
    sizeMin: {
        type: Number,
        default: null,
    },
    sizeMax: {
        type: Number,
        default: null,
    },
});

const emit = defineEmits(['select-date', 'select-box', 'book']);

// State
const loading = ref(false);
const loadingBoxes = ref(false);
const currentDate = ref(new Date());
const calendarData = ref([]);
const summary = ref(null);
const selectedDate = ref(null);
const selectedBox = ref(null);
const availableBoxes = ref([]);

// Week days
const weekDays = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

// Computed
const currentMonthLabel = computed(() => {
    const options = { month: 'long', year: 'numeric' };
    return currentDate.value.toLocaleDateString('fr-FR', options);
});

const startDayOffset = computed(() => {
    const firstDay = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1);
    let day = firstDay.getDay();
    // Convert Sunday (0) to 7 for Monday-first calendar
    return day === 0 ? 6 : day - 1;
});

const calendarDays = computed(() => {
    return calendarData.value;
});

const selectedDay = computed(() => {
    if (!selectedDate.value) return null;
    return calendarData.value.find(d => d.date === selectedDate.value);
});

// Methods
const fetchCalendar = async () => {
    loading.value = true;

    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const startDate = new Date(year, month, 1);
    const endDate = new Date(year, month + 1, 0);

    try {
        const params = {
            site_id: props.siteId,
            start_date: formatDateISO(startDate),
            end_date: formatDateISO(endDate),
        };

        if (props.boxType) params.box_type = props.boxType;
        if (props.sizeMin) params.size_min = props.sizeMin;
        if (props.sizeMax) params.size_max = props.sizeMax;

        const response = await axios.get('/api/v1/availability/calendar', { params });

        calendarData.value = response.data.calendar;
        summary.value = response.data.summary;
    } catch (error) {
        console.error('Error fetching calendar:', error);
    } finally {
        loading.value = false;
    }
};

const fetchAvailableBoxes = async (date) => {
    if (!props.showBoxes) return;

    loadingBoxes.value = true;

    try {
        const params = {
            site_id: props.siteId,
            date: date,
        };

        if (props.boxType) params.box_type = props.boxType;
        if (props.sizeMin) params.size_min = props.sizeMin;
        if (props.sizeMax) params.size_max = props.sizeMax;

        const response = await axios.get('/api/v1/availability/boxes', { params });

        availableBoxes.value = response.data.boxes;
    } catch (error) {
        console.error('Error fetching boxes:', error);
        availableBoxes.value = [];
    } finally {
        loadingBoxes.value = false;
    }
};

const previousMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() - 1,
        1
    );
};

const nextMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        1
    );
};

const selectDate = (day) => {
    if (day.status === 'full') return;

    selectedDate.value = day.date;
    selectedBox.value = null;
    emit('select-date', day);

    if (props.showBoxes) {
        fetchAvailableBoxes(day.date);
    }
};

const selectBox = (box) => {
    selectedBox.value = box;
    emit('select-box', box);
};

const getDayClass = (day) => {
    const classes = [
        'day-cell',
        'aspect-square',
        'p-1',
        'rounded-lg',
        'flex',
        'flex-col',
        'items-center',
        'justify-center',
        'transition-all',
        'cursor-pointer',
    ];

    if (day.is_today) {
        classes.push('ring-2', 'ring-primary', 'ring-offset-1');
    }

    if (day.date === selectedDate.value) {
        classes.push('bg-primary', 'text-white');
    } else if (day.status === 'available') {
        classes.push('bg-green-50', 'hover:bg-green-100');
    } else if (day.status === 'limited') {
        classes.push('bg-yellow-50', 'hover:bg-yellow-100');
    } else {
        classes.push('bg-red-50', 'text-gray-400', 'cursor-not-allowed');
    }

    if (day.is_weekend && day.status !== 'full') {
        classes.push('bg-opacity-70');
    }

    return classes;
};

const formatDateISO = (date) => {
    return date.toISOString().split('T')[0];
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
    });
};

// Watchers
watch(currentDate, () => {
    fetchCalendar();
});

watch(() => [props.siteId, props.boxType, props.sizeMin, props.sizeMax], () => {
    fetchCalendar();
    selectedDate.value = null;
    selectedBox.value = null;
    availableBoxes.value = [];
}, { deep: true });

// Lifecycle
onMounted(() => {
    fetchCalendar();
});
</script>

<style scoped>
.availability-calendar {
    @apply bg-white rounded-xl p-4;
}

.calendar-grid {
    @apply select-none;
}

.day-cell {
    min-height: 60px;
}

.day-cell.cursor-not-allowed {
    pointer-events: none;
}

.day-number {
    line-height: 1;
}

.day-availability {
    line-height: 1;
}

/* Primary color variables */
.bg-primary {
    background-color: #8FBD56;
}

.bg-primary-dark {
    background-color: #7aa94a;
}

.text-primary {
    color: #8FBD56;
}

.text-primary-dark {
    color: #7aa94a;
}

.border-primary {
    border-color: #8FBD56;
}

.ring-primary {
    --tw-ring-color: #8FBD56;
}

.hover\:bg-primary-dark:hover {
    background-color: #7aa94a;
}

.hover\:border-primary:hover {
    border-color: #8FBD56;
}

.bg-primary\/5 {
    background-color: rgba(143, 189, 86, 0.05);
}
</style>
