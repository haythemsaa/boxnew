<template>
    <div
        class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden group hover:scale-105"
        :class="{
            'border-l-4 border-primary-500': variant === 'primary',
            'border-l-4 border-green-500': variant === 'success',
            'border-l-4 border-red-500': variant === 'danger',
            'border-l-4 border-yellow-500': variant === 'warning',
        }"
    >
        <!-- Header avec gradient optionnel -->
        <div v-if="hasHeader" class="relative overflow-hidden bg-gradient-to-r p-4" :class="headerGradient">
            <div class="relative z-10">
                <h3 class="text-lg font-bold text-white">{{ title }}</h3>
                <p v-if="subtitle" class="text-sm text-white opacity-90">{{ subtitle }}</p>
            </div>
            <!-- Décoration de fond -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-16 -mt-16"></div>
        </div>

        <!-- Contenu -->
        <div class="p-6">
            <slot></slot>
        </div>

        <!-- Footer optionnel -->
        <div v-if="hasFooter" class="border-t border-gray-100 p-4 bg-gray-50 flex gap-3">
            <slot name="footer"></slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'success', 'danger', 'warning'].includes(value),
    },
    hasHeader: {
        type: Boolean,
        default: true,
    },
    hasFooter: {
        type: Boolean,
        default: false,
    },
})

const headerGradient = computed(() => {
    const gradients = {
        primary: 'from-primary-500 to-primary-600',
        success: 'from-green-500 to-green-600',
        danger: 'from-red-500 to-red-600',
        warning: 'from-yellow-500 to-yellow-600',
    }
    return gradients[props.variant]
})
</script>

<style scoped>
.group:hover {
    transform: translateY(-4px);
}
</style>
