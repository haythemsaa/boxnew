<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 animate-pulse">
        <!-- Header with avatar -->
        <div v-if="showHeader" class="flex items-center mb-4">
            <div class="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-xl"></div>
            <div class="ml-3 flex-1">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2"></div>
                <div class="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2"></div>
            </div>
        </div>

        <!-- Content lines -->
        <div class="space-y-3">
            <div v-for="i in lines" :key="i" class="h-3 bg-gray-200 dark:bg-gray-700 rounded" :class="getLineWidth(i)"></div>
        </div>

        <!-- Footer -->
        <div v-if="showFooter" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between">
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-24"></div>
            <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded-lg w-24"></div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    lines: {
        type: Number,
        default: 3
    },
    showHeader: {
        type: Boolean,
        default: true
    },
    showFooter: {
        type: Boolean,
        default: false
    }
})

const getLineWidth = (index) => {
    const widths = ['w-full', 'w-5/6', 'w-4/6', 'w-3/4', 'w-2/3']
    return widths[(index - 1) % widths.length]
}
</script>
