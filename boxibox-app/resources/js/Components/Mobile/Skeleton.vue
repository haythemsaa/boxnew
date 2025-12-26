<template>
    <div
        class="animate-pulse rounded-lg"
        :class="[
            colorClass,
            { 'rounded-full': circle },
            customClass
        ]"
        :style="computedStyle"
    ></div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    width: {
        type: [String, Number],
        default: '100%'
    },
    height: {
        type: [String, Number],
        default: '1rem'
    },
    circle: {
        type: Boolean,
        default: false
    },
    variant: {
        type: String,
        default: 'default', // default, dark, light
    },
    customClass: {
        type: String,
        default: ''
    }
})

const colorClass = computed(() => {
    const variants = {
        default: 'bg-gray-200 dark:bg-gray-700',
        dark: 'bg-gray-300 dark:bg-gray-600',
        light: 'bg-gray-100 dark:bg-gray-800',
    }
    return variants[props.variant] || variants.default
})

const computedStyle = computed(() => {
    const width = typeof props.width === 'number' ? `${props.width}px` : props.width
    const height = typeof props.height === 'number' ? `${props.height}px` : props.height

    return {
        width: props.circle ? height : width,
        height: height
    }
})
</script>
