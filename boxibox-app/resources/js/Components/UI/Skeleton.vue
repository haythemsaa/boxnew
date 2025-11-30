<template>
    <div class="skeleton-container" :class="{ 'skeleton-animate': animate }">
        <!-- Avatar -->
        <div
            v-if="type === 'avatar'"
            class="skeleton skeleton-avatar"
            :class="[`skeleton-avatar-${size}`, { 'skeleton-rounded': rounded }]"
        ></div>

        <!-- Text lines -->
        <div v-else-if="type === 'text'" class="skeleton-text">
            <div
                v-for="(line, index) in lines"
                :key="index"
                class="skeleton skeleton-line"
                :style="{ width: getLineWidth(index) }"
            ></div>
        </div>

        <!-- Card -->
        <div v-else-if="type === 'card'" class="skeleton-card">
            <div v-if="showImage" class="skeleton skeleton-image"></div>
            <div class="skeleton-card-content">
                <div class="skeleton skeleton-line" style="width: 60%"></div>
                <div class="skeleton skeleton-line" style="width: 80%"></div>
                <div class="skeleton skeleton-line" style="width: 40%"></div>
            </div>
        </div>

        <!-- Table -->
        <div v-else-if="type === 'table'" class="skeleton-table">
            <div v-for="row in rows" :key="row" class="skeleton-table-row">
                <div
                    v-for="col in cols"
                    :key="col"
                    class="skeleton skeleton-cell"
                ></div>
            </div>
        </div>

        <!-- Custom -->
        <div
            v-else
            class="skeleton"
            :style="{ width, height, borderRadius: radius }"
        ></div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'custom',
        validator: (v) => ['custom', 'avatar', 'text', 'card', 'table'].includes(v)
    },
    // Avatar
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(v)
    },
    rounded: Boolean,
    // Text
    lines: {
        type: Number,
        default: 3
    },
    // Card
    showImage: {
        type: Boolean,
        default: true
    },
    // Table
    rows: {
        type: Number,
        default: 5
    },
    cols: {
        type: Number,
        default: 4
    },
    // Custom
    width: {
        type: String,
        default: '100%'
    },
    height: {
        type: String,
        default: '20px'
    },
    radius: {
        type: String,
        default: '8px'
    },
    animate: {
        type: Boolean,
        default: true
    }
})

const getLineWidth = (index) => {
    const widths = ['100%', '80%', '60%', '90%', '70%']
    return widths[index % widths.length]
}
</script>

<style scoped>
@reference "tailwindcss";

.skeleton {
    @apply bg-gray-200 rounded-lg;
}

.skeleton-animate .skeleton {
    animation: skeleton-pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes skeleton-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.4;
    }
}

/* Avatar */
.skeleton-avatar {
    @apply flex-shrink-0;
}

.skeleton-avatar-xs {
    @apply w-6 h-6;
}

.skeleton-avatar-sm {
    @apply w-8 h-8;
}

.skeleton-avatar-md {
    @apply w-10 h-10;
}

.skeleton-avatar-lg {
    @apply w-12 h-12;
}

.skeleton-avatar-xl {
    @apply w-16 h-16;
}

.skeleton-rounded {
    @apply rounded-full;
}

/* Text */
.skeleton-text {
    @apply flex flex-col gap-2;
}

.skeleton-line {
    @apply h-4;
}

/* Card */
.skeleton-card {
    @apply bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100;
}

.skeleton-image {
    @apply w-full h-40 rounded-none;
}

.skeleton-card-content {
    @apply p-4 flex flex-col gap-2;
}

/* Table */
.skeleton-table {
    @apply flex flex-col gap-3;
}

.skeleton-table-row {
    @apply flex gap-4;
}

.skeleton-cell {
    @apply flex-1 h-10;
}
</style>
