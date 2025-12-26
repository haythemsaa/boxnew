<template>
    <div class="space-y-3">
        <div
            v-for="i in count"
            :key="i"
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-4 animate-pulse flex items-center"
        >
            <!-- Icon/Avatar -->
            <div
                class="flex-shrink-0 bg-gray-200 dark:bg-gray-700"
                :class="[
                    avatar ? 'rounded-full' : 'rounded-xl',
                    iconSize
                ]"
            ></div>

            <!-- Content -->
            <div class="ml-4 flex-1">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>

            <!-- Right side -->
            <div v-if="showRight" class="ml-4">
                <div class="h-6 bg-gray-200 dark:bg-gray-700 rounded-lg w-16"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    count: {
        type: Number,
        default: 3
    },
    avatar: {
        type: Boolean,
        default: false
    },
    size: {
        type: String,
        default: 'md' // sm, md, lg
    },
    showRight: {
        type: Boolean,
        default: true
    }
})

const iconSize = computed(() => {
    const sizes = {
        sm: 'w-8 h-8',
        md: 'w-12 h-12',
        lg: 'w-16 h-16'
    }
    return sizes[props.size] || sizes.md
})
</script>
