<template>
    <div
        ref="cardRef"
        class="relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100"
        :class="{ 'transition-transform duration-200': !isDragging }"
        :style="cardStyle"
        @touchstart="onTouchStart"
        @touchmove="onTouchMove"
        @touchend="onTouchEnd"
    >
        <!-- Swipe Actions (Left) -->
        <div
            v-if="leftAction"
            class="absolute inset-y-0 left-0 flex items-center justify-start pl-4 transition-opacity"
            :class="leftActionVisible ? 'opacity-100' : 'opacity-0'"
            :style="{ width: Math.abs(translateX) + 'px' }"
        >
            <div
                class="flex flex-col items-center justify-center w-16 h-full rounded-l-2xl"
                :class="leftAction.bgClass || 'bg-green-500'"
            >
                <component :is="leftAction.icon" class="w-6 h-6 text-white" />
                <span class="text-xs text-white mt-1 font-medium">{{ leftAction.label }}</span>
            </div>
        </div>

        <!-- Swipe Actions (Right) -->
        <div
            v-if="rightAction"
            class="absolute inset-y-0 right-0 flex items-center justify-end pr-4 transition-opacity"
            :class="rightActionVisible ? 'opacity-100' : 'opacity-0'"
            :style="{ width: Math.abs(translateX) + 'px' }"
        >
            <div
                class="flex flex-col items-center justify-center w-16 h-full rounded-r-2xl"
                :class="rightAction.bgClass || 'bg-red-500'"
            >
                <component :is="rightAction.icon" class="w-6 h-6 text-white" />
                <span class="text-xs text-white mt-1 font-medium">{{ rightAction.label }}</span>
            </div>
        </div>

        <!-- Card Content -->
        <div
            class="relative bg-white z-10"
            :style="{ transform: `translateX(${translateX}px)` }"
        >
            <slot></slot>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    leftAction: {
        type: Object,
        default: null,
        // { icon: Component, label: String, bgClass: String, action: Function }
    },
    rightAction: {
        type: Object,
        default: null,
    },
    threshold: {
        type: Number,
        default: 80,
    },
});

const emit = defineEmits(['swipe-left', 'swipe-right']);

const cardRef = ref(null);
const translateX = ref(0);
const isDragging = ref(false);
const startX = ref(0);
const startY = ref(0);

const leftActionVisible = computed(() => translateX.value > props.threshold / 2);
const rightActionVisible = computed(() => translateX.value < -props.threshold / 2);

const cardStyle = computed(() => ({
    transform: isDragging.value ? 'scale(0.98)' : 'scale(1)',
}));

const onTouchStart = (e) => {
    startX.value = e.touches[0].clientX;
    startY.value = e.touches[0].clientY;
    isDragging.value = true;
};

const onTouchMove = (e) => {
    if (!isDragging.value) return;

    const currentX = e.touches[0].clientX;
    const currentY = e.touches[0].clientY;
    const diffX = currentX - startX.value;
    const diffY = currentY - startY.value;

    // Determine if horizontal swipe
    if (Math.abs(diffY) > Math.abs(diffX)) {
        return;
    }

    // Limit swipe distance
    const maxSwipe = 120;
    let newTranslate = diffX;

    // Apply resistance at edges
    if (newTranslate > maxSwipe) {
        newTranslate = maxSwipe + (newTranslate - maxSwipe) * 0.2;
    } else if (newTranslate < -maxSwipe) {
        newTranslate = -maxSwipe + (newTranslate + maxSwipe) * 0.2;
    }

    // Only allow swipe in direction where action exists
    if (newTranslate > 0 && !props.leftAction) {
        newTranslate = 0;
    }
    if (newTranslate < 0 && !props.rightAction) {
        newTranslate = 0;
    }

    translateX.value = newTranslate;
};

const onTouchEnd = () => {
    isDragging.value = false;

    if (translateX.value > props.threshold && props.leftAction) {
        emit('swipe-right');
        if (props.leftAction.action) {
            props.leftAction.action();
        }
    } else if (translateX.value < -props.threshold && props.rightAction) {
        emit('swipe-left');
        if (props.rightAction.action) {
            props.rightAction.action();
        }
    }

    // Reset position
    translateX.value = 0;
};
</script>
