<template>
    <Teleport to="body">
        <div class="toast-container" :class="position">
            <TransitionGroup
                :name="transitionName"
                tag="div"
                class="toast-list"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="toast"
                    :class="[`toast-${toast.type}`, { 'toast-dismissible': toast.dismissible }]"
                    @mouseenter="pauseTimer(toast)"
                    @mouseleave="resumeTimer(toast)"
                >
                    <!-- Icon -->
                    <div class="toast-icon">
                        <component :is="getIcon(toast.type)" class="w-5 h-5" />
                    </div>

                    <!-- Content -->
                    <div class="toast-content">
                        <p v-if="toast.title" class="toast-title">{{ toast.title }}</p>
                        <p class="toast-message">{{ toast.message }}</p>
                    </div>

                    <!-- Progress bar -->
                    <div v-if="toast.showProgress" class="toast-progress">
                        <div
                            class="toast-progress-bar"
                            :style="{ width: `${toast.progress}%` }"
                        ></div>
                    </div>

                    <!-- Close button -->
                    <button
                        v-if="toast.dismissible"
                        type="button"
                        class="toast-close"
                        @click="removeToast(toast.id)"
                    >
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
    CheckCircleIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
    position: {
        type: String,
        default: 'top-right',
        validator: (v) => ['top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center'].includes(v)
    },
    maxToasts: { type: Number, default: 5 },
})

const toasts = ref([])
const timers = new Map()

const transitionName = computed(() => {
    if (props.position.includes('right')) return 'toast-slide-right'
    if (props.position.includes('left')) return 'toast-slide-left'
    return 'toast-slide-down'
})

const getIcon = (type) => {
    const icons = {
        success: CheckCircleIcon,
        error: ExclamationCircleIcon,
        warning: ExclamationTriangleIcon,
        info: InformationCircleIcon,
    }
    return icons[type] || InformationCircleIcon
}

const addToast = (options) => {
    const toast = {
        id: Date.now() + Math.random(),
        type: 'info',
        title: '',
        message: '',
        duration: 5000,
        dismissible: true,
        showProgress: true,
        progress: 100,
        ...options,
    }

    // Limit max toasts
    if (toasts.value.length >= props.maxToasts) {
        const oldest = toasts.value[0]
        removeToast(oldest.id)
    }

    toasts.value.push(toast)

    if (toast.duration > 0) {
        startTimer(toast)
    }

    return toast.id
}

const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
        clearTimer(id)
        toasts.value.splice(index, 1)
    }
}

const startTimer = (toast) => {
    const startTime = Date.now()
    const duration = toast.duration

    const updateProgress = () => {
        const elapsed = Date.now() - startTime
        const remaining = Math.max(0, duration - elapsed)
        toast.progress = (remaining / duration) * 100

        if (remaining > 0) {
            const timerId = requestAnimationFrame(updateProgress)
            timers.set(toast.id, { timerId, startTime, elapsed })
        } else {
            removeToast(toast.id)
        }
    }

    updateProgress()
}

const pauseTimer = (toast) => {
    const timer = timers.get(toast.id)
    if (timer) {
        cancelAnimationFrame(timer.timerId)
    }
}

const resumeTimer = (toast) => {
    if (toast.duration > 0) {
        const timer = timers.get(toast.id)
        const remainingTime = (toast.progress / 100) * toast.duration

        toast.duration = remainingTime
        startTimer(toast)
    }
}

const clearTimer = (id) => {
    const timer = timers.get(id)
    if (timer) {
        cancelAnimationFrame(timer.timerId)
        timers.delete(id)
    }
}

const clearAll = () => {
    toasts.value.forEach(toast => clearTimer(toast.id))
    toasts.value = []
}

// Expose methods globally
const toast = {
    success: (message, options = {}) => addToast({ type: 'success', message, ...options }),
    error: (message, options = {}) => addToast({ type: 'error', message, ...options }),
    warning: (message, options = {}) => addToast({ type: 'warning', message, ...options }),
    info: (message, options = {}) => addToast({ type: 'info', message, ...options }),
    remove: removeToast,
    clear: clearAll,
}

