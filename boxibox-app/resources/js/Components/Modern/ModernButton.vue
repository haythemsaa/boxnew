<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        @click="$emit('click')"
        class="relative px-6 py-3 font-semibold rounded-lg transition-all duration-300 transform overflow-hidden group"
        :class="[
            variantClass,
            sizeClass,
            {
                'opacity-50 cursor-not-allowed': disabled || loading,
                'hover:shadow-lg hover:scale-105 active:scale-95': !disabled && !loading,
            },
        ]"
    >
        <!-- Background animé -->
        <div
            class="absolute inset-0 bg-gradient-to-r opacity-0 group-hover:opacity-100 transition-opacity duration-300"
            :class="gradientClass"
        ></div>

        <!-- Contenu -->
        <div class="relative flex items-center justify-center gap-2">
            <!-- Spinner pour l'état loading -->
            <transition name="fade">
                <svg
                    v-if="loading"
                    class="w-5 h-5 animate-spin"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </transition>

            <!-- Icône -->
            <slot name="icon"></slot>

            <!-- Texte -->
            <span>{{ loading ? 'Chargement...' : label }}</span>
        </div>

        <!-- Ripple effect -->
        <div
            v-for="ripple in ripples"
            :key="ripple.id"
            class="absolute rounded-full bg-white opacity-30 animate-ping"
            :style="ripple.style"
        ></div>
    </button>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    type: {
        type: String,
        default: 'button',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'ghost'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['click'])

const ripples = ref([])

const variantClass = {
    primary: 'bg-gradient-to-r from-primary-500 to-primary-600 text-white',
    secondary: 'bg-gray-200 text-gray-900 hover:bg-gray-300',
    success: 'bg-gradient-to-r from-green-500 to-green-600 text-white',
    danger: 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    warning: 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
    ghost: 'bg-transparent border-2 border-primary-500 text-primary-500 hover:bg-primary-50',
}[props.variant]

const sizeClass = {
    sm: 'text-sm px-4 py-2',
    md: 'text-base px-6 py-3',
    lg: 'text-lg px-8 py-4',
}[props.size]

const gradientClass = {
    primary: 'from-primary-400 to-primary-500',
    secondary: 'from-gray-300 to-gray-400',
    success: 'from-green-400 to-green-500',
    danger: 'from-red-400 to-red-500',
    warning: 'from-yellow-400 to-yellow-500',
    ghost: 'from-primary-100 to-primary-200',
}[props.variant]
</script>

<style scoped>
@keyframes ping {
    75%,
    100% {
        transform: scale(2);
        opacity: 0;
    }
}

.animate-ping {
    animation: ping 1s cubic-bezier(0, 0, 0.2, 1) infinite;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
