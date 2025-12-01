<template>
    <teleport to="body">
        <transition name="modal-fade">
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop avec blur -->
                <div
                    class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity duration-300"
                    @click="closeModal"
                ></div>

                <!-- Modal container -->
                <transition name="modal-scale">
                    <div
                        v-if="isOpen"
                        class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-slideUp"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                            <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
                            <button
                                @click="closeModal"
                                class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
import { watch } from 'vue'

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

const closeModal = () => {
    emit('close')
}

watch(
    () => props.isOpen,
    (newValue) => {
        if (newValue) {
            document.body.style.overflow = 'hidden'
        } else {
            document.body.style.overflow = 'auto'
        }
    }
)
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