// Make toast available globally
if (typeof window !== 'undefined') {
    window.$toast = toast
}

defineExpose({ toast, addToast, removeToast, clearAll })

onUnmounted(() => {
    timers.forEach((_, id) => clearTimer(id))
})
</script>

<style scoped>
@reference "tailwindcss";

.toast-container {
    @apply fixed z-[100] pointer-events-none p-4;
}

.toast-container.top-right {
    @apply top-0 right-0;
}

.toast-container.top-left {
    @apply top-0 left-0;
}

.toast-container.top-center {
    @apply top-0 left-1/2 -translate-x-1/2;
}

.toast-container.bottom-right {
    @apply bottom-0 right-0;
}

.toast-container.bottom-left {
    @apply bottom-0 left-0;
}

.toast-container.bottom-center {
    @apply bottom-0 left-1/2 -translate-x-1/2;
}

.toast-list {
    @apply flex flex-col gap-3;
}

.toast {
    @apply relative flex items-start gap-3 p-4 pr-10 min-w-[320px] max-w-[420px]
           bg-white rounded-xl shadow-xl border pointer-events-auto
           overflow-hidden;
}

.toast-success {
    @apply border-emerald-200 bg-gradient-to-r from-emerald-50 to-white;
}

.toast-success .toast-icon {
    @apply text-emerald-500;
}

.toast-success .toast-progress-bar {
    @apply bg-emerald-500;
}

.toast-error {
    @apply border-red-200 bg-gradient-to-r from-red-50 to-white;
}

.toast-error .toast-icon {
    @apply text-red-500;
}

.toast-error .toast-progress-bar {
    @apply bg-red-500;
}

.toast-warning {
    @apply border-amber-200 bg-gradient-to-r from-amber-50 to-white;
}

.toast-warning .toast-icon {
    @apply text-amber-500;
}

.toast-warning .toast-progress-bar {
    @apply bg-amber-500;
}

.toast-info {
    @apply border-blue-200 bg-gradient-to-r from-blue-50 to-white;
}

.toast-info .toast-icon {
    @apply text-blue-500;
}

.toast-info .toast-progress-bar {
    @apply bg-blue-500;
}

.toast-icon {
    @apply flex-shrink-0 mt-0.5;
}

.toast-content {
    @apply flex-1 min-w-0;
}

.toast-title {
    @apply font-semibold text-gray-900 mb-0.5;
}

.toast-message {
    @apply text-sm text-gray-600;
}

.toast-progress {
    @apply absolute bottom-0 left-0 right-0 h-1 bg-gray-100;
}

.toast-progress-bar {
    @apply h-full transition-all duration-100 ease-linear;
}

.toast-close {
    @apply absolute top-3 right-3 p-1 text-gray-400 rounded-lg
           hover:text-gray-600 hover:bg-gray-100 transition-colors;
}

/* Animations */
.toast-slide-right-enter-active,
.toast-slide-right-leave-active {
    transition: all 0.3s ease;
}

.toast-slide-right-enter-from {
    opacity: 0;
    transform: translateX(100%);
}

.toast-slide-right-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.9);
}

.toast-slide-left-enter-active,
.toast-slide-left-leave-active {
    transition: all 0.3s ease;
}

.toast-slide-left-enter-from {
    opacity: 0;
    transform: translateX(-100%);
}

.toast-slide-left-leave-to {
    opacity: 0;
    transform: translateX(-100%) scale(0.9);
}

.toast-slide-down-enter-active,
.toast-slide-down-leave-active {
    transition: all 0.3s ease;
}

.toast-slide-down-enter-from {
    opacity: 0;
    transform: translateY(-100%);
}

.toast-slide-down-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.9);
}

/* Move animation */
.toast-slide-right-move,
.toast-slide-left-move,
.toast-slide-down-move {
    transition: transform 0.3s ease;
}
</style>
