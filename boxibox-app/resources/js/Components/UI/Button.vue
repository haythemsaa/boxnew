<template>
    <component
        :is="componentType"
        :href="href"
        :type="type"
        :disabled="disabled || loading"
        :class="buttonClasses"
        @click="handleClick"
    >
        <!-- Loading spinner -->
        <transition name="spin-fade">
            <span v-if="loading" class="btn-spinner">
                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </span>
        </transition>

        <!-- Icon left -->
        <span v-if="$slots.icon && iconPosition === 'left'" class="btn-icon" :class="{ 'opacity-0': loading }">
            <slot name="icon" />
        </span>

        <!-- Content -->
        <span class="btn-content" :class="{ 'opacity-0': loading && !$slots.icon }">
            <slot />
        </span>

        <!-- Icon right -->
        <span v-if="$slots.icon && iconPosition === 'right'" class="btn-icon" :class="{ 'opacity-0': loading }">
            <slot name="icon" />
        </span>

        <!-- Ripple effect container -->
        <span ref="rippleContainer" class="ripple-container"></span>
    </component>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'success', 'danger', 'warning', 'ghost', 'outline', 'link'].includes(v)
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v)
    },
    href: String,
    type: { type: String, default: 'button' },
    disabled: Boolean,
    loading: Boolean,
    block: Boolean,
    rounded: Boolean,
    iconOnly: Boolean,
    iconPosition: { type: String, default: 'left' },
    ripple: { type: Boolean, default: true },
})

const emit = defineEmits(['click'])

const rippleContainer = ref(null)

const componentType = computed(() => {
    return props.href ? Link : 'button'
})

const buttonClasses = computed(() => {
    return [
        'btn',
        `btn-${props.variant}`,
        `btn-${props.size}`,
        {
            'btn-block': props.block,
            'btn-rounded': props.rounded,
            'btn-icon-only': props.iconOnly,
            'btn-loading': props.loading,
            'btn-disabled': props.disabled,
        }
    ]
})

const handleClick = (e) => {
    if (props.disabled || props.loading) {
        e.preventDefault()
        return
    }

    if (props.ripple) {
        createRipple(e)
    }

    emit('click', e)
}

const createRipple = (e) => {
    const button = e.currentTarget
    const rect = button.getBoundingClientRect()
    const size = Math.max(rect.width, rect.height)
    const x = e.clientX - rect.left - size / 2
    const y = e.clientY - rect.top - size / 2

    const ripple = document.createElement('span')
    ripple.className = 'ripple'
    ripple.style.width = ripple.style.height = `${size}px`
    ripple.style.left = `${x}px`
    ripple.style.top = `${y}px`

    rippleContainer.value?.appendChild(ripple)

    setTimeout(() => {
        ripple.remove()
    }, 600)
}
</script>

<style scoped>
@reference "tailwindcss";

.btn {
    @apply relative inline-flex items-center justify-center font-medium
           transition-all duration-200 ease-out
           focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
           overflow-hidden select-none;
}

/* Sizes */
.btn-xs {
    @apply px-2.5 py-1 text-xs rounded-lg gap-1;
}

.btn-sm {
    @apply px-3 py-1.5 text-sm rounded-lg gap-1.5;
}

.btn-md {
    @apply px-4 py-2.5 text-sm rounded-xl gap-2;
}

.btn-lg {
    @apply px-6 py-3 text-base rounded-xl gap-2;
}

.btn-xl {
    @apply px-8 py-4 text-lg rounded-2xl gap-3;
}

/* Icon only */
.btn-icon-only.btn-xs {
    @apply p-1;
}

.btn-icon-only.btn-sm {
    @apply p-1.5;
}

.btn-icon-only.btn-md {
    @apply p-2.5;
}

.btn-icon-only.btn-lg {
    @apply p-3;
}

.btn-icon-only.btn-xl {
    @apply p-4;
}

/* Variants */
.btn-primary {
    @apply bg-gradient-to-r from-blue-500 to-blue-600 text-white
           shadow-lg shadow-blue-500/25
           hover:from-blue-600 hover:to-blue-700 hover:shadow-xl hover:shadow-blue-500/30
           active:from-blue-700 active:to-blue-800
           focus-visible:ring-blue-500;
}

.btn-secondary {
    @apply bg-gray-100 text-gray-700
           hover:bg-gray-200 active:bg-gray-300
           focus-visible:ring-gray-500;
}

.btn-success {
    @apply bg-gradient-to-r from-emerald-500 to-emerald-600 text-white
           shadow-lg shadow-emerald-500/25
           hover:from-emerald-600 hover:to-emerald-700 hover:shadow-xl hover:shadow-emerald-500/30
           active:from-emerald-700 active:to-emerald-800
           focus-visible:ring-emerald-500;
}

.btn-danger {
    @apply bg-gradient-to-r from-red-500 to-red-600 text-white
           shadow-lg shadow-red-500/25
           hover:from-red-600 hover:to-red-700 hover:shadow-xl hover:shadow-red-500/30
           active:from-red-700 active:to-red-800
           focus-visible:ring-red-500;
}

.btn-warning {
    @apply bg-gradient-to-r from-amber-500 to-amber-600 text-white
           shadow-lg shadow-amber-500/25
           hover:from-amber-600 hover:to-amber-700 hover:shadow-xl hover:shadow-amber-500/30
           active:from-amber-700 active:to-amber-800
           focus-visible:ring-amber-500;
}

.btn-ghost {
    @apply bg-transparent text-gray-700
           hover:bg-gray-100 active:bg-gray-200
           focus-visible:ring-gray-500;
}

.btn-outline {
    @apply bg-transparent border-2 border-blue-500 text-blue-600
           hover:bg-blue-50 active:bg-blue-100
           focus-visible:ring-blue-500;
}

.btn-link {
    @apply bg-transparent text-blue-600 underline-offset-4
           hover:underline active:text-blue-800
           focus-visible:ring-blue-500 shadow-none;
}

/* States */
.btn-block {
    @apply w-full;
}

.btn-rounded {
    @apply rounded-full;
}

.btn-disabled,
.btn:disabled {
    @apply opacity-50 cursor-not-allowed pointer-events-none;
}

.btn-loading {
    @apply cursor-wait;
}

/* Spinner */
.btn-spinner {
    @apply absolute inset-0 flex items-center justify-center;
}

/* Content */
.btn-content {
    @apply transition-opacity duration-150;
}

.btn-icon {
    @apply flex-shrink-0 transition-opacity duration-150;
}

/* Ripple effect */
.ripple-container {
    @apply absolute inset-0 pointer-events-none overflow-hidden;
    border-radius: inherit;
}

:deep(.ripple) {
    @apply absolute rounded-full pointer-events-none;
    background: rgba(255, 255, 255, 0.3);
    transform: scale(0);
    animation: ripple-animation 0.6s ease-out;
}

.btn-secondary :deep(.ripple),
.btn-ghost :deep(.ripple) {
    background: rgba(0, 0, 0, 0.1);
}

@keyframes ripple-animation {
    to {
        transform: scale(4);
        opacity: 0;
    }
}

/* Hover lift effect */
.btn-primary:hover,
.btn-success:hover,
.btn-danger:hover,
.btn-warning:hover {
    transform: translateY(-1px);
}

.btn-primary:active,
.btn-success:active,
.btn-danger:active,
.btn-warning:active {
    transform: translateY(0);
}

/* Spin fade animation */
.spin-fade-enter-active,
.spin-fade-leave-active {
    transition: opacity 0.2s ease;
}

.spin-fade-enter-from,
.spin-fade-leave-to {
    opacity: 0;
}
</style>
