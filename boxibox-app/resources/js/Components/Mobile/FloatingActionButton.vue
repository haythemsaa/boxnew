<template>
    <div class="fixed z-40" :class="positionClass">
        <!-- Mini FABs (expanded state) -->
        <Transition
            enter-active-class="transition-all duration-200"
            enter-from-class="opacity-0 scale-0"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-0"
        >
            <div v-if="expanded && actions.length > 0" class="absolute bottom-16 right-0 flex flex-col-reverse items-end gap-3 mb-3">
                <div
                    v-for="(action, index) in actions"
                    :key="index"
                    class="flex items-center gap-3"
                    :style="{ transitionDelay: `${index * 50}ms` }"
                >
                    <!-- Label -->
                    <span class="px-3 py-1.5 bg-gray-800 text-white text-sm font-medium rounded-lg shadow-lg whitespace-nowrap">
                        {{ action.label }}
                    </span>

                    <!-- Mini FAB -->
                    <button
                        @click="handleAction(action)"
                        class="w-12 h-12 rounded-full flex items-center justify-center shadow-lg active:scale-95 transition-all duration-200"
                        :class="action.bgClass || 'bg-white text-gray-700'"
                    >
                        <span v-if="typeof action.icon === 'string'" class="text-xl">{{ action.icon }}</span>
                        <component v-else :is="action.icon" class="w-5 h-5" />
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Main FAB -->
        <button
            @click="toggleExpanded"
            class="w-14 h-14 rounded-full flex items-center justify-center shadow-xl active:scale-95 transition-all duration-300"
            :class="[
                expanded ? 'bg-gray-800 rotate-45' : mainButtonClass,
            ]"
        >
            <span v-if="typeof icon === 'string' && !expanded" class="text-2xl">{{ icon }}</span>
            <component v-else-if="!expanded" :is="icon" class="w-6 h-6" :class="mainIconClass" />
            <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>

        <!-- Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="expanded"
                class="fixed inset-0 bg-black/30 -z-10"
                @click="expanded = false"
            ></div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    icon: {
        type: [String, Object],
        default: '➕',
    },
    actions: {
        type: Array,
        default: () => [],
        // [{ icon: String|Component, label: String, action: Function, bgClass: String }]
    },
    position: {
        type: String,
        default: 'bottom-right', // bottom-right, bottom-left, bottom-center
    },
    variant: {
        type: String,
        default: 'primary', // primary, secondary, success, danger
    },
});

const emit = defineEmits(['click']);

const expanded = ref(false);

const positionClass = computed(() => {
    const positions = {
        'bottom-right': 'bottom-24 right-4',
        'bottom-left': 'bottom-24 left-4',
        'bottom-center': 'bottom-24 left-1/2 -translate-x-1/2',
    };
    return positions[props.position] || positions['bottom-right'];
});

const variantStyles = {
    primary: {
        button: 'bg-gradient-to-br from-primary-500 to-primary-600 text-white shadow-primary-500/40',
        icon: 'text-white',
    },
    secondary: {
        button: 'bg-gray-800 text-white shadow-gray-800/30',
        icon: 'text-white',
    },
    success: {
        button: 'bg-gradient-to-br from-green-500 to-emerald-600 text-white shadow-green-500/40',
        icon: 'text-white',
    },
    danger: {
        button: 'bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-red-500/40',
        icon: 'text-white',
    },
};

const styles = computed(() => variantStyles[props.variant] || variantStyles.primary);
const mainButtonClass = computed(() => styles.value.button);
const mainIconClass = computed(() => styles.value.icon);

const toggleExpanded = () => {
    if (props.actions.length > 0) {
        expanded.value = !expanded.value;
    } else {
        emit('click');
    }
};

const handleAction = (action) => {
    expanded.value = false;
    if (action.action) {
        action.action();
    }
};
</script>

<style scoped>
.from-primary-500 {
    --tw-gradient-from: #8FBD56;
}
.to-primary-600 {
    --tw-gradient-to: #7aa74a;
}
.shadow-primary-500\/40 {
    box-shadow: 0 10px 25px -5px rgba(143, 189, 86, 0.4);
}
</style>
