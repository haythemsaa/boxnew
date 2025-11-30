<template>
    <Teleport to="body">
        <transition
            enter-active-class="duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="modelValue"
                class="modal-overlay"
                @click.self="closeOnOverlay && close()"
                @keydown.esc="closeOnEsc && close()"
            >
                <transition
                    enter-active-class="duration-300 ease-out"
                    :enter-from-class="enterFromClass"
                    enter-to-class="opacity-100 translate-y-0 scale-100"
                    leave-active-class="duration-200 ease-in"
                    leave-from-class="opacity-100 translate-y-0 scale-100"
                    :leave-to-class="leaveToClass"
                    @after-enter="onAfterEnter"
                    @after-leave="onAfterLeave"
                >
                    <div
                        v-if="modelValue"
                        ref="modalRef"
                        class="modal-container"
                        :class="[sizeClass, { 'modal-fullscreen': fullscreen }]"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="title ? 'modal-title' : undefined"
                    >
                        <!-- Header -->
                        <div v-if="$slots.header || title" class="modal-header">
                            <slot name="header">
                                <h3 id="modal-title" class="modal-title">{{ title }}</h3>
                            </slot>
                            <button
                                v-if="showClose"
                                type="button"
                                class="modal-close"
                                @click="close"
                            >
                                <XMarkIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="modal-body" :class="{ 'modal-body-scrollable': scrollable }">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div v-if="$slots.footer" class="modal-footer">
                            <slot name="footer" />
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    modelValue: Boolean,
    title: String,
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl', '2xl', 'full'].includes(v)
    },
    animation: {
        type: String,
        default: 'scale',
        validator: (v) => ['scale', 'slide-up', 'slide-down', 'fade'].includes(v)
    },
    showClose: { type: Boolean, default: true },
    closeOnOverlay: { type: Boolean, default: true },
    closeOnEsc: { type: Boolean, default: true },
    scrollable: Boolean,
    fullscreen: Boolean,
    persistent: Boolean,
})

const emit = defineEmits(['update:modelValue', 'close', 'open', 'closed', 'opened'])

const modalRef = ref(null)

const sizeClass = computed(() => {
    const sizes = {
        xs: 'max-w-xs',
        sm: 'max-w-sm',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
        '2xl': 'max-w-6xl',
        full: 'max-w-full mx-4',
    }
    return sizes[props.size] || sizes.md
})

const enterFromClass = computed(() => {
    const animations = {
        scale: 'opacity-0 scale-95',
        'slide-up': 'opacity-0 translate-y-8',
        'slide-down': 'opacity-0 -translate-y-8',
        fade: 'opacity-0',
    }
    return animations[props.animation] || animations.scale
})

const leaveToClass = computed(() => {
    const animations = {
        scale: 'opacity-0 scale-95',
        'slide-up': 'opacity-0 translate-y-8',
        'slide-down': 'opacity-0 -translate-y-8',
        fade: 'opacity-0',
    }
    return animations[props.animation] || animations.scale
})

const close = () => {
    if (props.persistent) {
        shakeModal()
        return
    }
    emit('update:modelValue', false)
    emit('close')
}

const shakeModal = () => {
    if (!modalRef.value) return
    modalRef.value.classList.add('modal-shake')
    setTimeout(() => {
        modalRef.value?.classList.remove('modal-shake')
    }, 300)
}

const onAfterEnter = () => {
    emit('opened')
    // Focus trap
    modalRef.value?.focus()
}

const onAfterLeave = () => {
    emit('closed')
}

// Lock body scroll when modal is open
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden'
        emit('open')
    } else {
        document.body.style.overflow = ''
    }
})

// Cleanup on unmount
onUnmounted(() => {
    document.body.style.overflow = ''
})
</script>

<style scoped>
@reference "tailwindcss";

.modal-overlay {
    @apply fixed inset-0 z-50 flex items-center justify-center p-4
           bg-gray-900/60 backdrop-blur-sm;
}

.modal-container {
    @apply relative w-full bg-white rounded-2xl shadow-2xl
           flex flex-col max-h-[90vh] overflow-hidden
           outline-none;
}

.modal-fullscreen {
    @apply max-w-none m-0 h-screen max-h-screen rounded-none;
}

/* Header */
.modal-header {
    @apply flex items-center justify-between px-6 py-4
           border-b border-gray-100 flex-shrink-0;
}

.modal-title {
    @apply text-lg font-semibold text-gray-900;
}

.modal-close {
    @apply p-1.5 -mr-1.5 text-gray-400 rounded-lg
           hover:text-gray-600 hover:bg-gray-100
           transition-colors duration-150;
}

/* Body */
.modal-body {
    @apply px-6 py-5 flex-1 overflow-y-auto;
}

.modal-body-scrollable {
    @apply overflow-y-auto;
}

/* Footer */
.modal-footer {
    @apply flex items-center justify-end gap-3 px-6 py-4
           border-t border-gray-100 bg-gray-50/50 flex-shrink-0;
}

/* Shake animation for persistent modal */
.modal-shake {
    animation: shake 0.3s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-8px); }
    75% { transform: translateX(8px); }
}
</style>
