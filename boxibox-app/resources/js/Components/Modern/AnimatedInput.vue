<template>
    <div class="relative">
        <!-- Input avec style moderne -->
        <div class="relative">
            <input
                :id="id"
                :type="type"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                placeholder=""
                :disabled="disabled"
                class="peer w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-primary-500 focus:outline-none transition-colors duration-200 bg-white"
                :class="{
                    'cursor-not-allowed opacity-50': disabled,
                    'border-red-500 focus:border-red-500': error,
                    'border-green-500 focus:border-green-500': success,
                }"
            />
            <!-- Label flottant animé -->
            <label
                :for="id"
                class="absolute left-4 top-3 text-gray-600 transition-all duration-200 transform pointer-events-none"
                :class="{
                    'scale-75 -translate-y-6 text-primary-500': modelValue || focused,
                    'text-red-500': error,
                    'text-green-500': success,
                }"
            >
                {{ label }}
            </label>
        </div>

        <!-- Messages d'erreur et succès -->
        <transition name="fade-slide">
            <div v-if="error" class="mt-2 text-sm text-red-500 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                {{ error }}
            </div>
        </transition>

        <transition name="fade-slide">
            <div v-if="success && !error" class="mt-2 text-sm text-green-500 flex items-center gap-1">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                {{ success }}
            </div>
        </transition>

        <!-- Helper text -->
        <p v-if="hint && !error" class="mt-2 text-xs text-gray-500">{{ hint }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const focused = ref(false)

defineProps({
    id: {
        type: String,
        required: true,
    },
    modelValue: {
        type: [String, Number],
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    label: {
        type: String,
        required: true,
    },
    placeholder: {
        type: String,
        default: '',
    },
    error: {
        type: String,
        default: '',
    },
    success: {
        type: String,
        default: '',
    },
    hint: {
        type: String,
        default: '',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})

defineEmits(['update:modelValue'])
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
