<template>
    <span
        class="badge"
        :class="[
            `badge-${variant}`,
            `badge-${size}`,
            {
                'badge-pill': pill,
                'badge-dot': dot,
                'badge-outline': outline,
                'badge-pulse': pulse,
            }
        ]"
    >
        <!-- Dot indicator -->
        <span v-if="dot" class="badge-dot-indicator" :class="{ 'badge-dot-animate': pulse }"></span>

        <!-- Icon -->
        <component v-if="icon && !dot" :is="icon" class="badge-icon" />

        <!-- Content -->
        <span v-if="!dot" class="badge-content">
            <slot />
        </span>

        <!-- Close button -->
        <button v-if="closable" class="badge-close" @click="$emit('close')">
            <XMarkIcon class="w-3 h-3" />
        </button>
    </span>
</template>

<script setup>
import { XMarkIcon } from '@heroicons/vue/24/outline'

defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'primary', 'success', 'warning', 'danger', 'info', 'gray'].includes(v)
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg'].includes(v)
    },
    icon: Object,
    pill: Boolean,
    dot: Boolean,
    outline: Boolean,
    pulse: Boolean,
    closable: Boolean,
})

defineEmits(['close'])
</script>

<style scoped>
@reference "tailwindcss";

.badge {
    @apply inline-flex items-center gap-1 font-medium rounded-lg
           transition-all duration-200;
}

/* Sizes */
.badge-xs {
    @apply px-1.5 py-0.5 text-xs;
}

.badge-sm {
    @apply px-2 py-0.5 text-xs;
}

.badge-md {
    @apply px-2.5 py-1 text-sm;
}

.badge-lg {
    @apply px-3 py-1.5 text-sm;
}

/* Pill */
.badge-pill {
    @apply rounded-full;
}

/* Variants - Solid */
.badge-default {
    @apply bg-gray-100 text-gray-700;
}

.badge-primary {
    @apply bg-blue-100 text-blue-700;
}

.badge-success {
    @apply bg-emerald-100 text-emerald-700;
}

.badge-warning {
    @apply bg-amber-100 text-amber-700;
}

.badge-danger {
    @apply bg-red-100 text-red-700;
}

.badge-info {
    @apply bg-blue-100 text-blue-700;
}

.badge-gray {
    @apply bg-gray-100 text-gray-600;
}

/* Variants - Outline */
.badge-outline.badge-default {
    @apply bg-transparent border border-gray-300 text-gray-700;
}

.badge-outline.badge-primary {
    @apply bg-transparent border border-blue-300 text-blue-700;
}

.badge-outline.badge-success {
    @apply bg-transparent border border-emerald-300 text-emerald-700;
}

.badge-outline.badge-warning {
    @apply bg-transparent border border-amber-300 text-amber-700;
}

.badge-outline.badge-danger {
    @apply bg-transparent border border-red-300 text-red-700;
}

.badge-outline.badge-info {
    @apply bg-transparent border border-blue-300 text-blue-700;
}

/* Dot */
.badge-dot {
    @apply p-0 bg-transparent;
}

.badge-dot-indicator {
    @apply w-2 h-2 rounded-full;
}

.badge-dot.badge-default .badge-dot-indicator {
    @apply bg-gray-400;
}

.badge-dot.badge-primary .badge-dot-indicator {
    @apply bg-blue-500;
}

.badge-dot.badge-success .badge-dot-indicator {
    @apply bg-emerald-500;
}

.badge-dot.badge-warning .badge-dot-indicator {
    @apply bg-amber-500;
}

.badge-dot.badge-danger .badge-dot-indicator {
    @apply bg-red-500;
}

.badge-dot.badge-info .badge-dot-indicator {
    @apply bg-blue-500;
}

/* Pulse animation */
.badge-dot-animate {
    animation: pulse-dot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-dot {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

/* Icon */
.badge-icon {
    @apply w-3.5 h-3.5 flex-shrink-0;
}

/* Close button */
.badge-close {
    @apply -mr-1 ml-0.5 p-0.5 rounded hover:bg-black/10 transition-colors;
}
</style>
