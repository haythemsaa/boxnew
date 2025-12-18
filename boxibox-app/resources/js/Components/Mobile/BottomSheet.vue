<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="modelValue" class="fixed inset-0 z-[100]">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="close"
                ></div>

                <!-- Sheet -->
                <Transition
                    enter-active-class="transform transition-transform duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transform transition-transform duration-300 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div
                        v-if="modelValue"
                        ref="sheetRef"
                        class="absolute bottom-0 left-0 right-0 bg-white rounded-t-[2rem] shadow-2xl max-h-[90vh] overflow-hidden"
                        :style="sheetStyle"
                        @touchstart="onTouchStart"
                        @touchmove="onTouchMove"
                        @touchend="onTouchEnd"
                    >
                        <!-- Handle -->
                        <div class="flex justify-center pt-3 pb-2 cursor-grab" @mousedown="onMouseDown">
                            <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                        </div>

                        <!-- Header (optional) -->
                        <div v-if="title || $slots.header" class="px-6 pb-4 border-b border-gray-100">
                            <slot name="header">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
                                    <button
                                        v-if="showClose"
                                        @click="close"
                                        class="p-2 rounded-xl bg-gray-100 hover:bg-gray-200 transition-colors"
                                    >
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
                            </slot>
                        </div>

                        <!-- Content -->
                        <div
                            class="overflow-y-auto overscroll-contain"
                            :style="{ maxHeight: contentMaxHeight }"
                        >
                            <slot></slot>
                        </div>

                        <!-- Footer (optional) -->
                        <div v-if="$slots.footer" class="px-6 py-4 border-t border-gray-100 bg-white">
                            <slot name="footer"></slot>
                        </div>

                        <!-- Safe area -->
                        <div class="h-safe-area-inset-bottom bg-white"></div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    showClose: {
        type: Boolean,
        default: true,
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
    height: {
        type: String,
        default: 'auto', // auto, full, half
    },
});

const emit = defineEmits(['update:modelValue', 'close']);

const sheetRef = ref(null);
const translateY = ref(0);
const isDragging = ref(false);
const startY = ref(0);

const DISMISS_THRESHOLD = 100;

const sheetStyle = computed(() => {
    if (isDragging.value && translateY.value > 0) {
        return {
            transform: `translateY(${translateY.value}px)`,
            transition: 'none',
        };
    }
    return {};
});

const contentMaxHeight = computed(() => {
    if (props.height === 'full') return 'calc(90vh - 120px)';
    if (props.height === 'half') return 'calc(45vh - 80px)';
    return 'calc(70vh - 100px)';
});

const close = () => {
    if (props.dismissible) {
        emit('update:modelValue', false);
        emit('close');
    }
};

const onTouchStart = (e) => {
    if (!props.dismissible) return;
    startY.value = e.touches[0].clientY;
    isDragging.value = true;
};

const onTouchMove = (e) => {
    if (!isDragging.value || !props.dismissible) return;

    const currentY = e.touches[0].clientY;
    const diff = currentY - startY.value;

    // Only allow downward drag
    if (diff > 0) {
        translateY.value = diff;
    }
};

const onTouchEnd = () => {
    if (!props.dismissible) return;
    isDragging.value = false;

    if (translateY.value > DISMISS_THRESHOLD) {
        close();
    }

    translateY.value = 0;
};

const onMouseDown = (e) => {
    // Allow dragging via handle on desktop
    startY.value = e.clientY;
    isDragging.value = true;

    const onMouseMove = (e) => {
        if (!isDragging.value) return;
        const diff = e.clientY - startY.value;
        if (diff > 0) {
            translateY.value = diff;
        }
    };

    const onMouseUp = () => {
        isDragging.value = false;
        if (translateY.value > DISMISS_THRESHOLD) {
            close();
        }
        translateY.value = 0;
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    };

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
};

// Prevent body scroll when open
watch(() => props.modelValue, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});
</script>

<style scoped>
.h-safe-area-inset-bottom {
    height: env(safe-area-inset-bottom, 0px);
}
</style>
