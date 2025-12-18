<template>
    <div
        class="relative overflow-hidden rounded-2xl p-4 transition-all duration-200 active:scale-95"
        :class="cardClass"
        @click="$emit('click')"
    >
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 80 80">
                <pattern id="pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="2" fill="currentColor" />
                </pattern>
                <rect width="100%" height="100%" fill="url(#pattern)" />
            </svg>
        </div>

        <!-- Content -->
        <div class="relative">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium" :class="labelClass">{{ label }}</p>
                    <p class="text-2xl font-bold mt-1" :class="valueClass">{{ formattedValue }}</p>
                    <div v-if="trend !== null" class="flex items-center mt-2" :class="trendClass">
                        <svg v-if="trend >= 0" class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                        <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
                        </svg>
                        <span class="text-xs font-semibold">{{ Math.abs(trend) }}%</span>
                    </div>
                </div>
                <div
                    class="w-12 h-12 rounded-xl flex items-center justify-center"
                    :class="iconBgClass"
                >
                    <span class="text-2xl">{{ icon }}</span>
                </div>
            </div>

            <!-- Progress Bar (optional) -->
            <div v-if="progress !== null" class="mt-3">
                <div class="w-full h-2 rounded-full bg-white/30">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressClass"
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>
                <p class="text-xs mt-1" :class="labelClass">{{ progress }}% {{ progressLabel }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [Number, String],
        required: true,
    },
    icon: {
        type: String,
        default: '📊',
    },
    variant: {
        type: String,
        default: 'primary', // primary, success, warning, danger, info
    },
    trend: {
        type: Number,
        default: null,
    },
    progress: {
        type: Number,
        default: null,
    },
    progressLabel: {
        type: String,
        default: 'complete',
    },
    format: {
        type: String,
        default: 'number', // number, currency, percent
    },
});

defineEmits(['click']);

const formattedValue = computed(() => {
    if (props.format === 'currency') {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'EUR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(props.value);
    }
    if (props.format === 'percent') {
        return props.value + '%';
    }
    if (typeof props.value === 'number') {
        return new Intl.NumberFormat('fr-FR').format(props.value);
    }
    return props.value;
});

const variantStyles = {
    primary: {
        card: 'bg-gradient-to-br from-primary-500 to-primary-600 text-white',
        label: 'text-primary-100',
        value: 'text-white',
        iconBg: 'bg-white/20',
        trend: 'text-primary-100',
        progress: 'bg-white',
    },
    success: {
        card: 'bg-gradient-to-br from-green-500 to-emerald-600 text-white',
        label: 'text-green-100',
        value: 'text-white',
        iconBg: 'bg-white/20',
        trend: 'text-green-100',
        progress: 'bg-white',
    },
    warning: {
        card: 'bg-gradient-to-br from-amber-400 to-orange-500 text-white',
        label: 'text-amber-100',
        value: 'text-white',
        iconBg: 'bg-white/20',
        trend: 'text-amber-100',
        progress: 'bg-white',
    },
    danger: {
        card: 'bg-gradient-to-br from-red-500 to-rose-600 text-white',
        label: 'text-red-100',
        value: 'text-white',
        iconBg: 'bg-white/20',
        trend: 'text-red-100',
        progress: 'bg-white',
    },
    info: {
        card: 'bg-gradient-to-br from-blue-500 to-indigo-600 text-white',
        label: 'text-blue-100',
        value: 'text-white',
        iconBg: 'bg-white/20',
        trend: 'text-blue-100',
        progress: 'bg-white',
    },
    light: {
        card: 'bg-white border border-gray-100 shadow-sm',
        label: 'text-gray-500',
        value: 'text-gray-900',
        iconBg: 'bg-gray-100',
        trend: 'text-gray-500',
        progress: 'bg-primary-500',
    },
};

const styles = computed(() => variantStyles[props.variant] || variantStyles.primary);
const cardClass = computed(() => styles.value.card);
const labelClass = computed(() => styles.value.label);
const valueClass = computed(() => styles.value.value);
const iconBgClass = computed(() => styles.value.iconBg);
const trendClass = computed(() => styles.value.trend);
const progressClass = computed(() => styles.value.progress);
</script>

<style scoped>
.bg-primary-500 {
    background-color: #8FBD56;
}
.from-primary-500 {
    --tw-gradient-from: #8FBD56;
}
.to-primary-600 {
    --tw-gradient-to: #7aa74a;
}
.text-primary-100 {
    color: rgba(255, 255, 255, 0.8);
}
</style>
