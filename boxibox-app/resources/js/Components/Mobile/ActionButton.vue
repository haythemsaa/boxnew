<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        class="flex flex-col items-center justify-center p-4 rounded-2xl transition-all duration-200 active:scale-95"
        :class="buttonClass"
        @click="$emit('click')"
    >
        <!-- Icon -->
        <div
            class="w-14 h-14 rounded-2xl flex items-center justify-center mb-2 transition-transform duration-200"
            :class="iconContainerClass"
        >
            <span class="text-2xl" v-if="typeof icon === 'string'">{{ icon }}</span>
            <component v-else :is="icon" class="w-7 h-7" :class="iconClass" />
        </div>

        <!-- Label -->
        <span class="text-sm font-medium text-center" :class="labelClass">{{ label }}</span>

        <!-- Badge (optional) -->
        <span
            v-if="badge"
            class="absolute -top-1 -right-1 min-w-5 h-5 px-1.5 flex items-center justify-center rounded-full text-xs font-bold"
            :class="badgeClass"
        >
            {{ badge }}
        </span>
    </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: [String, Object],
        required: true,
    },
    href: {
        type: String,
        default: null,
    },
    variant: {
        type: String,
        default: 'default', // default, primary, success, warning, danger
    },
    badge: {
        type: [String, Number],
        default: null,
    },
});

defineEmits(['click']);

const variantStyles = {
    default: {
        button: 'bg-white border border-gray-100 shadow-sm hover:shadow-md hover:border-gray-200',
        iconContainer: 'bg-gray-100',
        icon: 'text-gray-600',
        label: 'text-gray-700',
        badge: 'bg-gray-600 text-white',
    },
    primary: {
        button: 'bg-primary-50 border border-primary-100 hover:bg-primary-100',
        iconContainer: 'bg-primary-100',
        icon: 'text-primary-600',
        label: 'text-primary-700',
        badge: 'bg-primary-600 text-white',
    },
    success: {
        button: 'bg-green-50 border border-green-100 hover:bg-green-100',
        iconContainer: 'bg-green-100',
        icon: 'text-green-600',
        label: 'text-green-700',
        badge: 'bg-green-600 text-white',
    },
    warning: {
        button: 'bg-amber-50 border border-amber-100 hover:bg-amber-100',
        iconContainer: 'bg-amber-100',
        icon: 'text-amber-600',
        label: 'text-amber-700',
        badge: 'bg-amber-600 text-white',
    },
    danger: {
        button: 'bg-red-50 border border-red-100 hover:bg-red-100',
        iconContainer: 'bg-red-100',
        icon: 'text-red-600',
        label: 'text-red-700',
        badge: 'bg-red-600 text-white',
    },
};

const styles = computed(() => variantStyles[props.variant] || variantStyles.default);
const buttonClass = computed(() => ['relative', styles.value.button]);
const iconContainerClass = computed(() => styles.value.iconContainer);
const iconClass = computed(() => styles.value.icon);
const labelClass = computed(() => styles.value.label);
const badgeClass = computed(() => styles.value.badge);
</script>

<style scoped>
.bg-primary-50 {
    background-color: rgba(143, 189, 86, 0.1);
}
.bg-primary-100 {
    background-color: rgba(143, 189, 86, 0.2);
}
.border-primary-100 {
    border-color: rgba(143, 189, 86, 0.3);
}
.text-primary-600 {
    color: #8FBD56;
}
.text-primary-700 {
    color: #7aa74a;
}
.bg-primary-600 {
    background-color: #8FBD56;
}
</style>
