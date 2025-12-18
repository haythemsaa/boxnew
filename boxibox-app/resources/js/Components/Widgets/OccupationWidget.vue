<template>
    <DashboardWidget
        :title="title"
        icon="📦"
        :loading="loading"
        :refreshable="true"
        :collapsible="true"
        @refresh="refresh"
    >
        <div class="space-y-4">
            <!-- Occupation Rate Circle -->
            <div class="flex items-center justify-center">
                <div class="relative w-32 h-32">
                    <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                        <!-- Background circle -->
                        <circle
                            cx="50"
                            cy="50"
                            r="40"
                            fill="none"
                            stroke="#e5e7eb"
                            stroke-width="12"
                        />
                        <!-- Progress circle -->
                        <circle
                            cx="50"
                            cy="50"
                            r="40"
                            fill="none"
                            :stroke="getOccupationColor(occupationRate)"
                            stroke-width="12"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="progressOffset"
                            class="transition-all duration-500"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-bold text-gray-900">{{ occupationRate }}%</span>
                        <span class="text-xs text-gray-500">occupe</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-2 text-center">
                <div class="p-2 bg-green-50 rounded-lg">
                    <p class="text-lg font-bold text-green-600">{{ occupied }}</p>
                    <p class="text-xs text-green-700">Occupees</p>
                </div>
                <div class="p-2 bg-gray-50 rounded-lg">
                    <p class="text-lg font-bold text-gray-600">{{ available }}</p>
                    <p class="text-xs text-gray-700">Libres</p>
                </div>
                <div class="p-2 bg-amber-50 rounded-lg">
                    <p class="text-lg font-bold text-amber-600">{{ reserved }}</p>
                    <p class="text-xs text-amber-700">Reservees</p>
                </div>
            </div>

            <!-- Site Breakdown -->
            <div v-if="sites && sites.length > 0" class="space-y-2 pt-3 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase">Par site</p>
                <div
                    v-for="site in sites.slice(0, 3)"
                    :key="site.id"
                    class="flex items-center justify-between"
                >
                    <span class="text-sm text-gray-700">{{ site.name }}</span>
                    <div class="flex items-center gap-2">
                        <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="getOccupationBarClass(site.rate)"
                                :style="{ width: site.rate + '%' }"
                            ></div>
                        </div>
                        <span class="text-xs font-medium text-gray-600 w-10 text-right">{{ site.rate }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <Link href="/tenant/boxes/plan" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                Voir le plan des boxes →
            </Link>
        </template>
    </DashboardWidget>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import DashboardWidget from '@/Components/DashboardWidget.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Occupation',
    },
    occupied: {
        type: Number,
        default: 0,
    },
    available: {
        type: Number,
        default: 0,
    },
    reserved: {
        type: Number,
        default: 0,
    },
    sites: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['refresh']);

const loading = ref(false);

const total = computed(() => props.occupied + props.available + props.reserved);
const occupationRate = computed(() => {
    if (total.value === 0) return 0;
    return Math.round((props.occupied / total.value) * 100);
});

const circumference = 2 * Math.PI * 40; // 40 is the radius
const progressOffset = computed(() => {
    return circumference - (occupationRate.value / 100) * circumference;
});

const getOccupationColor = (rate) => {
    if (rate >= 90) return '#ef4444'; // red - too full
    if (rate >= 75) return '#10b981'; // green - good
    if (rate >= 50) return '#f59e0b'; // amber - moderate
    return '#6b7280'; // gray - low
};

const getOccupationBarClass = (rate) => {
    if (rate >= 90) return 'bg-red-500';
    if (rate >= 75) return 'bg-green-500';
    if (rate >= 50) return 'bg-amber-500';
    return 'bg-gray-400';
};

const refresh = async () => {
    loading.value = true;
    emit('refresh');
    setTimeout(() => {
        loading.value = false;
    }, 1000);
};
</script>

<style scoped>
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
</style>
