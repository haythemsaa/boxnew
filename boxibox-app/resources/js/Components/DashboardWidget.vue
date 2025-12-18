<template>
    <div
        class="dashboard-widget bg-white rounded-xl shadow-sm border overflow-hidden"
        :class="{ 'cursor-move': draggable }"
        :style="widgetStyle"
    >
        <!-- Widget Header -->
        <div class="widget-header flex items-center justify-between p-4 border-b bg-gray-50">
            <div class="flex items-center gap-2">
                <span v-if="icon" class="text-xl">{{ icon }}</span>
                <h3 class="font-semibold text-gray-900">{{ title }}</h3>
            </div>
            <div class="flex items-center gap-2">
                <button
                    v-if="refreshable"
                    @click="$emit('refresh')"
                    class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                    :class="{ 'animate-spin': loading }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <button
                    v-if="collapsible"
                    @click="collapsed = !collapsed"
                    class="p-1 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <button
                    v-if="removable"
                    @click="$emit('remove')"
                    class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Widget Content -->
        <transition name="collapse">
            <div v-show="!collapsed" class="widget-content">
                <!-- Loading State -->
                <div v-if="loading" class="p-8 flex items-center justify-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="p-4 text-center">
                    <div class="text-red-500 mb-2">⚠️ Erreur de chargement</div>
                    <button @click="$emit('refresh')" class="text-sm text-blue-600 hover:underline">
                        Réessayer
                    </button>
                </div>

                <!-- Content Slot -->
                <div v-else :class="contentClass">
                    <slot></slot>
                </div>
            </div>
        </transition>

        <!-- Widget Footer (optional) -->
        <div v-if="$slots.footer" class="widget-footer border-t p-3 bg-gray-50">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        default: null,
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: Boolean,
        default: false,
    },
    refreshable: {
        type: Boolean,
        default: true,
    },
    collapsible: {
        type: Boolean,
        default: true,
    },
    removable: {
        type: Boolean,
        default: false,
    },
    draggable: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
        default: 'normal', // 'small', 'normal', 'large', 'full'
    },
    height: {
        type: String,
        default: 'auto',
    },
    contentClass: {
        type: String,
        default: 'p-4',
    },
});

defineEmits(['refresh', 'remove']);

const collapsed = ref(false);

const widgetStyle = computed(() => {
    const styles = {};
    if (props.height !== 'auto') {
        styles.height = props.height;
    }
    return styles;
});
</script>

<style scoped>
.dashboard-widget {
    transition: all 0.2s ease;
}

.dashboard-widget:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.collapse-enter-active,
.collapse-leave-active {
    transition: all 0.3s ease;
    overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
    max-height: 0;
    opacity: 0;
}

.collapse-enter-to,
.collapse-leave-from {
    max-height: 500px;
    opacity: 1;
}

.border-primary {
    border-color: #8FBD56;
}
</style>
