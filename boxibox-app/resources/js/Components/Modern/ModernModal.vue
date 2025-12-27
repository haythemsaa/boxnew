<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop avec blur -->
                <div
                    class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300"
                    aria-hidden="true"
                    @click="closeModal"
                ></div>

                <!-- Modal container -->
                <transition name="modal-scale">
                    <div
                        v-if="isOpen"
                        ref="modalRef"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="titleId"
                        class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-slideUp"
                        @keydown.escape="closeModal"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 :id="titleId" class="text-xl font-bold text-gray-900">{{ title }}</h2>
                            <button
                                ref="closeButtonRef"
                                type="button"
                                aria-label="Fermer la modale"
                                @click="closeModal"
                                class="p-1 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-6 max-h-96 overflow-y-auto">
                            <slot></slot>
                        </div>

                        <!-- Footer -->
                        <div v-if="hasFooter" class="p-6 border-t border-gray-100 bg-gray-50 flex gap-3">
                            <slot name="footer"></slot>
                        </div>
                    </div>
                </transition>
            </div>
        </transition>
    </teleport>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    hasFooter: {
        type: Boolean,
        default: true,
    },
})

const emit = defineEmits(['close', 'open'])

const modalRef = ref(null)
const closeButtonRef = ref(null)
const previousActiveElement = ref(null)

// Generate unique ID for aria-labelledby
const titleId = computed(() => `modal-title-${Math.random().toString(36).substr(2, 9)}`)

const closeModal = () => {
    emit('close')
}

// Focus trap implementation
const trapFocus = (e) => {
    if (!modalRef.value) return

    const focusableElements = modalRef.value.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    const firstElement = focusableElements[0]
    const lastElement = focusableElements[focusableElements.length - 1]

    if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault()
        lastElement?.focus()
    } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault()
        firstElement?.focus()
    }
}

watch(
    () => props.isOpen,
    (newValue) => {
        if (newValue) {
            // Store currently focused element
            previousActiveElement.value = document.activeElement
            document.body.style.overflow = 'hidden'
            // Focus first focusable element in modal
            nextTick(() => {
                closeButtonRef.value?.focus()
            })
            document.addEventListener('keydown', handleTabKey)
        } else {
            document.body.style.overflow = 'auto'
            document.removeEventListener('keydown', handleTabKey)
            // Restore focus to previously focused element
            nextTick(() => {
                previousActiveElement.value?.focus()
            })
        }
    }
)

const handleTabKey = (e) => {
    if (e.key === 'Tab') {
        trapFocus(e)
    }
}

onUnmounted(() => {
    document.removeEventListener('keydown', handleTabKey)
    document.body.style.overflow = 'auto'
})
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
    transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
    opacity: 0;
}

.modal-scale-enter-active,
.modal-scale-leave-active {
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-scale-enter-from,
.modal-scale-leave-to {
    transform: scale(0.95) translateY(-20px);
    opacity: 0;
}

@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.animate-slideUp {
    animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
</style>
