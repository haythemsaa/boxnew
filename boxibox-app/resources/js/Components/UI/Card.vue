<template>
    <component
        :is="href ? Link : 'div'"
        :href="href"
        ref="cardRef"
        class="card"
        :class="[
            `card-${variant}`,
            `card-${padding}`,
            {
                'card-hoverable': hoverable,
                'card-clickable': !!href || clickable,
                'card-bordered': bordered,
                'card-elevated': elevated,
                'card-glass': glass,
            }
        ]"
        @mouseenter="onMouseEnter"
        @mouseleave="onMouseLeave"
        @mousemove="onMouseMove"
    >
        <!-- Gradient overlay for 3D effect -->
        <div
            v-if="hoverable && is3D"
            class="card-gradient"
            :style="gradientStyle"
        ></div>

        <!-- Header -->
        <div v-if="$slots.header || title" class="card-header">
            <slot name="header">
                <div class="card-header-content">
                    <div v-if="icon" class="card-icon">
                        <component :is="icon" class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="card-title">{{ title }}</h3>
                        <p v-if="subtitle" class="card-subtitle">{{ subtitle }}</p>
                    </div>
                </div>
                <div v-if="$slots.actions" class="card-actions">
                    <slot name="actions" />
                </div>
            </slot>
        </div>

        <!-- Body -->
        <div v-if="$slots.default" class="card-body">
            <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" class="card-footer">
            <slot name="footer" />
        </div>

        <!-- Loading overlay -->
        <transition name="fade">
            <div v-if="loading" class="card-loading">
                <div class="card-spinner"></div>
            </div>
        </transition>
    </component>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'primary', 'success', 'warning', 'danger', 'info'].includes(v)
    },
    padding: {
        type: String,
        default: 'md',
        validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v)
    },
    title: String,
    subtitle: String,
    icon: Object,
    href: String,
    hoverable: Boolean,
    clickable: Boolean,
    bordered: Boolean,
    elevated: Boolean,
    glass: Boolean,
    loading: Boolean,
    is3D: Boolean,
})

const cardRef = ref(null)
const mousePosition = ref({ x: 0, y: 0 })
const isHovered = ref(false)

const gradientStyle = computed(() => {
    if (!isHovered.value) return { opacity: 0 }

    return {
        opacity: 1,
        background: `radial-gradient(600px circle at ${mousePosition.value.x}px ${mousePosition.value.y}px, rgba(255,255,255,0.15), transparent 40%)`
    }
})

const onMouseEnter = () => {
    isHovered.value = true
}

const onMouseLeave = () => {
    isHovered.value = false
}

const onMouseMove = (e) => {
    if (!cardRef.value || !props.is3D) return

    const rect = cardRef.value.getBoundingClientRect()
    mousePosition.value = {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top
    }
}
</script>

<style scoped>
@reference "tailwindcss";

.card {
    @apply relative bg-white rounded-2xl overflow-hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Variants */
.card-default {
    @apply bg-white;
}

.card-primary {
    @apply bg-gradient-to-br from-blue-500 to-blue-600 text-white;
}

.card-success {
    @apply bg-gradient-to-br from-emerald-500 to-emerald-600 text-white;
}

.card-warning {
    @apply bg-gradient-to-br from-amber-500 to-amber-600 text-white;
}

.card-danger {
    @apply bg-gradient-to-br from-red-500 to-red-600 text-white;
}

.card-info {
    @apply bg-gradient-to-br from-blue-500 to-blue-600 text-white;
}

/* Padding */
.card-none .card-body {
    @apply p-0;
}

.card-sm .card-header,
.card-sm .card-body,
.card-sm .card-footer {
    @apply px-4 py-3;
}

.card-md .card-header,
.card-md .card-body,
.card-md .card-footer {
    @apply px-6 py-4;
}

.card-lg .card-header,
.card-lg .card-body,
.card-lg .card-footer {
    @apply px-8 py-6;
}

/* States */
.card-bordered {
    @apply border border-gray-200;
}

.card-elevated {
    @apply shadow-lg;
}

.card-hoverable:hover {
    @apply shadow-xl;
    transform: translateY(-2px);
}

.card-clickable {
    @apply cursor-pointer;
}

.card-glass {
    @apply bg-white/80 backdrop-blur-xl border border-white/20;
}

/* Header */
.card-header {
    @apply flex items-center justify-between border-b border-gray-100;
}

.card-primary .card-header,
.card-success .card-header,
.card-warning .card-header,
.card-danger .card-header,
.card-info .card-header {
    @apply border-white/20;
}

.card-header-content {
    @apply flex items-center gap-3;
}

.card-icon {
    @apply flex-shrink-0 p-2 bg-blue-100 text-blue-600 rounded-xl;
}

.card-primary .card-icon,
.card-success .card-icon,
.card-warning .card-icon,
.card-danger .card-icon,
.card-info .card-icon {
    @apply bg-white/20 text-white;
}

.card-title {
    @apply font-semibold text-gray-900;
}

.card-primary .card-title,
.card-success .card-title,
.card-warning .card-title,
.card-danger .card-title,
.card-info .card-title {
    @apply text-white;
}

.card-subtitle {
    @apply text-sm text-gray-500 mt-0.5;
}

.card-primary .card-subtitle,
.card-success .card-subtitle,
.card-warning .card-subtitle,
.card-danger .card-subtitle,
.card-info .card-subtitle {
    @apply text-white/70;
}

/* Body */
.card-body {
    @apply flex-1;
}

/* Footer */
.card-footer {
    @apply border-t border-gray-100 bg-gray-50/50;
}

.card-primary .card-footer,
.card-success .card-footer,
.card-warning .card-footer,
.card-danger .card-footer,
.card-info .card-footer {
    @apply border-white/20 bg-black/10;
}

/* Gradient overlay */
.card-gradient {
    @apply absolute inset-0 pointer-events-none transition-opacity duration-300;
}

/* Loading */
.card-loading {
    @apply absolute inset-0 flex items-center justify-center bg-white/80 backdrop-blur-sm z-10;
}

.card-spinner {
    @apply w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin;
}

/* Fade transition */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
